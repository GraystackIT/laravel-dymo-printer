/**
 * DymoSystem — event-driven wrapper around the official DYMO Connect Framework SDK.
 *
 * This file is a plain classic script: no top-level import/export, nothing
 * leaks into the global scope except `window.DymoSystem`. It therefore works
 * both ways the package can be integrated:
 *
 *   - Bundler (Vite): imported for its side effects by resources/js/index.js,
 *     which also configures the SDK URL of the vendored SDK copy.
 *   - Classic: served by the package route `dymo-printer.js` and loaded by
 *     `@dymoscripts` / `<x-dymo-printer::scripts />`, which sets
 *     `window.DymoPrinterConfig` right before it.
 *
 * ─── Loading the SDK ─────────────────────────────────────────────────────────
 *
 * The SDK is injected lazily — on the first call that needs it — via
 * document.createElement('script'), from `window.DymoPrinterConfig.sdkUrl`
 * (set by @dymoscripts or with DymoSystem.configure({ sdkUrl })), else from
 * the `defaultSdkUrl` the bundler entry sets. The URL is read when the SDK is
 * first needed, never when this file is evaluated: ES
 * module imports are hoisted, so a bundler entry configures the URL only
 * after this file has run. A script element created from JS is always
 * requested, also on pages whose HTML is inserted by Livewire, where an
 * HTML-parsed <script src> tag may never execute.
 *
 * It is loaded as a classic script on purpose, never as an ES `import`: the
 * SDK is a Google Closure bundle that only bootstraps its `goog` namespace
 * under classic-script global semantics (see the header of
 * resources/js/vendor/dymo-connect-framework.js).
 *
 * Nothing is injected when `window.dymo.label.framework` already exists or an
 * SDK <script> element is already in the document. With `sdkUrl: null` the
 * page is expected to load the SDK itself and DymoSystem only waits for it.
 *
 * ─── Safe against being evaluated twice in one document ─────────────────────
 *
 * wire:navigate injects every head <script> the next page has and the current
 * one does not — so after a rebuild or deploy (a new content-hashed bundle),
 * the next navigation in an open tab evaluates the whole bundle a second time
 * beside the first; a body <script> is even re-run on every navigation. A
 * second evaluation used to inject the SDK a second time
 * (`Namespace "goog.debug.Error" already declared`) and, worse, register a
 * second pair of dymo:print / dymo:print-batch listeners, so every label was
 * printed twice. The SDK load and the listeners therefore live in a
 * window-level registry (`window.__dymoPrinterSystem`): the SDK is injected
 * once per document, and a re-evaluation replaces the previous listeners
 * instead of adding to them.
 *
 * ─── Events on window ───────────────────────────────────────────────────────
 *
 *   dymo:print           — { ref?, printer, template, data }    one label
 *   dymo:print-batch     — { ref?, printer, template, labels }  several labels, ONE SDK call
 *   dymo:print-success   — { ref, printer, count }
 *   dymo:print-error     — { ref, error }
 *
 * `ref` is optional and opaque: whatever a print event carries is echoed back
 * on its success/error event (null when omitted), so a caller can settle
 * exactly the jobs a result belongs to.
 */
(() => {
    'use strict';

    /**
     * State that must exist once per document, not once per evaluation of
     * this file — see "Safe against being evaluated twice" in the header.
     *
     * @returns {{sdkLoadPromise: Promise<void>|null, listeners: {print: Function, printBatch: Function}|null}}
     */
    function documentRegistry() {
        window.__dymoPrinterSystem ??= { sdkLoadPromise: null, listeners: null };

        return window.__dymoPrinterSystem;
    }

    /**
     * The shared, mutable configuration object. Lives on window so that
     * @dymoscripts' inline config, a bundler entry and a later re-evaluation
     * of this file all see the same values.
     *
     *   sdkUrl         explicit SDK URL (@dymoscripts, DymoSystem.configure());
     *                  null = the page loads the SDK itself
     *   defaultSdkUrl  fallback set by the bundler entry (resources/js/index.js)
     *
     * @returns {{sdkUrl?: string|null, defaultSdkUrl?: string}}
     */
    function config() {
        window.DymoPrinterConfig ??= {};

        return window.DymoPrinterConfig;
    }

    /**
     * The SDK URL to inject, read at the time the SDK is first needed.
     *
     * @returns {?string}
     */
    function resolveSdkUrl() {
        const { sdkUrl, defaultSdkUrl } = config();

        return sdkUrl !== undefined ? sdkUrl : (defaultSdkUrl ?? null);
    }

    /**
     * Whether the SDK is already present or on its way: defined globally, or a
     * script element for it is already in the document (injected by this file
     * — marked with data-dymo-printer-sdk — by an earlier bundle that predates
     * the registry, or by the page itself).
     *
     * @param {?string} sdkUrl
     * @returns {boolean}
     */
    function sdkAlreadyPresent(sdkUrl) {
        if (window.dymo?.label?.framework) return true;

        const scripts = Array.from(document.querySelectorAll('script[src]'));

        return scripts.some((script) => script.hasAttribute('data-dymo-printer-sdk')
            || (sdkUrl && script.getAttribute('src') === sdkUrl)
            || /dymo[-.]connect[-.]framework/i.test(script.src));
    }

    /**
     * Injects the DYMO Framework SDK <script> exactly once per document,
     * however often and from however many evaluations of this file it is
     * called. A script element that is already present counts as loaded:
     * waitForFramework() still waits for it to define dymo.label.framework.
     * After a failed load the element is removed and the cached promise
     * cleared, so a later retry (e.g. after the network is back) really tries
     * again; a script that failed to load never executed, so this cannot run
     * the SDK twice.
     *
     * @returns {Promise<void>}
     */
    function loadSdkScript() {
        const registry = documentRegistry();

        if (registry.sdkLoadPromise) return registry.sdkLoadPromise;

        const sdkUrl = resolveSdkUrl();

        // No URL: the page loads the SDK itself — just wait for it.
        if (!sdkUrl || sdkAlreadyPresent(sdkUrl)) {
            registry.sdkLoadPromise = Promise.resolve();

            return registry.sdkLoadPromise;
        }

        registry.sdkLoadPromise = new Promise((resolve, reject) => {
            const script = document.createElement('script');
            script.src = sdkUrl;
            script.setAttribute('data-dymo-printer-sdk', '');
            script.onload = () => resolve();
            script.onerror = () => {
                script.remove();
                reject(new Error(`Failed to load the DYMO Framework SDK from ${sdkUrl}.`));
            };
            document.head.appendChild(script);
        }).catch((err) => {
            registry.sdkLoadPromise = null;
            throw err;
        });

        return registry.sdkLoadPromise;
    }

    // ─── State ────────────────────────────────────────────────────────────────

    let initialized = false;
    let initPromise = null;

    /** @type {Map<string, string>} */
    const templateCache = new Map();

    // ─── Framework bootstrap ──────────────────────────────────────────────────

    /**
     * Poll until window.dymo.label.framework is defined (by the SDK script
     * loadSdkScript() injected, or by one the page loaded itself).
     * Checks immediately, then retries every 400 ms for up to 8 checks total
     * — 7 delays, so 2.8 s of polling before giving up.
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
                    reject(new Error(resolveSdkUrl()
                        ? 'DYMO Framework SDK failed to initialize after loading. '
                            + 'The DYMO Connect software may not be installed or running on this machine.'
                        : 'DYMO Framework SDK not found. No SDK URL is configured: import the package\'s '
                            + 'resources/js/index.js, call DymoSystem.configure({ sdkUrl }) or add @dymoscripts to your layout.',
                    ));
                    return;
                }
                setTimeout(check, delayMs);
            })();
        });
    }

    async function _initialize() {
        await loadSdkScript();
        await waitForFramework();

        await new Promise((resolve, reject) => {
            try {
                dymo.label.framework.init((isOk) => {
                    if (isOk) {
                        resolve(isOk);
                    } else {
                        reject(new Error(
                            'DYMO Framework init failed. '
                            + (describeEnvironmentFailure() ?? 'Is the DYMO Connect service running on this machine?'),
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
     * Best-effort, richer diagnosis for an init() failure, built from
     * checkEnvironment()'s own isFrameworkInstalled/errorDetails.
     *
     * Deliberately never called before init() has run at least once (i.e.
     * only from inside init()'s failure branch above): calling
     * checkEnvironment() cold — before any internal _createFramework instance
     * exists — reaches into the SDK's own `_createFramework.resetFramework()`
     * call, which is only assigned as a side effect of _createFramework's
     * first construction and throws `_createFramework.resetFramework is not a
     * function` otherwise. init() always constructs one first (success or
     * failure), so by the time this runs the same call is safe.
     *
     * @returns {?string} A specific message, or null to fall back to the generic one.
     */
    function describeEnvironmentFailure() {
        if (typeof dymo.label.framework.checkEnvironment !== 'function') return null;

        try {
            const env = dymo.label.framework.checkEnvironment();

            return env.isFrameworkInstalled
                ? null
                : 'DYMO Connect software is not installed or not running on this machine. ' + (env.errorDetails ?? '');
        } catch {
            return null;
        }
    }

    /**
     * Ensure the DYMO Framework is initialised exactly once.
     * Safe to call multiple times — subsequent calls await the same promise.
     * On failure the cached promise is cleared, so a later retry — e.g.
     * after starting DYMO Connect — isn't permanently stuck replaying the
     * first failure's rejection.
     */
    async function ensureInitialized() {
        if (initialized) return;

        if (!initPromise) {
            initPromise = _initialize().catch((err) => {
                initPromise = null;
                throw err;
            });
        }

        return initPromise;
    }

    /**
     * Merge options into the shared configuration (window.DymoPrinterConfig).
     * Only takes effect for an SDK that has not been loaded yet.
     *
     * @param {{sdkUrl?: string|null, defaultSdkUrl?: string}} options
     * @returns {{sdkUrl?: string|null, defaultSdkUrl?: string}} The resulting configuration.
     */
    function configure(options = {}) {
        return Object.assign(config(), options);
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
            .filter((p) => p.printerType === 'LabelWriterPrinter')
            .map((p) => ({
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
     * The SDK's setObjectText() only ever writes a QRCodeObject's
     * <Data><DataString> element (see the SDK's own _setQRCodeObjectText).
     * DYMO Connect's print-time rendering instead reads a QR code's content
     * from a second element every QRCodeObject carries:
     * <TextDataHolder><Value>, which the SDK never touches — so
     * setObjectText() silently "succeeds" while the barcode keeps printing the
     * template's own placeholder. Patches that element by hand via the same
     * dymo.xml.* DOM helpers the SDK uses internally, for every QRCodeObject
     * whose <Name> matches a key in `data`. A no-op for a template with no
     * QRCodeObject, or none named in `data`, and for an SDK without those
     * helpers. Only needed on the single-label path: in a LabelSet the merge
     * is done by the DYMO Connect service itself (see printBatch()).
     *
     * @param {object} label  A dymo.label.framework.Label instance (has a private ._doc XML Document).
     * @param {Record<string, any>} data
     */
    function patchQrCodeTextDataHolders(label, data) {
        if (!label?._doc || typeof dymo.xml?.getElements !== 'function') return;

        const qrObjects = dymo.xml.getElements(label._doc, 'QRCodeObject');

        for (const qrObject of qrObjects) {
            const name = dymo.xml.getElementText(dymo.xml.getElement(qrObject, 'Name'));

            if (!(name in data)) continue;

            const holder = dymo.xml.getElement(qrObject, 'TextDataHolder');
            const valueElement = holder && dymo.xml.getElement(holder, 'Value');

            if (valueElement) dymo.xml.setElementText(valueElement, String(data[name] ?? ''));
        }
    }

    /**
     * Send a label (and an optional LabelSet) to DYMO Connect. Prefers the
     * asynchronous printAsync() — its promise settles only once the DYMO
     * Connect service answered the PrintLabel request, and rejects on a
     * service error — and falls back to the synchronous print() of older SDKs.
     *
     * @param {object} label  A dymo.label.framework.Label instance.
     * @param {string} printerName
     * @param {string} labelSetXml  Serialized LabelSet, or '' for the label as it is.
     */
    async function sendToPrinter(label, printerName, labelSetXml) {
        if (typeof label.printAsync !== 'function') {
            label.print(printerName, '', labelSetXml);
            return;
        }

        const result = await label.printAsync(printerName, '', labelSetXml);

        if (result === false) {
            throw new Error('DYMO Connect did not accept the print job.');
        }
    }

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

        patchQrCodeTextDataHolders(label, data);

        await sendToPrinter(label, printerName, '');
    }

    /**
     * Print several labels with ONE SDK call: one LabelSet with a record per
     * label, sent with the template in a single PrintLabel request.
     *
     * Every label keeps its own QR code. record.setText() does not touch the
     * template at all — LabelSetBuilder only serializes name/value pairs to
     * `<LabelRecord><ObjectData Name="…">value</ObjectData></LabelRecord>`
     * (see LabelSetBuilder.toXml in the SDK) — and the DYMO Connect service
     * merges each record into the named object itself, QRCodeObject included.
     * The setObjectText()/<TextDataHolder> limitation patched in printSingle()
     * is a client-side SDK one and does not apply here.
     *
     * Only names the template actually has go into a record, so a payload
     * field without a matching object cannot fail the whole batch. Falls back
     * to one call per label only when the SDK has no LabelSetBuilder at all.
     *
     * A failure of the batch call itself propagates instead of falling back:
     * retrying label by label could reprint labels that already came out.
     *
     * @param {string} printerName
     * @param {string} templateXml
     * @param {Record<string, any>[]} labelsData
     */
    async function printBatch(printerName, templateXml, labelsData) {
        if (labelsData.length === 0) return;

        if (typeof dymo.label.framework.LabelSetBuilder !== 'function') {
            for (const data of labelsData) {
                await printSingle(printerName, templateXml, data);
            }

            return;
        }

        const label = dymo.label.framework.openLabelXml(templateXml);
        // No filter at all when the SDK reports no names, rather than a
        // filter that would silently drop every field.
        const knownNames = typeof label.getObjectNames === 'function' ? label.getObjectNames() : [];
        const objectNames = knownNames.length > 0 ? new Set(knownNames) : null;
        const labelSet = new dymo.label.framework.LabelSetBuilder();

        for (const data of labelsData) {
            const record = labelSet.addRecord();

            for (const [key, value] of Object.entries(data)) {
                if (objectNames && !objectNames.has(key)) continue;

                // setText(), not setTextMarkup(): markup is not supported for
                // DYMO Connect format labels and prints blank.
                record.setText(key, String(value ?? ''));
            }
        }

        await sendToPrinter(label, printerName, labelSet.toString());
    }

    // ─── Event Bus ────────────────────────────────────────────────────────────

    /**
     * dymo:print — { ref?, printer, template, data }
     * @param {CustomEvent} e
     */
    async function onPrint(e) {
        const { ref = null, printer, template, data } = e.detail ?? {};

        try {
            await ensureInitialized();
            const xml = await loadTemplate(template);
            await printSingle(printer, xml, data ?? {});
            window.dispatchEvent(new CustomEvent('dymo:print-success', { detail: { ref, printer, count: 1 } }));
        } catch (err) {
            window.dispatchEvent(new CustomEvent('dymo:print-error', { detail: { ref, error: err?.message ?? String(err) } }));
        }
    }

    /**
     * dymo:print-batch — { ref?, printer, template, labels }
     * @param {CustomEvent} e
     */
    async function onPrintBatch(e) {
        const { ref = null, printer, template, labels } = e.detail ?? {};

        try {
            await ensureInitialized();
            const xml = await loadTemplate(template);
            await printBatch(printer, xml, labels ?? []);
            window.dispatchEvent(new CustomEvent('dymo:print-success', { detail: { ref, printer, count: (labels ?? []).length } }));
        } catch (err) {
            window.dispatchEvent(new CustomEvent('dymo:print-error', { detail: { ref, error: err?.message ?? String(err) } }));
        }
    }

    // Exactly one listener per event per document: a re-evaluation of this
    // file (see the header) swaps the previous pair out.
    const registry = documentRegistry();

    if (registry.listeners) {
        window.removeEventListener('dymo:print', registry.listeners.print);
        window.removeEventListener('dymo:print-batch', registry.listeners.printBatch);
    }

    registry.listeners = { print: onPrint, printBatch: onPrintBatch };
    window.addEventListener('dymo:print', onPrint);
    window.addEventListener('dymo:print-batch', onPrintBatch);

    // ─── Public API ───────────────────────────────────────────────────────────

    window.DymoSystem = {
        configure,
        ensureInitialized,
        getPrinters,
        loadTemplate,
        printSingle,
        printBatch,
    };
})();
