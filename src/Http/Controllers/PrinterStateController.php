<?php

declare(strict_types=1);

namespace GraystackIT\DymoPrinter\Http\Controllers;

use GraystackIT\DymoPrinter\Facades\DymoPrinter;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

class PrinterStateController extends Controller
{
    public function show(): JsonResponse
    {
        return response()->json([
            'printer'     => DymoPrinter::get(),
            'has_printer' => DymoPrinter::has(),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'printer_name' => ['required', 'string', 'max:255'],
        ]);

        DymoPrinter::set($validated['printer_name']);

        return response()->json([
            'success' => true,
            'printer' => $validated['printer_name'],
        ]);
    }

    public function destroy(): JsonResponse
    {
        DymoPrinter::clear();

        return response()->json(['success' => true]);
    }
}
