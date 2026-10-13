{{--
    Standalone (non-Livewire) printer selector.
    Uses Alpine.js + the package REST API — no Livewire dependency needed.

    Usage (scripts loaded once in the layout, or via the bundler entry):
        @dymoscripts
        <x-dymo-printer::printer-select />

    Optional class for custom styling:
        <x-dymo-printer::printer-select class="my-custom-class" />

    Dispatches: dymo-printer-saved { printer } on window

    The Alpine factory `dymoPrinterSelectStandalone` comes from the package's
    printer-select.js, not from an inline <script> here, so the component also
    works where its markup is inserted by JS (e.g. inside a Livewire component).
--}}

@props(['class' => ''])

<div
    x-data="dymoPrinterSelectStandalone(@js([
        'showUrl'  => route('dymo-printer.printer.show'),
        'storeUrl' => route('dymo-printer.printer.store'),
    ]))"
    {{ $attributes->merge(['class' => trim('w-full min-w-0 ' . $class)]) }}
>
    <div class="flex items-center gap-2">

        <select
            x-ref="select"
            x-model="selected"
            @focus="loadPrinters()"
            :disabled="loading"
            aria-label="{{ __('Select a DYMO printer') }}"
            class="block min-w-0 grow rounded-md border border-gray-300 px-3 py-1.5 text-sm shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500 disabled:opacity-60"
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

        {{-- While saving, a spinner over the hidden label: the label keeps the button's width. --}}
        <button
            type="button"
            @click="save()"
            :disabled="!selected || saving"
            :aria-busy="saving ? 'true' : 'false'"
            class="relative inline-flex shrink-0 items-center rounded-md bg-indigo-600 px-3 py-1.5 text-sm font-medium text-white shadow-sm hover:bg-indigo-700 disabled:cursor-not-allowed disabled:opacity-50"
        >
            <span :class="saving && 'invisible'">{{ __('Save') }}</span>
            <span x-show="saving" style="display: none;" class="absolute inset-0 flex items-center justify-center">
                <svg class="h-4 w-4 animate-spin" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"/>
                </svg>
                <span class="sr-only">{{ __('Saving…') }}</span>
            </span>
        </button>

        <span
            x-show="saved"
            x-transition.opacity
            style="display: none;"
            role="status"
            class="shrink-0 text-sm text-green-600"
        >{{ __('Saved!') }}</span>

    </div>

    {{-- On its own full-width row and allowed to break anywhere: an SDK load
         error carries a long, hyphen-less URL that would otherwise widen the row. --}}
    <span
        x-show="error"
        x-transition.opacity
        x-text="error"
        style="display: none;"
        role="alert"
        class="mt-1.5 block max-w-full text-sm text-red-600 [overflow-wrap:anywhere]"
    ></span>
</div>
