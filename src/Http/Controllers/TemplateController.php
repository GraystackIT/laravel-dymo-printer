<?php

declare(strict_types=1);

namespace GraystackIT\DymoPrinter\Http\Controllers;

use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use Illuminate\Support\Str;

class TemplateController extends Controller
{
    public function show(string $name): Response
    {
        // Guard against path traversal — only allow simple filenames
        $name = basename($name);

        if (! Str::endsWith($name, '.dymo')) {
            abort(404);
        }

        $basePath = rtrim((string) config('dymo-printer.templates_path'), '/\\');
        $fullPath = $basePath . DIRECTORY_SEPARATOR . $name;

        if (! file_exists($fullPath) || ! is_file($fullPath)) {
            abort(404);
        }

        return response(
            file_get_contents($fullPath),
            200,
            ['Content-Type' => 'application/xml; charset=utf-8'],
        );
    }
}
