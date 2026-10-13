# graystackit/laravel-dymo-printer

A Laravel package for DYMO LabelWriter printer integration with Alpine.js and Livewire support. Provides printer selection persistence, REST API endpoints, and an event-driven JavaScript bridge to the official DYMO JavaScript Framework SDK.

## Requirements

- PHP 8.3+
- Laravel 11, 12, or 13
- [DYMO Connect](https://www.dymo.com/support/dymo-connect-for-desktop-download.html) installed on client machines
- Livewire 3.x or 4.x *(optional — only needed for the Livewire printer-select component)*
- Alpine.js *(for the printer-select components; Livewire ships it)*

## Installation

### 1. Install via Composer

```bash
composer require graystackit/laravel-dymo-printer
```

The package auto-registers via Laravel's service provider discovery — no manual registration needed.

### 2. Publish Configuration *(optional)*

```bash
php artisan vendor:publish --tag=dymo-printer-config
```

### 3. Migrate *(database driver only)*

Only required if you plan to use the `database` state driver:

```bash
php artisan vendor:publish --tag=dymo-printer-migrations
php artisan migrate
```

---

## Configuration

| Key | Default | Description |
|-----|---------|-------------|
| `state_driver` | `session` | How printer selection is persisted: `session`, `cache`, or `database` |
| `session_key` | `dymo_printer_name` | Session array key (session driver only) |
| `cache_prefix` | `dymo_printer` | Cache key prefix (cache driver only) |
| `cache_ttl` | `7776000` | Cache TTL in seconds — 90 days (cache driver only) |
| `device_cookie_name` | `dymo_device_uuid` | Cookie name used to identify a device/browser |
| `templates_path` | `resource_path('labels')` | Base directory for `.dymo` template files |
| `sdk_url` | `null` (vendored copy) | URL `@dymoscripts` loads the DYMO Connect SDK from; `null` serves the package's own pinned copy |
| `auto_load_scripts` | `false` | Auto-include scripts inside the Livewire component (via Livewire `@assets`) |
| `route_prefix` | `dymo-printer` | URL prefix for all package routes |
| `route_middleware` | `['web']` | Middleware applied to all package routes |

---

## State Drivers

### Session *(default)*

No setup required. Selections are tied to the user's PHP session.

```env
DYMO_STATE_DRIVER=session
```

### Cache

Selections survive session expiry. Good for browser-specific persistence across sessions.

```env
DYMO_STATE_DRIVER=cache
```

### Database

Selections are stored in the `dymo_printers` table, keyed by device UUID. Best for multi-device environments where you want a permanent record per device.

```env
DYMO_STATE_DRIVER=database
```

Publish and run the migration before using this driver (see Installation step 3).

---

## Usage

### 1. Load the JavaScript

The package ships three scripts:

| File | Purpose |
|------|---------|
| `resources/js/dymo-system.js` | `window.DymoSystem` and the `dymo:print*` event bus |
| `resources/js/printer-select.js` | Alpine factories `dymoPrinterSelect` / `dymoPrinterSelectStandalone` |
| `resources/js/vendor/dymo-connect-framework.js` | The DYMO Connect Framework SDK, vendored and pinned to a reviewed commit (see its header) |

Pick **one** of the two integration paths below — not both.

#### a) With a bundler (Vite) — recommended

Import the package entry in your app bundle (e.g. `resources/js/app.js`):

```js
import '../../vendor/graystackit/laravel-dymo-printer/resources/js/index.js';
```

That is all. Vite pins and content-hashes the vendored SDK as a static asset
(`?url` import) and `DymoSystem` injects it as a classic `<script>` the first
time it is needed — the SDK is a Google Closure bundle that does not run as an
ES module. The factories are registered with `Alpine.data()` on `alpine:init`
(and immediately, when Alpine is already there), so the printer pickers also
work as nested Livewire children, where an inline `<script>` never runs.

To load the SDK from somewhere else, configure it before the first print:

```js
window.DymoSystem.configure({ sdkUrl: 'https://cdn.example.com/dymo.connect.framework.js' });
// or sdkUrl: null — the page loads the SDK itself, DymoSystem only waits for it
```

#### b) Classic `<script>` tags — no bundler

Add the scripts component to your layout, before the closing `</body>` tag:

```blade
<x-dymo-printer::scripts />
```

Or use the Blade directive shorthand:

```blade
@dymoscripts
```

It sets `window.DymoPrinterConfig.sdkUrl` and loads `dymo-system.js` and
`printer-select.js` from the package routes (versioned URLs, no asset
publishing). The SDK URL defaults to the package route serving the vendored
copy; set `sdk_url` (or `DYMO_SDK_URL`) to use another build, or pass
`:load-sdk="false"` when the page loads the SDK itself. Nothing is injected when
`window.dymo.label.framework` already exists.

#### Safe against double evaluation

`wire:navigate` re-runs body scripts on every navigation and, after a deploy,
evaluates a newly hashed bundle beside the old one. `DymoSystem` keeps its state
in a per-document registry (`window.__dymoPrinterSystem`): the SDK is injected
at most once per document, and a second evaluation **replaces** the
`dymo:print` / `dymo:print-batch` listeners instead of adding a second pair —
so a label is never printed twice. A failed SDK load or `init()` is not cached;
the next call retries.

### 2. Printer Selection UI

#### Standalone Alpine.js *(no Livewire required)*

```blade
<x-dymo-printer::scripts />
<x-dymo-printer::printer-select />
```

The component uses the REST API (`/dymo-printer/printer`) and stores the selection via the configured state driver. It loads the printer list when the select is focused — once it succeeded; a failed load (DYMO Connect not running yet) is retried on the next focus.

#### Livewire Component

```blade
<livewire:dymo-printer::printer-select />
```

If `auto_load_scripts` is `false` (the default), load the scripts once in your layout (or import `resources/js/index.js`, see above):

```blade
@dymoscripts
<livewire:dymo-printer::printer-select />
```

Works with Livewire 3 and 4, also as a nested child component. When you publish
and restyle the view (`--tag=dymo-printer-views`), keep its two structural
rules, documented at the top of the file: the root `<div>` must be the very first
thing rendered (no leading `@if`), and no inline `<script>`.

The standalone component dispatches a `dymo-printer-saved` window event with `{ printer }`; the Livewire component dispatches a `dymo-printer-saved` Livewire event.

### 3. Printing Labels

#### Single Label *(from a Livewire component)*

```php
use GraystackIT\DymoPrinter\Facades\DymoPrinter;

public function printLabel(): void
{
    $this->dispatch(...DymoPrinter::forPrint(
        templateUrl: DymoPrinter::templateUrl('shipping-label.dymo'),
        data: ['RecipientName' => 'John Doe', 'Address' => '123 Main St'],
    ));
}
```

Convenience method equivalent:

```php
DymoPrinter::printViaLivewire($this, DymoPrinter::templateUrl('shipping-label.dymo'), [
    'RecipientName' => 'John Doe',
    'Address'       => '123 Main St',
]);
```

#### Batch Print *(from a Livewire component)*

```php
DymoPrinter::batchViaLivewire($this, DymoPrinter::templateUrl('shipping-label.dymo'), [
    ['RecipientName' => 'John Doe',   'Address' => '123 Main St'],
    ['RecipientName' => 'Jane Smith', 'Address' => '456 Oak Ave'],
]);
```

A batch is sent to DYMO Connect as **one** SDK call: one `LabelSet` with a record
per label, printed with `label.printAsync()`. Fields the template has no object
for are left out, so a stray key cannot fail the batch. If that one call fails,
the error is reported and nothing is retried label by label (that could reprint
labels that already came out). Only an SDK without `LabelSetBuilder` falls back
to one call per label.

#### Correlating results with `ref`

Pass a `ref` to tell which print a result belongs to — it is echoed back on
`dymo:print-success` / `dymo:print-error`:

```php
DymoPrinter::batchViaLivewire($this, $url, $labels, ref: 'job-42');
// or: $this->dispatch(...DymoPrinter::forPrint($url, $data, ref: 'job-42'));
```

#### Using a Specific Printer

Pass a printer name as the last argument to override the stored selection:

```php
DymoPrinter::printViaLivewire($this, $url, $data, printerName: 'DYMO LabelWriter 450');
```

### 4. Label Templates

Place `.dymo` template files in `resources/labels/` (or the path set in `templates_path`). Templates are created with the free [DYMO Connect](https://www.dymo.com/support/dymo-connect-for-desktop-download.html) desktop app.

Generate a secure URL to serve a template:

```php
DymoPrinter::templateUrl('shipping-label.dymo');
// → https://your-app.test/dymo-printer/templates/shipping-label.dymo
```

The template endpoint is protected against path traversal — only `.dymo` files within the configured `templates_path` are served.

### 5. Programmatic State Management

```php
use GraystackIT\DymoPrinter\Facades\DymoPrinter;

DymoPrinter::set('DYMO LabelWriter 450');  // Save printer selection
DymoPrinter::get();                         // Get selected printer (or null)
DymoPrinter::has();                         // true if a printer is selected
DymoPrinter::clear();                       // Remove printer selection
```

All methods accept an optional `$deviceId` parameter to target a specific device explicitly.

### 6. Helper Functions

```php
dymo_printer();               // Get the currently selected printer name (or null)
dymo_has_printer();           // Whether a printer has been selected (bool)
dymo_template_url($filename); // Generate the URL for a .dymo template file
```

---

## Browser Events

### Events the Package Listens For

Dispatch these on `window` from Livewire or anywhere in your JavaScript to trigger a print:

| Event | Payload | Description |
|-------|---------|-------------|
| `dymo:print` | `{ ref?, printer, template, data }` | Print a single label |
| `dymo:print-batch` | `{ ref?, printer, template, labels }` | Print several labels with one SDK call |

`template` is a template URL (e.g. `DymoPrinter::templateUrl(...)`, fetched once and cached) or the template XML itself.

### Events the Package Dispatches

| Event | Payload | Description |
|-------|---------|-------------|
| `dymo:print-success` | `{ ref, printer, count }` | The print was accepted by DYMO Connect |
| `dymo:print-error` | `{ ref, error }` | The print failed (e.g. DYMO Connect not running) |
| `dymo-printer-saved` | `{ printer }` (standalone) / none (Livewire) | The user saved their printer selection |

`ref` is whatever the print event carried, or `null`. It is opaque to the
package — use it to settle exactly the jobs a result belongs to:

```js
const ref = crypto.randomUUID();

window.addEventListener('dymo:print-success', (e) => {
    if (e.detail.ref === ref) console.log(`${e.detail.count} label(s) printed`);
});

window.dispatchEvent(new CustomEvent('dymo:print-batch', {
    detail: { ref, printer: 'DYMO LabelWriter 450', template: '/dymo-printer/templates/shipping-label.dymo', labels },
}));
```

#### Listening in Alpine.js

```html
<div x-on:dymo:print-success.window="console.log('Printed!', $event.detail)"></div>
```

#### Listening in Livewire

```php
#[On('dymo-printer-saved')]
public function onPrinterSaved(): void
{
    // Refresh any printer-dependent state
}
```

### JavaScript API

`window.DymoSystem` also offers `configure({ sdkUrl })`, `ensureInitialized()`,
`getPrinters()` (`[{ name, isConnected }]`), `loadTemplate(urlOrXml)`,
`printSingle(printer, xml, data)` and `printBatch(printer, xml, labels)`.

---

## Multi-Device Support

The package identifies devices via a UUID cookie (`dymo_device_uuid` by default). Set this cookie in your frontend once per browser so each device maintains its own printer selection:

```javascript
if (!document.cookie.includes('dymo_device_uuid')) {
    const uuid = crypto.randomUUID();
    document.cookie = `dymo_device_uuid=${uuid}; path=/; max-age=${60 * 60 * 24 * 365}`;
}
```

The `cache` and `database` drivers are recommended for multi-device environments.

---

## Publishing Assets

```bash
# Configuration file
php artisan vendor:publish --tag=dymo-printer-config

# Blade views (to customise the printer-select component)
php artisan vendor:publish --tag=dymo-printer-views

# JavaScript and the vendored SDK, to public/vendor/dymo-printer (optional:
# the package routes serve them, bundlers import them from vendor/)
php artisan vendor:publish --tag=dymo-printer-assets

# Database migration
php artisan vendor:publish --tag=dymo-printer-migrations
```

---

## Testing

Install dev dependencies and run the test suite:

```bash
composer install
./vendor/bin/pest
```

Run a specific test file:

```bash
./vendor/bin/pest tests/Unit/Drivers/SessionDriverTest.php
```

---

## License

MIT
