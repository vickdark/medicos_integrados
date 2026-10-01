<?php

namespace App\Http\Controllers;

use App\Actions\Verification\DocumentVerifier;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Public page where anyone can check that a prescription or clinical document
 * was issued by the clinic, by scanning its QR or typing its code.
 */
class DocumentVerificationController extends Controller
{
    /**
     * Show the form to type a code, or go to the result of the code sent.
     */
    public function index(Request $request): Response|RedirectResponse
    {
        $code = trim($request->string('codigo')->limit(40, '')->toString());

        if ($code !== '') {
            return to_route('verification.show', ['code' => DocumentVerifier::normalize($code)]);
        }

        return Inertia::render('Verify', ['code' => null, 'result' => null]);
    }

    /**
     * Show whether the code belongs to a document issued by the clinic.
     */
    public function show(string $code, DocumentVerifier $verifier): Response
    {
        $code = DocumentVerifier::normalize(substr($code, 0, 40));

        return Inertia::render('Verify', [
            'code' => $code,
            'result' => $verifier->find($code),
        ]);
    }
}
