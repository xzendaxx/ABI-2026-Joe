<?php

namespace App\Services\Projects\Reports;

use App\Models\Project;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

/**
 * Report module that returns project distribution by status from database.
 */
class ProjectStatusReportModule extends AbstractProjectDistributionReport
{
    /**
     * @param array<string, mixed> $filters
     * @return Collection<int, object>
     */
    protected function fetchDistribution(array $filters = []): Collection
    {
        $query = Project::query()
            ->leftJoin('project_statuses', 'project_statuses.id', '=', 'projects.project_status_id')
            ->selectRaw("COALESCE(project_statuses.name, 'Sin estado') as category")
            ->selectRaw('COUNT(projects.id) as total')
            ->groupBy('category')
            ->orderByDesc('total');

        $this->applyProjectFilters($query, $filters);

        return $query->get();
    }

    protected function applyCategorySearch(Builder $query, string $term): void
    {
        $query->orWhere('project_statuses.name', 'like', $term);
    }
}