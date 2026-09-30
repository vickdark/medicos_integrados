<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreDoctorScheduleRequest;
use App\Http\Requests\UpdateDoctorSlotRequest;
use App\Models\Doctor;
use App\Models\DoctorSchedule;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class DoctorScheduleController extends Controller
{
    /**
     * Display the doctor's weekly office hours.
     */
    public function index(Doctor $doctor): Response
    {
        Gate::authorize('manage', [DoctorSchedule::class, $doctor]);

        $doctor->load(['user', 'specialty']);

        return Inertia::render('doctors/Schedules', [
            'doctor' => [
                'id' => $doctor->id,
                'name' => $doctor->user->name,
                'specialty' => $doctor->specialty->name,
                'slot_minutes' => $doctor->slotLength(),
            ],
            'slotOptions' => Doctor::SLOT_OPTIONS,
            'schedules' => $doctor->schedules()
                ->orderBy('day_of_week')
                ->orderBy('starts_at')
                ->get()
                ->map(fn (DoctorSchedule $schedule): array => [
                    'id' => $schedule->id,
                    'day_of_week' => $schedule->day_of_week,
                    'day_name' => DoctorSchedule::DAY_NAMES[$schedule->day_of_week],
                    'starts_at' => $schedule->startTime(),
                    'ends_at' => $schedule->endTime(),
                ]),
            'days' => collect(DoctorSchedule::DAY_NAMES)
                ->map(fn (string $name, int $day): array => ['value' => $day, 'label' => $name])
                ->values(),
        ]);
    }

    /**
     * Add an office hours block.
     */
    public function store(StoreDoctorScheduleRequest $request, Doctor $doctor): RedirectResponse
    {
        $days = $request->dayList();

        foreach ($days as $day) {
            $doctor->schedules()->create([
                'day_of_week' => $day,
                'starts_at' => $request->validated('starts_at'),
                'ends_at' => $request->validated('ends_at'),
            ]);
        }

        return back()->with('success', count($days) > 1 ? 'Horarios agregados.' : 'Horario agregado.');
    }

    /**
     * Change how long each appointment of the doctor lasts.
     */
    public function updateSlot(UpdateDoctorSlotRequest $request, Doctor $doctor): RedirectResponse
    {
        $doctor->update($request->validated());

        return back()->with('success', 'Duración de las citas actualizada.');
    }

    /**
     * Remove an office hours block.
     */
    public function destroy(DoctorSchedule $schedule): RedirectResponse
    {
        Gate::authorize('delete', $schedule);

        $schedule->delete();

        return back()->with('success', 'Horario eliminado.');
    }
}
