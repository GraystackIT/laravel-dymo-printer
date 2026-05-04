<?php

declare(strict_types=1);

use GraystackIT\DymoPrinter\Facades\DymoPrinter;

if (! function_exists('dymo_printer')) {
    /**
     * Get the currently selected printer name for this device.
     */
    function dymo_printer(): ?string
    {
        return DymoPrinter::get();
    }
}

if (! function_exists('dymo_has_printer')) {
    /**
     * Whether a printer has been selected for this device.
     */
    function dymo_has_printer(): bool
    {
        return DymoPrinter::has();
    }
}

if (! function_exists('dymo_template_url')) {
    /**
     * Generate the URL to serve a .dymo template by filename.
     */
    function dymo_template_url(string $name): string
    {
        return DymoPrinter::templateUrl($name);
    }
}
