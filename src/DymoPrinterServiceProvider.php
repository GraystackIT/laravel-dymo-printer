<?php

declare(strict_types=1);

namespace GraystackIT\DymoPrinter;

use GraystackIT\DymoPrinter\Drivers\CacheDriver;
use GraystackIT\DymoPrinter\Drivers\DatabaseDriver;
use GraystackIT\DymoPrinter\Drivers\SessionDriver;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\ServiceProvider;

class DymoPrinterServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/../config/dymo-printer.php', 'dymo-printer');

        $this->app->singleton(DymoPrinterManager::class, function ($app) {
            $stateDriver = config('dymo-printer.state_driver', 'session');

            if ($stateDriver === 'cache') {
                $driver = new CacheDriver(
                    $app->make('cache.store'),
                    config('dymo-printer.cache_prefix', 'dymo_printer'),
                    config('dymo-printer.cache_ttl', 7776000),
                );
            } elseif ($stateDriver === 'database') {
                $driver = new DatabaseDriver();
            } else {
                $driver = new SessionDriver(
                    config('dymo-printer.session_key', 'dymo_printer_name'),
                );
            }

            return new DymoPrinterManager($driver);
        });

        $this->app->alias(DymoPrinterManager::class, 'dymo-printer');
    }

    public function boot(): void
    {
        $this->registerPublishables();
        $this->loadViewsFrom(__DIR__ . '/../resources/views', 'dymo-printer');
        $this->loadRoutesFrom(__DIR__ . '/../routes/web.php');

        Blade::componentNamespace('GraystackIT\\DymoPrinter\\View\\Components', 'dymo-printer');
        Blade::anonymousComponentPath(__DIR__ . '/../resources/views/components', 'dymo-printer');

        $this->registerBladeDirectives();
        $this->registerLivewireComponents();
    }

    private function registerPublishables(): void
    {
        $this->publishes([
            __DIR__ . '/../config/dymo-printer.php' => config_path('dymo-printer.php'),
        ], 'dymo-printer-config');

        $this->publishes([
            __DIR__ . '/../resources/views' => resource_path('views/vendor/dymo-printer'),
        ], 'dymo-printer-views');

        // Self-hosting the scripts and the vendored SDK is optional: the
        // package routes serve them, and bundler consumers import them from
        // vendor/ directly (see README).
        $this->publishes([
            __DIR__ . '/../resources/js' => public_path('vendor/dymo-printer'),
        ], 'dymo-printer-assets');

        $this->publishes([
            __DIR__ . '/../database/migrations' => database_path('migrations'),
        ], 'dymo-printer-migrations');
    }

    private function registerBladeDirectives(): void
    {
        // @dymoscripts — shorthand for <x-dymo-printer::scripts />
        Blade::directive('dymoscripts', function (): string {
            return "<?php echo view('dymo-printer::components.scripts')->render(); ?>";
        });
    }

    /**
     * Register <livewire:dymo-printer::printer-select /> for Livewire 3 and 4.
     *
     * Livewire 4's finder treats any `::` in a component name as a namespace
     * lookup and only consults explicit Livewire::component() registrations
     * for names without one, so the v3-style registration below is silently
     * ineffective there (ComponentNotFoundException). Registering the
     * `dymo-printer` namespace routes the same name to this package's
     * Livewire classes. The finder is only touched once Livewire itself
     * resolves it, so the order of the two service providers does not matter.
     */
    private function registerLivewireComponents(): void
    {
        if (! class_exists(\Livewire\Livewire::class)) {
            return;
        }

        if (class_exists(\Livewire\Finder\Finder::class)) {
            $this->callAfterResolving('livewire.finder', function (\Livewire\Finder\Finder $finder): void {
                $finder->addNamespace('dymo-printer', classNamespace: 'GraystackIT\\DymoPrinter\\Livewire');
            });

            return;
        }

        \Livewire\Livewire::component(
            'dymo-printer::printer-select',
            \GraystackIT\DymoPrinter\Livewire\PrinterSelect::class,
        );
    }
}
