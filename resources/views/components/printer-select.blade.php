{{--
    Standalone (non-Livewire) printer selector.
    Uses Alpine.js + the package REST API — no Livewire dependency needed.

    Usage:
        <x-dymo-printer::scripts />
        <x-dymo-printer::printer-select />

    Optional slot for custom styling:
        <x-dymo-printer::printer-select class="my-custom-class" />
--}}

@props(['class' => ''])

<div
    x-data="dymoPrinterSelectStandalone()"
    x-init="init()"
    {{ $attributes->merge(['class' => $class]) }}
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

@once('dymo-printer-standalone-script')
<script>
function dymoPrinterSelectStandalone() {
    return {
        selected: '',
        printers: [],
        loading:  false,
        saving:   false,
        saved:    false,
        error:    null,
        loaded:   false,

        async init() {
            try {
                const res  = await fetch('{{ route('dymo-printer.printer.show') }}');
                const body = await res.json();
                this.selected = body.printer ?? '';
            } catch {
                // Non-critical; user can still select manually
            }
        },

        async loadPrinters() {
            if (this.loaded) return;
            this.loading = true;
            this.error   = null;

            try {
                if (typeof DymoSystem === 'undefined') {
                    throw new Error('DymoSystem not found. Add <x-dymo-printer::scripts /> to your layout.');
                }
                await DymoSystem.ensureInitialized();
                this.printers = await DymoSystem.getPrinters();
                this.loaded   = true;

                // Keep the previously saved printer in the list even if not returned
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
                const res = await fetch('{{ route('dymo-printer.printer.store') }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content ?? '',
                    },
                    body: JSON.stringify({ printer_name: this.selected }),
                });

                const body = await res.json();

                if (body.success) {
                    this.saved = true;
                    setTimeout(() => { this.saved = false; }, 3000);
                    window.dispatchEvent(new CustomEvent('dymo-printer-saved', { detail: { printer: this.selected } }));
                }
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
