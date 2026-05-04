<?php

declare(strict_types=1);

use GraystackIT\DymoPrinter\Drivers\CacheDriver;
use Illuminate\Support\Facades\Cache;

it('returns null when no printer is cached', function (): void {
    $driver = new CacheDriver(Cache::store());

    expect($driver->get('device-1'))->toBeNull();
});

it('stores and retrieves a printer for a device', function (): void {
    $driver = new CacheDriver(Cache::store());
    $driver->set('device-1', 'DYMO LabelWriter 450');

    expect($driver->get('device-1'))->toBe('DYMO LabelWriter 450');
});

it('isolates printer selections across different devices', function (): void {
    $driver = new CacheDriver(Cache::store());
    $driver->set('device-1', 'Printer A');
    $driver->set('device-2', 'Printer B');

    expect($driver->get('device-1'))->toBe('Printer A')
        ->and($driver->get('device-2'))->toBe('Printer B');
});

it('clears the printer selection', function (): void {
    $driver = new CacheDriver(Cache::store());
    $driver->set('device-1', 'DYMO LabelWriter 450');
    $driver->clear('device-1');

    expect($driver->get('device-1'))->toBeNull();
});

it('clearing one device does not affect another device', function (): void {
    $driver = new CacheDriver(Cache::store());
    $driver->set('device-1', 'Printer A');
    $driver->set('device-2', 'Printer B');
    $driver->clear('device-1');

    expect($driver->get('device-1'))->toBeNull()
        ->and($driver->get('device-2'))->toBe('Printer B');
});

it('stores with the correct namespaced cache key', function (): void {
    $driver = new CacheDriver(Cache::store(), 'my_prefix');
    $driver->set('device-1', 'Printer A');

    expect(Cache::get('my_prefix:device-1'))->toBe('Printer A');
});

it('uses the default cache prefix in the key', function (): void {
    $driver = new CacheDriver(Cache::store());
    $driver->set('device-1', 'Printer A');

    expect(Cache::get('dymo_printer:device-1'))->toBe('Printer A');
});

it('overwrites an existing printer selection', function (): void {
    $driver = new CacheDriver(Cache::store());
    $driver->set('device-1', 'Old Printer');
    $driver->set('device-1', 'New Printer');

    expect($driver->get('device-1'))->toBe('New Printer');
});
