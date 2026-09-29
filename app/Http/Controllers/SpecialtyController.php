<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreSpecialtyRequest;
use App\Http\Requests\UpdateSpecialtyRequest;
use App\Models\Specialty;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class SpecialtyController extends Controller
{
    /**
     * Display the specialties catalog.
     */
    public function index(Request $request): Response
    {
        Gate::authorize('viewAny', Specialty::class);

        return Inertia::render('specialties/Index', [
            'specialties' => Specialty::query()
                ->withCount('doctors')
                ->orderBy('name')
                ->get()
                ->map(fn (Specialty $specialty): array => [
                    'id' => $specialty->id,
                    'name' => $specialty->name,
                    'description' => $specialty->description,
                    'doctors_count' => $specialty->doctors_count,
                    'can_delete' => $request->user()->can('delete', $specialty),
                ]),
        ]);
    }

    /**
     * Show the form to create a specialty.
     */
    public function create(): Response
    {
        Gate::authorize('create', Specialty::class);

        return Inertia::render('specialties/Form', ['specialty' => null]);
    }

    /**
     * Store the new specialty.
     */
    public function store(StoreSpecialtyRequest $request): RedirectResponse
    {
        Specialty::create($request->validated());

        return to_route('specialties.index')->with('success', 'Especialidad creada correctamente.');
    }

    /**
     * Show the form to edit the specialty.
     */
    public function edit(Specialty $specialty): Response
    {
        Gate::authorize('update', $specialty);

        return Inertia::render('specialties/Form', [
            'specialty' => $specialty->only(['id', 'name', 'description']),
        ]);
    }

    /**
     * Update the specialty.
     */
    public function update(UpdateSpecialtyRequest $request, Specialty $specialty): RedirectResponse
    {
        $specialty->update($request->validated());

        return to_route('specialties.index')->with('success', 'Especialidad actualizada.');
    }

    /**
     * Remove a specialty without doctors assigned.
     */
    public function destroy(Specialty $specialty): RedirectResponse
    {
        Gate::authorize('delete', $specialty);

        $specialty->delete();

        return to_route('specialties.index')->with('success', 'Especialidad eliminada.');
    }
}
