<?php

namespace App\Http\Controllers;

use App\Models\AppSetting;
use Inertia\Inertia;
use Inertia\Response;

class PrivacyPolicyController extends Controller
{
    /**
     * Show the public personal data processing policy.
     */
    public function __invoke(): Response
    {
        $policy = AppSetting::privacyPolicy();

        return Inertia::render('Privacy', [
            'version' => $policy['version'],
            'updatedAt' => $policy['updated_at'],
            'controller' => [
                'company' => $policy['company'],
                'nit' => $policy['nit'],
                'address' => $policy['address'],
                'phone' => $policy['phone'],
                'email' => $policy['contact_email'],
                'rnbd' => $policy['rnbd_registration'],
            ],
        ]);
    }
}
