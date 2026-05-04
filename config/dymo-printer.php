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
    | URL to the official DYMO JavaScript Framework SDK.
    | The default loads it from DYMO's CDN. You can override this to point
    | to a locally hosted copy.
    |
    */
    'sdk_url' => env(
        'DYMO_SDK_URL',
        'https://labelwriter.com/software/dls/sdk/js/DYMO.Label.Framework.latest.js'
    ),

    /*
    |--------------------------------------------------------------------------
    | Auto-Load Scripts
    |--------------------------------------------------------------------------
    |
    | When true, the <livewire:dymo-printer::printer-select /> component will
    | automatically include the required DYMO scripts. Set to false if you
    | include <x-dymo-printer::scripts /> manually in your layout.
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
