<?php

declare(strict_types=1);

namespace GraystackIT\DymoPrinter;

use GraystackIT\DymoPrinter\Contracts\PrinterStateDriver;

class DymoPrinterManager
{
    protected PrinterStateDriver $driver;

    public function __construct(PrinterStateDriver $driver)
    {
        $this->driver = $driver;
    }

    /**
     * Get the selected printer name for a device (defaults to current request device).
     */
    public function get(?string $deviceId = null): ?string
    {
        return $this->driver->get($deviceId ?? $this->resolveDeviceId());
    }

    /**
     * Persist the printer selection for a device.
     */
    public function set(string $printerName, ?string $deviceId = null): void
    {
        $this->driver->set($deviceId ?? $this->resolveDeviceId(), $printerName);
    }

    /**
     * Remove the printer selection for a device.
     */
    public function clear(?string $deviceId = null): void
    {
        $this->driver->clear($deviceId ?? $this->resolveDeviceId());
    }

    /**
     * Whether a printer has been selected for this device.
     */
    public function has(?string $deviceId = null): bool
    {
        return $this->get($deviceId) !== null;
    }

    /**
     * Build the event payload for printing a single label.
     *
     * Usage in Livewire: $this->dispatch(...DymoPrinter::forPrint($url, $data));
     *
     * A non-null $ref is echoed back on the matching dymo:print-success /
     * dymo:print-error event, so the caller can tell which print a result
     * belongs to.
     *
     * @return array{0: string, printer: string, template: string, data: array<string, mixed>, ref?: string}
     */
    public function forPrint(string $templateUrl, array $data = [], ?string $printerName = null, ?string $ref = null): array
    {
        return [
            'dymo:print',
            ...$this->refPayload($ref),
            'printer'  => $printerName ?? $this->get() ?? '',
            'template' => $templateUrl,
            'data'     => $data,
        ];
    }

    /**
     * Build the event payload for printing multiple labels in one batch.
     *
     * Usage in Livewire: $this->dispatch(...DymoPrinter::forBatch($url, $labels));
     *
     * All labels go to the printer in ONE SDK call (one LabelSet). A non-null
     * $ref is echoed back on the matching success/error event.
     *
     * @param  array<int, array<string, mixed>>  $labels
     * @return array{0: string, printer: string, template: string, labels: array<int, array<string, mixed>>, ref?: string}
     */
    public function forBatch(string $templateUrl, array $labels = [], ?string $printerName = null, ?string $ref = null): array
    {
        return [
            'dymo:print-batch',
            ...$this->refPayload($ref),
            'printer'  => $printerName ?? $this->get() ?? '',
            'template' => $templateUrl,
            'labels'   => $labels,
        ];
    }

    /**
     * Dispatch a print event directly from a Livewire component.
     */
    public function printViaLivewire(object $component, string $templateUrl, array $data = [], ?string $printerName = null, ?string $ref = null): void
    {
        $component->dispatch(...$this->forPrint($templateUrl, $data, $printerName, $ref));
    }

    /**
     * Dispatch a batch print event directly from a Livewire component.
     *
     * @param  array<int, array<string, mixed>>  $labels
     */
    public function batchViaLivewire(object $component, string $templateUrl, array $labels = [], ?string $printerName = null, ?string $ref = null): void
    {
        $component->dispatch(...$this->forBatch($templateUrl, $labels, $printerName, $ref));
    }

    /**
     * Generate the URL for serving a .dymo template by filename.
     */
    public function templateUrl(string $name): string
    {
        return route('dymo-printer.template', ['name' => basename($name)]);
    }

    /**
     * The optional `ref` entry of a print event payload — omitted entirely
     * when null, so payloads without a ref are unchanged.
     *
     * @return array{ref?: string}
     */
    private function refPayload(?string $ref): array
    {
        return $ref === null ? [] : ['ref' => $ref];
    }

    private function resolveDeviceId(): string
    {
        $cookieName = config('dymo-printer.device_cookie_name', 'dymo_device_uuid');

        return (string) request()->cookie($cookieName, 'default');
    }
}
