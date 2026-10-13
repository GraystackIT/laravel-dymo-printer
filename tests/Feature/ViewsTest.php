<?php

declare(strict_types=1);

use GraystackIT\DymoPrinter\Facades\DymoPrinter;
use GraystackIT\DymoPrinter\Livewire\PrinterSelect;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Js;
use Livewire\Livewire;

it('configures the vendored SDK URL and loads both package scripts', function (): void {
    $html = Blade::render('@dymoscripts');

    expect($html)
        ->toContain('window.DymoPrinterConfig')
        ->toContain('dymo-connect-framework.js?v=')
        ->toContain('/dymo-printer/dymo-system.js?v=')
        ->toContain('/dymo-printer/printer-select.js?v=')
        // The SDK is injected lazily by DymoSystem, never as a parsed <script src> tag.
        ->not->toMatch('/<script[^>]+src="[^"]*dymo-connect-framework/');
});

it('passes a configured SDK URL to DymoSystem', function (): void {
    config(['dymo-printer.sdk_url' => 'https://cdn.example.com/dymo.js']);

    expect(Blade::render('<x-dymo-printer::scripts />'))
        ->toContain((string) Js::from(['sdkUrl' => 'https://cdn.example.com/dymo.js']));
});

it('configures no SDK URL when the page loads the SDK itself', function (): void {
    $html = Blade::render('<x-dymo-printer::scripts :load-sdk="false" />');

    expect($html)->toContain((string) Js::from(['sdkUrl' => null]))
        ->not->toContain('dymo-connect-framework.js');
});

it('renders the scripts only once per request', function (): void {
    $html = Blade::render('@dymoscripts <x-dymo-printer::scripts />');

    expect(substr_count($html, '/dymo-printer/dymo-system.js'))->toBe(1);
});

it('starts the Livewire printer-select view with its root element', function (bool $autoLoadScripts): void {
    config(['dymo-printer.auto_load_scripts' => $autoLoadScripts]);

    $html = view('dymo-printer::livewire.printer-select', ['selectedPrinter' => ''])->render();

    // A leading Blade control-flow block would make Livewire emit a leading
    // <!--[if BLOCK]--> comment and break the component as a nested child.
    expect(ltrim($html))->toStartWith('<div')
        ->not->toContain('<script');
})->with([false, true]);

it('renders the Livewire printer-select component with the shared Alpine factory', function (): void {
    DymoPrinter::set('LabelWriter 450');

    $html = Livewire::test('dymo-printer::printer-select')->html();

    expect(ltrim($html))->toStartWith('<div')
        ->and($html)->toContain('x-data="dymoPrinterSelect(')
        ->toContain('LabelWriter 450')
        ->toContain('@focus="loadPrinters()"')
        ->toContain('x-ref="select"')
        ->not->toContain('<script')
        ->not->toContain('@focus.once');
});

it('resolves the namespaced Livewire component name to the package class', function (): void {
    Livewire::test('dymo-printer::printer-select')->assertOk();

    expect(Livewire::test(PrinterSelect::class)->instance())->toBeInstanceOf(PrinterSelect::class);
});

it('saves the selected printer and dispatches dymo-printer-saved', function (): void {
    Livewire::test('dymo-printer::printer-select')
        ->call('save', 'LabelWriter 550')
        ->assertSet('selectedPrinter', 'LabelWriter 550')
        ->assertDispatched('dymo-printer-saved');

    expect(DymoPrinter::get())->toBe('LabelWriter 550');
});

it('renders the standalone printer-select with the shared factory and no inline script', function (): void {
    $html = Blade::render('<x-dymo-printer::printer-select class="my-picker" />');

    expect($html)->toContain('x-data="dymoPrinterSelectStandalone(')
        ->toContain('my-picker')
        ->toContain((string) Js::from([
            'showUrl'  => route('dymo-printer.printer.show'),
            'storeUrl' => route('dymo-printer.printer.store'),
        ]))
        ->not->toContain('<script');
});

it('hands the scripts to Livewire @assets only when auto_load_scripts is enabled', function (bool $autoLoadScripts): void {
    config(['dymo-printer.auto_load_scripts' => $autoLoadScripts]);

    $component = Livewire::test('dymo-printer::printer-select');

    expect($component->html())->not->toContain('<script')
        ->and($component->snapshot['memo']['assets'])->toHaveCount($autoLoadScripts ? 1 : 0);
})->with([false, true]);
