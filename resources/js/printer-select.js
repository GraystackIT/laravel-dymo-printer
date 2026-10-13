/**
 * Alpine data for the package's printer pickers:
 *
 *   dymoPrinterSelect(initialPrinter)
 *       <livewire:dymo-printer::printer-select /> — saves through $wire.save().
 *
 *   dymoPrinterSelectStandalone({ showUrl, storeUrl, initialPrinter? })
 *       <x-dymo-printer::printer-select /> — loads and saves through the
 *       package's REST endpoints (dymo-printer.printer.show / .store).
 *
 * A plain classic script (no import/export), loaded by @dymoscripts or
 * imported by resources/js/index.js. The factories are registered with
 * Alpine.data() — on `alpine:init`, and right away when Alpine is already
 * there — instead of living in an inline <script> inside the component views:
 * a component reached as a NESTED Livewire child has its DOM inserted by
 * Livewire's JS rather than by the browser's HTML parser, and an inline
 * <script> inside that markup never reliably executes. Alpine.data()
 * registrations are looked up by name, however and whenever the element was
 * inserted. They are also exposed as window globals, which Alpine resolves as
 * well when an element was initialised before the registration ran.
 *
 * Evaluating this file twice is harmless: a second registration simply
 * replaces the first with an identical factory.
 */
(() => {
    'use strict';

    /**
     * Shared state and behaviour of both pickers.
     *
     * @param {string} initialPrinter  The saved printer, shown before the list is loaded.
     * @param {(printer: string, component: object) => Promise<void>} persist
     * @returns {object}
     */
    function basePrinterSelect(initialPrinter, persist) {
        return {
            selected: initialPrinter || '',
            printers: initialPrinter ? [{ name: initialPrinter, isConnected: true }] : [],
            loading: false,
            saving: false,
            saved: false,
            error: null,
            loaded: false,

            /**
             * Load the connected printers. Bound to every focus of the select,
             * not just the first: it returns at once after a successful load,
             * but a failed one (DYMO Connect not running yet) is retried on
             * the next focus instead of leaving the picker dead.
             */
            async loadPrinters() {
                if (this.loaded || this.loading) return;
                this.loading = true;
                this.error = null;

                try {
                    if (typeof window.DymoSystem === 'undefined') {
                        throw new Error('DymoSystem not found. Import the package\'s resources/js/index.js or add @dymoscripts to your layout.');
                    }

                    await window.DymoSystem.ensureInitialized();
                    this.printers = await window.DymoSystem.getPrinters();
                    this.loaded = true;

                    // Keep the previously saved printer visible even if not currently returned
                    if (this.selected && !this.printers.find((p) => p.name === this.selected)) {
                        this.printers.unshift({ name: this.selected, isConnected: false });
                    }

                    // The <option> list just went from "Loading printers…" (a single,
                    // disabled placeholder) to the real printer list via x-if/x-for —
                    // removing the previously-selected <option> from a <select> with
                    // nothing else marked selected makes the browser auto-select
                    // whatever is now first in DOM order, silently, with no `change`
                    // event. Since x-model only learns of a new value from that event,
                    // `selected` (and the Save button's disabled state, bound to it)
                    // would be left at a stale '' while the <select> visibly shows a
                    // printer. Re-read the real DOM value once the option swap has
                    // settled and adopt it, so Alpine's state matches the screen.
                    await this.$nextTick();
                    if (this.$refs.select && this.$refs.select.value) {
                        this.selected = this.$refs.select.value;
                    }
                } catch (e) {
                    this.error = e?.message ?? String(e);
                } finally {
                    this.loading = false;
                }
            },

            async save() {
                if (!this.selected || this.saving) return;
                this.saving = true;
                this.saved = false;
                this.error = null;

                try {
                    await persist(this.selected, this);
                    this.saved = true;
                    setTimeout(() => { this.saved = false; }, 3000);
                } catch (e) {
                    this.error = 'Could not save: ' + (e?.message ?? String(e));
                } finally {
                    this.saving = false;
                }
            },
        };
    }

    /**
     * Livewire picker: persists through the component's save() action, which
     * dispatches the `dymo-printer-saved` Livewire event.
     *
     * @param {?string} initialPrinter
     * @returns {object}
     */
    function dymoPrinterSelect(initialPrinter) {
        return basePrinterSelect(initialPrinter, (printer, component) => component.$wire.save(printer));
    }

    /**
     * Standalone (non-Livewire) picker: persists through the package's REST
     * endpoint and dispatches `dymo-printer-saved` { printer } on window.
     *
     * @param {{showUrl?: string, storeUrl: string, initialPrinter?: ?string}} options
     * @returns {object}
     */
    function dymoPrinterSelectStandalone(options = {}) {
        const { showUrl = null, storeUrl, initialPrinter = null } = options;

        return {
            ...basePrinterSelect(initialPrinter, async (printer) => {
                const res = await fetch(storeUrl, {
                    method: 'POST',
                    headers: {
                        'Accept': 'application/json',
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content ?? '',
                    },
                    body: JSON.stringify({ printer_name: printer }),
                });

                const body = await res.json().catch(() => ({}));

                if (!res.ok || !body.success) {
                    throw new Error(body.message ?? `HTTP ${res.status}`);
                }

                window.dispatchEvent(new CustomEvent('dymo-printer-saved', { detail: { printer } }));
            }),

            async init() {
                if (!showUrl || this.selected) return;

                try {
                    const res = await fetch(showUrl, { headers: { 'Accept': 'application/json' } });
                    const body = await res.json();

                    if (body.printer && !this.selected) {
                        this.selected = body.printer;
                        this.printers = [{ name: body.printer, isConnected: true }];
                    }
                } catch {
                    // Non-critical; the user can still select manually.
                }
            },
        };
    }

    /**
     * @param {object} Alpine
     */
    function register(Alpine) {
        Alpine.data('dymoPrinterSelect', dymoPrinterSelect);
        Alpine.data('dymoPrinterSelectStandalone', dymoPrinterSelectStandalone);
    }

    window.dymoPrinterSelect = dymoPrinterSelect;
    window.dymoPrinterSelectStandalone = dymoPrinterSelectStandalone;

    document.addEventListener('alpine:init', () => register(window.Alpine));

    if (window.Alpine && typeof window.Alpine.data === 'function') {
        register(window.Alpine);
    }
})();
