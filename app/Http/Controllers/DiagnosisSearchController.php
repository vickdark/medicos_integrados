<?php

namespace App\Http\Controllers;

use App\Models\Diagnosis;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DiagnosisSearchController extends Controller
{
    private const LIMIT = 20;

    /**
     * Search the CIE-10 catalog by code or description. The catalog is too big to
     * send to the browser, so the diagnosis picker queries it as the doctor types.
     */
    public function __invoke(Request $request): JsonResponse
    {
        abort_unless($request->user()->isStaff(), 403);

        $term = trim($request->string('q')->limit(100, '')->toString());

        if (mb_strlen($term) < 2) {
            return response()->json([]);
        }

        return response()->json(
            Diagnosis::query()
                ->active()
                ->search($term)
                ->orderBy('code')
                ->limit(self::LIMIT)
                ->get()
                ->map(fn (Diagnosis $diagnosis): array => $diagnosis->toOption()),
        );
    }
}
