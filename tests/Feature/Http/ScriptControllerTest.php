<?php

declare(strict_types=1);

use GraystackIT\DymoPrinter\ScriptAssets;

it('serves the printer-select.js factories with the correct content type', function (): void {
    $this->get('/dymo-printer/printer-select.js')
        ->assertSuccessful()
        ->assertHeader('Content-Type', 'application/javascript')
        ->assertSee("Alpine.data('dymoPrinterSelect'", false);
});

it('serves the vendored DYMO Connect SDK with the correct content type', function (): void {
    $response = $this->get('/dymo-printer/dymo-connect-framework.js');

    $response->assertSuccessful()
        ->assertHeader('Content-Type', 'application/javascript')
        ->assertSee('DYMO Connect Framework SDK — vendored from', false);

    expect($response->getContent())->toBe(file_get_contents(ScriptAssets::path('sdk')));
});

it('serves the scripts from the configured route prefix', function (): void {
    expect(route('dymo-printer.sdk'))->toEndWith('/dymo-printer/dymo-connect-framework.js')
        ->and(route('dymo-printer.printer-select-js'))->toEndWith('/dymo-printer/printer-select.js');
});

it('ships classic scripts without top-level import or export', function (string $asset): void {
    $source = file_get_contents(ScriptAssets::path($asset));

    expect($source)->not->toMatch('/^\s*(import|export)\b/m');
})->with(['dymo-system', 'printer-select']);

it('keeps the per-document registry package-neutral', function (): void {
    $source = file_get_contents(ScriptAssets::path('dymo-system'));

    expect($source)->toContain('window.__dymoPrinterSystem')
        ->not->toContain('__lama');
});
