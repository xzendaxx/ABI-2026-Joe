<?php

namespace Tests\Unit\Services\Projects\Reports;

use App\Services\Projects\Reports\ProjectStatusReportModule;
use App\Services\Projects\Reports\ReportModuleFactory;
use InvalidArgumentException;
use Tests\TestCase;

class ReportModuleFactoryTest extends TestCase
{
    public function test_it_resolves_the_project_status_module(): void
    {
        $factory = new ReportModuleFactory();

        $module = $factory->make(ReportModuleFactory::PROJECTS_BY_STATUS);

        $this->assertInstanceOf(ProjectStatusReportModule::class, $module);
    }

    public function test_it_throws_for_unknown_modules(): void
    {
        $factory = new ReportModuleFactory();

        $this->expectException(InvalidArgumentException::class);

        $factory->make('unknown_report');
    }
}