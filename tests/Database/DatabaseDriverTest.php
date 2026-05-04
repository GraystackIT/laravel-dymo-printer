<?php

declare(strict_types=1);

use GraystackIT\DymoPrinter\Drivers\DatabaseDriver;
use Illuminate\Support\Facades\DB;

it('returns null when no printer is stored', function (): void {
    $driver = new DatabaseDriver();

    expect($driver->get('device-1'))->toBeNull();
});

it('stores and retrieves a printer for a device', function (): void {
    $driver = new DatabaseDriver();
    $driver->set('device-1', 'DYMO LabelWriter 450');

    expect($driver->get('device-1'))->toBe('DYMO LabelWriter 450');
});

it('inserts a new database record on first set', function (): void {
    $driver = new DatabaseDriver();
    $driver->set('device-1', 'Printer A');

    expect(DB::table('dymo_printers')->where('device_id', 'device-1')->count())->toBe(1);
});

it('upserts on duplicate device_id instead of inserting a duplicate record', function (): void {
    $driver = new DatabaseDriver();
    $driver->set('device-1', 'Printer A');
    $driver->set('device-1', 'Printer B');

    expect(DB::table('dymo_printers')->where('device_id', 'device-1')->count())->toBe(1)
        ->and($driver->get('device-1'))->toBe('Printer B');
});

it('isolates printer selections across different devices', function (): void {
    $driver = new DatabaseDriver();
    $driver->set('device-1', 'Printer A');
    $driver->set('device-2', 'Printer B');

    expect($driver->get('device-1'))->toBe('Printer A')
        ->and($driver->get('device-2'))->toBe('Printer B');
});

it('clears the printer selection', function (): void {
    $driver = new DatabaseDriver();
    $driver->set('device-1', 'DYMO LabelWriter 450');
    $driver->clear('device-1');

    expect($driver->get('device-1'))->toBeNull();
});

it('clearing deletes the database record', function (): void {
    $driver = new DatabaseDriver();
    $driver->set('device-1', 'Printer A');
    $driver->clear('device-1');

    expect(DB::table('dymo_printers')->where('device_id', 'device-1')->exists())->toBeFalse();
});

it('clearing one device does not affect another device', function (): void {
    $driver = new DatabaseDriver();
    $driver->set('device-1', 'Printer A');
    $driver->set('device-2', 'Printer B');
    $driver->clear('device-1');

    expect($driver->get('device-1'))->toBeNull()
        ->and($driver->get('device-2'))->toBe('Printer B');
});
