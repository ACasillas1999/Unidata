@extends('layouts.app')

@section('title', 'Estadísticas Globales | Unidata')

@section('content')

<style>
    .glass-card {
        background: var(--bg-card);
        border: 1px solid var(--border);
        border-radius: var(--radius-lg);
    }
    .glass {
        background: rgba(255, 255, 255, 0.02);
        border: 1px solid var(--border);
    }
    .shadow-premium {
        box-shadow: var(--shadow-sm);
    }
    /* Custom Scrollbar for horizontal scrolling panels */
    .premium-scroll::-webkit-scrollbar {
        height: 8px;
    }
    .premium-scroll::-webkit-scrollbar-track {
        background: rgba(255, 255, 255, 0.02);
        border-radius: 4px;
    }
    .premium-scroll::-webkit-scrollbar-thumb {
        background: rgba(139, 92, 246, 0.3);
        border-radius: 4px;
    }
    .premium-scroll::-webkit-scrollbar-thumb:hover {
        background: rgba(139, 92, 246, 0.5);
    }
</style>

<div style="padding: 12px 0 20px 0;">
    <div style="display: flex; align-items: center; justify-content: space-between;">
        <div style="display: flex; align-items: center; gap: 16px;">
            <div style="background: rgba(139, 92, 246, 0.1); width: 44px; height: 44px; border-radius: 12px; display: flex; align-items: center; justify-content: center;">
                <svg viewBox="0 0 24 24" fill="none" width="22" height="22" stroke="var(--violet)" stroke-width="2.5">
                    <line x1="18" y1="20" x2="18" y2="10"/>
                    <line x1="12" y1="20" x2="12" y2="4"/>
                    <line x1="6" y1="20" x2="6" y2="14"/>
                </svg>
            </div>
            <div>
                <h1 style="font-size: 20px; font-weight: 800; color: var(--text-primary); margin: 0; letter-spacing: -0.02em;">Reportes y Estadísticas</h1>
                <p style="font-size: 13px; color: var(--text-muted); margin: 4px 0 0 0;">Análisis global de cobertura e inventarios locales</p>
            </div>
        </div>
    </div>
</div>

@if($error)
<div class="alert alert--error shadow-premium" style="margin-bottom:16px; border-left: 4px solid var(--rose); padding: 12px 16px; background: rgba(244, 63, 94, 0.1); color: var(--rose); border-radius: 6px;">
    <p style="font-weight: 700; font-size: 13px; margin:0;">{{ $error }}</p>
</div>
@endif

@if(!empty($stats))
@php
    $cols = [];
    foreach($branches as $name => $info) {
        $key = strtolower($info['conn']);
        $cols[$name] = $stats[$key] ?? 0;
    }
@endphp

<div class="glass-card shadow-premium" style="margin-bottom: 24px;">
    <div style="padding: 16px 20px; border-bottom: 1px solid var(--border); background: rgba(255,255,255,0.02);">
        <h3 style="font-size: 12px; font-weight: 800; text-transform: uppercase; letter-spacing: 0.1em; color: var(--text-primary); margin:0;">Cobertura de Homologación por Sucursal</h3>
    </div>
    
    <div class="premium-scroll" style="overflow-x: auto; padding: 20px;">
        <div style="display: flex; flex-direction: row; flex-wrap: nowrap; gap: 16px; min-width: max-content;">
            {{-- Central inventory --}}
            <div class="glass shadow-premium" style="padding: 16px 20px; border-radius: var(--radius-lg); border-left: 4px solid var(--violet); min-width: 160px; flex-shrink:0;">
                <p style="font-size: 10px; font-weight: 800; text-transform: uppercase; color: var(--text-muted); letter-spacing: 0.1em; margin-bottom: 8px;">Inventario Universal</p>
                <p style="font-size: 26px; font-weight: 800; color: #fff; margin:0; line-height: 1;">{{ number_format($stats['universo']) }}</p>
                <p style="font-size: 11px; color: var(--violet-light); margin-top: 6px; font-weight: 600;">Artículos Maestros</p>
            </div>
            
            {{-- Branch cards --}}
            @foreach($cols as $label => $cnt)
                @php $pct = $stats['total'] > 0 ? round($cnt / $stats['total'] * 100) : 0; @endphp
                <div class="glass shadow-premium" style="padding: 16px 20px; border-radius: var(--radius-lg); min-width: 150px; flex-shrink:0; transition: transform 0.2s;" onmouseover="this.style.transform='translateY(-2px)'" onmouseout="this.style.transform='none'">
                    <p style="font-size: 10px; font-weight: 700; color: var(--text-muted); text-transform: uppercase; letter-spacing: 0.05em; margin-bottom: 8px; white-space:nowrap;">{{ $label }}</p>
                    <div style="display: flex; align-items: baseline; gap: 6px;">
                        <p style="font-size: 20px; font-weight: 800; color: var(--text-primary); margin:0; line-height: 1;">{{ number_format($cnt) }}</p>
                        <span style="font-size: 12px; font-weight: 700; color: {{ $pct > 80 ? 'var(--emerald)' : ($pct > 50 ? 'var(--amber)' : 'var(--rose)') }};">{{ $pct }}%</span>
                    </div>
                    <div style="height: 4px; width: 100%; background: rgba(255,255,255,0.05); border-radius: 10px; margin-top: 10px; overflow: hidden;">
                        <div style="height: 100%; width: {{ $pct }}%; background: var(--grad-premium); border-radius: 10px;"></div>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
</div>

<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(320px, 1fr)); gap: 24px; margin-bottom: 24px;">
    {{-- Chart: Distribución de Cobertura --}}
    <div class="glass-card shadow-premium" style="padding: 20px;">
        <h3 style="font-size: 12px; font-weight: 800; text-transform: uppercase; letter-spacing: 0.1em; color: var(--text-primary); margin-bottom: 20px;">Distribución de Inventario Maestro</h3>
        <div style="position: relative; height: 260px; width: 100%;">
            <canvas id="distChart"></canvas>
        </div>
    </div>

    {{-- Chart: Comparativa de Sucursales --}}
    <div class="glass-card shadow-premium" style="padding: 20px;">
        <h3 style="font-size: 12px; font-weight: 800; text-transform: uppercase; letter-spacing: 0.1em; color: var(--text-primary); margin-bottom: 20px;">Comparativa de Sucursales</h3>
        <div style="position: relative; height: 260px; width: 100%;">
            <canvas id="branchChart"></canvas>
        </div>
    </div>
</div>

{{-- Data Table --}}
<div class="glass-card shadow-premium" style="margin-bottom: 24px;">
    <div style="padding: 16px 20px; border-bottom: 1px solid var(--border); background: rgba(255,255,255,0.02);">
        <h3 style="font-size: 12px; font-weight: 800; text-transform: uppercase; letter-spacing: 0.1em; color: var(--text-primary); margin:0;">Matriz de Salud de Inventario Global</h3>
    </div>
    <div class="premium-scroll" style="overflow-x: auto;">
        <table style="width: 100%; border-collapse: collapse; min-width: 600px;">
            <thead>
                <tr style="border-bottom: 1px solid var(--border); background: rgba(255,255,255,0.01);">
                    <th style="padding: 12px 20px; text-align: left; font-size: 10px; font-weight: 800; color: var(--text-muted); text-transform: uppercase;">Aparece en</th>
                    <th style="padding: 12px 20px; text-align: right; font-size: 10px; font-weight: 800; color: var(--text-muted); text-transform: uppercase;">Cantidad Artículos</th>
                    <th style="padding: 12px 20px; text-align: right; font-size: 10px; font-weight: 800; color: var(--text-muted); text-transform: uppercase;">Frecuencia (%)</th>
                    <th style="padding: 12px 20px; text-align: left; font-size: 10px; font-weight: 800; color: var(--text-muted); text-transform: uppercase;">Clasificación Visual</th>
                </tr>
            </thead>
            <tbody>
                @php
                    $ranks = [
                        12 => ['label' => '12 Sucursales (En Todas)', 'color' => 'var(--emerald)', 'bg' => 'rgba(16,185,129,0.1)'],
                        11 => ['label' => '11 Sucursales', 'color' => 'var(--emerald)', 'bg' => 'rgba(16,185,129,0.05)'],
                        10 => ['label' => '10 Sucursales', 'color' => 'var(--amber)', 'bg' => 'rgba(245,158,11,0.1)'],
                        9  => ['label' => '9 Sucursales', 'color' => 'var(--amber)', 'bg' => 'rgba(245,158,11,0.05)'],
                        8  => ['label' => '8 Sucursales', 'color' => 'var(--amber)', 'bg' => 'rgba(245,158,11,0.05)'],
                        7  => ['label' => '7 Sucursales', 'color' => 'var(--rose)', 'bg' => 'rgba(244,63,94,0.1)'],
                        6  => ['label' => '6 Sucursales', 'color' => 'var(--rose)', 'bg' => 'rgba(244,63,94,0.1)'],
                        5  => ['label' => '5 Sucursales', 'color' => 'var(--rose)', 'bg' => 'rgba(244,63,94,0.1)'],
                        4  => ['label' => '4 Sucursales', 'color' => 'var(--rose)', 'bg' => 'rgba(244,63,94,0.05)'],
                        3  => ['label' => '3 Sucursales', 'color' => 'var(--rose)', 'bg' => 'rgba(244,63,94,0.05)'],
                        2  => ['label' => '2 Sucursales', 'color' => 'var(--text-muted)', 'bg' => 'rgba(255,255,255,0.05)'],
                        1  => ['label' => '1 Sucursal', 'color' => 'var(--text-muted)', 'bg' => 'rgba(255,255,255,0.02)'],
                        0  => ['label' => '0 Sucursales (En Ninguna)', 'color' => 'var(--text-muted)', 'bg' => 'transparent']
                    ];
                @endphp
                @for($i = 12; $i >= 0; $i--)
                    @php
                        $cnt = $stats['distribucion'][$i] ?? 0;
                        if ($cnt === 0 && $i !== 12 && $i !== 0) continue; // Skip empty rows unless 0 or 12
                        $pct = $stats['universo'] > 0 ? ($cnt / $stats['universo']) * 100 : 0;
                        $rank = $ranks[$i];
                    @endphp
                    <tr style="border-bottom: 1px solid var(--border); background: {{ $rank['bg'] }}; cursor: pointer; transition: opacity 0.2s;" 
                        onclick="window.open('{{ route('homologacion.index', ['exact' => $i]) }}', '_blank')"
                        onmouseover="this.style.opacity='0.7'" 
                        onmouseout="this.style.opacity='1'"
                        title="Ver listado de artículos en {{ $i }} sucursales">
                        
                        <td style="padding: 12px 20px; font-size: 12px; font-weight: 700; color: var(--text-primary);">
                            {{ $rank['label'] }}
                            <svg style="margin-left:6px; vertical-align:middle; opacity:0.5;" viewBox="0 0 24 24" fill="none" width="12" height="12" stroke="currentColor" stroke-width="2"><path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"></path><polyline points="15 3 21 3 21 9"></polyline><line x1="10" y1="14" x2="21" y2="3"></line></svg>
                        </td>
                        <td style="padding: 12px 20px; font-size: 13px; font-weight: 800; color: var(--text-primary); text-align: right;">{{ number_format($cnt) }}</td>
                        <td style="padding: 12px 20px; font-size: 12px; color: var(--text-muted); text-align: right;">{{ number_format($pct, 2) }}%</td>
                        <td style="padding: 12px 20px;">
                            <div style="height: 6px; width: 100%; max-width: 150px; background: rgba(255,255,255,0.05); border-radius: 10px; overflow: hidden;">
                                <div style="height: 100%; width: {{ $pct }}%; background: {{ $rank['color'] }}; border-radius: 10px;"></div>
                            </div>
                        </td>
                    </tr>
                @endfor
            </tbody>
        </table>
    </div>
</div>

@endif



@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    @if(!empty($stats))
    // Cores para los gráficos basados en las variables CSS
    const gradientColors = [
        '#10b981', // En todas (emerald)
        '#3b82f6', // Casi todas (blue)
        '#f59e0b', // Parcial (amber)
        '#ec4899', // Baja (pink)
        '#64748b'  // Ninguna (slate)
    ];

    // Data for Doughnut Chart (Distribution)
    const distData = [
        {{ $stats['en_todas'] ?? 0 }},
        {{ $stats['casi_todas'] ?? 0 }},
        {{ $stats['parcial'] ?? 0 }},
        {{ $stats['baja'] ?? 0 }},
        {{ $stats['en_ninguna'] ?? 0 }}
    ];

    const ctxDist = document.getElementById('distChart').getContext('2d');
    new Chart(ctxDist, {
        type: 'doughnut',
        data: {
            labels: ['En Todas (12)', 'Casi Todas (8-11)', 'Parcial (3-7)', 'Baja (1-2)', 'Sin Cobertura (0)'],
            datasets: [{
                data: distData,
                backgroundColor: gradientColors,
                borderWidth: 0,
                hoverOffset: 4
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            cutout: '75%',
            plugins: {
                legend: {
                    position: 'right',
                    labels: { color: '#94a3b8', font: { family: 'inherit', size: 11 } }
                }
            }
        }
    });

    // Data for Bar Chart (Branches)
    const branchLabels = {!! json_encode(array_keys($cols ?? [])) !!};
    const branchValues = {!! json_encode(array_values($cols ?? [])) !!};
    
    // Creating gradient for bars (vertical)
    let ctxBranch = document.getElementById('branchChart').getContext('2d');
    let gradientBar = ctxBranch.createLinearGradient(0, 0, 0, 400);
    gradientBar.addColorStop(0, 'rgba(139, 92, 246, 0.8)');   // violet
    gradientBar.addColorStop(1, 'rgba(139, 92, 246, 0.2)');

    new Chart(ctxBranch, {
        type: 'bar',
        data: {
            labels: branchLabels,
            datasets: [{
                label: 'Artículos Activos',
                data: branchValues,
                backgroundColor: gradientBar,
                borderRadius: 4,
                borderWidth: 1,
                borderColor: 'rgba(139, 92, 246, 1)'
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { display: false }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    grid: { color: 'rgba(255, 255, 255, 0.05)', drawBorder: false },
                    ticks: { color: '#64748b', font: { size: 10 } }
                },
                x: {
                    grid: { display: false, drawBorder: false },
                    ticks: { color: '#94a3b8', font: { size: 9 }, maxRotation: 45, minRotation: 45 }
                }
            }
        }
    });
    @endif
});
</script>
@endpush

@endsection
