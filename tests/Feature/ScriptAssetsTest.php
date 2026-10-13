<?php

declare(strict_types=1);

use GraystackIT\DymoPrinter\ScriptAssets;

it('resolves every asset to an existing file', function (string $asset): void {
    expect(ScriptAssets::path($asset))->toBeFile();
})->with(array_keys(ScriptAssets::ASSETS));

it('rejects an unknown asset', function (): void {
    ScriptAssets::path('../../composer.json');
})->throws(InvalidArgumentException::class);

it('versions asset URLs with a content hash', function (): void {
    expect(ScriptAssets::url('dymo-system'))
        ->toContain('/dymo-printer/dymo-system.js?v=' . substr(md5_file(ScriptAssets::path('dymo-system')), 0, 12));
});

it('defaults the SDK URL to the package route serving the vendored copy', function (): void {
    config(['dymo-printer.sdk_url' => null]);

    expect(ScriptAssets::sdkUrl())->toBe(ScriptAssets::url('sdk'))
        ->toContain('/dymo-printer/dymo-connect-framework.js?v=');
});

it('uses a configured SDK URL instead of the vendored copy', function (): void {
    config(['dymo-printer.sdk_url' => 'https://cdn.example.com/dymo.js']);

    expect(ScriptAssets::sdkUrl())->toBe('https://cdn.example.com/dymo.js');
});
