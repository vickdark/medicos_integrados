<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreInsurerRequest;
use App\Http\Requests\TableQueryRequest;
use App\Http\Requests\UpdateInsurerRequest;
use App\Models\Insurer;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class InsurerController extends Controller
{
    /**
     * Display the EPS and insurers catalog.
     */
    public function index(TableQueryRequest $request): Response
    {
        Gate::authorize('viewAny', Insurer::class);

        $term = $request->searchTerm();

        return Inertia::render('insurers/Index', [
            'insurers' => Insurer::query()
                ->withCount('patients')
                ->when($term, fn (Builder $query) => $query->where(function (Builder $query) use ($term): void {
                    $query->where('name', 'like', "%{$term}%")
                        ->orWhere('code', 'like', "%{$term}%");
                }))
                ->orderBy('name')
                ->paginate(self::TABLE_PAGE_SIZE)
                ->withQueryString()
                ->through(fn (Insurer $insurer): array => [
                    'id' => $insurer->id,
                    'name' => $insurer->name,
                    'code' => $insurer->code,
                    'patients_count' => $insurer->patients_count,
                    'can_delete' => $insurer->patients_count === 0,
                ]),
            'filters' => $request->filters(),
        ]);
    }

    /**
     * Show the form to create an insurer.
     */
    public function create(): Response
    {
        Gate::authorize('create', Insurer::class);

        return Inertia::render('insurers/Form', ['insurer' => null]);
    }

    /**
     * Store the new insurer.
     */
    public function store(StoreInsurerRequest $request): RedirectResponse
    {
        Insurer::create($request->validated());

        return to_route('insurers.index')->with('success', 'Aseguradora creada correctamente.');
    }

    /**
     * Show the form to edit the insurer.
     */
    public function edit(Insurer $insurer): Response
    {
        Gate::authorize('update', $insurer);

        return Inertia::render('insurers/Form', [
            'insurer' => $insurer->only(['id', 'name', 'code']),
        ]);
    }

    /**
     * Update the insurer.
     */
    public function update(UpdateInsurerRequest $request, Insurer $insurer): RedirectResponse
    {
        $insurer->update($request->validated());

        return to_route('insurers.index')->with('success', 'Aseguradora actualizada.');
    }

    /**
     * Remove an insurer no patient is assigned to.
     */
    public function destroy(Insurer $insurer): RedirectResponse
    {
        Gate::authorize('delete', $insurer);

        $insurer->delete();

        return to_route('insurers.index')->with('success', 'Aseguradora eliminada.');
    }
}
