<?php

namespace App\Http\Controllers;

use App\Actions\Reports\BuildReport;
use App\Enums\ReportGroup;
use App\Enums\ReportType;
use App\Exports\ReportExport;
use App\Exports\TableExporter;
use App\Http\Requests\ExportReportRequest;
use App\Http\Requests\ReportRequest;
use App\Models\Doctor;
use App\Models\Specialty;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;

class ReportController extends Controller
{
    /**
     * Show the report filtered by period, doctor and specialty.
     */
    public function index(ReportRequest $request, BuildReport $report): Response
    {
        return Inertia::render('reports/Index', [
            'report' => $this->build($request, $report),
            'filters' => $request->filters(),
            'restricted' => $request->isDoctor(),
            'types' => array_values(array_filter(
                ReportType::options(),
                fn (array $option): bool => ! $request->isDoctor() || $option['value'] !== ReportType::Income->value,
            )),
            'groups' => ReportGroup::options(),
            'doctors' => $request->isDoctor() ? [] : Doctor::query()
                ->with('user:id,name')
                ->get()
                ->sortBy('user.name')
                ->map(fn (Doctor $doctor): array => ['value' => $doctor->id, 'label' => $doctor->user->name])
                ->values(),
            'specialties' => $request->isDoctor() ? [] : Specialty::query()
                ->orderBy('name')
                ->get(['id', 'name'])
                ->map(fn (Specialty $specialty): array => ['value' => $specialty->id, 'label' => $specialty->name]),
        ]);
    }

    /**
     * Export the report to Excel or PDF.
     */
    public function export(ExportReportRequest $request, BuildReport $report, TableExporter $exporter): SymfonyResponse
    {
        $filters = $request->filters();

        return $exporter->download(
            new ReportExport($this->build($request, $report), $this->summary($filters)),
            $request->exportFormat(),
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function build(ReportRequest $request, BuildReport $report): array
    {
        [$from, $to] = $request->period();
        $filters = $request->filters();

        return $report->handle(
            $request->reportType(),
            $request->reportGroup(),
            $from,
            $to,
            $filters['doctor_id'],
            $filters['specialty_id'],
        );
    }

    /**
     * @param  array{from: string, to: string, doctor_id: int|null, specialty_id: int|null}  $filters
     * @return list<string>
     */
    private function summary(array $filters): array
    {
        return array_values(array_filter([
            'Período: '.now()->parse($filters['from'])->format('d/m/Y').' al '.now()->parse($filters['to'])->format('d/m/Y'),
            $filters['doctor_id'] ? 'Médico: '.Doctor::query()->with('user')->find($filters['doctor_id'])?->user->name : null,
            $filters['specialty_id'] ? 'Especialidad: '.Specialty::query()->find($filters['specialty_id'])?->name : null,
        ]));
    }
}
