<?php

declare(strict_types=1);

use GraystackIT\DymoPrinter\Drivers\SessionDriver;

it('returns null when no printer is stored', function (): void {
    $driver = new SessionDriver();

    expect($driver->get('device-1'))->toBeNull();
});

it('stores and retrieves a printer for a device', function (): void {
    $driver = new SessionDriver();
    $driver->set('device-1', 'DYMO LabelWriter 450');

    expect($driver->get('device-1'))->toBe('DYMO LabelWriter 450');
});

it('isolates printer selections across different devices', function (): void {
    $driver = new SessionDriver();
    $driver->set('device-1', 'Printer A');
    $driver->set('device-2', 'Printer B');

    expect($driver->get('device-1'))->toBe('Printer A')
        ->and($driver->get('device-2'))->toBe('Printer B');
});

it('clears the printer selection for a device', function (): void {
    $driver = new SessionDriver();
    $driver->set('device-1', 'DYMO LabelWriter 450');
    $driver->clear('device-1');

    expect($driver->get('device-1'))->toBeNull();
});

it('clearing one device does not affect another device', function (): void {
    $driver = new SessionDriver();
    $driver->set('device-1', 'Printer A');
    $driver->set('device-2', 'Printer B');
    $driver->clear('device-1');

    expect($driver->get('device-1'))->toBeNull()
        ->and($driver->get('device-2'))->toBe('Printer B');
});

it('overwrites an existing printer selection', function (): void {
    $driver = new SessionDriver();
    $driver->set('device-1', 'Old Printer');
    $driver->set('device-1', 'New Printer');

    expect($driver->get('device-1'))->toBe('New Printer');
});

it('uses a custom session key', function (): void {
    $driver = new SessionDriver('custom_printer_key');
    $driver->set('device-1', 'My Printer');

    expect($driver->get('device-1'))->toBe('My Printer')
        ->and(session('dymo_printer_name', []))->toBeEmpty();
});
