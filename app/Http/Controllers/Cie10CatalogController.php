<?php

namespace App\Http\Controllers;

use App\Actions\Diagnoses\ImportCie10Catalog;
use App\Enums\AuditAction;
use App\Http\Requests\ImportCie10Request;
use App\Http\Requests\TableQueryRequest;
use App\Models\AuditLog;
use App\Models\Diagnosis;
use App\Models\DiagnosisImport;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Screen to load the official CIE-10 table and check the catalog.
 */
class Cie10CatalogController extends Controller
{
    private const HISTORY_SIZE = 10;

    /**
     * Show the catalog status, the import form, the latest result and the history.
     */
    public function index(TableQueryRequest $request): Response
    {
        Gate::authorize('manage-cie10');

        $term = $request->searchTerm();
        $status = $request->string('status')->toString();

        $imports = DiagnosisImport::query()
            ->with('user:id,name')
            ->latest('id')
            ->limit(self::HISTORY_SIZE)
            ->get();

        $latest = $imports->firstWhere('id', $request->session()->get('cie10_import_id'));

        return Inertia::render('cie10/Index', [
            'stats' => [
                'active' => Diagnosis::query()->active()->count(),
                'inactive' => Diagnosis::query()->where('is_active', false)->count(),
                'last_load_at' => $imports->firstWhere('dry_run', false)?->created_at?->toIso8601String(),
            ],
            'latest' => $latest ? [
                ...$this->present($latest),
                'rejection_sample' => $latest->rejectionSample(),
            ] : null,
            'imports' => $imports->map(fn (DiagnosisImport $import): array => $this->present($import)),
            'codes' => Diagnosis::query()
                ->when($term, fn (Builder $query) => $query->search($term))
                ->when($status === 'active', fn (Builder $query) => $query->where('is_active', true))
                ->when($status === 'inactive', fn (Builder $query) => $query->where('is_active', false))
                ->orderBy('code')
                ->paginate(self::TABLE_PAGE_SIZE)
                ->withQueryString()
                ->through(fn (Diagnosis $diagnosis): array => $diagnosis->only(['id', 'code', 'description', 'category', 'chapter', 'is_active'])),
            'filters' => $request->filters(),
            'sourceUrl' => 'https://web.sispro.gov.co/WebPublico/Consultas/ConsultarDetalleReferenciaBasica.aspx?Code=CIE10',
            'maxFileMb' => (int) (ImportCie10Request::MAX_FILE_KB / 1024),
        ]);
    }

    /**
     * Run the ETL with the uploaded file. The file is not kept once processed.
     */
    public function store(ImportCie10Request $request, ImportCie10Catalog $etl): RedirectResponse
    {
        $file = $request->file('file');
        $stored = $file->storeAs('imports/cie10-uploads', now()->format('Ymd-His').'-'.uniqid().'.'.strtolower($file->getClientOriginalExtension()), 'local');
        $dryRun = $request->boolean('dry_run');

        try {
            $report = $etl->run(
                Storage::disk('local')->path($stored),
                $dryRun,
                $request->boolean('deactivate_missing'),
                fileName: $file->getClientOriginalName(),
                userId: $request->user()->id,
            );
        } finally {
            Storage::disk('local')->delete($stored);
        }

        if (! $dryRun) {
            AuditLog::record(AuditAction::Updated, null, "Cargó el catálogo CIE-10 desde «{$file->getClientOriginalName()}»: {$report['inserted']} nuevos, {$report['updated']} actualizados, {$report['deactivated']} deshabilitados");
        }

        return to_route('cie10.index')
            ->with('cie10_import_id', $report['import_id'])
            ->with('success', $dryRun
                ? 'Simulación terminada: revisa el resultado; no se guardó ningún cambio.'
                : 'Catálogo CIE-10 actualizado.');
    }

    /**
     * Download the rejected rows of a run.
     */
    public function rejections(DiagnosisImport $import): StreamedResponse
    {
        Gate::authorize('manage-cie10');

        abort_unless($import->hasRejectionReport(), 404);

        /** @var FilesystemAdapter $disk */
        $disk = Storage::disk('local');

        return $disk->download(
            (string) $import->report_path,
            'cie10-rechazos-'.$import->created_at?->format('Ymd-His').'.csv',
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function present(DiagnosisImport $import): array
    {
        return [
            'id' => $import->id,
            'file_name' => $import->file_name,
            'dry_run' => $import->dry_run,
            'deactivate_missing' => $import->deactivate_missing,
            'user' => $import->user?->name,
            'created_at' => $import->created_at?->toIso8601String(),
            'counts' => $import->only(['read', 'valid', 'rejected', 'inserted', 'updated', 'unchanged', 'deactivated']),
            'has_report' => $import->hasRejectionReport(),
        ];
    }
}
