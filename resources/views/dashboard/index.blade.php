@extends('layouts.app')

@section('title', 'Dashboard · Portal Central')
@section('breadcrumb', 'Dashboard')

@push('page-content-style')
    .page-content { overflow-y: auto !important; }

    /* ── Scrollbar Cobertura por Sucursal ──────────────────────────────── */
    #cobertura-scroll {
        scrollbar-width: thin;
        scrollbar-color: rgba(139,92,246,.5) rgba(255,255,255,.04);
    }
    #cobertura-scroll::-webkit-scrollbar {
        height: 6px;
    }
    #cobertura-scroll::-webkit-scrollbar-track {
        background: rgba(255,255,255,.04);
        border-radius: 10px;
        margin: 0 16px;
    }
    #cobertura-scroll::-webkit-scrollbar-thumb {
        background: linear-gradient(90deg, #8b5cf6, #6366f1);
        border-radius: 10px;
        box-shadow: 0 0 6px rgba(139,92,246,.4);
        transition: background .2s;
    }
    #cobertura-scroll::-webkit-scrollbar-thumb:hover {
        background: linear-gradient(90deg, #a78bfa, #818cf8);
        box-shadow: 0 0 10px rgba(139,92,246,.6);
    }
@endpush

@section('content')

{{-- ── Header ─────────────────────────────────────────────────────────────── --}}
<div style="display:flex; align-items:center; justify-content:space-between; margin-bottom:24px; flex-shrink:0;">
    <div>
        <h1 style="font-size:22px; font-weight:800; color:var(--text-primary); margin:0; letter-spacing:-0.03em; display:flex; align-items:center; gap:12px;">
            <span style="width:38px; height:38px; background:linear-gradient(135deg,#f59e0b,#f97316); border-radius:10px; display:flex; align-items:center; justify-content:center; box-shadow:0 0 18px rgba(245,158,11,.35); flex-shrink:0;">
                <svg viewBox="0 0 24 24" fill="none" width="20" height="20" stroke="white" stroke-width="2">
                    <rect x="3" y="3" width="7" height="7" rx="1"/><rect x="14" y="3" width="7" height="7" rx="1"/>
                    <rect x="3" y="14" width="7" height="7" rx="1"/><rect x="14" y="14" width="7" height="7" rx="1"/>
                </svg>
            </span>
            Panel de Control
        </h1>
        <p style="font-size:13px; color:var(--text-secondary); margin:5px 0 0 50px;">
            Panorama general del sistema · {{ now()->format('d M Y, H:i') }}
        </p>
    </div>
    <a href="{{ route('homologacion.index') }}" style="display:flex; align-items:center; gap:8px; background:rgba(245,158,11,.1); border:1px solid rgba(245,158,11,.25); color:#fbbf24; border-radius:10px; padding:9px 16px; font-size:13px; font-weight:700; text-decoration:none; transition:all .2s;">
        <svg viewBox="0 0 24 24" fill="none" width="14" height="14" stroke="currentColor" stroke-width="2.5"><path d="M21 2v6h-6"/><path d="M3 12a9 9 0 0 1 15-6.7L21 8"/><path d="M3 22v-6h6"/><path d="M21 12a9 9 0 0 1-15 6.7L3 16"/></svg>
        Sincronizar datos
    </a>
</div>

@if($error)
<div style="margin-bottom:20px; border-left:4px solid var(--rose); padding:12px 16px; background:rgba(244,63,94,.08); color:var(--rose); border-radius:8px; font-size:13px;">
    ⚠️ {{ $error }}
</div>
@endif

{{-- ═══════════════════════════════════════════════════════════════════════════
     ROW 1 — KPI Cards
═══════════════════════════════════════════════════════════════════════════ --}}
<div style="display:grid; grid-template-columns:repeat(4,1fr); gap:14px; margin-bottom:20px;">

    {{-- Artículos Universo --}}
    <div style="background:var(--bg-card); border:1px solid var(--border); border-radius:14px; padding:18px 20px; position:relative; overflow:hidden;">
        <div style="position:absolute; top:-20px; right:-20px; width:80px; height:80px; background:rgba(139,92,246,.07); border-radius:50%;"></div>
        <div style="display:flex; align-items:center; gap:10px; margin-bottom:12px;">
            <span style="width:32px; height:32px; background:var(--violet-bg); border-radius:8px; display:flex; align-items:center; justify-content:center; flex-shrink:0;">
                <svg viewBox="0 0 24 24" fill="none" width="16" height="16" stroke="var(--violet-light)" stroke-width="2"><ellipse cx="12" cy="5" rx="9" ry="3"/><path d="M3 5v14c0 1.66 4 3 9 3s9-1.34 9-3V5"/><path d="M3 12c0 1.66 4 3 9 3s9-1.34 9-3"/></svg>
            </span>
            <span style="font-size:11px; font-weight:700; color:var(--text-muted); text-transform:uppercase; letter-spacing:.08em;">Universo de artículos</span>
        </div>
        <div style="font-size:30px; font-weight:800; color:var(--text-primary); letter-spacing:-0.04em; line-height:1;">
            {{ !empty($stats['universo']) ? number_format($stats['universo']) : '—' }}
        </div>
        <p style="font-size:12px; color:var(--text-muted); margin:6px 0 0;">Catálogo maestro total</p>
        @if(!empty($stats['en_todas']))
        <div style="margin-top:12px; padding-top:12px; border-top:1px solid rgba(255,255,255,.05); display:flex; align-items:center; gap:6px;">
            <span style="width:6px; height:6px; border-radius:50%; background:var(--emerald); box-shadow:0 0 6px var(--emerald);"></span>
            <span style="font-size:11px; color:var(--emerald);">{{ number_format($stats['en_todas']) }} en todas las sucursales</span>
        </div>
        @endif
    </div>

    {{-- Cobertura Global --}}
    <div style="background:var(--bg-card); border:1px solid var(--border); border-radius:14px; padding:18px 20px; position:relative; overflow:hidden;">
        <div style="position:absolute; top:-20px; right:-20px; width:80px; height:80px; background:rgba(16,185,129,.07); border-radius:50%;"></div>
        <div style="display:flex; align-items:center; gap:10px; margin-bottom:12px;">
            <span style="width:32px; height:32px; background:var(--emerald-bg); border-radius:8px; display:flex; align-items:center; justify-content:center; flex-shrink:0;">
                <svg viewBox="0 0 24 24" fill="none" width="16" height="16" stroke="var(--emerald)" stroke-width="2"><polyline points="22 12 18 12 15 21 9 3 6 12 2 12"/></svg>
            </span>
            <span style="font-size:11px; font-weight:700; color:var(--text-muted); text-transform:uppercase; letter-spacing:.08em;">Cobertura global</span>
        </div>
        <div style="font-size:30px; font-weight:800; letter-spacing:-0.04em; line-height:1; color:{{ !empty($stats['cobertura_global_pct']) && $stats['cobertura_global_pct'] >= 60 ? 'var(--emerald)' : (!empty($stats['cobertura_global_pct']) && $stats['cobertura_global_pct'] >= 30 ? 'var(--amber)' : 'var(--rose)') }};">
            {{ !empty($stats['cobertura_global_pct']) ? $stats['cobertura_global_pct'] . '%' : '—' }}
        </div>
        <p style="font-size:12px; color:var(--text-muted); margin:6px 0 0;">Presencia promedio en sucursales</p>
        @if(!empty($stats['total_branches']))
        <div style="margin-top:12px; padding-top:12px; border-top:1px solid rgba(255,255,255,.05); display:flex; align-items:center; gap:6px;">
            <span style="width:6px; height:6px; border-radius:50%; background:var(--sky);"></span>
            <span style="font-size:11px; color:var(--text-muted);">{{ $stats['total_branches'] }} sucursales activas</span>
        </div>
        @endif
    </div>

    {{-- DB Master --}}
    <div style="background:var(--bg-card); border:1px solid var(--border); border-radius:14px; padding:18px 20px; position:relative; overflow:hidden;">
        <div style="position:absolute; top:-20px; right:-20px; width:80px; height:80px; background:rgba(14,165,233,.07); border-radius:50%;"></div>
        <div style="display:flex; align-items:center; gap:10px; margin-bottom:12px;">
            <span style="width:32px; height:32px; background:var(--sky-bg); border-radius:8px; display:flex; align-items:center; justify-content:center; flex-shrink:0;">
                <svg viewBox="0 0 24 24" fill="none" width="16" height="16" stroke="var(--sky)" stroke-width="2"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
            </span>
            <span style="font-size:11px; font-weight:700; color:var(--text-muted); text-transform:uppercase; letter-spacing:.08em;">DB Master</span>
        </div>
        <div style="font-size:30px; font-weight:800; color:var(--text-primary); letter-spacing:-0.04em; line-height:1;">
            {{ !empty($stats['db_master_total']) ? number_format($stats['db_master_total']) : '—' }}
        </div>
        <p style="font-size:12px; color:var(--text-muted); margin:6px 0 0;">Artículos en lista maestra</p>
        <div style="margin-top:12px; padding-top:12px; border-top:1px solid rgba(255,255,255,.05); display:flex; align-items:center; gap:6px;">
            <span style="width:6px; height:6px; border-radius:50%; background:var(--sky);"></span>
            <span style="font-size:11px; color:var(--text-muted);">Último sync: {{ $stats['db_master_last_sync'] ?? 'N/A' }}</span>
        </div>
    </div>

    {{-- Usuarios --}}
    <div style="background:var(--bg-card); border:1px solid var(--border); border-radius:14px; padding:18px 20px; position:relative; overflow:hidden;">
        <div style="position:absolute; top:-20px; right:-20px; width:80px; height:80px; background:rgba(245,158,11,.07); border-radius:50%;"></div>
        <div style="display:flex; align-items:center; gap:10px; margin-bottom:12px;">
            <span style="width:32px; height:32px; background:var(--amber-bg); border-radius:8px; display:flex; align-items:center; justify-content:center; flex-shrink:0;">
                <svg viewBox="0 0 24 24" fill="none" width="16" height="16" stroke="var(--amber)" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
            </span>
            <span style="font-size:11px; font-weight:700; color:var(--text-muted); text-transform:uppercase; letter-spacing:.08em;">Usuarios del sistema</span>
        </div>
        <div style="font-size:30px; font-weight:800; color:var(--text-primary); letter-spacing:-0.04em; line-height:1;">
            {{ $stats['total_usuarios'] ?? 0 }}
        </div>
        <p style="font-size:12px; color:var(--text-muted); margin:6px 0 0;">{{ $stats['total_roles'] ?? 0 }} roles configurados</p>
        <div style="margin-top:12px; padding-top:12px; border-top:1px solid rgba(255,255,255,.05); display:flex; gap:8px; flex-wrap:wrap;">
            @foreach($stats['roles_breakdown'] ?? [] as $roleName => $cnt)
            <span style="font-size:10px; font-weight:700; background:rgba(255,255,255,.05); border-radius:20px; padding:2px 9px; color:var(--text-secondary);">{{ $roleName }}: {{ $cnt }}</span>
            @endforeach
        </div>
    </div>
</div>

{{-- ═══════════════════════════════════════════════════════════════════════════
     ROW 2 — Cobertura por Sucursal + Distribución Doughnut
═══════════════════════════════════════════════════════════════════════════ --}}
@if(!empty($stats) && !empty($branchesArr))
@php
    $cols = [];
    foreach($branchesArr as $name => $info) {
        $key = strtolower($info['conn']);
        $cols[$name] = $stats[$key] ?? 0;
    }
@endphp

<div style="display:grid; grid-template-columns:1fr 340px; gap:14px; margin-bottom:20px;">
    {{-- Cobertura scrollable por sucursal --}}
    <div style="background:var(--bg-card); border:1px solid var(--border); border-radius:14px; overflow:hidden;">
        <div style="padding:14px 20px; border-bottom:1px solid var(--border); background:rgba(255,255,255,.02); display:flex; align-items:center; justify-content:space-between;">
            <h3 style="font-size:12px; font-weight:800; text-transform:uppercase; letter-spacing:.1em; color:var(--text-primary); margin:0;">Cobertura por Sucursal</h3>
            <span style="font-size:11px; color:var(--text-muted);">Artículos homologados / total</span>
        </div>
        @php $cobCols = (int)ceil(count($cols) / 2); @endphp
        <div id="cobertura-scroll" style="overflow-x:auto; padding:16px 20px;">
            <div style="display:grid; grid-template-columns:repeat({{ $cobCols }}, 1fr); grid-template-rows:repeat(2, auto); grid-auto-flow:column; gap:10px;">
                @foreach($cols as $label => $cnt)
                @php $pct = ($stats['total'] ?? 0) > 0 ? round($cnt / $stats['total'] * 100) : 0; @endphp
                <div style="background:rgba(255,255,255,.02); border:1px solid var(--border); border-radius:10px; padding:12px 14px; transition:transform .2s, border-color .2s; cursor:default;"
                     onmouseover="this.style.transform='translateY(-2px)'; this.style.borderColor='rgba(139,92,246,.3)';"
                     onmouseout="this.style.transform='none'; this.style.borderColor='var(--border)';">
                    <p style="font-size:10px; font-weight:700; color:var(--text-muted); text-transform:uppercase; letter-spacing:.05em; margin-bottom:7px; white-space:nowrap; overflow:hidden; text-overflow:ellipsis;">{{ $label }}</p>
                    <div style="display:flex; align-items:baseline; gap:5px; margin-bottom:8px;">
                        <span style="font-size:18px; font-weight:800; color:var(--text-primary);">{{ number_format($cnt) }}</span>
                        <span style="font-size:12px; font-weight:800; color:{{ $pct > 80 ? 'var(--emerald)' : ($pct > 50 ? 'var(--amber)' : 'var(--rose)') }};">{{ $pct }}%</span>
                    </div>
                    <div style="height:4px; background:rgba(255,255,255,.05); border-radius:10px; overflow:hidden;">
                        <div style="height:100%; width:{{ $pct }}%; background:{{ $pct > 80 ? 'var(--emerald)' : ($pct > 50 ? 'var(--amber)' : 'var(--rose)') }}; border-radius:10px; transition:width 1s ease;"></div>
                    </div>
                </div>
                @endforeach
            </div>
        </div>
    </div>

    {{-- Doughnut distribución --}}
    <div style="background:var(--bg-card); border:1px solid var(--border); border-radius:14px; padding:16px 20px;">
        <h3 style="font-size:12px; font-weight:800; text-transform:uppercase; letter-spacing:.1em; color:var(--text-primary); margin:0 0 16px;">Distribución</h3>
        <div style="position:relative; height:200px;">
            <canvas id="distChart"></canvas>
        </div>
        <div style="display:grid; grid-template-columns:1fr 1fr; gap:6px; margin-top:14px;">
            @php
            $distItems = [
                ['label' => 'En todas',     'val' => $stats['en_todas'] ?? 0,    'color' => '#10b981'],
                ['label' => 'Casi todas',   'val' => $stats['casi_todas'] ?? 0,  'color' => '#3b82f6'],
                ['label' => 'Parcial',      'val' => $stats['parcial'] ?? 0,     'color' => '#f59e0b'],
                ['label' => 'Baja cobert.', 'val' => $stats['baja'] ?? 0,        'color' => '#ec4899'],
                ['label' => 'Sin cobert.',  'val' => $stats['en_ninguna'] ?? 0,  'color' => '#64748b'],
            ];
            @endphp
            @foreach($distItems as $di)
            <div style="display:flex; align-items:center; gap:6px;">
                <span style="width:8px; height:8px; border-radius:2px; background:{{ $di['color'] }}; flex-shrink:0;"></span>
                <span style="font-size:11px; color:var(--text-muted);">{{ $di['label'] }}: <strong style="color:var(--text-primary);">{{ number_format($di['val']) }}</strong></span>
            </div>
            @endforeach
        </div>
    </div>
</div>

{{-- ═══════════════════════════════════════════════════════════════════════════
     ROW 3 — Barras por sucursal + Matriz de Salud
═══════════════════════════════════════════════════════════════════════════ --}}
<div style="display:grid; grid-template-columns:1fr 1fr; gap:14px; margin-bottom:20px;">
    {{-- Bar chart sucursales --}}
    <div style="background:var(--bg-card); border:1px solid var(--border); border-radius:14px; padding:16px 20px;">
        <h3 style="font-size:12px; font-weight:800; text-transform:uppercase; letter-spacing:.1em; color:var(--text-primary); margin:0 0 16px;">Comparativa de Sucursales</h3>
        <div style="position:relative; height:240px;">
            <canvas id="branchChart"></canvas>
        </div>
    </div>

    {{-- Matriz de Salud compacta --}}
    <div style="background:var(--bg-card); border:1px solid var(--border); border-radius:14px; overflow:hidden;">
        <div style="padding:14px 20px; border-bottom:1px solid var(--border); background:rgba(255,255,255,.02); display:flex; align-items:center; justify-content:space-between;">
            <h3 style="font-size:12px; font-weight:800; text-transform:uppercase; letter-spacing:.1em; color:var(--text-primary); margin:0;">Matriz de Salud Global</h3>
            <a href="{{ route('estadisticas.index') }}" style="font-size:11px; color:var(--violet-light); text-decoration:none; font-weight:700;">Ver completo →</a>
        </div>
        <div style="overflow-y:auto; max-height:300px;">
            <table style="width:100%; border-collapse:collapse;">
                <thead>
                    <tr style="border-bottom:1px solid var(--border); background:rgba(255,255,255,.01);">
                        <th style="padding:10px 16px; text-align:left; font-size:10px; font-weight:800; color:var(--text-muted); text-transform:uppercase;">Aparece en</th>
                        <th style="padding:10px 16px; text-align:right; font-size:10px; font-weight:800; color:var(--text-muted); text-transform:uppercase;">Artículos</th>
                        <th style="padding:10px 16px; text-align:right; font-size:10px; font-weight:800; color:var(--text-muted); text-transform:uppercase;">Frec.</th>
                        <th style="padding:10px 16px; text-align:left; font-size:10px; font-weight:800; color:var(--text-muted); text-transform:uppercase;">Estado</th>
                    </tr>
                </thead>
                <tbody>
                @php
                    $totalB = $stats['total_branches'] ?? 0;
                    $distColors = fn($i, $tb) => $i === $tb
                        ? ['color' => 'var(--emerald)', 'bg' => 'rgba(16,185,129,0.07)', 'label' => "$i Sucursales (Todas)"]
                        : ($i === 0
                            ? ['color' => 'var(--text-muted)', 'bg' => 'transparent', 'label' => "0 Sucursales (Ninguna)"]
                            : ((($i/$tb)*100 >= 80)
                                ? ['color' => 'var(--emerald)', 'bg' => 'rgba(16,185,129,0.04)', 'label' => "$i Sucursales"]
                                : ((($i/$tb)*100 >= 30)
                                    ? ['color' => 'var(--amber)', 'bg' => 'rgba(245,158,11,0.04)', 'label' => "$i Sucursales"]
                                    : ['color' => 'var(--rose)', 'bg' => 'rgba(244,63,94,0.04)', 'label' => "$i Sucursales"]
                                )
                            )
                        );
                @endphp
                @for($i = $totalB; $i >= 0; $i--)
                @php
                    $cnt = $stats['distribucion'][$i] ?? 0;
                    if ($cnt === 0 && $i !== $totalB && $i !== 0) continue;
                    $pct  = ($stats['universo'] ?? 0) > 0 ? round(($cnt / $stats['universo']) * 100, 1) : 0;
                    $meta = $distColors($i, $totalB);
                @endphp
                <tr style="border-bottom:1px solid rgba(255,255,255,.04); background:{{ $meta['bg'] }}; cursor:pointer; transition:opacity .15s;"
                    onclick="window.open('{{ route('homologacion.index', ['exact' => $i]) }}', '_blank')"
                    onmouseover="this.style.opacity='.7'" onmouseout="this.style.opacity='1'">
                    <td style="padding:9px 16px; font-size:12px; font-weight:700; color:{{ $meta['color'] }};">{{ $meta['label'] }}</td>
                    <td style="padding:9px 16px; font-size:13px; font-weight:800; color:var(--text-primary); text-align:right;">{{ number_format($cnt) }}</td>
                    <td style="padding:9px 16px; font-size:12px; color:var(--text-muted); text-align:right;">{{ $pct }}%</td>
                    <td style="padding:9px 16px;">
                        <div style="height:5px; width:80px; background:rgba(255,255,255,.05); border-radius:10px; overflow:hidden;">
                            <div style="height:100%; width:{{ min($pct, 100) }}%; background:{{ $meta['color'] }}; border-radius:10px;"></div>
                        </div>
                    </td>
                </tr>
                @endfor
                </tbody>
            </table>
        </div>
    </div>
</div>

{{-- ═══════════════════════════════════════════════════════════════════════════
     ROW 4 — Accesos rápidos a módulos
═══════════════════════════════════════════════════════════════════════════ --}}
<div style="margin-bottom:32px;">
    <p style="font-size:11px; font-weight:700; color:var(--text-muted); text-transform:uppercase; letter-spacing:.1em; margin-bottom:12px;">Accesos rápidos</p>
    <div style="display:grid; grid-template-columns:repeat(auto-fill, minmax(160px, 1fr)); gap:10px;">
        @php
        $shortcuts = [
            ['label' => 'Catálogo',        'desc' => 'Ver artículos',        'route' => 'articulos.index',     'color' => '#8b5cf6', 'icon' => '<path d="M21 12c0 1.66-4 3-9 3s-9-1.34-9-3"/><path d="M3 5v14c0 1.66 4 3 9 3s9-1.34 9-3V5"/><ellipse cx="12" cy="5" rx="9" ry="3"/>'],
            ['label' => 'Homologación',    'desc' => 'Comparar sucursales',  'route' => 'homologacion.index',  'color' => '#0ea5e9', 'icon' => '<polyline points="22 12 18 12 15 21 9 3 6 12 2 12"/>'],
            ['label' => 'DB Master',       'desc' => 'Lista maestra',        'route' => 'db_master.index',     'color' => '#10b981', 'icon' => '<path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/>'],
            ['label' => 'Estadísticas',    'desc' => 'Análisis global',      'route' => 'estadisticas.index',  'color' => '#f59e0b', 'icon' => '<line x1="18" y1="20" x2="18" y2="10"/><line x1="12" y1="20" x2="12" y2="4"/><line x1="6" y1="20" x2="6" y2="14"/>'],
            ['label' => 'Usuarios',        'desc' => 'Gestionar usuarios',   'route' => 'usuarios.index',      'color' => '#f43f5e', 'icon' => '<path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/>'],
            ['label' => 'Conexiones',      'desc' => 'Bases de datos',       'route' => 'conexiones.index',    'color' => '#06b6d4', 'icon' => '<path d="M5 12.55a11 11 0 0 1 14.08 0"/><path d="M1.42 9a16 16 0 0 1 21.16 0"/><circle cx="12" cy="20" r="1" fill="currentColor"/>'],
            ['label' => 'Roles',           'desc' => 'Permisos y accesos',   'route' => 'roles.index',         'color' => '#a855f7', 'icon' => '<path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/><path d="M9 12l2 2 4-4"/>'],
            ['label' => 'Descargas',       'desc' => 'Exportaciones',        'route' => 'descargas.index',     'color' => '#64748b', 'icon' => '<path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/>'],
        ];
        @endphp
        @foreach($shortcuts as $sc)
        <a href="{{ route($sc['route']) }}" style="background:var(--bg-card); border:1px solid var(--border); border-radius:12px; padding:14px 16px; text-decoration:none; display:flex; flex-direction:column; gap:10px; transition:all .2s; cursor:pointer;"
           onmouseover="this.style.borderColor='{{ $sc['color'] }}44'; this.style.transform='translateY(-2px)';"
           onmouseout="this.style.borderColor='var(--border)'; this.style.transform='none';">
            <span style="width:30px; height:30px; border-radius:8px; background:{{ $sc['color'] }}18; display:flex; align-items:center; justify-content:center; flex-shrink:0;">
                <svg viewBox="0 0 24 24" fill="none" width="15" height="15" stroke="{{ $sc['color'] }}" stroke-width="2">{!! $sc['icon'] !!}</svg>
            </span>
            <div>
                <div style="font-size:13px; font-weight:700; color:var(--text-primary);">{{ $sc['label'] }}</div>
                <div style="font-size:11px; color:var(--text-muted); margin-top:2px;">{{ $sc['desc'] }}</div>
            </div>
        </a>
        @endforeach
    </div>
</div>

@else
{{-- ═══════════════════════════════════════════════════════════════════════════
     ESTADO VACÍO — Información del sistema siempre disponible
═══════════════════════════════════════════════════════════════════════════ --}}

{{-- Banner de acción --}}
<div style="background:linear-gradient(135deg, rgba(139,92,246,.12), rgba(99,102,241,.08)); border:1px solid rgba(139,92,246,.25); border-radius:16px; padding:20px 24px; margin-bottom:20px; display:flex; align-items:center; gap:20px; flex-wrap:wrap;">
    <div style="width:48px; height:48px; background:linear-gradient(135deg,#8b5cf6,#6366f1); border-radius:12px; display:flex; align-items:center; justify-content:center; flex-shrink:0; box-shadow:0 0 20px rgba(139,92,246,.35);">
        <svg viewBox="0 0 24 24" fill="none" width="22" height="22" stroke="white" stroke-width="2"><polyline points="22 12 18 12 15 21 9 3 6 12 2 12"/></svg>
    </div>
    <div style="flex:1; min-width:200px;">
        <h2 style="font-size:15px; font-weight:800; color:var(--text-primary); margin:0 0 4px;">Homologación pendiente de sincronización</h2>
        <p style="font-size:12px; color:var(--text-muted); margin:0;">El sistema está listo. Ejecuta la sincronización para comparar artículos entre sucursales y poblar el dashboard.</p>
    </div>
    <a href="{{ route('homologacion.index') }}" style="display:inline-flex; align-items:center; gap:8px; background:linear-gradient(135deg,#8b5cf6,#6366f1); color:white; border-radius:10px; padding:10px 20px; font-size:13px; font-weight:700; text-decoration:none; white-space:nowrap; flex-shrink:0; box-shadow:0 4px 15px rgba(139,92,246,.3);">
        <svg viewBox="0 0 24 24" fill="none" width="13" height="13" stroke="white" stroke-width="2.5"><path d="M21 2v6h-6"/><path d="M3 12a9 9 0 0 1 15-6.7L21 8"/><path d="M3 22v-6h6"/><path d="M21 12a9 9 0 0 1-15 6.7L3 16"/></svg>
        Ir a Homologación
    </a>
</div>

{{-- Mini KPIs del sistema (siempre disponibles) --}}
<div style="display:grid; grid-template-columns:repeat(3,1fr); gap:14px; margin-bottom:20px;">

    {{-- Sucursales --}}
    <div style="background:var(--bg-card); border:1px solid var(--border); border-radius:14px; padding:16px 20px; position:relative; overflow:hidden;">
        <div style="position:absolute; top:-15px; right:-15px; width:70px; height:70px; background:rgba(14,165,233,.06); border-radius:50%;"></div>
        <div style="display:flex; align-items:center; gap:10px; margin-bottom:14px;">
            <span style="width:32px; height:32px; background:var(--sky-bg); border-radius:8px; display:flex; align-items:center; justify-content:center; flex-shrink:0;">
                <svg viewBox="0 0 24 24" fill="none" width="16" height="16" stroke="var(--sky)" stroke-width="2"><path d="M5 12.55a11 11 0 0 1 14.08 0"/><path d="M1.42 9a16 16 0 0 1 21.16 0"/><circle cx="12" cy="20" r="1" fill="var(--sky)"/></svg>
            </span>
            <span style="font-size:11px; font-weight:700; color:var(--text-muted); text-transform:uppercase; letter-spacing:.08em;">Sucursales</span>
        </div>
        <div style="font-size:28px; font-weight:800; color:var(--text-primary); letter-spacing:-0.04em; line-height:1; margin-bottom:6px;">
            {{ $stats['total_branches'] ?? 0 }}
        </div>
        <p style="font-size:12px; color:var(--text-muted); margin:0 0 12px;">Conexiones configuradas</p>
        <div style="display:flex; flex-direction:column; gap:6px;">
            @forelse($branchesArr ?? [] as $bName => $bInfo)
            <div style="display:flex; align-items:center; justify-content:space-between; padding:5px 10px; background:rgba(14,165,233,.05); border-radius:6px; border:1px solid rgba(14,165,233,.1);">
                <span style="font-size:11px; font-weight:600; color:var(--text-secondary);">{{ $bName }}</span>
                <span style="font-size:10px; color:var(--sky); font-weight:700; background:rgba(14,165,233,.1); padding:1px 7px; border-radius:10px;">{{ strtoupper($bInfo['conn']) }}</span>
            </div>
            @empty
            <p style="font-size:11px; color:var(--text-muted); margin:0;">Sin conexiones activas</p>
            @endforelse
        </div>
    </div>

    {{-- Usuarios & Roles --}}
    <div style="background:var(--bg-card); border:1px solid var(--border); border-radius:14px; padding:16px 20px; position:relative; overflow:hidden;">
        <div style="position:absolute; top:-15px; right:-15px; width:70px; height:70px; background:rgba(245,158,11,.06); border-radius:50%;"></div>
        <div style="display:flex; align-items:center; gap:10px; margin-bottom:14px;">
            <span style="width:32px; height:32px; background:var(--amber-bg); border-radius:8px; display:flex; align-items:center; justify-content:center; flex-shrink:0;">
                <svg viewBox="0 0 24 24" fill="none" width="16" height="16" stroke="var(--amber)" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
            </span>
            <span style="font-size:11px; font-weight:700; color:var(--text-muted); text-transform:uppercase; letter-spacing:.08em;">Usuarios & Roles</span>
        </div>
        <div style="font-size:28px; font-weight:800; color:var(--text-primary); letter-spacing:-0.04em; line-height:1; margin-bottom:6px;">
            {{ $stats['total_usuarios'] ?? 0 }}
        </div>
        <p style="font-size:12px; color:var(--text-muted); margin:0 0 12px;">{{ $stats['total_roles'] ?? 0 }} roles configurados</p>
        <div style="display:flex; flex-direction:column; gap:6px;">
            @forelse($stats['roles_breakdown'] ?? [] as $rolName => $rolCnt)
            <div style="display:flex; align-items:center; justify-content:space-between; padding:5px 10px; background:rgba(245,158,11,.05); border-radius:6px; border:1px solid rgba(245,158,11,.1);">
                <span style="font-size:11px; font-weight:600; color:var(--text-secondary);">{{ $rolName }}</span>
                <span style="font-size:12px; color:var(--amber); font-weight:800;">{{ $rolCnt }} usuario{{ $rolCnt != 1 ? 's' : '' }}</span>
            </div>
            @empty
            <p style="font-size:11px; color:var(--text-muted); margin:0;">Sin roles definidos</p>
            @endforelse
        </div>
    </div>

    {{-- DB Master --}}
    <div style="background:var(--bg-card); border:1px solid var(--border); border-radius:14px; padding:16px 20px; position:relative; overflow:hidden;">
        <div style="position:absolute; top:-15px; right:-15px; width:70px; height:70px; background:rgba(16,185,129,.06); border-radius:50%;"></div>
        <div style="display:flex; align-items:center; gap:10px; margin-bottom:14px;">
            <span style="width:32px; height:32px; background:var(--emerald-bg); border-radius:8px; display:flex; align-items:center; justify-content:center; flex-shrink:0;">
                <svg viewBox="0 0 24 24" fill="none" width="16" height="16" stroke="var(--emerald)" stroke-width="2"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
            </span>
            <span style="font-size:11px; font-weight:700; color:var(--text-muted); text-transform:uppercase; letter-spacing:.08em;">DB Master</span>
        </div>
        <div style="font-size:28px; font-weight:800; color:var(--text-primary); letter-spacing:-0.04em; line-height:1; margin-bottom:6px;">
            {{ !empty($stats['db_master_total']) ? number_format($stats['db_master_total']) : '—' }}
        </div>
        <p style="font-size:12px; color:var(--text-muted); margin:0 0 12px;">Artículos en lista maestra</p>
        <div style="display:flex; flex-direction:column; gap:6px;">
            <div style="display:flex; align-items:center; justify-content:space-between; padding:5px 10px; background:rgba(16,185,129,.05); border-radius:6px; border:1px solid rgba(16,185,129,.1);">
                <span style="font-size:11px; color:var(--text-muted);">Último sync</span>
                <span style="font-size:11px; color:var(--emerald); font-weight:700;">{{ $stats['db_master_last_sync'] ?? 'Nunca' }}</span>
            </div>
            @if(!empty($stats['db_master_total']))
            <div style="display:flex; align-items:center; gap:6px; padding:4px 10px;">
                <span style="width:6px; height:6px; border-radius:50%; background:var(--emerald); box-shadow:0 0 6px var(--emerald);"></span>
                <span style="font-size:11px; color:var(--emerald);">Lista maestra activa</span>
            </div>
            @else
            <div style="display:flex; align-items:center; gap:6px; padding:4px 10px;">
                <span style="width:6px; height:6px; border-radius:50%; background:var(--rose);"></span>
                <span style="font-size:11px; color:var(--text-muted);">Sin sincronización aún</span>
            </div>
            @endif
        </div>
    </div>

</div>

{{-- Accesos rápidos (siempre visibles) --}}
<div style="margin-bottom:32px;">
    <p style="font-size:11px; font-weight:700; color:var(--text-muted); text-transform:uppercase; letter-spacing:.1em; margin-bottom:12px;">Accesos rápidos</p>
    <div style="display:grid; grid-template-columns:repeat(auto-fill, minmax(160px, 1fr)); gap:10px;">
        @php
        $shortcuts = [
            ['label' => 'Catálogo',        'desc' => 'Ver artículos',        'route' => 'articulos.index',     'color' => '#8b5cf6', 'icon' => '<path d="M21 12c0 1.66-4 3-9 3s-9-1.34-9-3"/><path d="M3 5v14c0 1.66 4 3 9 3s9-1.34 9-3V5"/><ellipse cx="12" cy="5" rx="9" ry="3"/>'],
            ['label' => 'Homologación',    'desc' => 'Comparar sucursales',  'route' => 'homologacion.index',  'color' => '#0ea5e9', 'icon' => '<polyline points="22 12 18 12 15 21 9 3 6 12 2 12"/>'],
            ['label' => 'DB Master',       'desc' => 'Lista maestra',        'route' => 'db_master.index',     'color' => '#10b981', 'icon' => '<path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/>'],
            ['label' => 'Estadísticas',    'desc' => 'Análisis global',      'route' => 'estadisticas.index',  'color' => '#f59e0b', 'icon' => '<line x1="18" y1="20" x2="18" y2="10"/><line x1="12" y1="20" x2="12" y2="4"/><line x1="6" y1="20" x2="6" y2="14"/>'],
            ['label' => 'Usuarios',        'desc' => 'Gestionar usuarios',   'route' => 'usuarios.index',      'color' => '#f43f5e', 'icon' => '<path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/>'],
            ['label' => 'Conexiones',      'desc' => 'Bases de datos',       'route' => 'conexiones.index',    'color' => '#06b6d4', 'icon' => '<path d="M5 12.55a11 11 0 0 1 14.08 0"/><path d="M1.42 9a16 16 0 0 1 21.16 0"/><circle cx="12" cy="20" r="1" fill="currentColor"/>'],
            ['label' => 'Roles',           'desc' => 'Permisos y accesos',   'route' => 'roles.index',         'color' => '#a855f7', 'icon' => '<path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/><path d="M9 12l2 2 4-4"/>'],
            ['label' => 'Descargas',       'desc' => 'Exportaciones',        'route' => 'descargas.index',     'color' => '#64748b', 'icon' => '<path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/>'],
        ];
        @endphp
        @foreach($shortcuts as $sc)
        <a href="{{ route($sc['route']) }}" style="background:var(--bg-card); border:1px solid var(--border); border-radius:12px; padding:14px 16px; text-decoration:none; display:flex; flex-direction:column; gap:10px; transition:all .2s; cursor:pointer;"
           onmouseover="this.style.borderColor='{{ $sc['color'] }}44'; this.style.transform='translateY(-2px)';"
           onmouseout="this.style.borderColor='var(--border)'; this.style.transform='none';">
            <span style="width:30px; height:30px; border-radius:8px; background:{{ $sc['color'] }}18; display:flex; align-items:center; justify-content:center; flex-shrink:0;">
                <svg viewBox="0 0 24 24" fill="none" width="15" height="15" stroke="{{ $sc['color'] }}" stroke-width="2">{!! $sc['icon'] !!}</svg>
            </span>
            <div>
                <div style="font-size:13px; font-weight:700; color:var(--text-primary);">{{ $sc['label'] }}</div>
                <div style="font-size:11px; color:var(--text-muted); margin-top:2px;">{{ $sc['desc'] }}</div>
            </div>
        </a>
        @endforeach
    </div>
</div>
@endif

@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    @if(!empty($stats) && !empty($branchesArr))

    // ── Doughnut distribución ──────────────────────────────────────────────
    const ctxDist = document.getElementById('distChart')?.getContext('2d');
    if (ctxDist) {
        new Chart(ctxDist, {
            type: 'doughnut',
            data: {
                labels: ['En Todas', 'Casi Todas', 'Parcial', 'Baja', 'Sin Cobertura'],
                datasets: [{
                    data: [
                        {{ $stats['en_todas']   ?? 0 }},
                        {{ $stats['casi_todas'] ?? 0 }},
                        {{ $stats['parcial']    ?? 0 }},
                        {{ $stats['baja']       ?? 0 }},
                        {{ $stats['en_ninguna'] ?? 0 }}
                    ],
                    backgroundColor: ['#10b981','#3b82f6','#f59e0b','#ec4899','#64748b'],
                    borderWidth: 0,
                    hoverOffset: 6,
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                cutout: '72%',
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        callbacks: {
                            label: ctx => ` ${ctx.label}: ${ctx.parsed.toLocaleString()}`
                        }
                    }
                }
            }
        });
    }

    // ── Bar Sucursales ─────────────────────────────────────────────────────
    const ctxBranch = document.getElementById('branchChart')?.getContext('2d');
    if (ctxBranch) {
        const branchLabels = {!! json_encode(array_keys($cols ?? [])) !!};
        const branchValues = {!! json_encode(array_values($cols ?? [])) !!};
        const total        = {{ $stats['total'] ?? 1 }};
        const barColors    = branchValues.map(v => {
            const p = (v / total) * 100;
            return p > 80 ? 'rgba(16,185,129,.75)' : p > 50 ? 'rgba(245,158,11,.75)' : 'rgba(244,63,94,.75)';
        });

        let grad = ctxBranch.createLinearGradient(0, 0, 0, 280);
        grad.addColorStop(0, 'rgba(139,92,246,.85)');
        grad.addColorStop(1, 'rgba(99,102,241,.3)');

        new Chart(ctxBranch, {
            type: 'bar',
            data: {
                labels: branchLabels,
                datasets: [{
                    label: 'Homologados',
                    data: branchValues,
                    backgroundColor: barColors,
                    borderRadius: 5,
                    borderSkipped: false,
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { display: false } },
                scales: {
                    y: {
                        beginAtZero: true,
                        grid: { color: 'rgba(255,255,255,.04)' },
                        ticks: { color: '#64748b', font: { size: 10 } }
                    },
                    x: {
                        grid: { display: false },
                        ticks: { color: '#94a3b8', font: { size: 9 }, maxRotation: 45, minRotation: 30 }
                    }
                }
            }
        });
    }
    @endif
});
</script>
@endpush
