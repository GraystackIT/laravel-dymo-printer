@props([
    'loadSdk' => true,
    'sdkUrl'  => null,
])

{{--
    Classic integration: configures DymoSystem and loads the package scripts
    from the package routes — no asset publishing or bundler needed. Apps with
    a bundler import resources/js/index.js instead; do not use both.

    The DYMO Connect SDK itself is not loaded here: DymoSystem injects it from
    `sdkUrl` the first time it is needed (`dymo-printer.sdk_url`, by default the
    package's vendored, pinned copy). :load-sdk="false" means the page loads
    the SDK itself and DymoSystem only waits for `window.dymo.label.framework`.

    data-navigate-once: with wire:navigate the scripts run once per document.
    Running them again would be harmless (DymoSystem keeps one SDK load and one
    pair of print listeners per document), just wasteful.
--}}
@once('dymo-printer-scripts')
    <script data-navigate-once>
        window.DymoPrinterConfig = Object.assign(window.DymoPrinterConfig || {}, @js([
            'sdkUrl' => $loadSdk ? ($sdkUrl ?? \GraystackIT\DymoPrinter\ScriptAssets::sdkUrl()) : null,
        ]));
    </script>
    <script src="{{ \GraystackIT\DymoPrinter\ScriptAssets::url('dymo-system') }}" data-navigate-once></script>
    <script src="{{ \GraystackIT\DymoPrinter\ScriptAssets::url('printer-select') }}" data-navigate-once></script>
@endonce
