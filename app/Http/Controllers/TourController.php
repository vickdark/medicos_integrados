<?php

namespace App\Http\Controllers;

use App\Http\Middleware\EnsureDeviceId;
use App\Models\TourView;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class TourController extends Controller
{
    /**
     * Remember that the user completed or skipped the guided tour on this device.
     */
    public function store(Request $request): RedirectResponse
    {
        $request->user()->tourViews()->firstOrCreate([
            'tour' => TourView::PATIENT_ONBOARDING,
            'device_id' => EnsureDeviceId::from($request),
        ]);

        return back();
    }
}
