# Changelog

All notable changes to `graystackit/laravel-dymo-printer` will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [1.2.0] - 2026-10-13

> **Note:** 1.2.0 is the first tagged release. The 1.0.0 and 1.1.0 entries below were never tagged — their changes only ever existed on `main` — so 1.2.0 includes everything listed under them.

### Added

#### JavaScript

- **Bundler entry `resources/js/index.js`** — import it from your app bundle (`import '../../vendor/graystackit/laravel-dymo-printer/resources/js/index.js'`) instead of using `@dymoscripts`. It imports the vendored SDK with `?url`, so Vite pins and content-hashes it as a static asset, and loads `dymo-system.js` and `printer-select.js`.
- **Vendored DYMO Connect Framework SDK** (`resources/js/vendor/dymo-connect-framework.js`), pinned to commit `2182e505e334663bf79a48dea24800f2aebcfa98` of `dymosoftware/dymo-connect-framework`. Its header documents the pin, how to update it, and why it must run as a classic script rather than an ES module.
- **`resources/js/printer-select.js`** — the Alpine factories `dymoPrinterSelect` (Livewire) and `dymoPrinterSelectStandalone` (REST), registered with `Alpine.data()` on `alpine:init` and immediately when Alpine is already loaded, and exposed as window globals. Replaces the inline `<script>` blocks of both component views.
- **`ref` correlation** — `dymo:print` and `dymo:print-batch` accept an optional `ref`; `dymo:print-success` / `dymo:print-error` echo it back (`null` when omitted). `dymo:print-success` now always carries `count` (1 for a single label).
- **`DymoSystem.configure({ sdkUrl })`** — set the SDK URL at runtime; `sdkUrl: null` means the page loads the SDK itself.

#### PHP

- **Route `dymo-printer.sdk`** (`/dymo-printer/dymo-connect-framework.js`) serving the vendored SDK, and **route `dymo-printer.printer-select-js`** (`/dymo-printer/printer-select.js`). All package scripts are served by `ScriptController` and linked with a content-hash `?v=` query string, so a package update reaches browsers that cached the previous file.
- **`GraystackIT\DymoPrinter\ScriptAssets`** — paths, versioned URLs and the effective SDK URL of the package scripts.
- **Optional `$ref` argument** on `forPrint()`, `forBatch()`, `printViaLivewire()` and `batchViaLivewire()`; the `ref` key is only added to the payload when given.
- **Livewire 4 support** (`livewire/livewire` `^3.0|^4.0`).

### Changed

#### JavaScript SDK (`dymo-system.js`)

- **The SDK is injected lazily** by `DymoSystem` through `document.createElement('script')` the first time it is needed, instead of a parsed `<script src>` tag (which may never execute when Livewire inserts the markup). Nothing is injected when `window.dymo.label.framework` already exists or an SDK script element is already present. The URL is read at that moment, not when the file is evaluated, so a bundler entry can configure it after its hoisted imports ran.
- **Safe against being evaluated twice in one document** (`wire:navigate` re-running scripts, or a newly hashed bundle after a deploy): the SDK load and the print listeners live in a per-document registry (`window.__dymoPrinterSystem`). The SDK is injected once, and a re-evaluation replaces the `dymo:print` / `dymo:print-batch` listeners instead of adding a second pair — which used to print every label twice.
- **Batch printing is one SDK call** — one `LabelSet` sent with `label.printAsync()` (falling back to `print()` on older SDKs). Payload fields the template has no object for are skipped instead of failing the batch. A failing batch call is reported and **no longer falls back to printing label by label**, which could reprint labels that already came out; only an SDK without `LabelSetBuilder` prints one call per label.
- **Single labels use `printAsync()`** where available, so success is only reported once DYMO Connect accepted the job, and a rejected job (`false`) is reported as an error.
- **QR codes on single labels** — `setObjectText()` only updates a QR code's `<DataString>`, while DYMO Connect prints from `<TextDataHolder><Value>`; that element is now patched too, so single QR labels no longer print the template's placeholder.
- **Retry after failure** — a failed SDK load or `init()` is no longer cached; the next call tries again (e.g. after starting DYMO Connect).
- **Better init error messages** — `checkEnvironment()` is only consulted after a failed `init()` (calling it before `init()` throws inside the SDK, `_createFramework.resetFramework is not a function`), and the polling timeout names the missing SDK URL when none is configured.
- `dymo:print-error` reports a message even for non-`Error` rejections.

#### Views

- **Livewire `printer-select` view** — the root `<div>` is now the first rendered node: the leading `@if(auto_load_scripts)` block made Livewire emit a leading `<!--[if BLOCK]-->` comment, which broke the component as a nested child ("Invalid Livewire child tag name"). `auto_load_scripts` now loads the scripts through Livewire's `@assets` at the end of the root element. The inline factory `<script>` is gone (it never reliably runs inside a nested Livewire child).
- **Standalone `printer-select` component** — uses the shared `dymoPrinterSelectStandalone` factory instead of its own inline script; it therefore needs the package scripts (as before).
- **Both pickers** — the printer list loads on every focus until it succeeded (a failed load is retried instead of leaving the picker dead), the select's value is re-synced after the options are replaced, the error message sits on its own row and may break anywhere (long SDK URLs no longer widen the layout), the Save button keeps its width and shows a spinner only while saving, and the controls carry `aria-label` / `aria-busy` / `role="alert"`. `x-init="init()"` was removed (Alpine already calls `init()`, which ran it twice).
- **`<x-dymo-printer::scripts />` / `@dymoscripts`** — sets `window.DymoPrinterConfig` and loads `dymo-system.js` and `printer-select.js` (with `data-navigate-once`); it no longer emits a `<script src>` tag for the SDK itself. `:load-sdk="false"` keeps its meaning: the page loads the SDK.

#### Configuration

- **`sdk_url` defaults to `null`**, meaning the package's vendored, pinned SDK served by the `dymo-printer.sdk` route — no more runtime dependency on a `master`-tracking `raw.githubusercontent.com` URL. `DYMO_SDK_URL` still overrides it.

#### Metadata

- `require-dev`: `livewire/livewire` `^3.0|^4.0`, `orchestra/testbench` `^9.0|^10.0|^11.0`, `pestphp/pest` and `pestphp/pest-plugin-laravel` `^3.0|^4.0`. Tested on Laravel 11, 12 and 13 with Livewire 3 and 4.

### Fixed

- **Livewire 4: `<livewire:dymo-printer::printer-select />` threw `ComponentNotFoundException`.** Livewire 4 resolves any `::` name through a registered namespace and ignores the `Livewire::component()` registration for it; the package now registers the `dymo-printer` component namespace on Livewire 4 and keeps `Livewire::component()` for Livewire 3. Apps that worked around this with `Livewire::addNamespace('dymo-printer', …)` can drop the workaround.
- **`--tag=dymo-printer-assets` published only half of what it declared** — the same source directory was listed twice, so only the `public/vendor/dymo-printer` target survived. It now publishes exactly that, now including the vendored SDK.

### Removed

- The early `checkEnvironment()` check before `init()` added in 1.1.0 (see "Better init error messages").

---

## 1.1.0 - 2026-06-19 *(never tagged)*

### Fixed

#### JavaScript SDK (`dymo-system.js`)

- **`setText()` in batch printing** — `LabelSetBuilder` records now use `setText()` instead of `setTextMarkup()`. The DYMO Connect Framework explicitly does not support `setTextMarkup()` for DYMO Connect format labels; calling it silently produced blank output on every label in a batch. Labels created with DYMO Connect software now print correctly.
- **`isConnected` normalisation** — Printer objects from the DYMO Connect Framework expose `isConnected` as a method, not a boolean property. The previous check (`p.isConnected !== false`) evaluated to `true` for any function reference, meaning all printers were always reported as connected regardless of their real state. The mapping now calls `p.isConnected()` when it is a function and falls back to the property form for older DLS SDK versions.
- **`LabelSetBuilder.toString()`** — The package previously called the undocumented `labelSet.toXml()`. The documented serialisation method is `toString()`. When `toXml()` was absent, `printWithParamsAndLabelSetAsync()` received `undefined`, threw immediately, and always fell back to the slower sequential-print path. Batch printing now uses the correct method and the optimised code path is actually reached.
- **`getPrinters()` fallback** — `getPrintersAsync()` is a DYMO Connect Framework addition absent from older DLS SDK builds. Calling an undefined function threw an uncaught error and returned no printers. The SDK call now guards `typeof getPrintersAsync === 'function'` and falls back to the synchronous `getPrinters()` wrapped in `Promise.resolve()`.

#### JavaScript SDK (`dymo-system.js`) — Improvement

- **`checkEnvironment()` early check** — When the DYMO Connect Framework is loaded, `checkEnvironment()` is called before the async `init()` handshake. This surfaces "DYMO Connect software not installed" immediately with a clear error message instead of waiting out the 3.2 s polling timeout.

### Changed

#### Configuration (`config/dymo-printer.php`)

- **`sdk_url` default updated** — The previous default pointed to `labelwriter.com`, which is the old DYMO Label Software CDN and is no longer reliably reachable. The default now points to the DYMO Connect Framework hosted on the official DYMO GitHub repository (`dymosoftware/dymo-connect-framework`). The environment variable `DYMO_SDK_URL` continues to work for self-hosted or custom CDN URLs.

#### Metadata

- `composer.json` description updated to accurately reflect Alpine.js support alongside Livewire
- `composer.json` keywords extended with `dymo-connect` and `alpine`

---

## 1.0.0 - 2025-05-04 *(never tagged)*

### Added

#### Core
- `DymoPrinterManager` — core orchestrator for printer selection and print event building
  - `get(?string $deviceId)` — retrieve selected printer for a device
  - `set(string $printerName, ?string $deviceId)` — persist printer selection
  - `clear(?string $deviceId)` — remove printer selection
  - `has(?string $deviceId)` — check whether a printer has been selected
  - `forPrint(string $templateUrl, array $data, ?string $printerName)` — build single-label print event payload
  - `forBatch(string $templateUrl, array $labels, ?string $printerName)` — build batch print event payload
  - `printViaLivewire(object $component, ...)` — dispatch single-label print event from a Livewire component
  - `batchViaLivewire(object $component, ...)` — dispatch batch print event from a Livewire component
  - `templateUrl(string $name)` — generate a URL for a `.dymo` template file
- `DymoPrinter` facade with full IDE type-hint support
- `DymoPrinterServiceProvider` with Laravel package auto-discovery

#### State Drivers
- `PrinterStateDriver` contract (interface) for pluggable persistence backends
- `SessionDriver` — stores printer selection in the Laravel session (default)
- `CacheDriver` — stores printer selection in the Laravel cache with configurable TTL (default 90 days) and key prefix
- `DatabaseDriver` — stores printer selection in the `dymo_printers` table with upsert support

#### HTTP Layer
- `PrinterStateController` with three endpoints:
  - `GET /dymo-printer/printer` — returns current printer and `has_printer` flag as JSON
  - `POST /dymo-printer/printer` — validates and stores printer selection
  - `DELETE /dymo-printer/printer` — clears printer selection
- `TemplateController` — serves `.dymo` template files with path-traversal protection (restricted to `templates_path` directory, `.dymo` extension enforced)
- Inline route serving `dymo-system.js` directly from the package (no asset publishing required)

#### Frontend
- `dymo-system.js` — event-driven JavaScript wrapper around the official DYMO Label Framework SDK
  - `getPrinters()` — returns connected LabelWriter printers
  - `loadTemplate(urlOrXml)` — fetches and caches `.dymo` template XML
  - `printSingle(printerName, templateXml, data)` — prints a single label
  - `printBatch(printerName, templateXml, labelsData)` — batch prints with `LabelSetBuilder` fallback
  - Listens for `dymo:print` and `dymo:print-batch` browser events
  - Dispatches `dymo:print-success` and `dymo:print-error` browser events
- `<x-dymo-printer::scripts />` Blade component — loads the DYMO SDK and `dymo-system.js`
- `<x-dymo-printer::printer-select />` Blade component — standalone Alpine.js printer selector (no Livewire dependency), lazy-loads printer list on first focus
- `<livewire:dymo-printer::printer-select />` Livewire component — reactive printer selector backed by `DymoPrinterManager`
- `@dymoscripts` Blade directive — shorthand for `<x-dymo-printer::scripts />`

#### Configuration & Infrastructure
- `config/dymo-printer.php` — full configuration file with environment variable support (`DYMO_STATE_DRIVER`, `DYMO_DEVICE_COOKIE`, `DYMO_SDK_URL`, `DYMO_AUTO_LOAD_SCRIPTS`)
- `database/migrations/2024_01_01_000000_create_dymo_printers_table.php` — creates the `dymo_printers` table with `device_id` (unique), `printer_name`, `device_info` (JSON, nullable), and timestamps
- Multi-device support via a configurable UUID cookie (`dymo_device_uuid` by default)
- Helper functions: `dymo_printer()`, `dymo_has_printer()`, `dymo_template_url(string $name)`
- Publishable groups: `dymo-printer-config`, `dymo-printer-views`, `dymo-printer-assets`, `dymo-printer-migrations`

[Unreleased]: https://github.com/GraystackIT/laravel-dymo-printer/compare/v1.2.0...HEAD
[1.2.0]: https://github.com/GraystackIT/laravel-dymo-printer/releases/tag/v1.2.0
