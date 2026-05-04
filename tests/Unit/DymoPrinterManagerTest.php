<?php

declare(strict_types=1);

use GraystackIT\DymoPrinter\Contracts\PrinterStateDriver;
use GraystackIT\DymoPrinter\DymoPrinterManager;

beforeEach(function (): void {
    $this->driver = new class implements PrinterStateDriver {
        /** @var array<string, string> */
        private array $store = [];

        public function get(string $deviceId): ?string
        {
            return $this->store[$deviceId] ?? null;
        }

        public function set(string $deviceId, string $printerName): void
        {
            $this->store[$deviceId] = $printerName;
        }

        public function clear(string $deviceId): void
        {
            unset($this->store[$deviceId]);
        }
    };

    $this->manager = new DymoPrinterManager($this->driver);
});

it('returns null when no printer is set for a device', function (): void {
    expect($this->manager->get('device-1'))->toBeNull();
});

it('stores and retrieves a printer for a device', function (): void {
    $this->manager->set('DYMO LabelWriter 450', 'device-1');

    expect($this->manager->get('device-1'))->toBe('DYMO LabelWriter 450');
});

it('has() returns false when no printer is set', function (): void {
    expect($this->manager->has('device-1'))->toBeFalse();
});

it('has() returns true after a printer is set', function (): void {
    $this->manager->set('DYMO LabelWriter 450', 'device-1');

    expect($this->manager->has('device-1'))->toBeTrue();
});

it('clear() removes the printer selection', function (): void {
    $this->manager->set('DYMO LabelWriter 450', 'device-1');
    $this->manager->clear('device-1');

    expect($this->manager->has('device-1'))->toBeFalse();
});

it('forPrint() returns the correct event name as the first element', function (): void {
    $payload = $this->manager->forPrint('http://example.com/label.dymo');

    expect($payload[0])->toBe('dymo:print');
});

it('forPrint() includes template URL and data in the payload', function (): void {
    $payload = $this->manager->forPrint('http://example.com/label.dymo', ['Name' => 'Test']);

    expect($payload['template'])->toBe('http://example.com/label.dymo')
        ->and($payload['data'])->toBe(['Name' => 'Test']);
});

it('forPrint() uses the stored printer name when none is explicitly provided', function (): void {
    $this->manager->set('Stored Printer', 'default');

    $payload = $this->manager->forPrint('http://example.com/label.dymo');

    expect($payload['printer'])->toBe('Stored Printer');
});

it('forPrint() uses the explicitly provided printer name over the stored one', function (): void {
    $this->manager->set('Stored Printer', 'default');

    $payload = $this->manager->forPrint('http://example.com/label.dymo', [], 'Override Printer');

    expect($payload['printer'])->toBe('Override Printer');
});

it('forPrint() falls back to an empty string when no printer is set and none is provided', function (): void {
    $payload = $this->manager->forPrint('http://example.com/label.dymo');

    expect($payload['printer'])->toBe('');
});

it('forBatch() returns the correct event name as the first element', function (): void {
    $payload = $this->manager->forBatch('http://example.com/label.dymo');

    expect($payload[0])->toBe('dymo:print-batch');
});

it('forBatch() includes template URL and labels in the payload', function (): void {
    $labels = [['Name' => 'Label 1'], ['Name' => 'Label 2']];

    $payload = $this->manager->forBatch('http://example.com/label.dymo', $labels);

    expect($payload['template'])->toBe('http://example.com/label.dymo')
        ->and($payload['labels'])->toBe($labels);
});

it('forBatch() uses the explicitly provided printer name over the stored one', function (): void {
    $this->manager->set('Stored Printer', 'default');

    $payload = $this->manager->forBatch('http://example.com/label.dymo', [], 'Override Printer');

    expect($payload['printer'])->toBe('Override Printer');
});

it('printViaLivewire() dispatches the correct print event on the component', function (): void {
    $component = new class {
        public string $event = '';

        /** @var array<string, mixed> */
        public array $params = [];

        public function dispatch(string $event, mixed ...$params): void
        {
            $this->event  = $event;
            $this->params = $params;
        }
    };

    $this->manager->printViaLivewire(
        $component,
        'http://example.com/label.dymo',
        ['Name' => 'Test'],
        'My Printer',
    );

    expect($component->event)->toBe('dymo:print')
        ->and($component->params['printer'])->toBe('My Printer')
        ->and($component->params['template'])->toBe('http://example.com/label.dymo')
        ->and($component->params['data'])->toBe(['Name' => 'Test']);
});

it('batchViaLivewire() dispatches the correct batch event on the component', function (): void {
    $labels    = [['Name' => 'Label 1'], ['Name' => 'Label 2']];
    $component = new class {
        public string $event = '';

        /** @var array<string, mixed> */
        public array $params = [];

        public function dispatch(string $event, mixed ...$params): void
        {
            $this->event  = $event;
            $this->params = $params;
        }
    };

    $this->manager->batchViaLivewire($component, 'http://example.com/label.dymo', $labels, 'My Printer');

    expect($component->event)->toBe('dymo:print-batch')
        ->and($component->params['printer'])->toBe('My Printer')
        ->and($component->params['template'])->toBe('http://example.com/label.dymo')
        ->and($component->params['labels'])->toBe($labels);
});

it('templateUrl() generates a URL containing the template filename', function (): void {
    $url = $this->manager->templateUrl('my-label.dymo');

    expect($url)->toContain('/dymo-printer/templates/my-label.dymo');
});

it('templateUrl() strips directory traversal sequences from the filename', function (): void {
    $url = $this->manager->templateUrl('../../../etc/passwd.dymo');

    expect($url)->toContain('passwd.dymo')
        ->and($url)->not->toContain('..');
});
