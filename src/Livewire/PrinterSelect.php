<?php

declare(strict_types=1);

namespace GraystackIT\DymoPrinter\Livewire;

use GraystackIT\DymoPrinter\Facades\DymoPrinter;
use Illuminate\View\View;
use Livewire\Component;

class PrinterSelect extends Component
{
    public string $selectedPrinter = '';

    public bool $saved = false;

    public function mount(): void
    {
        $this->selectedPrinter = DymoPrinter::get() ?? '';
    }

    /**
     * Called from Alpine via $wire.save(printerName).
     * Persists the selection and fires a browser event for other components to react.
     */
    public function save(string $printerName): void
    {
        $this->selectedPrinter = $printerName;

        DymoPrinter::set($printerName);

        $this->saved = true;

        $this->dispatch('dymo-printer-saved');
    }

    public function render(): View
    {
        return view('dymo-printer::livewire.printer-select');
    }
}
