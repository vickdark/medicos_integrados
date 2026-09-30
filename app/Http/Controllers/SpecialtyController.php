<?php

namespace App\Http\Controllers;

use App\Exports\SpecialtiesExport;
use App\Exports\TableExporter;
use App\Http\Requests\ExportTableRequest;
use App\Http\Requests\StoreSpecialtyRequest;
use App\Http\Requests\TableQueryRequest;
use App\Http\Requests\UpdateSpecialtyRequest;
use App\Models\Specialty;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;

class SpecialtyController extends Controller
{
    /**
     * Display the specialties catalog.
     */
    public function index(TableQueryRequest $request): Response
    {
        Gate::authorize('viewAny', Specialty::class);

        return Inertia::render('specialties/Index', [
            'specialties' => $this->filteredQuery($request)
                ->paginate(15)
                ->withQueryString()
                ->through(fn (Specialty $specialty): array => [
                    'id' => $specialty->id,
                    'name' => $specialty->name,
                    'description' => $specialty->description,
                    'doctors_count' => $specialty->doctors_count,
                    'can_delete' => $specialty->doctors_count === 0,
                ]),
            'filters' => $request->filters(),
        ]);
    }

    /**
     * Export the filtered specialties to Excel or PDF.
     */
    public function export(ExportTableRequest $request, TableExporter $exporter): SymfonyResponse
    {
        Gate::authorize('viewAny', Specialty::class);

        return $exporter->download(
            new SpecialtiesExport($this->filteredQuery($request), $request->filters()),
            $request->exportFormat(),
        );
    }

    /**
     * Specialties with their doctor count, narrowed by the table search.
     *
     * @return Builder<Specialty>
     */
    private function filteredQuery(TableQueryRequest $request): Builder
    {
        $term = $request->searchTerm();

        return Specialty::query()
            ->withCount('doctors')
            ->when($term, fn (Builder $query) => $query->where(function (Builder $query) use ($term): void {
                $query->where('name', 'like', "%{$term}%")
                    ->orWhere('description', 'like', "%{$term}%");
            }))
            ->orderBy('name');
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
