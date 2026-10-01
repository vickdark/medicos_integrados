<?php

namespace App\Http\Controllers;

use App\Models\AppSetting;
use App\Models\Doctor;
use Inertia\Inertia;
use Inertia\Response;
use Laravel\Fortify\Features;

class HomeController extends Controller
{
    /**
     * Show the public landing page.
     */
    public function __invoke(): Response
    {
        return Inertia::render('Welcome', [
            'canRegister' => Features::enabled(Features::registration()),
            'content' => AppSetting::landingContent(),
            'doctors' => AppSetting::showDoctorsOnLanding() ? $this->publicDoctors() : null,
        ]);
    }

    /**
     * Basic public profile of the active doctors, for the landing page.
     *
     * @return list<array{id: int, name: string, specialty: string, bio: string|null, photo_url: string|null}>
     */
    private function publicDoctors(): array
    {
        return Doctor::query()
            ->with(['user', 'specialty'])
            ->whereHas('user', fn ($query) => $query->where('is_active', true))
            ->get()
            ->sortBy('user.name')
            ->values()
            ->map(fn (Doctor $doctor): array => [
                'id' => $doctor->id,
                'name' => $doctor->user->name,
                'specialty' => $doctor->specialty->name,
                'bio' => $doctor->bio,
                'photo_url' => $doctor->photoUrl(),
            ])
            ->all();
    }
}
