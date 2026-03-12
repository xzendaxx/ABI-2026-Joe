<?php

namespace App\Services\Reports;

use InvalidArgumentException;

/**
 * Resolves report modules by key so controllers or commands can consume them
 * without depending on concrete implementations.
 */
class ReportModuleFactory
{
    public const PROJECTS_BY_STATUS = 'projects_by_status';

    public function make(string $reportKey): AbstractReportGenerator
    {
        return match ($reportKey) {
            self::PROJECTS_BY_STATUS => new ProjectStatusReportModule(),
            default => throw new InvalidArgumentException("Unknown report module: {$reportKey}"),
        };
    }
}