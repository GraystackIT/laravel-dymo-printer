<?php

declare(strict_types=1);

namespace GraystackIT\DymoPrinter;

use InvalidArgumentException;

/**
 * The JavaScript files the package serves through its own routes, so the
 * classic integration (@dymoscripts) needs no asset publishing or bundler.
 */
class ScriptAssets
{
    /**
     * Asset key => [file relative to resources/js, route name].
     *
     * @var array<string, array{0: string, 1: string}>
     */
    public const ASSETS = [
        'dymo-system'    => ['dymo-system.js', 'dymo-printer.js'],
        'printer-select' => ['printer-select.js', 'dymo-printer.printer-select-js'],
        'sdk'            => ['vendor/dymo-connect-framework.js', 'dymo-printer.sdk'],
    ];

    /** @var array<string, string> */
    private static array $versions = [];

    /**
     * Absolute path of an asset on disk.
     */
    public static function path(string $asset): string
    {
        if (! isset(self::ASSETS[$asset])) {
            throw new InvalidArgumentException("Unknown DYMO printer script asset [{$asset}].");
        }

        return dirname(__DIR__) . '/resources/js/' . self::ASSETS[$asset][0];
    }

    /**
     * Short content hash of an asset, used as a cache-busting query string so
     * a package update reaches browsers that cached the previous file.
     */
    public static function version(string $asset): string
    {
        return self::$versions[$asset] ??= substr((string) md5_file(self::path($asset)), 0, 12);
    }

    /**
     * Versioned URL of an asset served by the package routes.
     */
    public static function url(string $asset): string
    {
        self::path($asset);

        return route(self::ASSETS[$asset][1], ['v' => self::version($asset)]);
    }

    /**
     * URL of the DYMO Connect Framework SDK: `dymo-printer.sdk_url` when set,
     * otherwise the package's own vendored, pinned copy.
     */
    public static function sdkUrl(): string
    {
        $configured = config('dymo-printer.sdk_url');

        return is_string($configured) && $configured !== '' ? $configured : self::url('sdk');
    }
}
