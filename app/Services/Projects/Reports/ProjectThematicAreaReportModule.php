<?php

namespace App\Services\Projects\Reports;

use App\Models\Project;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class ProjectThematicAreaReportModule extends AbstractProjectDistributionReport
{
    /**
     * @param  array<string, mixed>  $filters
     * @return Collection<int, object>
     */
    protected function fetchDistribution(array $filters = []): Collection
    {
        $query = Project::query()
            ->leftJoin('thematic_areas', 'thematic_areas.id', '=', 'projects.thematic_area_id')
            ->selectRaw("COALESCE(thematic_areas.name, 'Sin area tematica') as category")
            ->selectRaw('COUNT(projects.id) as total')
            ->groupBy('category')
            ->orderByDesc('total');

        $this->applyProjectFilters($query, $filters);

        return $query->get();
    }

    protected function applyCategorySearch(Builder $query, string $term): void
    {
        $query->orWhere('thematic_areas.name', 'like', $term);
    }
}