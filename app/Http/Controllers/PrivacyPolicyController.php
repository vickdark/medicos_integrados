<?php

namespace App\Http\Controllers;

use Inertia\Inertia;
use Inertia\Response;

class PrivacyPolicyController extends Controller
{
    /**
     * Show the public personal data processing policy.
     */
    public function __invoke(): Response
    {
        return Inertia::render('Privacy', [
            'version' => config('privacy.version'),
            'updatedAt' => config('privacy.updated_at'),
            'controller' => [
                'company' => config('privacy.company'),
                'nit' => config('privacy.nit'),
                'address' => config('privacy.address'),
                'phone' => config('privacy.phone'),
                'email' => config('privacy.contact_email'),
            ],
        ]);
    }
}
