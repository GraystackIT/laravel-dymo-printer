<?php

declare(strict_types=1);

namespace GraystackIT\DymoPrinter\Facades;

use GraystackIT\DymoPrinter\DymoPrinterManager;
use Illuminate\Support\Facades\Facade;

/**
 * @method static string|null get(?string $deviceId = null)
 * @method static void        set(string $printerName, ?string $deviceId = null)
 * @method static void        clear(?string $deviceId = null)
 * @method static bool        has(?string $deviceId = null)
 * @method static array       forPrint(string $templateUrl, array $data = [], ?string $printerName = null)
 * @method static array       forBatch(string $templateUrl, array $labels = [], ?string $printerName = null)
 * @method static void        printViaLivewire(object $component, string $templateUrl, array $data = [], ?string $printerName = null)
 * @method static void        batchViaLivewire(object $component, string $templateUrl, array $labels = [], ?string $printerName = null)
 * @method static string      templateUrl(string $name)
 *
 * @see DymoPrinterManager
 */
class DymoPrinter extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return 'dymo-printer';
    }
}
