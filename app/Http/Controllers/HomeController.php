<?php

namespace App\Http\Controllers;

use App\Models\Specialty;
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
            'specialties' => Specialty::query()
                ->withCount('doctors')
                ->orderBy('name')
                ->get(['id', 'name', 'description']),
        ]);
    }
}
