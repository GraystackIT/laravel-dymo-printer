<?php

declare(strict_types=1);

namespace GraystackIT\DymoPrinter\Drivers;

use GraystackIT\DymoPrinter\Contracts\PrinterStateDriver;
use Illuminate\Support\Facades\DB;

class DatabaseDriver implements PrinterStateDriver
{
    public function get(string $deviceId): ?string
    {
        return DB::table('dymo_printers')
            ->where('device_id', $deviceId)
            ->value('printer_name');
    }

    public function set(string $deviceId, string $printerName): void
    {
        DB::table('dymo_printers')->upsert(
            [
                'device_id'    => $deviceId,
                'printer_name' => $printerName,
                'updated_at'   => now(),
                'created_at'   => now(),
            ],
            ['device_id'],
            ['printer_name', 'updated_at'],
        );
    }

    public function clear(string $deviceId): void
    {
        DB::table('dymo_printers')->where('device_id', $deviceId)->delete();
    }
}
