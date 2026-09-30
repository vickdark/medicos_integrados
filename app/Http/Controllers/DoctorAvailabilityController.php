<?php

namespace App\Http\Controllers;

use App\Http\Requests\CalendarRangeRequest;
use App\Models\Appointment;
use App\Models\Doctor;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Gate;

class DoctorAvailabilityController extends Controller
{
    /**
     * Office hours and taken times of the doctor for each day of the window, so
     * the booking calendar can show what is free and what is not.
     */
    public function __invoke(CalendarRangeRequest $request, Doctor $doctor): JsonResponse
    {
        Gate::authorize('create', Appointment::class);

        return response()->json($doctor->availabilityBetween(
            $request->rangeStart(),
            $request->rangeEnd(),
            $request->integer('exclude') ?: null,
        ));
    }
}
