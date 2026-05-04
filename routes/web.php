<?php

declare(strict_types=1);

use GraystackIT\DymoPrinter\Http\Controllers\PrinterStateController;
use GraystackIT\DymoPrinter\Http\Controllers\TemplateController;
use Illuminate\Support\Facades\Route;

Route::prefix(config('dymo-printer.route_prefix', 'dymo-printer'))
    ->middleware(config('dymo-printer.route_middleware', ['web']))
    ->name('dymo-printer.')
    ->group(function (): void {

        // Printer state API (used by the standalone Alpine component)
        Route::get('/printer', [PrinterStateController::class, 'show'])->name('printer.show');
        Route::post('/printer', [PrinterStateController::class, 'store'])->name('printer.store');
        Route::delete('/printer', [PrinterStateController::class, 'destroy'])->name('printer.destroy');

        // Serve .dymo template files from the configured templates_path
        Route::get('/templates/{name}', [TemplateController::class, 'show'])
            ->name('template')
            ->where('name', '[a-zA-Z0-9_\-\.]+');

        // Serve the package JS bundle without requiring asset publishing
        Route::get('/dymo-system.js', function (): \Illuminate\Http\Response {
            $path = realpath(__DIR__ . '/../resources/js/dymo-system.js');

            return response(
                file_get_contents($path),
                200,
                [
                    'Content-Type'  => 'application/javascript',
                    'Cache-Control' => 'public, max-age=86400',
                ],
            );
        })->name('js');

    });
