<?php

declare(strict_types=1);

namespace GraystackIT\DymoPrinter\Http\Controllers;

use GraystackIT\DymoPrinter\ScriptAssets;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;

/**
 * Serves the package's JavaScript files (see ScriptAssets) without requiring
 * asset publishing.
 */
class ScriptController extends Controller
{
    public function show(string $asset): Response
    {
        if (! isset(ScriptAssets::ASSETS[$asset])) {
            abort(404);
        }

        return response(
            (string) file_get_contents(ScriptAssets::path($asset)),
            200,
            [
                'Content-Type'  => 'application/javascript',
                'Cache-Control' => 'public, max-age=86400',
            ],
        );
    }
}
