@props([
    'loadSdk' => true,
    'sdkUrl'  => null,
])

@once('dymo-printer-scripts')
    @if($loadSdk)
        <script src="{{ $sdkUrl ?? config('dymo-printer.sdk_url') }}"></script>
    @endif

    {{--
        Serve dymo-system.js directly from the package route so no asset
        publishing step is needed. Once you publish assets and point Vite at
        resources/js/vendor/dymo-printer/dymo-system.js you can replace this
        with @vite or a plain asset() call.
    --}}
    <script src="{{ route('dymo-printer.js') }}"></script>
@endonce
