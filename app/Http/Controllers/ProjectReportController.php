<?php

namespace App\Http\Controllers;

use App\Helpers\AuthUserHelper;
use App\Http\Requests\ProjectReportRequest;
use App\Models\Program;
use App\Services\Projects\Reports\ReportModuleFactory;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ProjectReportController extends Controller
{
    public function __construct(
        private readonly ReportModuleFactory $reportFactory
    ) {
    }

    public function index(ProjectReportRequest $request): View|Response|StreamedResponse
    {
        $reportKey = $request->reportKey();
        $availableModules = $this->reportFactory->availableModules();
        $activeReport = $availableModules[$reportKey] ?? $availableModules[ReportModuleFactory::PROJECTS_BY_STATUS];
        [$viewer, $filters, $scopeSummary] = $this->resolveContext($request);
        $reportData = $this->reportFactory->make($reportKey)->generate($filters);
        $segments = $this->buildSegments($reportData);
        $exportFormat = $request->exportFormat();

        if ($exportFormat !== null) {
            return $this->export($exportFormat, $reportKey, $activeReport, $reportData, $filters, $scopeSummary, $viewer, $segments);
        }

        return view('reports.module-overview', [
            'reportData' => $reportData,
            'filters' => $filters,
            'programOptions' => $viewer->role === 'research_staff'
                ? Program::query()->orderBy('name')->get(['id', 'name'])
                : collect(),
            'scopeSummary' => $scopeSummary,
            'segments' => $segments,
            'reportModules' => $availableModules,
            'activeReportKey' => $reportKey,
            'activeReport' => $activeReport,
            'isExportMode' => false,
        ]);
    }

    /**
     * @param  array{label: string, description: string}  $activeReport
     * @param  array{categories: array<int, string>, values: array<int, int>, percentages: array<int, float>, total: int}  $reportData
     * @param  array{from: ?string, to: ?string, program_id: ?int, search: ?string}  $filters
     * @param  array<int, array{label: string, value: int, percentage: float, color: string}>  $segments
     */
    private function export(
        string $format,
        string $reportKey,
        array $activeReport,
        array $reportData,
        array $filters,
        string $scopeSummary,
        object $viewer,
        array $segments
    ): Response|StreamedResponse {
        $filename = $this->buildFilename($format, $reportKey);

        if ($format === 'csv') {
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

        $html = view('reports.module-overview', [
            'reportData' => $reportData,
            'filters' => $filters,
            'programOptions' => collect(),
            'scopeSummary' => $scopeSummary,
            'segments' => $segments,
            'reportModules' => $this->reportFactory->availableModules(),
            'activeReportKey' => $reportKey,
            'activeReport' => $activeReport,
            'isExportMode' => true,
            'viewer' => $viewer,
        ])->render();

        $pdf = app('dompdf.wrapper');
        $pdf->loadHTML($html);

        return $pdf->download($filename);
    }

    /**
     * @return array{0: \App\Models\User, 1: array{from: ?string, to: ?string, program_id: ?int, search: ?string}, 2: string}
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

    private function buildFilename(string $format, string $reportKey): string
    {
        return sprintf('reporte-proyectos-%s-%s.%s', now()->format('Ymd-His'), str_replace('_', '-', $reportKey), $format);
    }
}
