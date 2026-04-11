@extends('tablar::page')

@section('title', 'Modulo de Reportes - Vista de prueba')

@section('content')
    @php
        $filters = $filters ?? ['from' => null, 'to' => null, 'program_id' => null, 'search' => null, 'chart_type' => 'pastel'];
        $reportData = $reportData ?? [
            'categories' => [],
            'values' => [],
            'percentages' => [],
            'total' => 0,
        ];
        $segments = $segments ?? [];
        $programOptions = $programOptions ?? collect();
        $reportModules = $reportModules ?? [];
        $activeReportKey = $activeReportKey ?? \App\Services\Projects\Reports\ReportModuleFactory::PROJECTS_BY_STATUS;
        $activeReport = $activeReport ?? ($reportModules[$activeReportKey] ?? [
            'label' => 'Proyectos por estado',
            'description' => 'Compara la distribucion de proyectos segun su estado actual.',
        ]);
        $chartType = $filters['chart_type'] ?? 'pastel';
        $topSegment = collect($segments)->sortByDesc('value')->first();
        $currentPercent = 0;
        $chartStops = [];

        foreach ($segments as $segment) {
            $start = $currentPercent;
            $currentPercent = min(100, $currentPercent + $segment['percentage']);
            $chartStops[] = "{$segment['color']} {$start}% {$currentPercent}%";
        }

        $chartBackground = $chartStops !== []
            ? 'conic-gradient(' . implode(', ', $chartStops) . ')'
            : 'linear-gradient(135deg, #d1d5db, #9ca3af)';

        $baseQuery = array_filter([
            'report_key' => $activeReportKey,
            'chart_type' => $chartType,
            'search' => $filters['search'],
            'from' => $filters['from'],
            'to' => $filters['to'],
            'program_id' => $filters['program_id'],
        ], static fn ($value) => $value !== null && $value !== '');
    @endphp

    <style>
        .reports-shell {
            display: grid;
            gap: 1.5rem;
        }

        .reports-grid {
            display: grid;
            gap: 1rem;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
        }

        .report-stat {
            border: 1px solid rgba(15, 23, 42, 0.08);
            border-radius: 16px;
            padding: 1rem 1.25rem;
            background: linear-gradient(180deg, #ffffff 0%, #f8fafc 100%);
            box-shadow: 0 10px 30px rgba(15, 23, 42, 0.06);
        }

        .report-stat__label {
            font-size: 0.8rem;
            text-transform: uppercase;
            letter-spacing: 0.08em;
            color: #64748b;
        }

        .report-stat__value {
            margin-top: 0.35rem;
            font-size: 1.9rem;
            font-weight: 700;
            color: #0f172a;
        }

        .report-visual {
            display: grid;
            gap: 1.5rem;
            grid-template-columns: minmax(240px, 320px) minmax(0, 1fr);
            align-items: center;
        }

        .report-donut-wrap {
            display: grid;
            place-items: center;
            gap: 1rem;
        }

        .report-donut {
            width: 240px;
            height: 240px;
            border-radius: 50%;
            background: {{ $chartBackground }};
            position: relative;
            box-shadow: inset 0 0 0 1px rgba(15, 23, 42, 0.06);
        }

        .report-donut::after {
            content: '';
            position: absolute;
            inset: 48px;
            border-radius: 50%;
            background: #ffffff;
            box-shadow: inset 0 0 0 1px rgba(15, 23, 42, 0.08);
        }

        .report-donut__center {
            position: absolute;
            inset: 0;
            display: grid;
            place-items: center;
            text-align: center;
            z-index: 1;
            padding: 0 1.5rem;
        }

        .report-donut__center strong {
            display: block;
            font-size: 2rem;
            color: #0f172a;
        }

        .report-donut__center span {
            color: #64748b;
            font-size: 0.9rem;
        }

        .report-legend {
            display: grid;
            gap: 0.75rem;
        }

        .report-legend__item {
            display: grid;
            grid-template-columns: auto minmax(0, 1fr) auto;
            gap: 0.75rem;
            align-items: center;
            padding: 0.85rem 1rem;
            border-radius: 14px;
            background: #f8fafc;
        }

        .report-legend__swatch {
            width: 0.9rem;
            height: 0.9rem;
            border-radius: 999px;
        }

        .report-columns {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(120px, 1fr));
            gap: 1rem;
            align-items: end;
            min-height: 320px;
        }

        .report-columns__item {
            display: grid;
            gap: 0.75rem;
            justify-items: center;
        }

        .report-columns__chart {
            width: 100%;
            min-height: 240px;
            padding: 0.75rem;
            border-radius: 16px;
            background: #f8fafc;
            display: flex;
            align-items: end;
            justify-content: center;
        }

        .report-columns__bar {
            width: min(72px, 100%);
            min-height: 6px;
            border-radius: 14px 14px 0 0;
        }

        .report-columns__value {
            font-weight: 700;
            color: #0f172a;
            text-align: center;
        }

        .report-columns__label {
            color: #475569;
            text-align: center;
            font-size: 0.92rem;
            line-height: 1.3;
            word-break: break-word;
        }

        @media (max-width: 991px) {
            .report-visual {
                grid-template-columns: 1fr;
            }
        }
    </style>

    <div class="page-header d-print-none">
        <div class="container-xl">
            <h2 class="page-title">Modulo de Reportes</h2>
            <p class="text-muted mb-0">
                Vista de prueba unica para consultar diferentes datos del sistema y compararlos desde un mismo buscador.
            </p>
        </div>
    </div>

    <div class="page-body">
        <div class="container-xl">
            <div class="reports-shell">
                @if ($errors->any())
                    <div class="alert alert-danger mb-0">
                        {{ $errors->first() }}
                    </div>
                @endif

                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">Parametros de prueba</h3>
                    </div>
                    <div class="card-body">
                        <form method="GET" class="row g-3 align-items-end">
                            <div class="col-md-3">
                                <label for="report_key" class="form-label">Que deseas comparar</label>
                                <select id="report_key" name="report_key" class="form-select">
                                    @foreach ($reportModules as $reportKey => $module)
                                        <option value="{{ $reportKey }}" @selected($activeReportKey === $reportKey)>
                                            {{ $module['label'] }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label for="search" class="form-label">Buscar dato</label>
                                <input
                                    type="text"
                                    id="search"
                                    name="search"
                                    class="form-control"
                                    placeholder="Estado, area, linea o titulo"
                                    value="{{ $filters['search'] }}"
                                >
                            </div>
                            <div class="col-md-2">
                                <label for="from" class="form-label">Desde</label>
                                <input
                                    type="date"
                                    id="from"
                                    name="from"
                                    class="form-control"
                                    value="{{ $filters['from'] }}"
                                >
                            </div>
                            <div class="col-md-2">
                                <label for="to" class="form-label">Hasta</label>
                                <input
                                    type="date"
                                    id="to"
                                    name="to"
                                    class="form-control"
                                    value="{{ $filters['to'] }}"
                                >
                            </div>
                            @if ($programOptions->isNotEmpty())
                                <div class="col-md-2">
                                    <label for="program_id" class="form-label">Programa</label>
                                    <select id="program_id" name="program_id" class="form-select">
                                        <option value="">Todos los programas</option>
                                        @foreach ($programOptions as $program)
                                            <option value="{{ $program->id }}" @selected((int) $filters['program_id'] === (int) $program->id)>
                                                {{ $program->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                            @endif
                            <div class="col-md-{{ $programOptions->isNotEmpty() ? '12' : '2' }}">
                                <div class="d-flex flex-wrap gap-2">
                                    <button type="submit" class="btn btn-primary">Generar reporte</button>
                                    <a href="{{ route('reports.module-overview') }}" class="btn btn-outline-secondary">Limpiar</a>
                                    <a
                                        href="{{ route('reports.module-overview', array_merge($baseQuery, ['export' => 'csv'])) }}"
                                        class="btn btn-outline-primary"
                                    >
                                        Exportar CSV
                                    </a>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>

                <div class="card">
                    <div class="card-header">
                        <div>
                            <h3 class="card-title mb-1">{{ $activeReport['label'] }}</h3>
                            <div class="text-muted mb-1">{{ $activeReport['description'] }}</div>
                            <div class="text-muted">{{ $scopeSummary }}</div>
                        </div>
                    </div>
                    <div class="card-body reports-shell">
                        @if (! empty($filters['search']))
                            <div class="alert alert-secondary mb-0">
                                Busqueda aplicada: <strong>{{ $filters['search'] }}</strong>
                            </div>
                        @endif

                        <div class="reports-grid">
                            <div class="report-stat">
                                <div class="report-stat__label">Total de registros</div>
                                <div class="report-stat__value">{{ $reportData['total'] }}</div>
                            </div>
                            <div class="report-stat">
                                <div class="report-stat__label">Categorias detectadas</div>
                                <div class="report-stat__value">{{ count($reportData['categories']) }}</div>
                            </div>
                            <div class="report-stat">
                                <div class="report-stat__label">Categoria principal</div>
                                <div class="report-stat__value" style="font-size: 1.2rem;">
                                    {{ $topSegment['label'] ?? 'Sin datos' }}
                                </div>
                            </div>
                        </div>

                        <div class="alert alert-info mb-0">
                            Este reporte compara <strong>{{ mb_strtolower($activeReport['label']) }}</strong> y mantiene la salida estandar del generador abstracto:
                            <code>categories[]</code>, <code>values[]</code>, <code>percentages[]</code> y <code>total</code>.
                        </div>

                        <div class="d-flex flex-wrap gap-2">
                            <a
                                href="{{ route('reports.module-overview', array_merge($baseQuery, ['chart_type' => 'pastel'])) }}"
                                class="btn {{ $chartType === 'pastel' ? 'btn-primary' : 'btn-outline-primary' }}"
                            >
                                Diagrama pastel
                            </a>
                            <a
                                href="{{ route('reports.module-overview', array_merge($baseQuery, ['chart_type' => 'columnas'])) }}"
                                class="btn {{ $chartType === 'columnas' ? 'btn-primary' : 'btn-outline-primary' }}"
                            >
                                Diagrama de columnas
                            </a>
                            <a
                                href="{{ route('reports.module-overview', array_merge($baseQuery, ['chart_type' => 'comparativo'])) }}"
                                class="btn {{ $chartType === 'comparativo' ? 'btn-primary' : 'btn-outline-primary' }}"
                            >
                                Cuadro comparativo
                            </a>
                        </div>

                        @if ($chartType === 'pastel')
                            <div class="report-visual">
                                <div class="report-donut-wrap">
                                    <div class="report-donut">
                                        <div class="report-donut__center">
                                            <div>
                                                <strong>{{ $reportData['total'] }}</strong>
                                                <span>Total de proyectos</span>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="text-muted text-center">
                                        Visualizacion proporcional construida a partir de categorias y valores.
                                    </div>
                                </div>

                                <div class="report-legend">
                                    @forelse ($segments as $segment)
                                        <div class="report-legend__item">
                                            <span class="report-legend__swatch" style="background: {{ $segment['color'] }}"></span>
                                            <div>
                                                <div class="fw-semibold">{{ $segment['label'] }}</div>
                                                <div class="text-muted small">{{ $segment['value'] }} registros</div>
                                            </div>
                                            <div class="fw-semibold">{{ number_format($segment['percentage'], 2) }}%</div>
                                        </div>
                                    @empty
                                        <div class="text-muted">Sin datos para construir la visualizacion.</div>
                                    @endforelse
                                </div>
                            </div>
                        @elseif ($chartType === 'columnas')
                            <div>
                                <h4 class="mb-3">Diagrama de columnas</h4>
                                <div class="report-columns">
                                    @forelse ($segments as $segment)
                                        <div class="report-columns__item">
                                            <div class="report-columns__value">
                                                {{ $segment['value'] }} | {{ number_format($segment['percentage'], 2) }}%
                                            </div>
                                            <div class="report-columns__chart">
                                                <div
                                                    class="report-columns__bar"
                                                    style="height: {{ max(6, min(100, $segment['percentage'])) }}%; background: {{ $segment['color'] }};"
                                                ></div>
                                            </div>
                                            <div class="report-columns__label">{{ $segment['label'] }}</div>
                                        </div>
                                    @empty
                                        <div class="text-muted">No hay datos para construir el diagrama de columnas.</div>
                                    @endforelse
                                </div>
                            </div>
                        @else
                            <div>
                                <h4 class="mb-3">Cuadro comparativo</h4>
                                <div class="table-responsive">
                                    <table class="table table-bordered table-striped align-middle mb-0">
                                        <thead>
                                            <tr>
                                                <th>#</th>
                                                <th>Categoria</th>
                                                <th class="text-end">Valor</th>
                                                <th class="text-end">Porcentaje</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @forelse ($segments as $index => $segment)
                                                <tr>
                                                    <td>{{ $index + 1 }}</td>
                                                    <td>{{ $segment['label'] }}</td>
                                                    <td class="text-end">{{ $segment['value'] }}</td>
                                                    <td class="text-end">{{ number_format($segment['percentage'], 2) }}%</td>
                                                </tr>
                                            @empty
                                                <tr>
                                                    <td colspan="4" class="text-center text-muted">Sin datos para comparar.</td>
                                                </tr>
                                            @endforelse
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        @endif

                        <div class="table-responsive">
                            <table class="table card-table table-vcenter">
                                <thead>
                                    <tr>
                                        <th>Categoria</th>
                                        <th class="text-end">Valor</th>
                                        <th class="text-end">Porcentaje</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse ($reportData['categories'] as $index => $category)
                                        <tr>
                                            <td>{{ $category }}</td>
                                            <td class="text-end">{{ $reportData['values'][$index] ?? 0 }}</td>
                                            <td class="text-end">{{ number_format($reportData['percentages'][$index] ?? 0, 2) }}%</td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="3" class="text-center text-muted">Sin datos para los filtros seleccionados.</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection