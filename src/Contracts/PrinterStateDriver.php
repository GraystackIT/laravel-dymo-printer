<?php

declare(strict_types=1);

namespace GraystackIT\DymoPrinter\Contracts;

interface PrinterStateDriver
{
    public function get(string $deviceId): ?string;

    public function set(string $deviceId, string $printerName): void;

    public function clear(string $deviceId): void;
}
