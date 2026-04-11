<?php

namespace App\Http\Controllers;

use App\Helpers\AuthUserHelper;
use App\Http\Requests\ProjectReportRequest;
use App\Models\Program;
use App\Services\Projects\Reports\ReportModuleFactory;
use Illuminate\Contracts\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ProjectReportController extends Controller
{
    public function __construct(
        private readonly ReportModuleFactory $reportFactory
    ) {
    }

    public function index(ProjectReportRequest $request): View|StreamedResponse
    {
        $reportKey = $request->reportKey();
        $availableModules = $this->reportFactory->availableModules();
        $activeReport = $availableModules[$reportKey] ?? $availableModules[ReportModuleFactory::PROJECTS_BY_STATUS];
        [$viewer, $filters, $scopeSummary] = $this->resolveContext($request);
        $reportData = $this->reportFactory->make($reportKey)->generate($filters);

        if ($request->exportFormat() !== null) {
            return $this->export($reportKey, $reportData);
        }

        return view('reports.module-overview', [
            'reportData' => $reportData,
            'filters' => $filters,
            'programOptions' => $viewer->role === 'research_staff'
                ? Program::query()
                    ->selectRaw('MIN(id) as id, name')
                    ->groupBy('name')
                    ->orderBy('name')
                    ->get()
                : collect(),
            'scopeSummary' => $scopeSummary,
            'segments' => $this->buildSegments($reportData),
            'reportModules' => $availableModules,
            'activeReportKey' => $reportKey,
            'activeReport' => $activeReport,
        ]);
    }

    /**
     * @param  array{categories: array<int, string>, values: array<int, int>, percentages: array<int, float>, total: int}  $reportData
     */
    private function export(string $reportKey, array $reportData): StreamedResponse
    {
        $filename = sprintf(
            'reporte-proyectos-%s-%s.csv',
            now()->format('Ymd-His'),
            str_replace('_', '-', $reportKey)
        );

        return response()->streamDownload(function () use ($reportData): void {
            $handle = fopen('php://output', 'wb');

            if ($handle === false) {
                return;
            }

            fputcsv($handle, ['Categoria', 'Valor', 'Porcentaje']);

            foreach ($reportData['categories'] as $index => $category) {
                fputcsv($handle, [
                    $category,
                    $reportData['values'][$index] ?? 0,
                    $reportData['percentages'][$index] ?? 0,
                ]);
            }

            fputcsv($handle, ['Total', $reportData['total'], 100]);
            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    /**
     * @return array{0: \App\Models\User, 1: array{from: ?string, to: ?string, program_id: ?int, search: ?string, chart_type: ?string}, 2: string}
     */
    private function resolveContext(ProjectReportRequest $request): array
    {
        $viewer = AuthUserHelper::fullUser();

        if (! $viewer || ! in_array($viewer->role, ['research_staff', 'committee_leader'], true)) {
            abort(403, 'No tienes permiso para acceder a este modulo.');
        }

        $filters = $request->reportFilters();
        $scopeSummary = 'Cobertura: todos los proyectos registrados.';

        if ($viewer->role === 'committee_leader') {
            $programId = $viewer->professor?->cityProgram?->program_id;

            if (! $programId) {
                abort(403, 'No fue posible determinar el programa del lider de comite.');
            }

            $filters['program_id'] = $programId;
            $programName = $viewer->professor?->cityProgram?->program?->name ?? 'Programa asignado';
            $scopeSummary = "Cobertura: proyectos asociados al programa {$programName}.";
        } elseif ($filters['program_id']) {
            $programName = Program::query()->whereKey($filters['program_id'])->value('name') ?? 'Programa filtrado';
            $scopeSummary = "Cobertura: proyectos asociados al programa {$programName}.";
        }

        return [$viewer, $filters, $scopeSummary];
    }

    /**
     * @param  array{categories: array<int, string>, values: array<int, int>, percentages: array<int, float>, total: int}  $reportData
     * @return array<int, array{label: string, value: int, percentage: float, color: string}>
     */
    private function buildSegments(array $reportData): array
    {
        $palette = [
            '#0f766e',
            '#1d4ed8',
            '#b45309',
            '#be123c',
            '#7c3aed',
            '#0891b2',
            '#4d7c0f',
            '#c2410c',
        ];

        $segments = [];

        foreach ($reportData['categories'] as $index => $category) {
            $segments[] = [
                'label' => $category,
                'value' => $reportData['values'][$index] ?? 0,
                'percentage' => $reportData['percentages'][$index] ?? 0.0,
                'color' => $palette[$index % count($palette)],
            ];
        }

        return $segments;
    }
}