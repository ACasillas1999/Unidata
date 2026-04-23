@extends('layouts.app')

@section('title', 'Estadísticas Globales · Portal Central')
@section('breadcrumb', 'Estadísticas')

@push('page-content-style')
    .page-content { overflow-y: auto !important; }

    /* ── Scrollbars ──────────────────────────────────────────────────────── */
    .ps::-webkit-scrollbar { height:5px; width:5px; }
    .ps::-webkit-scrollbar-track { background:rgba(255,255,255,.04); border-radius:10px; }
    .ps::-webkit-scrollbar-thumb { background:linear-gradient(135deg,#8b5cf6,#6366f1); border-radius:10px; }
    .ps::-webkit-scrollbar-thumb:hover { background:linear-gradient(135deg,#a78bfa,#818cf8); }

    /* ── Tablas ──────────────────────────────────────────────────────────── */
    .et { width:100%; border-collapse:collapse; }
    .et th { padding:9px 14px; text-align:left; font-size:10px; font-weight:800;
        color:var(--text-muted); text-transform:uppercase; letter-spacing:.08em;
        border-bottom:1px solid var(--border); white-space:nowrap; background:rgba(255,255,255,.01); }
    .et td { padding:8px 14px; font-size:12px; border-bottom:1px solid rgba(255,255,255,.04); }
    .et tbody tr { transition:background .15s; cursor:default; }
    .et tbody tr:hover { background:rgba(255,255,255,.03); }
    .et td.num { text-align:right; font-weight:700; }
    .et td.pct { text-align:right; font-weight:800; }

    /* ── Panel header ────────────────────────────────────────────────────── */
    .ph { padding:12px 18px; border-bottom:1px solid var(--border);
        background:rgba(255,255,255,.02); display:flex; align-items:center;
        justify-content:space-between; gap:8px; }
    .ph h3 { font-size:11px; font-weight:800; text-transform:uppercase;
        letter-spacing:.1em; color:var(--text-primary); margin:0; }
    .ph span { font-size:11px; color:var(--text-muted); }

    /* ── Badges ──────────────────────────────────────────────────────────── */
    .b { display:inline-flex; align-items:center; padding:1px 8px;
        border-radius:20px; font-size:10px; font-weight:700; }
    .bg  { background:rgba(16,185,129,.12); color:var(--emerald); }
    .ba  { background:rgba(245,158,11,.12); color:var(--amber); }
    .br  { background:rgba(244,63,94,.12);  color:var(--rose); }
    .bs  { background:rgba(100,116,139,.12);color:#94a3b8; }
    .bv  { background:rgba(139,92,246,.12); color:var(--violet-light); }
    .bb  { background:rgba(59,130,246,.12); color:#60a5fa; }

    /* ── Tabs ────────────────────────────────────────────────────────────── */
    .tab-nav { display:flex; gap:2px; padding:10px 14px; border-bottom:1px solid var(--border); flex-wrap:wrap; }
    .tab-btn { padding:5px 14px; border-radius:8px; font-size:11px; font-weight:700;
        color:var(--text-muted); cursor:pointer; border:none; background:transparent;
        transition:all .2s; }
    .tab-btn.active, .tab-btn:hover { background:rgba(139,92,246,.15); color:var(--violet-light); }
    .tab-panel { display:none; }
    .tab-panel.active { display:block; }
@endpush

@section('content')

{{-- ── Header ──────────────────────────────────────────────────────────────── --}}
<div style="display:flex; align-items:center; justify-content:space-between; margin-bottom:24px; flex-shrink:0;">
    <div>
        <h1 style="font-size:22px; font-weight:800; color:var(--text-primary); margin:0; letter-spacing:-0.03em; display:flex; align-items:center; gap:12px;">
            <span style="width:38px; height:38px; background:linear-gradient(135deg,#f59e0b,#f97316); border-radius:10px; display:flex; align-items:center; justify-content:center; box-shadow:0 0 18px rgba(245,158,11,.35); flex-shrink:0;">
                <svg viewBox="0 0 24 24" fill="none" width="20" height="20" stroke="white" stroke-width="2">
                    <line x1="18" y1="20" x2="18" y2="10"/><line x1="12" y1="20" x2="12" y2="4"/><line x1="6" y1="20" x2="6" y2="14"/>
                </svg>
            </span>
            Estadísticas Globales
        </h1>
        <p style="font-size:13px; color:var(--text-muted); margin:5px 0 0 50px;">
            Análisis completo de cobertura, inventario y distribución · {{ now()->format('d M Y, H:i') }}
        </p>
    </div>
    <a href="{{ route('homologacion.index') }}" style="display:flex; align-items:center; gap:8px; background:rgba(245,158,11,.1); border:1px solid rgba(245,158,11,.25); color:#fbbf24; border-radius:10px; padding:9px 16px; font-size:13px; font-weight:700; text-decoration:none;">
        <svg viewBox="0 0 24 24" fill="none" width="14" height="14" stroke="currentColor" stroke-width="2.5"><path d="M21 2v6h-6"/><path d="M3 12a9 9 0 0 1 15-6.7L21 8"/><path d="M3 22v-6h6"/><path d="M21 12a9 9 0 0 1-15 6.7L3 16"/></svg>
        Sincronizar datos
    </a>
</div>

@if($error)
<div style="margin-bottom:20px; border-left:4px solid var(--rose); padding:12px 16px; background:rgba(244,63,94,.08); color:var(--rose); border-radius:8px; font-size:13px;">⚠️ {{ $error }}</div>
@endif

@if(!empty($stats))
@php
    $cols     = $stats['branch_coverage'] ?? [];
    $totalB   = $stats['total_branches']  ?? 0;
    $universo = $stats['universo']        ?? 0;
    $inv      = $stats['inventario']      ?? [];
@endphp

{{-- ══════════════════════════════════════════════════════════════════════════
     FILA 1 — KPIs GLOBALES (8 tarjetas)
══════════════════════════════════════════════════════════════════════════ --}}
<div style="display:grid; grid-template-columns:repeat(6,1fr); gap:10px; margin-bottom:20px;">
@php
$avance = $stats['avance_homologacion'] ?? ['pct'=>0, 'diferencia'=>0];
$kpis = [
    ['l'=>'Total Sucursales', 'v'=> number_format($universo),                    'c'=>'#8b5cf6', 'i'=>'<ellipse cx="12" cy="5" rx="9" ry="3"/><path d="M3 5v14c0 1.66 4 3 9 3s9-1.34 9-3V5"/><path d="M3 12c0 1.66 4 3 9 3s9-1.34 9-3"/>', 's'=>'HABILITADOS E INHABILITADOS EN TODAS LAS SUCURSALES'],
    ['l'=>'Habilitados EN SUCURSALES',     'v'=> number_format($inv['habilitados']??0),         'c'=>'#0ea5e9', 'i'=>'<path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/><path d="M9 12l2 2 4-4"/>', 's'=>'SOLO HABILITADOS EN TODAS LAS SUCURSALES'],
    ['l'=>'Activo en Todas', 'v'=> number_format($stats['en_todas']??0),          'c'=>'#10b981', 'i'=>'<polyline points="20 6 9 17 4 12"/>', 's'=>'SOLO HABILITADOS EN TODAS LAS SUCURSALES'],
    ['l'=>'Inhabilitados',   'v'=> number_format($inv['deshabilitados']??0),      'c'=>'#f43f5e', 'i'=>'<circle cx="12" cy="12" r="10"/><line x1="15" y1="9" x2="9" y2="15"/><line x1="9" y1="9" x2="15" y2="15"/>', 's'=>'INHABILITADOS E INEXISTENTES EN TODAS LAS SUCURSALES'],
    ['l'=>'Avance HOMOLOGACION',   'v'=> ($avance['pct']).'%',                        'c'=>'#fbbf24', 'i'=>'<path d="M12 20v-6M9 20v-10M15 20v-2M18 20v-8M21 20v-4M6 20v-12M3 20v-3"/><path d="M3 10l6-6 4 4 8-8"/>', 's' => number_format($avance['diferencia']).' pendientes'],
    ['l'=>'Cobertura Global','v'=> ($stats['cobertura_global_pct']??0).'%',      'c'=> ($stats['cobertura_global_pct']??0)>=60?'#10b981':(($stats['cobertura_global_pct']??0)>=30?'#f59e0b':'#f43f5e'), 'i'=>'<polyline points="22 12 18 12 15 21 9 3 6 12 2 12"/>', 's'=>'IGUALDAD DE TODAS LAS SUCURSALES'],
];
@endphp
@foreach($kpis as $k)
<div style="background:var(--bg-card); border:1px solid var(--border); border-radius:12px; padding:14px 14px; position:relative; overflow:hidden;">
    <div style="position:absolute; top:-14px; right:-14px; width:56px; height:56px; background:{{ $k['c'] }}10; border-radius:50%;"></div>
    <div style="width:26px; height:26px; background:{{ $k['c'] }}18; border-radius:7px; display:flex; align-items:center; justify-content:center; margin-bottom:9px;">
        <svg viewBox="0 0 24 24" fill="none" width="13" height="13" stroke="{{ $k['c'] }}" stroke-width="2">{!! $k['i'] !!}</svg>
    </div>
    <div style="font-size:20px; font-weight:800; color:{{ $k['c'] }}; letter-spacing:-0.04em; line-height:1;">{{ $k['v'] }}</div>
    <div style="font-size:10px; font-weight:700; color:var(--text-muted); margin-top:4px; text-transform:uppercase; letter-spacing:.06em;">{{ $k['l'] }}</div>
    @if(isset($k['s']))
    <div style="font-size:9px; color:{{ $k['c'] }}; opacity:0.8; margin-top:2px; font-weight:600;">{{ $k['s'] }}</div>
    @endif
</div>
@endforeach
</div>

{{-- ══════════════════════════════════════════════════════════════════════════
     FILA 2 — GRÁFICA 1 (Doughnut distribución) + GRÁFICA 2 (Bar sucursales)
══════════════════════════════════════════════════════════════════════════ --}}
<div style="display:grid; grid-template-columns:340px 1fr; gap:14px; margin-bottom:14px;">

    {{-- Gráfica 1 — Doughnut distribución cobertura --}}
    <div style="background:var(--bg-card); border:1px solid var(--border); border-radius:14px; padding:16px 18px;">
        <div class="ph" style="padding:0 0 12px; border:none; background:none; margin-bottom:4px;">
            <h3>Distribución de Cobertura</h3>
        </div>
        <div style="position:relative; height:180px;"><canvas id="chart1Dist"></canvas></div>
        <div style="display:grid; grid-template-columns:1fr 1fr; gap:5px; margin-top:12px;">
            @foreach([['En todas','#10b981',$stats['en_todas']??0],['Casi todas','#3b82f6',$stats['casi_todas']??0],['Parcial','#f59e0b',$stats['parcial']??0],['Baja','#ec4899',$stats['baja']??0],['Sin cob.','#64748b',$stats['en_ninguna']??0]] as $di)
            <div style="display:flex; align-items:center; gap:5px;">
                <span style="width:7px; height:7px; border-radius:2px; background:{{ $di[1] }}; flex-shrink:0;"></span>
                <span style="font-size:10px; color:var(--text-muted);">{{ $di[0] }}: <b style="color:var(--text-primary);">{{ number_format($di[2]) }}</b></span>
            </div>
            @endforeach
        </div>
    </div>

    {{-- Gráfica 2 — Bar cobertura comparativa sucursales --}}
    <div style="background:var(--bg-card); border:1px solid var(--border); border-radius:14px; padding:16px 18px;">
        <div class="ph" style="padding:0 0 12px; border:none; background:none;">
            <h3>Comparativa de Cobertura por Sucursal</h3>
            <span>artículos homologados</span>
        </div>
        <div style="position:relative; height:215px;"><canvas id="chart2Branch"></canvas></div>
    </div>
</div>

{{-- ══════════════════════════════════════════════════════════════════════════
     FILA 3 — GRÁFICA 3 (Radar sucursales) + GRÁFICA 4 (Horizontal bar líneas)
══════════════════════════════════════════════════════════════════════════ --}}
<div style="display:grid; grid-template-columns:1fr 1fr; gap:14px; margin-bottom:14px;">

    {{-- Gráfica 3 — Radar: % cobertura por sucursal --}}
    <div style="background:var(--bg-card); border:1px solid var(--border); border-radius:14px; padding:16px 18px;">
        <div class="ph" style="padding:0 0 12px; border:none; background:none;">
            <h3>Radar — % Cobertura por Sucursal</h3>
            <span>relativo al universo total</span>
        </div>
        <div style="position:relative; height:260px;"><canvas id="chart3Radar"></canvas></div>
    </div>

    {{-- Gráfica 4 — Histograma de presencias --}}
    <div style="background:var(--bg-card); border:1px solid var(--border); border-radius:14px; padding:16px 18px;">
        <div class="ph" style="padding:0 0 12px; border:none; background:none;">
            <h3>Histograma de Presencias</h3>
            <span>artículos que aparecen en exactamente N sucursales</span>
        </div>
        <div style="position:relative; height:260px;"><canvas id="chart4Hist"></canvas></div>
    </div>
</div>

{{-- ══════════════════════════════════════════════════════════════════════════
     FILA 4 — GRÁFICA 5 (Top líneas) + Score salud sucursales
══════════════════════════════════════════════════════════════════════════ --}}
<div style="display:grid; grid-template-columns:1fr 1fr; gap:14px; margin-bottom:20px;">

    {{-- Gráfica 5 — Top 10 Líneas horizontalbar --}}
    <div style="background:var(--bg-card); border:1px solid var(--border); border-radius:14px; padding:16px 18px;">
        <div class="ph" style="padding:0 0 12px; border:none; background:none;">
            <h3>Top 10 Líneas de Producto</h3>
            <span>por cantidad de artículos</span>
        </div>
        <div style="position:relative; height:260px;"><canvas id="chart5Lineas"></canvas></div>
    </div>

    {{-- Score de salud por sucursal --}}
    <div style="background:var(--bg-card); border:1px solid var(--border); border-radius:14px; overflow:hidden;">
        <div class="ph">
            <h3>PROGRESO DE HOMOLOGACION POR SUCURSAL</h3>
            <span>artículos / universo</span>
        </div>
        @php $hCols = (int)ceil(count($stats['health_scores']??[]) / 2); @endphp
        <div style="padding:14px 16px;">
            <div style="display:grid; grid-template-columns:repeat({{ max($hCols,1) }},1fr); grid-template-rows:repeat(2,auto); grid-auto-flow:column; gap:8px;">
            @foreach($stats['health_scores']??[] as $bName => $hs)
            @php
                $sc = match($hs['score']){
                    'excelente'=>'#10b981','bueno'=>'#3b82f6','regular'=>'#f59e0b',default=>'#f43f5e'};
                $sl = match($hs['score']){
                    'excelente'=>'Excelente','bueno'=>'Bueno','regular'=>'Regular',default=>'Bajo'};
            @endphp
            <div style="background:rgba(255,255,255,.02); border:1px solid var(--border); border-radius:9px; padding:10px 12px; transition:transform .2s,border-color .2s;"
                 onmouseover="this.style.transform='translateY(-2px)'; this.style.borderColor='{{ $sc }}44';"
                 onmouseout="this.style.transform='none'; this.style.borderColor='var(--border)';">
                <p style="font-size:9px; font-weight:700; color:var(--text-muted); text-transform:uppercase; letter-spacing:.05em; margin-bottom:5px; white-space:nowrap; overflow:hidden; text-overflow:ellipsis;">{{ $bName }}</p>
                <div style="display:flex; align-items:baseline; gap:4px; margin-bottom:6px;">
                    <span style="font-size:16px; font-weight:800; color:var(--text-primary);">{{ number_format($hs['count']) }}</span>
                    <span style="font-size:11px; font-weight:800; color:{{ $sc }};">{{ $hs['pct'] }}%</span>
                </div>
                <span class="b" style="background:{{ $sc }}18; color:{{ $sc }}; font-size:9px;">{{ $sl }}</span>
                <div style="height:3px; background:rgba(255,255,255,.05); border-radius:10px; overflow:hidden; margin-top:7px;">
                    <div style="height:100%; width:{{ $hs['pct'] }}%; background:{{ $sc }}; border-radius:10px;"></div>
                </div>
            </div>
            @endforeach
            </div>
        </div>
    </div>
</div>

{{-- ══════════════════════════════════════════════════════════════════════════
     FILA 5 — Métricas de Inventario Global (barra horizontal)
══════════════════════════════════════════════════════════════════════════ --}}
<div style="background:var(--bg-card); border:1px solid var(--border); border-radius:14px; overflow:hidden; margin-bottom:20px;">
    <div class="ph"><h3>Inventario & Catálogo Global</h3></div>
    <div style="display:grid; grid-template-columns:repeat(6,1fr); gap:0;">
    @php
    $invKpis = [
        ['l'=>'Exist. Teórica',  'v'=> number_format($inv['existencia_teorica']??0,0), 'c'=>'#8b5cf6', 's'=>'unidades planeadas'],
        ['l'=>'Exist. Física',   'v'=> number_format($inv['existencia_fisica']??0,0),  'c'=>'#10b981', 's'=>'unidades en almacén'],
        ['l'=>'Costo Prom.',     'v'=> '$'.number_format($inv['costo_prom_global']??0,2),'c'=>'#f59e0b','s'=>'costo unitario medio'],
        ['l'=>'Valor Catálogo',  'v'=> '$'.number_format($inv['valor_lista_total']??0,0),'c'=>'#0ea5e9','s'=>'suma precio lista'],
        ['l'=>'Kits',            'v'=> number_format($inv['kits_count']??0),            'c'=>'#a855f7', 's'=>'artículos kit'],
        ['l'=>'Con Sustituto',   'v'=> number_format($inv['con_sustituto']??0),         'c'=>'#06b6d4', 's'=>'tienen sustituto'],
    ];
    @endphp
    @foreach($invKpis as $idx => $ik)
    <div style="padding:16px 18px; {{ $idx < count($invKpis)-1 ? 'border-right:1px solid var(--border);' : '' }}">
        <div style="font-size:9px; font-weight:700; color:var(--text-muted); text-transform:uppercase; letter-spacing:.08em; margin-bottom:7px;">{{ $ik['l'] }}</div>
        <div style="font-size:18px; font-weight:800; color:{{ $ik['c'] }}; letter-spacing:-0.03em;">{{ $ik['v'] }}</div>
        <div style="font-size:10px; color:var(--text-muted); margin-top:3px;">{{ $ik['s'] }}</div>
    </div>
    @endforeach
    </div>
</div>

{{-- ══════════════════════════════════════════════════════════════════════════
     FILAS 6-8 — 6 TABLAS (en pestañas agrupadas)
══════════════════════════════════════════════════════════════════════════ --}}

{{-- Fila A: 2 tablas lado a lado --}}
<div style="display:grid; grid-template-columns:1fr 1fr; gap:14px; margin-bottom:14px;">
    {{-- Tabla 1 — Cobertura por Línea --}}
    <div style="background:var(--bg-card); border:1px solid var(--border); border-radius:14px; overflow:hidden;">
        <div class="ph">
            <h3>Cobertura por Línea de Producto</h3>
            <span>{{ count($stats['por_linea']??[]) }} líneas</span>
        </div>
        <div class="ps" style="overflow-y:auto; max-height:290px;">
            <table class="et">
                <thead><tr>
                    <th>Línea</th>
                    <th class="num">Arts.</th>
                    <th class="num">Hab.</th>
                    <th class="pct">Cob.</th>
                    <th style="width:70px;">Bar</th>
                </tr></thead>
                <tbody>
                @foreach($stats['por_linea']??[] as $row)
                @php $lc = $row['pct']>=70?'#10b981':($row['pct']>=40?'#f59e0b':'#f43f5e'); @endphp
                <tr>
                    <td style="font-weight:600; color:var(--text-primary); max-width:130px; overflow:hidden; text-overflow:ellipsis; white-space:nowrap;" title="{{ $row['linea'] }}">{{ $row['linea'] }}</td>
                    <td class="num" style="color:var(--text-secondary);">{{ number_format($row['total']) }}</td>
                    <td class="num" style="color:#0ea5e9;">{{ number_format($row['habilitados']??0) }}</td>
                    <td class="pct" style="color:{{ $lc }};">{{ $row['pct'] }}%</td>
                    <td><div style="height:4px; background:rgba(255,255,255,.05); border-radius:10px; overflow:hidden;"><div style="height:100%; width:{{ min($row['pct'],100) }}%; background:{{ $lc }}; border-radius:10px;"></div></div></td>
                </tr>
                @endforeach
                </tbody>
            </table>
        </div>
    </div>

    {{-- Tabla 3 — Discrepancias: DB Master -> Matriz --}}
    <div style="background:var(--bg-card); border:1px solid var(--border); border-radius:14px; overflow:hidden;">
        <div class="ph" style="border-bottom-color:rgba(244,63,94,.2); background:rgba(244,63,94,.04);">
            <div style="display:flex; align-items:center; gap:6px;">
                <span style="width:6px; height:6px; background:var(--rose); border-radius:50%; box-shadow:0 0 5px var(--rose);"></span>
                <h3>Faltantes en Matriz</h3>
            </div>
            <span>en master pero ausentes en homologación</span>
        </div>
        <div class="ps" style="overflow-y:auto; max-height:290px;">
            <table class="et">
                <thead><tr>
                    <th>Clave</th>
                    <th>Descripción</th>
                    <th>Línea</th>
                    <th class="num">P. Lista</th>
                </tr></thead>
                <tbody>
                @forelse($stats['missing_in_matrix']??[] as $art)
                <tr>
                    <td style="font-weight:700; color:var(--rose); white-space:nowrap;">{{ $art['clave'] }}</td>
                    <td style="color:var(--text-secondary); max-width:140px; overflow:hidden; text-overflow:ellipsis; white-space:nowrap;" title="{{ $art['descripcion'] }}">{{ $art['descripcion'] }}</td>
                    <td><span class="b bs">{{ Str::limit($art['linea']??'—',12) }}</span></td>
                    <td class="num" style="color:var(--text-primary); white-space:nowrap;">${{ number_format($art['precio_lista']??0,2) }}</td>
                </tr>
                @empty<tr><td colspan="4" style="text-align:center; color:var(--text-muted); padding:18px;">Matriz 100% sincera con catálogo master 🎉</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

{{-- Fila B: 2 tablas lado a lado --}}
<div style="display:grid; grid-template-columns:1fr 1fr; gap:14px; margin-bottom:14px;">
    {{-- Tabla 4 — Ranking de Sucursales (Salud de Cobertura) --}}
    <div style="background:var(--bg-card); border:1px solid var(--border); border-radius:14px; overflow:hidden;">
        <div class="ph" style="border-bottom-color:rgba(16,185,129,.2); background:rgba(16,185,129,.04);">
            <div style="display:flex; align-items:center; gap:6px;">
                <span style="width:6px; height:6px; background:#10b981; border-radius:50%; box-shadow:0 0 5px #10b981;"></span>
                <h3>Ranking de Sucursales</h3>
            </div>
            <span>por volumen de artículos</span>
        </div>
        <div class="ps" style="overflow-y:auto; max-height:290px;">
            <table class="et">
                <thead><tr>
                    <th>#</th>
                    <th>Sucursal</th>
                    <th class="num">Artículos</th>
                    <th class="num">Faltantes</th>
                    <th class="pct">Score</th>
                </tr></thead>
                <tbody>
                @forelse($stats['ranking_sucursales']??[] as $rank)
                @php
                    $rc = match($rank['score']){'excelente'=>'#10b981','bueno'=>'#3b82f6','regular'=>'#f59e0b',default=>'#f43f5e'};
                @endphp
                <tr>
                    <td style="font-weight:800; color:var(--text-muted);">{{ $rank['posicion'] }}</td>
                    <td style="font-weight:700; color:var(--text-primary); white-space:nowrap;">{{ $rank['sucursal'] }}</td>
                    <td class="num" style="color:var(--text-primary);">{{ number_format($rank['count']) }}</td>
                    <td class="num" style="color:var(--rose);">{{ number_format($rank['faltantes']) }}</td>
                    <td class="pct" style="color:{{ $rc }};">{{ $rank['pct'] }}%</td>
                </tr>
                @empty<tr><td colspan="5" style="text-align:center; color:var(--text-muted); padding:18px;">Sin datos</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- Tabla 6 — Distribución de Cobertura (Detalle) --}}
    <div style="background:var(--bg-card); border:1px solid var(--border); border-radius:14px; overflow:hidden;">
        <div class="ph" style="border-bottom-color:rgba(139,92,246,.2); background:rgba(139,92,246,.04);">
            <div style="display:flex; align-items:center; gap:6px;">
                <span style="width:6px; height:6px; background:#8b5cf6; border-radius:50%; box-shadow:0 0 5px #8b5cf6;"></span>
                <h3>Detalle de Matriz por Cobertura</h3>
            </div>
            <span>frecuencia exacta de artículos</span>
        </div>
        <div class="ps" style="overflow-y:auto; max-height:290px;">
            <table class="et">
                <thead><tr>
                    <th>Frecuencia</th>
                    <th class="num">Artículos</th>
                    <th class="pct">% del Total</th>
                    <th style="width:60px;">Bar</th>
                </tr></thead>
                <tbody>
                @forelse($stats['distribucion_detalle']??[] as $dist)
                @if($dist['articulos'] == 0 && $dist['sucursales'] != $totalB && $dist['sucursales'] != 0)
                    @continue
                @endif
                @php
                    $dc = $dist['sucursales'] == $totalB ? '#10b981' : ($dist['sucursales'] == 0 ? '#64748b' : '#3b82f6');
                @endphp
                <tr>
                    <td style="font-weight:700; color:var(--text-primary); white-space:nowrap;">
                        {{ $dist['sucursales'] == $totalB ? 'Todas ('.$totalB.')' : ($dist['sucursales'] == 0 ? 'Ninguna' : $dist['sucursales'].' sucursales') }}
                    </td>
                    <td class="num" style="color:var(--text-primary);">{{ number_format($dist['articulos']) }}</td>
                    <td class="pct" style="color:{{ $dc }};">{{ $dist['pct'] }}%</td>
                    <td>
                        <div style="height:4px; background:rgba(255,255,255,.05); border-radius:10px; overflow:hidden;">
                            <div style="height:100%; width:{{ $dist['pct'] }}%; background:{{ $dc }}; border-radius:10px;"></div>
                        </div>
                    </td>
                </tr>
                @empty<tr><td colspan="4" style="text-align:center; color:var(--text-muted); padding:18px;">Sin datos</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>





{{-- ══════════════════════════════════════════════════════════════════════════
     FILA FINAL — Matriz de Salud Global Completa
══════════════════════════════════════════════════════════════════════════ --}}
<div style="background:var(--bg-card); border:1px solid var(--border); border-radius:14px; overflow:hidden; margin-bottom:32px;">
    <div class="ph">
        <h3>Matriz de Salud Global Completa</h3>
        <span>clic en fila → ver en Homologación</span>
    </div>
    <table class="et">
        <thead><tr>
            <th>Aparece en</th>
            <th class="num">Artículos</th>
            <th class="num">Del universo</th>
            <th style="text-align:center;">Estado</th>
            <th>Distribución visual</th>
        </tr></thead>
        <tbody>
        @for($i = $totalB; $i >= 0; $i--)
        @php
            $cnt2 = $stats['distribucion'][$i] ?? 0;
            if ($cnt2 === 0 && $i !== $totalB && $i !== 0) continue;
            $pct2 = $universo > 0 ? round(($cnt2 / $universo) * 100, 1) : 0;
            if ($i === $totalB) {
                $rc='#10b981'; $rb='rgba(16,185,129,.05)'; $rl='Excelente'; $rcls='bg';
            } elseif ($i === 0) {
                $rc='#64748b'; $rb='transparent'; $rl='Sin cob.'; $rcls='bs';
            } else {
                $p2 = ($i/$totalB)*100;
                if ($p2>=80)     { $rc='#10b981'; $rb='rgba(16,185,129,.03)'; $rl='Bueno';   $rcls='bg'; }
                elseif ($p2>=30) { $rc='#f59e0b'; $rb='rgba(245,158,11,.03)'; $rl='Parcial'; $rcls='ba'; }
                else             { $rc='#f43f5e'; $rb='rgba(244,63,94,.03)';  $rl='Bajo';    $rcls='br'; }
            }
        @endphp
        <tr style="background:{{ $rb }}; cursor:pointer;"
            onclick="window.open('{{ route('homologacion.index', ['exact' => $i]) }}', '_blank')"
            onmouseover="this.style.opacity='.75'" onmouseout="this.style.opacity='1'">
            <td style="font-weight:700; color:{{ $rc }};">
                {{ $i === $totalB ? "$i Sucursales (Todas)" : ($i === 0 ? "0 Sucursales (Ninguna)" : "$i Sucursales") }}
                <svg style="margin-left:4px; vertical-align:middle; opacity:.4;" viewBox="0 0 24 24" fill="none" width="10" height="10" stroke="currentColor" stroke-width="2"><path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"/><polyline points="15 3 21 3 21 9"/><line x1="10" y1="14" x2="21" y2="3"/></svg>
            </td>
            <td class="num" style="color:var(--text-primary);">{{ number_format($cnt2) }}</td>
            <td class="num" style="color:var(--text-muted);">{{ $pct2 }}%</td>
            <td style="text-align:center;"><span class="b b{{ $rcls }}">{{ $rl }}</span></td>
            <td>
                <div style="height:5px; width:100%; max-width:220px; background:rgba(255,255,255,.05); border-radius:10px; overflow:hidden;">
                    <div style="height:100%; width:{{ min($pct2,100) }}%; background:{{ $rc }}; border-radius:10px;"></div>
                </div>
            </td>
        </tr>
        @endfor
        </tbody>
    </table>
</div>

{{-- Modal global de información sobre estadísticas --}}
<div id="infoModalOverlay" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.6); backdrop-filter:blur(4px); z-index:9999; align-items:center; justify-content:center;">
    <div id="infoModal" style="background:var(--bg-card); border:1px solid var(--border); border-radius:16px; width:400px; max-width:90%; padding:24px; box-shadow:0 10px 40px rgba(0,0,0,0.5); transform:scale(0.95); transition:transform 0.2s; position:relative;">
        <button onclick="this.closest('#infoModalOverlay').style.display='none'; document.getElementById('infoModal').style.transform='scale(0.95)';" style="position:absolute; top:16px; right:16px; background:none; border:none; color:var(--text-muted); cursor:pointer; padding:4px;" onmouseover="this.style.color='var(--text-primary)'" onmouseout="this.style.color='var(--text-muted)'">
            <svg viewBox="0 0 24 24" fill="none" width="18" height="18" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
        </button>
        <div style="display:flex; align-items:center; gap:12px; margin-bottom:16px;">
            <div style="width:40px; height:40px; border-radius:10px; background:rgba(139,92,246,0.1); color:var(--violet-light); display:flex; align-items:center; justify-content:center; flex-shrink:0;">
                <svg viewBox="0 0 24 24" fill="none" width="22" height="22" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="M12 16v-4"/><path d="M12 8h.01"/></svg>
            </div>
            <h2 id="infoModalTitle" style="font-size:16px; font-weight:800; color:var(--text-primary); margin:0; line-height:1.2;">Título</h2>
        </div>
        <p id="infoModalDesc" style="font-size:13px; color:var(--text-secondary); margin:0; line-height:1.6;">Descripción</p>
        <button onclick="this.closest('#infoModalOverlay').style.display='none'; document.getElementById('infoModal').style.transform='scale(0.95)';" style="margin-top:24px; width:100%; background:rgba(255,255,255,0.05); border:1px solid var(--border); color:var(--text-primary); border-radius:8px; padding:10px; font-size:13px; font-weight:700; cursor:pointer; transition:background 0.2s;" onmouseover="this.style.background='rgba(255,255,255,0.1)'" onmouseout="this.style.background='rgba(255,255,255,0.05)'">Entendido</button>
    </div>
</div>

@endif
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    @if(!empty($stats))

const fnt = { family: 'inherit', size: 10 };
    const gridC = 'rgba(255,255,255,.05)';
    const tickC = '#64748b';

    // ════════════════
    // Inyección dinámica de Iconos de Info
    // ════════════════
    const infoMap = {
        'Distribución de Cobertura': 'Muestra cómo se distribuyen los artículos del catálogo global según su nivel de presencia en las sucursales (En todas, Parcial, Baja cobertura o Ausentes).',
        'Comparativa de Cobertura por Sucursal': 'Visualiza el volumen total de artículos que posee cada sucursal de manera homologada. Permite detectar que sucursales tienen catálogos más completos.',
        'Radar — % Cobertura por Sucursal': 'Representa de forma radial la proporción del catálogo global que ha asimilado cada sucursal. Útil para identificar el "área" o alcance general de la red.',
        'Histograma de Presencias': 'Indica con qué frecuencia un artículo se repite en la misma cantidad de sucursales (ej. cuántos artículos únicos solo existen en 1 sucursal, cuántos existen en 2, etc).',
        'Top 10 Líneas de Producto': 'Clasifica las familias o líneas de producto principales basándose en la cantidad de artículos únicos vinculados.',
        'PROGRESO DE HOMOLOGACION POR SUCURSAL': 'Proporciona una calificación rápida del estado del inventario calculando el porcentaje del catálogo maestro presente en cada sucursal (Excelente, Bueno, Regular, Bajo).',
        'Inventario & Catálogo Global': 'Muestra indicadores aglomerados del conjunto del catálogo: valoración monetaria del inventario teórico listado y conteo de características especiales como promos y críticos.',
        'Cobertura por Línea de Producto': 'Evalúa porcentualmente la cobertura homologada de cada línea. Entre más alto, significa que los artículos de dicha línea están presentes en mayor número de sucursales.',
        'Cobertura por Clasificación': 'Desglosa la penetración en sucursales basándose en las clasificaciones internas de los artículos.',
        'Faltantes en Matriz': 'Artículos que existen oficialmente en el DB Master pero que no están sincronizados en la tabla de Homologación de esta instancia.',
        'Ranking de Sucursales': 'Clasifica a las sucursales ordenadas por el número artículos que tienen y detalla exactamente cuántos "slots" de productos únicos les faltan para tener el 100% de productos de la matriz.',
        'Detalle de Matriz por Cobertura': 'Una versión en tabla detallada sobre el histograma y radar, exponiendo métricas exactas del reparto de existencias multidependencia.',
        'Matriz de Salud Global Completa': 'Control maestro interactivo que agrupa a todos los artículos acorde a su redundancia. Cliquear en una fila abre automáticamente el Visualizador de Homologación con los artículos exactos listados.'
    };


    document.querySelectorAll('.ph').forEach(ph => {
        let titleEl = ph.querySelector('h3');
        if (!titleEl) return;
        let t = titleEl.textContent.trim();
        if (!infoMap[t]) return;
        
        ph.style.position = 'relative';
        ph.style.paddingRight = '45px'; // hacer espacio para el botón

        let btn = document.createElement('button');
        btn.innerHTML = '<svg viewBox="0 0 24 24" fill="none" width="13" height="13" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="M12 16v-4"/><path d="M12 8h.01"/></svg>';
        btn.style.cssText = 'position:absolute; right:15px; top:50%; transform:translateY(-50%); background:rgba(255,255,255,0.04); border:1px solid rgba(255,255,255,0.08); color:var(--text-muted); border-radius:50%; width:22px; height:22px; display:flex; align-items:center; justify-content:center; cursor:pointer; transition:all 0.2s; padding:0;';
        btn.title = "Información del panel";
        
        btn.onmouseover = () => { btn.style.background = 'rgba(255,255,255,0.12)'; btn.style.color = '#fff'; };
        btn.onmouseout = () => { btn.style.background = 'rgba(255,255,255,0.04)'; btn.style.color = 'var(--text-muted)'; };
        
        btn.onclick = (e) => {
            e.stopPropagation();
            document.getElementById('infoModalTitle').textContent = t;
            document.getElementById('infoModalDesc').textContent = infoMap[t];
            let overlay = document.getElementById('infoModalOverlay');
            let m = document.getElementById('infoModal');
            overlay.style.display = 'flex';
            setTimeout(() => m.style.transform = 'scale(1)', 10);
        };
        ph.appendChild(btn);
    });

    // ════════════════
    // Gráfica 1 — Doughnut distribución
    // ════════════════
    const c1 = document.getElementById('chart1Dist')?.getContext('2d');
    if (c1) {
        new Chart(c1, {
            type: 'doughnut',
            data: {
                labels: ['En Todas','Casi Todas','Parcial','Baja','Sin Cobertura'],
                datasets: [{ data: [{{ $stats['en_todas']??0 }},{{ $stats['casi_todas']??0 }},{{ $stats['parcial']??0 }},{{ $stats['baja']??0 }},{{ $stats['en_ninguna']??0 }}],
                    backgroundColor:['#10b981','#3b82f6','#f59e0b','#ec4899','#64748b'], borderWidth:0, hoverOffset:5 }]
            },
            options: { responsive:true, maintainAspectRatio:false, cutout:'70%',
                plugins:{ legend:{display:false}, tooltip:{ callbacks:{ label: c => ` ${c.label}: ${c.parsed.toLocaleString()}` }}}
            }
        });
    }

    // ════════════════
    // Gráfica 2 — Bar sucursales
    // ════════════════
    const c2 = document.getElementById('chart2Branch')?.getContext('2d');
    if (c2) {
        const bLabels = {!! json_encode(array_keys($cols??[])) !!};
        const bVals   = {!! json_encode(array_values($cols??[])) !!};
        const total   = {{ $stats['total']??1 }};
        const bColors = bVals.map(v => { const p=(v/total)*100; return p>80?'rgba(16,185,129,.8)':p>50?'rgba(245,158,11,.8)':'rgba(244,63,94,.8)'; });
        new Chart(c2, {
            type:'bar',
            data:{ labels:bLabels, datasets:[{ data:bVals, backgroundColor:bColors, borderRadius:5, borderSkipped:false }] },
            options:{ responsive:true, maintainAspectRatio:false,
                plugins:{ legend:{display:false}, tooltip:{ callbacks:{ label: c => ` ${c.parsed.y.toLocaleString()} arts (${Math.round(c.parsed.y/total*100)}%)` }}},
                scales:{
                    y:{ beginAtZero:true, grid:{color:gridC}, ticks:{color:tickC,font:fnt} },
                    x:{ grid:{display:false}, ticks:{color:'#94a3b8',font:{size:9},maxRotation:45,minRotation:30} }
                }
            }
        });
    }

    // ════════════════
    // Gráfica 3 — Radar sucursales
    // ════════════════
    const c3 = document.getElementById('chart3Radar')?.getContext('2d');
    if (c3) {
        const rLabels = {!! json_encode(array_keys($stats['radar_data']??[])) !!};
        const rVals   = {!! json_encode(array_values($stats['radar_data']??[])) !!};
        new Chart(c3, {
            type:'radar',
            data:{
                labels:rLabels,
                datasets:[{
                    label:'Cobertura %',
                    data:rVals,
                    backgroundColor:'rgba(139,92,246,.15)',
                    borderColor:'rgba(139,92,246,.7)',
                    pointBackgroundColor:'#8b5cf6',
                    pointBorderColor:'#fff',
                    pointRadius:4,
                    borderWidth:2,
                }]
            },
            options:{
                responsive:true, maintainAspectRatio:false,
                scales:{ r:{ min:0, max:100, beginAtZero:true,
                    grid:{color:'rgba(255,255,255,.08)'},
                    angleLines:{color:'rgba(255,255,255,.06)'},
                    pointLabels:{color:'#94a3b8',font:{size:9}},
                    ticks:{color:'#64748b',font:{size:8},backdropColor:'transparent',stepSize:20}
                }},
                plugins:{ legend:{display:false}, tooltip:{ callbacks:{ label: c => ` ${c.raw}%` }}}
            }
        });
    }

    // ════════════════
    // Gráfica 4 — Histograma de presencias
    // ════════════════
    const c4 = document.getElementById('chart4Hist')?.getContext('2d');
    if (c4) {
        const hLabels = {!! json_encode(array_map(fn($i) => "$i sucs", array_keys($stats['histograma']??[]))) !!};
        const hVals   = {!! json_encode(array_values($stats['histograma']??[])) !!};
        const maxV    = {{ $stats['total']??1 }};
        const hColors = hVals.map((v,i) => {
            const idx = {{ $totalB > 0 ? 'i' : '0' }};
            const total2 = {{ $totalB > 0 ? $totalB : 1 }};
            const p = (idx / total2) * 100;
            return idx === 0 ? 'rgba(100,116,139,.7)' : idx === total2 ? 'rgba(16,185,129,.8)' : p >= 80 ? 'rgba(59,130,246,.75)' : p >= 30 ? 'rgba(245,158,11,.75)' : 'rgba(244,63,94,.7)';
        });
        new Chart(c4, {
            type:'bar',
            data:{ labels:hLabels, datasets:[{ data:hVals, backgroundColor:'rgba(139,92,246,.65)', borderRadius:4, borderSkipped:false }] },
            options:{
                responsive:true, maintainAspectRatio:false,
                plugins:{ legend:{display:false}, tooltip:{ callbacks:{ label: c => ` ${c.parsed.y.toLocaleString()} artículos` }}},
                scales:{
                    y:{ beginAtZero:true, grid:{color:gridC}, ticks:{color:tickC,font:fnt} },
                    x:{ grid:{display:false}, ticks:{color:'#94a3b8',font:{size:9}} }
                }
            }
        });
    }

    // ════════════════
    // Gráfica 5 — Top 10 Líneas (horizontal bar)
    // ════════════════
    const c5 = document.getElementById('chart5Lineas')?.getContext('2d');
    if (c5) {
        const lLabels = {!! json_encode(array_keys($stats['lineas_chart']??[])) !!};
        const lVals   = {!! json_encode(array_values($stats['lineas_chart']??[])) !!};
        const palette = ['#8b5cf6','#6366f1','#3b82f6','#0ea5e9','#06b6d4','#10b981','#f59e0b','#f97316','#f43f5e','#ec4899'];
        new Chart(c5, {
            type:'bar',
            data:{ labels:lLabels, datasets:[{ data:lVals, backgroundColor:palette.slice(0,lLabels.length), borderRadius:4, borderSkipped:false }] },
            options:{
                indexAxis:'y',
                responsive:true, maintainAspectRatio:false,
                plugins:{ legend:{display:false}, tooltip:{ callbacks:{ label: c => ` ${c.parsed.x.toLocaleString()} artículos` }}},
                scales:{
                    x:{ beginAtZero:true, grid:{color:gridC}, ticks:{color:tickC,font:fnt} },
                    y:{ grid:{display:false}, ticks:{color:'#94a3b8',font:{size:9}} }
                }
            }
        });
    }

    @endif
});
</script>
@endpush
