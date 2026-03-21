<?php

namespace App\Services\Projects\Reports;

use Illuminate\Database\Eloquent\Builder;

abstract class AbstractProjectDistributionReport extends AbstractReportGenerator
{
    /**
     * Apply the common report filters shared by every project distribution report.
     *
     * @param  array<string, mixed>  $filters
     */
    protected function applyProjectFilters(Builder $query, array $filters = []): void
    {
        if (! empty($filters['program_id'])) {
            $programId = (int) $filters['program_id'];

            $query->where(function (Builder $builder) use ($programId): void {
                $builder
                    ->whereHas('professors.cityProgram', static function (Builder $relation) use ($programId): void {
                        $relation->where('program_id', $programId);
                    })
                    ->orWhereHas('students.cityProgram', static function (Builder $relation) use ($programId): void {
                        $relation->where('program_id', $programId);
                    });
            });
        }

        if (! empty($filters['from'])) {
            $query->whereDate('projects.created_at', '>=', $filters['from']);
        }

        if (! empty($filters['to'])) {
            $query->whereDate('projects.created_at', '<=', $filters['to']);
        }

        if (! empty($filters['search'])) {
            $term = '%' . trim((string) $filters['search']) . '%';

            $query->where(function (Builder $builder) use ($term): void {
                $builder->where('projects.title', 'like', $term);
                $this->applyCategorySearch($builder, $term);
            });
        }
    }

    abstract protected function applyCategorySearch(Builder $query, string $term): void;
}
