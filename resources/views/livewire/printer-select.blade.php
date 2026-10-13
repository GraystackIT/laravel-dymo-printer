{{--
    Livewire printer selector component.

    Usage (scripts loaded once in the layout, or via the bundler entry):
        @dymoscripts
        <livewire:dymo-printer::printer-select />

    Or with auto_load_scripts = true in config, just:
        <livewire:dymo-printer::printer-select />

    Dispatches: dymo-printer-saved (Livewire event, no payload)

    Two structural rules, both needed for the component to work as a NESTED
    Livewire child — keep them when you publish and restyle this view:

    1. The root <div> is the very first thing rendered. A leading Blade
       control-flow block (@if …) makes Livewire emit an
       `<!--[if BLOCK]><![endif]-->` comment pair as the literal start of the
       HTML; Livewire's nesting support reads the child's root tag name from the
       first `<`, stores an empty name and later throws "Invalid Livewire child
       tag name". The auto_load_scripts block therefore sits at the end, inside
       the root, and uses @assets.

    2. No inline <script>. Livewire inserts a nested child's DOM through its own
       JS, and an inline <script> inside that markup never reliably executes.
       The Alpine factory `dymoPrinterSelect` comes from the package's
       printer-select.js (loaded by @dymoscripts or resources/js/index.js) and
       is registered with Alpine.data(), resolved by name.
--}}
<div
    wire:ignore.self
    x-data="dymoPrinterSelect(@js($selectedPrinter))"
    class="w-full min-w-0"
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
         error carries a long, hyphen-less URL that would otherwise widen the
         row (and any popover around it) to fit. --}}
    <span
        x-show="error"
        x-transition.opacity
        x-text="error"
        style="display: none;"
        role="alert"
        class="mt-1.5 block max-w-full text-sm text-red-600 [overflow-wrap:anywhere]"
    ></span>

    @if(config('dymo-printer.auto_load_scripts'))
        @assets
            <x-dymo-printer::scripts />
        @endassets
    @endif
</div>
