<?php

declare(strict_types=1);

namespace GraystackIT\DymoPrinter\Tests;

use GraystackIT\DymoPrinter\DymoPrinterServiceProvider;
use Orchestra\Testbench\TestCase as Orchestra;

/**
 * Base test case for all package tests.
 */
class TestCase extends Orchestra
{
    /**
     * @param  \Illuminate\Foundation\Application  $app
     * @return array<int, class-string>
     */
    protected function getPackageProviders($app): array
    {
        return [
            \Livewire\LivewireServiceProvider::class,
            DymoPrinterServiceProvider::class,
        ];
    }

    /**
     * @param  \Illuminate\Foundation\Application  $app
     */
    protected function defineEnvironment($app): void
    {
        $app['config']->set('app.key', 'base64:' . base64_encode(str_repeat('x', 32)));
        $app['config']->set('dymo-printer.state_driver', 'session');
        $app['config']->set('session.driver', 'array');
        $app['config']->set('cache.default', 'array');
        $app['config']->set('database.default', 'sqlite');
        $app['config']->set('database.connections.sqlite', [
            'driver'   => 'sqlite',
            'database' => ':memory:',
            'prefix'   => '',
        ]);
    }
}
