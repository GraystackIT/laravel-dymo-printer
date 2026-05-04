# Changelog

All notable changes to `graystackit/laravel-dymo-printer` will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [1.0.0] - 2025-05-04

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

[Unreleased]: https://github.com/GraystackIT/laravel-dymo-printer/compare/v1.0.0...HEAD
[1.0.0]: https://github.com/GraystackIT/laravel-dymo-printer/releases/tag/v1.0.0
