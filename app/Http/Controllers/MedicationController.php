<?php

namespace App\Http\Controllers;

use App\Exports\MedicationsExport;
use App\Exports\TableExporter;
use App\Http\Requests\ExportTableRequest;
use App\Http\Requests\StoreMedicationRequest;
use App\Http\Requests\TableQueryRequest;
use App\Http\Requests\UpdateMedicationRequest;
use App\Models\Medication;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;

class MedicationController extends Controller
{
    /**
     * Display the medications catalog.
     */
    public function index(TableQueryRequest $request): Response
    {
        Gate::authorize('viewAny', Medication::class);

        return Inertia::render('medications/Index', [
            'medications' => $this->filteredQuery($request)
                ->paginate(self::TABLE_PAGE_SIZE)
                ->withQueryString()
                ->through(fn (Medication $medication): array => $medication->only([
                    'id', 'name', 'presentation', 'concentration', 'description',
                ])),
            'filters' => $request->filters(),
        ]);
    }

    /**
     * Export the filtered medications to Excel or PDF.
     */
    public function export(ExportTableRequest $request, TableExporter $exporter): SymfonyResponse
    {
        Gate::authorize('viewAny', Medication::class);

        return $exporter->download(
            new MedicationsExport($this->filteredQuery($request), $request->filters()),
            $request->exportFormat(),
        );
    }

    /**
     * Medications narrowed by the table search.
     *
     * @return Builder<Medication>
     */
    private function filteredQuery(TableQueryRequest $request): Builder
    {
        $term = $request->searchTerm();

        return Medication::query()
            ->when($term, fn (Builder $query) => $query->where(function (Builder $query) use ($term): void {
                $query->where('name', 'like', "%{$term}%")
                    ->orWhere('presentation', 'like', "%{$term}%")
                    ->orWhere('concentration', 'like', "%{$term}%");
            }))
            ->orderBy('name')
            ->orderBy('concentration');
    }

    /**
     * Show the form to create a medication.
     */
    public function create(): Response
    {
        Gate::authorize('create', Medication::class);

        return Inertia::render('medications/Form', ['medication' => null]);
    }

    /**
     * Store the new medication. When it comes from the consultation form the
     * doctor stays on that page.
     */
    public function store(StoreMedicationRequest $request): RedirectResponse
    {
        Medication::create($request->safe()->except('inline'));

        if ($request->boolean('inline')) {
            return back()->with('success', 'Medicamento agregado al catálogo.');
        }

        return to_route('medications.index')->with('success', 'Medicamento creado correctamente.');
    }

    /**
     * Show the form to edit the medication.
     */
    public function edit(Medication $medication): Response
    {
        Gate::authorize('update', $medication);

        return Inertia::render('medications/Form', [
            'medication' => $medication->only(['id', 'name', 'presentation', 'concentration', 'description']),
        ]);
    }

    /**
     * Update the medication.
     */
    public function update(UpdateMedicationRequest $request, Medication $medication): RedirectResponse
    {
        $medication->update($request->validated());

        return to_route('medications.index')->with('success', 'Medicamento actualizado.');
    }

    /**
     * Remove the medication from the catalog.
     */
    public function destroy(Medication $medication): RedirectResponse
    {
        Gate::authorize('delete', $medication);

        $medication->delete();

        return to_route('medications.index')->with('success', 'Medicamento eliminado.');
    }
}
