{{--
    Livewire printer selector component.

    Usage:
        <x-dymo-printer::scripts />
        <livewire:dymo-printer::printer-select />

    Or with auto_load_scripts = true in config, just:
        <livewire:dymo-printer::printer-select />

    Listens for: (none — Alpine manages all local state)
    Dispatches:  dymo-printer-saved { printer }
--}}

@if(config('dymo-printer.auto_load_scripts'))
    <x-dymo-printer::scripts />
@endif

<div
    wire:ignore.self
    x-data="dymoPrinterSelect(@js($selectedPrinter))"
    x-init="init()"
>
    <div class="flex flex-wrap items-center gap-2">

        <select
            x-model="selected"
            @focus.once="loadPrinters()"
            :disabled="loading"
            class="block rounded-md border border-gray-300 px-3 py-1.5 text-sm shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500 disabled:opacity-60"
        >
            <template x-if="!loading && printers.length === 0">
                <option value="">{{ __('Focus to load printers…') }}</option>
            </template>

            <template x-if="loading">
                <option value="" disabled>{{ __('Loading printers…') }}</option>
            </template>

            <template x-if="!loading && printers.length > 0">
                <option value="">{{ __('— select a printer —') }}</option>
            </template>

            <template x-for="printer in printers" :key="printer.name">
                <option :value="printer.name" x-text="printer.name"></option>
            </template>
        </select>

        <button
            type="button"
            @click="save()"
            :disabled="!selected || saving"
            class="inline-flex items-center rounded-md bg-indigo-600 px-3 py-1.5 text-sm font-medium text-white shadow-sm hover:bg-indigo-700 disabled:cursor-not-allowed disabled:opacity-50"
        >
            <span x-show="!saving">{{ __('Save') }}</span>
            <span x-show="saving" class="flex items-center gap-1">
                <svg class="h-3 w-3 animate-spin" viewBox="0 0 24 24" fill="none">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"/>
                </svg>
                {{ __('Saving…') }}
            </span>
        </button>

        <span
            x-show="saved"
            x-transition.opacity
            class="text-sm text-green-600"
        >{{ __('Saved!') }}</span>

        <span
            x-show="error"
            x-transition.opacity
            x-text="error"
            class="text-sm text-red-600"
        ></span>

    </div>
</div>

@once('dymo-printer-livewire-script')
<script>
function dymoPrinterSelect(initialPrinter) {
    return {
        selected: initialPrinter || '',
        printers: initialPrinter ? [{ name: initialPrinter, isConnected: true }] : [],
        loading:  false,
        saving:   false,
        saved:    false,
        error:    null,
        loaded:   false,

        async init() {
            // Pre-populate with the saved printer so the select shows it immediately,
            // even before the user focuses the dropdown to trigger a full printer list load.
        },

        async loadPrinters() {
            if (this.loaded) return;
            this.loading = true;
            this.error   = null;

            try {
                if (typeof DymoSystem === 'undefined') {
                    throw new Error('DymoSystem not found. Add <x-dymo-printer::scripts /> to your layout or enable auto_load_scripts in config.');
                }

                await DymoSystem.ensureInitialized();
                this.printers = await DymoSystem.getPrinters();
                this.loaded   = true;

                // Keep the previously saved printer visible even if not currently returned
                if (this.selected && !this.printers.find(p => p.name === this.selected)) {
                    this.printers.unshift({ name: this.selected, isConnected: false });
                }
            } catch (e) {
                this.error = e.message;
            } finally {
                this.loading = false;
            }
        },

        async save() {
            if (!this.selected) return;
            this.saving = true;
            this.error  = null;

            try {
                // Pass the selected printer name to the Livewire component
                await this.$wire.save(this.selected);
                this.saved = true;
                setTimeout(() => { this.saved = false; }, 3000);
            } catch (e) {
                this.error = 'Could not save: ' + e.message;
            } finally {
                this.saving = false;
            }
        },
    };
}
</script>
@endonce
