<?php

namespace App\Services\Projects\Reports;

use Illuminate\Support\Collection;

/**
 * Base report module for distribution-type visualizations.
 *
 * Child classes only need to provide the aggregated rows from database
 * and this template method will normalize categories, values and percentages.
 */
abstract class AbstractReportGenerator
{
    /**
     * Generate normalized report data for chart/table rendering.
     *
     * @param array<string, mixed> $filters
     * @return array{categories: array<int, string>, values: array<int, int>, percentages: array<int, float>, total: int}
     */
    public function generate(array $filters = []): array
    {
        $distribution = $this->fetchDistribution($filters);

        $categories = $distribution->pluck('category')->map(static fn ($item) => (string) $item)->values();
        $values = $distribution->pluck('total')->map(static fn ($item) => (int) $item)->values();
        $total = $values->sum();

        $percentages = $values->map(static function (int $value) use ($total): float {
            if ($total === 0) {
                return 0.0;
            }

            return round(($value / $total) * 100, 2);
        })->values();

        return [
            'categories' => $categories->all(),
            'values' => $values->all(),
            'percentages' => $percentages->all(),
            'total' => $total,
        ];
    }

    /**
     * Return rows with shape: [{ category: string, total: int }, ...]
     *
     * @param array<string, mixed> $filters
     * @return Collection<int, object>
     */
    abstract protected function fetchDistribution(array $filters = []): Collection;
}