/**
 * Bundler (Vite) entry of graystackit/laravel-dymo-printer.
 *
 *     import '../../vendor/graystackit/laravel-dymo-printer/resources/js/index.js';
 *
 * Replaces @dymoscripts / <x-dymo-printer::scripts />: do not use both.
 *
 * The DYMO Connect Framework SDK is NOT executed here. The `?url` import only
 * makes the bundler pin and content-hash the vendored SDK as a static asset;
 * DymoSystem injects it as a classic <script> the first time it is needed
 * (see the header of ./vendor/dymo-connect-framework.js for why it cannot be
 * an ES module).
 *
 * ES imports are hoisted and evaluated before the body of this file, so
 * dymo-system.js runs before the SDK URL is configured below. That is fine:
 * DymoSystem reads `window.DymoPrinterConfig.sdkUrl` only when it first loads
 * the SDK.
 */
import bundledSdkUrl from './vendor/dymo-connect-framework.js?url';
import './dymo-system.js';
import './printer-select.js';

// Only a default: an explicitly configured sdkUrl (DymoSystem.configure() or
// window.DymoPrinterConfig.sdkUrl) wins. Always overwritten, so after a deploy
// a re-evaluated newer bundle uses its own, current content-hashed URL.
window.DymoSystem.configure({ defaultSdkUrl: bundledSdkUrl });
