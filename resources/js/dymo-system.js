/**
 * DymoSystem — lightweight wrapper around the official DYMO Label Framework SDK.
 *
 * Assumes `dymo.label.framework` is available globally after loading the SDK script.
 * Fires browser CustomEvents so any component (Livewire, Alpine, vanilla) can react.
 *
 * Public events dispatched on window:
 *   dymo:print           — trigger single-label print
 *   dymo:print-batch     — trigger batch print
 *   dymo:print-success   — print completed successfully
 *   dymo:print-error     — print failed (detail.error contains message)
 */
window.DymoSystem = (() => {
    'use strict';

    // ─── State ────────────────────────────────────────────────────────────────

    let initialized   = false;
    let initPromise   = null;

    /** @type {Map<string, string>} */
    const templateCache = new Map();

    // ─── Framework bootstrap ──────────────────────────────────────────────────

    /**
     * Poll until window.dymo.label.framework is defined (loaded by SDK script).
     * Retries every 400 ms for up to 8 attempts (3.2 s total).
     */
    function waitForFramework(attempts = 8, delayMs = 400) {
        return new Promise((resolve, reject) => {
            let remaining = attempts;

            (function check() {
                if (window.dymo?.label?.framework) {
                    resolve();
                    return;
                }
                if (--remaining <= 0) {
                    reject(new Error(
                        'DYMO Framework SDK not found. ' +
                        'Ensure <x-dymo-printer::scripts /> is in your layout and the DYMO Connect software is installed.'
                    ));
                    return;
                }
                setTimeout(check, delayMs);
            })();
        });
    }

    async function _initialize() {
        await waitForFramework();

        // checkEnvironment() is available in the DYMO Connect Framework and gives an
        // immediate, actionable diagnosis before attempting the async init handshake.
        if (typeof dymo.label.framework.checkEnvironment === 'function') {
            const env = dymo.label.framework.checkEnvironment();
            if (!env.isFrameworkInstalled) {
                throw new Error(
                    'DYMO Connect software is not installed or not running on this machine. ' +
                    (env.errorDetails ?? '')
                );
            }
        }

        await new Promise((resolve, reject) => {
            try {
                dymo.label.framework.init((isOk) => {
                    if (isOk) {
                        resolve(isOk);
                    } else {
                        reject(new Error(
                            'DYMO Framework init failed. ' +
                            'Is the DYMO Connect service running on this machine?'
                        ));
                    }
                });
            } catch (err) {
                reject(err);
            }
        });

        initialized = true;
    }

    /**
     * Ensure the DYMO Framework is initialised exactly once.
     * Safe to call multiple times — subsequent calls await the same promise.
     */
    async function ensureInitialized() {
        if (initialized) return;
        if (!initPromise) initPromise = _initialize();
        return initPromise;
    }

    // ─── Printers ─────────────────────────────────────────────────────────────

    /**
     * Return all connected LabelWriter printers.
     * @returns {Promise<{name: string, isConnected: boolean}[]>}
     */
    async function getPrinters() {
        await ensureInitialized();

        // Prefer getPrintersAsync() (DYMO Connect Framework); fall back to the
        // synchronous getPrinters() present in older DLS SDK versions.
        const all = typeof dymo.label.framework.getPrintersAsync === 'function'
            ? await dymo.label.framework.getPrintersAsync()
            : dymo.label.framework.getPrinters();

        return all
            .filter(p => p.printerType === 'LabelWriterPrinter')
            .map(p => ({
                name: p.name,
                // isConnected is a method in DYMO Connect Framework but a plain boolean
                // property in the older DLS SDK — normalise both forms.
                isConnected: typeof p.isConnected === 'function'
                    ? p.isConnected()
                    : p.isConnected !== false,
            }));
    }

    // ─── Templates ────────────────────────────────────────────────────────────

    /**
     * Load a .dymo template XML.
     * - If the argument already looks like XML it is returned as-is.
     * - URL results are cached in memory to avoid repeated fetches.
     */
    async function loadTemplate(urlOrXml) {
        if (urlOrXml.trimStart().startsWith('<')) return urlOrXml;

        if (templateCache.has(urlOrXml)) return templateCache.get(urlOrXml);

        const res = await fetch(urlOrXml);

        if (!res.ok) {
            throw new Error(`Failed to load DYMO template: ${urlOrXml} (HTTP ${res.status})`);
        }

        const xml = await res.text();
        templateCache.set(urlOrXml, xml);

        return xml;
    }

    // ─── Printing ─────────────────────────────────────────────────────────────

    /**
     * Print one label by merging `data` fields into the template.
     * @param {string} printerName
     * @param {string} templateXml
     * @param {Record<string, any>} data  — keys must match object names in the .dymo template
     */
    async function printSingle(printerName, templateXml, data) {
        const label = dymo.label.framework.openLabelXml(templateXml);

        for (const [key, value] of Object.entries(data)) {
            try {
                label.setObjectText(key, String(value ?? ''));
            } catch {
                // Field not present in this template — skip silently
            }
        }

        label.print(printerName);
    }

    /**
     * Print multiple labels in one SDK call (uses LabelSetBuilder for speed).
     * Falls back to sequential per-label printing when LabelSetBuilder is unavailable
     * (older DYMO SDK versions or certain printer models).
     *
     * @param {string} printerName
     * @param {string} templateXml
     * @param {Record<string, any>[]} labelsData
     */
    async function printBatch(printerName, templateXml, labelsData) {
        const label = dymo.label.framework.openLabelXml(templateXml);

        try {
            const labelSet = new dymo.label.framework.LabelSetBuilder();

            for (const data of labelsData) {
                const record = labelSet.addRecord();
                for (const [key, value] of Object.entries(data)) {
                    try {
                        // setText() is the correct method for DYMO Connect labels.
                        // setTextMarkup() is explicitly not supported for DYMO Connect
                        // format labels and silently produces blank output.
                        record.setText(key, String(value ?? ''));
                    } catch {
                        // Field not in template — skip
                    }
                }
            }

            // toString() is the documented LabelSetBuilder serialisation method.
            await label.printWithParamsAndLabelSetAsync(printerName, null, labelSet.toString());

        } catch {
            // Batch API unavailable — fall back to sequential printing
            for (const data of labelsData) {
                await printSingle(printerName, templateXml, data);
                // Yield to the browser between labels to prevent UI freeze
                await new Promise(r => setTimeout(r, 30));
            }
        }
    }

    // ─── Event Bus ────────────────────────────────────────────────────────────

    /**
     * Listen for Livewire/Alpine dispatched print events and route to the SDK.
     *
     * dymo:print       — { printer, template, data }
     * dymo:print-batch — { printer, template, labels }
     */

    window.addEventListener('dymo:print', async (e) => {
        const { printer, template, data } = e.detail;

        try {
            await ensureInitialized();
            const xml = await loadTemplate(template);
            await printSingle(printer, xml, data ?? {});
            window.dispatchEvent(new CustomEvent('dymo:print-success', {
                detail: { printer },
            }));
        } catch (err) {
            window.dispatchEvent(new CustomEvent('dymo:print-error', {
                detail: { error: err.message },
            }));
        }
    });

    window.addEventListener('dymo:print-batch', async (e) => {
        const { printer, template, labels } = e.detail;

        try {
            await ensureInitialized();
            const xml = await loadTemplate(template);
            await printBatch(printer, xml, labels ?? []);
            window.dispatchEvent(new CustomEvent('dymo:print-success', {
                detail: { printer, count: (labels ?? []).length },
            }));
        } catch (err) {
            window.dispatchEvent(new CustomEvent('dymo:print-error', {
                detail: { error: err.message },
            }));
        }
    });

    // ─── Public API ───────────────────────────────────────────────────────────

    return {
        ensureInitialized,
        getPrinters,
        loadTemplate,
        printSingle,
        printBatch,
    };

})();
