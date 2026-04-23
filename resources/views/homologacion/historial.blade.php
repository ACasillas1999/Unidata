@extends('layouts.app')

@section('title', 'Historial de Sincronización')
@section('breadcrumb', 'Homologación › Historial')

@section('content')

<style>
.delta-pill {
    display: inline-flex;
    align-items: center;
    gap: 3px;
    padding: 2px 8px;
    border-radius: 20px;
    font-size: 10px;
    font-weight: 800;
    letter-spacing: 0.02em;
}
.delta-pos  { background: rgba(16,185,129,0.12); color: #10b981; border: 1px solid rgba(16,185,129,0.25); }
.delta-neg  { background: rgba(244,63,94,0.12);  color: #f43f5e; border: 1px solid rgba(244,63,94,0.25); }
.delta-zero { background: rgba(255,255,255,0.04); color: #64748b; border: 1px solid var(--border); }
.delta-base { background: rgba(139,92,246,0.1);  color: #a78bfa; border: 1px solid rgba(139,92,246,0.2); font-style: italic; }

.hist-table th, .hist-table td {
    padding: 11px 14px;
    border-bottom: 1px solid var(--border-light);
    white-space: nowrap;
}
.hist-table thead th {
    background: var(--bg-card-2);
    font-size: 10px;
    font-weight: 800;
    text-transform: uppercase;
    letter-spacing: 0.07em;
    color: var(--text-secondary);
    border-bottom: 2px solid var(--border);
    position: sticky;
    top: 0;
    z-index: 10;
}
.hist-table tbody tr:hover { background: rgba(255,255,255,0.02); }
.hist-table .date-cell {
    font-size: 11px;
    font-weight: 700;
    color: var(--text-primary);
    min-width: 130px;
}
.hist-table .date-cell span {
    display: block;
    font-size: 9px;
    font-weight: 500;
    color: var(--text-muted);
    margin-top: 2px;
}
.count-main {
    font-size: 12px;
    font-weight: 700;
    color: var(--text-primary);
    display: block;
}
.count-sub {
    font-size: 9px;
    color: var(--text-muted);
    display: block;
    margin-top: 1px;
}
</style>

{{-- ── HEADER ──────────────────────────────────────────────── --}}
<div class="page-header shadow-premium" style="margin-bottom: 16px; padding: 14px 20px; background: var(--grad-surface); border-radius: var(--radius-xl); border: 1px solid var(--glass-border); position: relative; overflow: hidden; display: flex; justify-content: space-between; align-items: center;">
    <div style="position:absolute; top:-50px; right:-50px; width:150px; height:150px; background:var(--emerald); filter:blur(100px); opacity:0.08; pointer-events:none;"></div>

    <div style="display: flex; gap: 16px; align-items: center; z-index: 1;">
        <div class="page-header-icon shadow-premium" style="background: linear-gradient(135deg,#10b981,#059669); border: none; color: white;">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                <polyline points="22 12 18 12 15 21 9 3 6 12 2 12"/>
            </svg>
        </div>
        <div>
            <h1 class="page-title" style="letter-spacing: -0.01em; margin:0;">Historial de Sincronización</h1>
            <p class="page-subtitle" style="color: var(--text-secondary); margin:4px 0 0;">
                Evolución de artículos activos por sucursal · {{ $total }} sync{{ $total !== 1 ? 's' : '' }} registrada{{ $total !== 1 ? 's' : '' }}
            </p>
        </div>
    </div>

    <div style="display:flex; gap:10px; align-items:center; z-index: 1;">
        {{-- Leyenda rápida --}}
        <div style="display:flex; gap:8px; align-items:center; flex-wrap:wrap;">
            <span class="delta-pill delta-pos">▲ Nuevos</span>
            <span class="delta-pill delta-neg">▼ Bajaron</span>
            <span class="delta-pill delta-zero">— Sin cambio</span>
            <span class="delta-pill delta-base">Base</span>
        </div>
        <a href="{{ route('homologacion.index') }}" class="btn btn--ghost btn--sm" style="font-size:11px; border:1px solid var(--border); display:flex; align-items:center; gap:6px;">
            <svg viewBox="0 0 24 24" fill="none" width="12" height="12" stroke="currentColor" stroke-width="2.5"><polyline points="15 18 9 12 15 6"/></svg>
            Volver
        </a>
    </div>
</div>

@if($total === 0)
{{-- ── ESTADO VACÍO ──────────────────────────────────────── --}}
<div class="glass-card shadow-premium" style="text-align:center; padding: 80px 40px;">
    <svg style="opacity:0.15; margin-bottom:20px;" viewBox="0 0 24 24" fill="none" width="64" height="64" stroke="currentColor" stroke-width="1">
        <path d="M21 2v6h-6"/><path d="M3 12a9 9 0 0 1 15-6.7L21 8"/>
        <path d="M3 22v-6h6"/><path d="M21 12a9 9 0 0 1-15 6.7L3 16"/>
    </svg>
    <h2 style="font-size:18px; font-weight:800; color:var(--text-primary); margin:0 0 8px;">Sin sincronizaciones registradas</h2>
    <p style="color:var(--text-muted); font-size:13px; margin:0 0 24px;">
        Ejecuta la primera sincronización desde la pantalla de Homologación y aquí aparecerá el historial de evolución.
    </p>
    <a href="{{ route('homologacion.index') }}" class="btn btn--primary shadow-premium" style="background:var(--grad-premium); border:none; color:white; padding:10px 24px;">
        Ir a Homologación
    </a>
</div>

@else

{{-- ── KPI CHIPS ──────────────────────────────────────────── --}}
@php
    $lastRow     = $rows[0] ?? null;
    $prevRow     = $rows[1] ?? null;
    $totalActivos = 0;
    $totalDelta   = 0;
    $branchCount  = count($branches);

    if ($lastRow) {
        foreach ($lastRow['cells'] as $cell) {
            $totalActivos += $cell['activos'] ?? 0;
            if ($cell['delta'] !== null) $totalDelta += $cell['delta'];
        }
    }
    $lastDate = $lastRow ? \Carbon\Carbon::parse($lastRow['date'], 'America/Mexico_City')->format('d/m/Y H:i') : '-';
@endphp

<div style="display:grid; grid-template-columns: repeat(auto-fit, minmax(180px,1fr)); gap:12px; margin-bottom:16px;">

    {{-- Total activos (última sync) --}}
    <div class="glass-card shadow-premium" style="padding:16px 18px; border-top: 2px solid var(--emerald);">
        <p style="font-size:9px; font-weight:800; text-transform:uppercase; letter-spacing:0.12em; color:var(--text-muted); margin:0 0 6px;">Activos (última sync)</p>
        <p style="font-size:22px; font-weight:800; color:var(--emerald); margin:0; line-height:1;">{{ number_format($totalActivos) }}</p>
        <p style="font-size:10px; color:var(--text-muted); margin:4px 0 0;">{{ $lastDate }}</p>
    </div>

    {{-- Delta total vs anterior --}}
    <div class="glass-card shadow-premium" style="padding:16px 18px; border-top: 2px solid {{ $totalDelta >= 0 ? 'var(--emerald)' : 'var(--rose)' }};">
        <p style="font-size:9px; font-weight:800; text-transform:uppercase; letter-spacing:0.12em; color:var(--text-muted); margin:0 0 6px;">Cambio vs sync anterior</p>
        <p style="font-size:22px; font-weight:800; color:{{ $totalDelta > 0 ? 'var(--emerald)' : ($totalDelta < 0 ? 'var(--rose)' : 'var(--text-muted)') }}; margin:0; line-height:1;">
            {{ $totalDelta > 0 ? '+' : '' }}{{ number_format($totalDelta) }}
        </p>
        <p style="font-size:10px; color:var(--text-muted); margin:4px 0 0;">artículos en todas las sucursales</p>
    </div>

    {{-- Syncs registradas --}}
    <div class="glass-card shadow-premium" style="padding:16px 18px; border-top: 2px solid var(--violet);">
        <p style="font-size:9px; font-weight:800; text-transform:uppercase; letter-spacing:0.12em; color:var(--text-muted); margin:0 0 6px;">Syncs Registradas</p>
        <p style="font-size:22px; font-weight:800; color:var(--violet-light); margin:0; line-height:1;">{{ $total }}</p>
        <p style="font-size:10px; color:var(--text-muted); margin:4px 0 0;">{{ $branchCount }} sucursales activas</p>
    </div>

</div>

{{-- ── TABLA HISTORIAL ───────────────────────────────────── --}}
<div class="glass-card shadow-premium" style="overflow:hidden;">

    <div style="padding:10px 16px; border-bottom:1px solid var(--border); display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:8px;">
        <h2 style="font-size:13px; font-weight:800; color:var(--text-primary); margin:0;">
            Evolución por Sucursal
        </h2>
        <p style="font-size:10px; color:var(--text-muted); margin:0;">
            Más reciente primero · El delta <span style="color:var(--emerald);font-weight:700;">▲</span> / <span style="color:var(--rose);font-weight:700;">▼</span> compara con la sync inmediata anterior
        </p>
    </div>

    <div style="overflow-x:auto; overflow-y:auto; max-height: calc(100vh - 360px);">
        <table class="hist-table" style="width:100%; border-collapse:separate; border-spacing:0;">
            <thead>
                <tr>
                    <th style="text-align:left; min-width:130px;">Fecha Sync</th>
                    @foreach($branches as $code => $name)
                        <th style="text-align:center; min-width:130px;">{{ $name }}</th>
                    @endforeach
                </tr>
            </thead>
            <tbody>
                @foreach($rows as $rowIdx => $row)
                    @php
                        $isBaseline = ($rowIdx === count($rows) - 1);
                    @endphp
                    <tr>
                        {{-- Columna de fecha --}}
                        <td class="date-cell">
                            {{ \Carbon\Carbon::parse($row['date'], 'America/Mexico_City')->format('d/m/Y') }}
                            <span>{{ \Carbon\Carbon::parse($row['date'], 'America/Mexico_City')->format('H:i:s') }} hrs (MX)</span>
                        </td>

                        {{-- Columnas de sucursales --}}
                        @foreach($branches as $code => $name)
                            @php
                                $cell    = $row['cells'][$code] ?? null;
                                $activos = $cell['activos']   ?? null;
                                $inact   = $cell['inactivos'] ?? null;
                                $falta   = $cell['falta']     ?? null;
                                $delta   = $cell['delta']     ?? null;
                            @endphp
                            <td style="text-align:center; vertical-align:middle;">
                                @if($activos === null)
                                    <span style="font-size:10px; color:var(--text-muted);">—</span>
                                @else
                                    <span class="count-main">{{ number_format($activos) }}</span>
                                    <span class="count-sub">
                                        <span style="color:#f59e0b;">{{ number_format($inact ?? 0) }} inact.</span>
                                        ·
                                        <span style="color:var(--rose);">{{ number_format($falta ?? 0) }} falta</span>
                                    </span>
                                    {{-- Badge delta --}}
                                    <span style="margin-top:4px; display:flex; justify-content:center;">
                                        @if($isBaseline || $delta === null)
                                            <span class="delta-pill delta-base">base</span>
                                        @elseif($delta > 0)
                                            <span class="delta-pill delta-pos">▲ +{{ number_format($delta) }}</span>
                                        @elseif($delta < 0)
                                            <span class="delta-pill delta-neg">▼ {{ number_format($delta) }}</span>
                                        @else
                                            <span class="delta-pill delta-zero">— 0</span>
                                        @endif
                                    </span>
                                @endif
                            </td>
                        @endforeach
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>

@endif

@endsection
