@extends('tablar::page')

@section('title', 'Módulo de Reportes - Test funcional')

@section('content')
    @php
        $from = (string) request('from', '');
        $to = (string) request('to', '');

        $factoryClass = '\\App\\Services\\Projects\\Reports\\ReportModuleFactory';
        $autoloadReady = class_exists($factoryClass);
        $reportData = [
            'categories' => [],
            'values' => [],
            'percentages' => [],
            'total' => 0,
        ];

        if ($autoloadReady) {
            $factory = new $factoryClass();
            $module = $factory->make($factoryClass::PROJECTS_BY_STATUS);

            $reportData = $module->generate([
                'from' => $from !== '' ? $from : null,
                'to' => $to !== '' ? $to : null,
            ]);
        }
    @endphp

    <div class="page-header d-print-none">
        <div class="container-xl">
            <h2 class="page-title">Módulo de Reportes (test funcional)</h2>
            <p class="text-muted mb-0">
                Vista de pruebas interna: ejecuta el módulo <code>projects_by_status</code> y muestra resultados reales desde base de datos.
            </p>
            @if (! $autoloadReady)
                <div class="alert alert-warning mt-3 mb-0">
                    No se encontró la clase <code>App\Services\Reports\ReportModuleFactory</code> en autoload.
                    Ejecuta <code>composer dump-autoload</code> y luego <code>php artisan optimize:clear</code>.
                </div>
            @endif
        </div>
    </div>

    <div class="page-body">
        <div class="container-xl">
            <div class="card mb-3">
                <div class="card-header">
                    <h3 class="card-title">Filtro de prueba</h3>
                </div>
                <div class="card-body">
                    <form method="GET" class="row g-3 align-items-end">
                        <div class="col-md-4">
                            <label for="from" class="form-label">Desde</label>
                            <input type="date" id="from" name="from" class="form-control" value="{{ $from }}">
                        </div>
                        <div class="col-md-4">
                            <label for="to" class="form-label">Hasta</label>
                            <input type="date" id="to" name="to" class="form-control" value="{{ $to }}">
                        </div>
                        <div class="col-md-4 d-flex gap-2">
                            <button type="submit" class="btn btn-primary">Ejecutar test</button>
                            <a href="?" class="btn btn-outline-secondary">Limpiar</a>
                        </div>
                    </form>
                </div>
            </div>

            <div class="card mb-3">
                <div class="card-header">
                    <h3 class="card-title">Resumen de ejecución</h3>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-3">
                            <div class="border rounded p-3">
                                <div class="text-muted">Total registros</div>
                                <div class="h2 mb-0">{{ $reportData['total'] }}</div>
                            </div>
                        </div>
                        <div class="col-md-9">
                            <div class="alert alert-info mb-0">
                                Salida validada del módulo: <code>categories[]</code>, <code>values[]</code>, <code>percentages[]</code>, <code>total</code>.
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Resultado para verificación manual</h3>
                </div>
                <div class="table-responsive">
                    <table class="table card-table table-vcenter">
                        <thead>
                            <tr>
                                <th>Categoría</th>
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
@endsection