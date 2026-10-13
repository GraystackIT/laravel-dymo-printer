<?php

declare(strict_types=1);

return [

    /*
    |--------------------------------------------------------------------------
    | State Driver
    |--------------------------------------------------------------------------
    |
    | Controls how the selected printer is persisted between requests.
    |
    | - session:  Laravel session (simple, no DB required) — default
    | - cache:    Laravel cache keyed by device ID (survives session expiry)
    | - database: dymo_printers table (best for multi-device environments)
    |
    */
    'state_driver' => env('DYMO_STATE_DRIVER', 'session'),

    /*
    |--------------------------------------------------------------------------
    | Session / Cache Settings
    |--------------------------------------------------------------------------
    */
    'session_key'  => 'dymo_printer_name',
    'cache_prefix' => 'dymo_printer',
    'cache_ttl'    => 60 * 60 * 24 * 90, // 90 days in seconds

    /*
    |--------------------------------------------------------------------------
    | Device Cookie Name
    |--------------------------------------------------------------------------
    |
    | Cookie name used to identify a device/browser. Required when using the
    | cache or database driver. The frontend should set this cookie.
    |
    */
    'device_cookie_name' => env('DYMO_DEVICE_COOKIE', 'dymo_device_uuid'),

    /*
    |--------------------------------------------------------------------------
    | Label Templates Path
    |--------------------------------------------------------------------------
    |
    | Base directory for .dymo template files. Paths passed to templateUrl()
    | are resolved relative to this directory.
    |
    */
    'templates_path' => resource_path('labels'),

    /*
    |--------------------------------------------------------------------------
    | DYMO SDK URL
    |--------------------------------------------------------------------------
    |
    | URL the DYMO Connect Framework JavaScript SDK is loaded from by
    | @dymoscripts / <x-dymo-printer::scripts />.
    |
    | null (the default) serves the package's own vendored copy of the SDK,
    | pinned to a reviewed commit, from the `dymo-printer.sdk` route — no
    | external host, no moving `master` branch. Set a URL only to load a
    | different build, e.g. from your own CDN.
    |
    | Bundler (Vite) consumers importing resources/js/index.js get the same
    | vendored file through Vite and do not use this value.
    |
    */
    'sdk_url' => env('DYMO_SDK_URL'),

    /*
    |--------------------------------------------------------------------------
    | Auto-Load Scripts
    |--------------------------------------------------------------------------
    |
    | When true, the <livewire:dymo-printer::printer-select /> component will
    | automatically include the required DYMO scripts (through Livewire's
    | @assets, so they load once even when the component is added later).
    | Leave false if you include <x-dymo-printer::scripts /> in your layout or
    | import the package's resources/js/index.js in your bundle.
    |
    */
    'auto_load_scripts' => (bool) env('DYMO_AUTO_LOAD_SCRIPTS', false),

    /*
    |--------------------------------------------------------------------------
    | Route Configuration
    |--------------------------------------------------------------------------
    */
    'route_prefix'     => 'dymo-printer',
    'route_middleware' => ['web'],

];
