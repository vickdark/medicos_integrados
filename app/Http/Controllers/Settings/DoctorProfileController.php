<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DoctorProfileController extends Controller
{
    /**
     * Show the doctor's own professional profile.
     */
    public function show(Request $request): Response
    {
        $user = $request->user();
        $doctor = $user->doctor?->load('specialty');

        abort_if($doctor === null, 403);

        return Inertia::render('settings/DoctorProfile', [
            'profile' => [
                'id' => $doctor->id,
                'name' => $user->name,
                'email' => $user->email,
                'specialty' => $doctor->specialty->name,
                'license_number' => $doctor->license_number,
                'phone' => $doctor->phone,
                'consultation_fee' => $doctor->consultation_fee,
                'slot_minutes' => $doctor->slotLength(),
                'bio' => $doctor->bio,
                'schedule_summary' => $doctor->weeklyScheduleSummary(),
            ],
        ]);
    }
}
