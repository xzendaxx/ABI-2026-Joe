<?php

namespace App\Http\Requests;

use App\Services\Projects\Reports\ReportModuleFactory;
use Illuminate\Foundation\Http\FormRequest;

class ProjectReportRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'report_key' => $this->normalizeString($this->input('report_key')),
            'from' => $this->normalizeDate($this->input('from')),
            'to' => $this->normalizeDate($this->input('to')),
            'program_id' => $this->normalizeInteger($this->input('program_id')),
            'search' => $this->normalizeString($this->input('search')),
            'export' => $this->normalizeString($this->input('export')),
        ]);
    }

    public function rules(): array
    {
        return [
            'report_key' => ['nullable', 'in:' . implode(',', ReportModuleFactory::keys())],
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date', 'after_or_equal:from'],
            'program_id' => ['nullable', 'integer', 'exists:programs,id'],
            'search' => ['nullable', 'string', 'max:120'],
            'export' => ['nullable', 'in:csv,pdf'],
        ];
    }

    /**
     * @return array{from: ?string, to: ?string, program_id: ?int, search: ?string}
     */
    public function reportFilters(): array
    {
        return [
            'from' => $this->validated('from'),
            'to' => $this->validated('to'),
            'program_id' => $this->validated('program_id'),
            'search' => $this->validated('search'),
        ];
    }

    public function reportKey(): string
    {
        return $this->validated('report_key') ?? ReportModuleFactory::PROJECTS_BY_STATUS;
    }

    public function exportFormat(): ?string
    {
        return $this->validated('export');
    }

    private function normalizeDate(mixed $value): ?string
    {
        if (! is_string($value)) {
            return null;
        }

        $value = trim($value);

        return $value === '' ? null : $value;
    }

    private function normalizeInteger(mixed $value): ?int
    {
        if ($value === null || $value === '') {
            return null;
        }

        return is_numeric($value) ? (int) $value : null;
    }

    private function normalizeString(mixed $value): ?string
    {
        if (! is_string($value)) {
            return null;
        }

        $value = trim($value);

        return $value === '' ? null : $value;
    }
}