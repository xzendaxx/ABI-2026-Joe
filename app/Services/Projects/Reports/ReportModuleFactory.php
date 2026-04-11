<?php

namespace App\Services\Projects\Reports;

use InvalidArgumentException;

require_once dirname(__FILE__) . '/AbstractReportGenerator.php';
require_once dirname(__FILE__) . '/AbstractProjectDistributionReport.php';
require_once dirname(__FILE__) . '/ProjectStatusReportModule.php';
require_once dirname(__FILE__) . '/ProjectThematicAreaReportModule.php';
require_once dirname(__FILE__) . '/ProjectInvestigationLineReportModule.php';

/**
 * Resolves report modules by key so controllers or commands can consume them
 * without depending on concrete implementations.
 */
class ReportModuleFactory
{
    public const PROJECTS_BY_STATUS = 'projects_by_status';
    public const PROJECTS_BY_THEMATIC_AREA = 'projects_by_thematic_area';
    public const PROJECTS_BY_INVESTIGATION_LINE = 'projects_by_investigation_line';

    public function make(string $reportKey): AbstractReportGenerator
    {
        return match ($reportKey) {
            self::PROJECTS_BY_STATUS => new ProjectStatusReportModule(),
            self::PROJECTS_BY_THEMATIC_AREA => new ProjectThematicAreaReportModule(),
            self::PROJECTS_BY_INVESTIGATION_LINE => new ProjectInvestigationLineReportModule(),
            default => throw new InvalidArgumentException("Unknown report module: {$reportKey}"),
        };
    }

    /**
     * @return array<string, array{label: string, description: string}>
     */
    public function availableModules(): array
    {
        return [
            self::PROJECTS_BY_STATUS => [
                'label' => 'Proyectos por estado',
                'description' => 'Compara la distribucion de proyectos segun su estado actual.',
            ],
            self::PROJECTS_BY_THEMATIC_AREA => [
                'label' => 'Proyectos por area tematica',
                'description' => 'Permite ver que areas tematicas concentran mas proyectos.',
            ],
            self::PROJECTS_BY_INVESTIGATION_LINE => [
                'label' => 'Proyectos por linea de investigacion',
                'description' => 'Compara la participacion de cada linea de investigacion dentro del conjunto filtrado.',
            ],
        ];
    }

    /**
     * @return array<int, string>
     */
    public static function keys(): array
    {
        return [
            self::PROJECTS_BY_STATUS,
            self::PROJECTS_BY_THEMATIC_AREA,
            self::PROJECTS_BY_INVESTIGATION_LINE,
        ];
    }
}