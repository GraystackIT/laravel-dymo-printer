<?php

declare(strict_types=1);

namespace GraystackIT\DymoPrinter\Drivers;

use GraystackIT\DymoPrinter\Contracts\PrinterStateDriver;

class SessionDriver implements PrinterStateDriver
{
    private string $sessionKey;

    public function __construct(string $sessionKey = 'dymo_printer_name')
    {
        $this->sessionKey = $sessionKey;
    }

    public function get(string $deviceId): ?string
    {
        /** @var array<string, string> $printers */
        $printers = session($this->sessionKey, []);

        return $printers[$deviceId] ?? null;
    }

    public function set(string $deviceId, string $printerName): void
    {
        /** @var array<string, string> $printers */
        $printers = session($this->sessionKey, []);
        $printers[$deviceId] = $printerName;

        session([$this->sessionKey => $printers]);
    }

    public function clear(string $deviceId): void
    {
        /** @var array<string, string> $printers */
        $printers = session($this->sessionKey, []);
        unset($printers[$deviceId]);

        session([$this->sessionKey => $printers]);
    }
}
