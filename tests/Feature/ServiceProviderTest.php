<?php

declare(strict_types=1);

use GraystackIT\DymoPrinter\Drivers\CacheDriver;
use GraystackIT\DymoPrinter\Drivers\DatabaseDriver;
use GraystackIT\DymoPrinter\Drivers\SessionDriver;
use GraystackIT\DymoPrinter\DymoPrinterManager;
use GraystackIT\DymoPrinter\Facades\DymoPrinter;
use Illuminate\Support\Facades\Blade;

it('registers DymoPrinterManager as a singleton', function (): void {
    $instance1 = app(DymoPrinterManager::class);
    $instance2 = app(DymoPrinterManager::class);

    expect($instance1)->toBeInstanceOf(DymoPrinterManager::class)
        ->and($instance1)->toBe($instance2);
});

it('registers the dymo-printer container alias', function (): void {
    expect(app('dymo-printer'))->toBeInstanceOf(DymoPrinterManager::class);
});

it('resolves the correct instance via the facade', function (): void {
    expect(DymoPrinter::getFacadeRoot())->toBeInstanceOf(DymoPrinterManager::class);
});

it('uses the session driver by default', function (): void {
    config(['dymo-printer.state_driver' => 'session']);
    app()->forgetInstance(DymoPrinterManager::class);
    app()->forgetInstance('dymo-printer');

    $manager    = app(DymoPrinterManager::class);
    $reflection = new ReflectionProperty($manager, 'driver');

    expect($reflection->getValue($manager))->toBeInstanceOf(SessionDriver::class);
});

it('uses the cache driver when configured', function (): void {
    config(['dymo-printer.state_driver' => 'cache']);
    app()->forgetInstance(DymoPrinterManager::class);
    app()->forgetInstance('dymo-printer');

    $manager    = app(DymoPrinterManager::class);
    $reflection = new ReflectionProperty($manager, 'driver');

    expect($reflection->getValue($manager))->toBeInstanceOf(CacheDriver::class);
});

it('uses the database driver when configured', function (): void {
    config(['dymo-printer.state_driver' => 'database']);
    app()->forgetInstance(DymoPrinterManager::class);
    app()->forgetInstance('dymo-printer');

    $manager    = app(DymoPrinterManager::class);
    $reflection = new ReflectionProperty($manager, 'driver');

    expect($reflection->getValue($manager))->toBeInstanceOf(DatabaseDriver::class);
});

it('registers all package routes', function (): void {
    $routeNames = collect(app('router')->getRoutes()->getRoutesByName())->keys();

    expect($routeNames)->toContain('dymo-printer.printer.show')
        ->toContain('dymo-printer.printer.store')
        ->toContain('dymo-printer.printer.destroy')
        ->toContain('dymo-printer.template')
        ->toContain('dymo-printer.js')
        ->toContain('dymo-printer.printer-select-js')
        ->toContain('dymo-printer.sdk');
});

it('registers the @dymoscripts Blade directive', function (): void {
    expect(Blade::getCustomDirectives())->toHaveKey('dymoscripts');
});
