<?php

declare(strict_types=1);

namespace GraystackIT\DymoPrinter\Drivers;

use GraystackIT\DymoPrinter\Contracts\PrinterStateDriver;
use Illuminate\Contracts\Cache\Repository;

class CacheDriver implements PrinterStateDriver
{
    private Repository $cache;

    private string $prefix;

    private int $ttl;

    public function __construct(Repository $cache, string $prefix = 'dymo_printer', int $ttl = 7_776_000)
    {
        $this->cache  = $cache;
        $this->prefix = $prefix;
        $this->ttl    = $ttl;
    }

    public function get(string $deviceId): ?string
    {
        return $this->cache->get($this->key($deviceId));
    }

    public function set(string $deviceId, string $printerName): void
    {
        $this->cache->put($this->key($deviceId), $printerName, $this->ttl);
    }

    public function clear(string $deviceId): void
    {
        $this->cache->forget($this->key($deviceId));
    }

    private function key(string $deviceId): string
    {
        return "{$this->prefix}:{$deviceId}";
    }
}
