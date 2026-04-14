@extends('layouts.app')

@section('title', 'DB Master')
@section('breadcrumb', 'DB Master')

@section('content')

<style>
/* Desactivar scroll de page-content para que la tabla maneje su propio scroll */
.page-content {
    overflow: hidden !important;
    padding-bottom: 0 !important;
    display: flex;
    flex-direction: column;
}
#db-master-table-card {
    flex: 1;
    min-height: 0;
    display: flex;
    flex-direction: column;
    overflow: hidden;
}
#table-inner-wrap {
    overflow-y: auto !important;
    overflow-x: auto !important;
    height: calc(100vh - 200px);
    min-height: 200px;
}
</style>

{{-- Wrapper --}}
<div style="flex-shrink: 0; overflow-x: auto;">

    {{-- ── PREMIUM HEADER ────────────────────────────────────────── --}}
    <div class="page-header shadow-premium" style="margin-bottom: 12px; padding: 14px 20px; background: var(--grad-surface); border-radius: var(--radius-xl); border: 1px solid var(--glass-border); position: relative; overflow: hidden; display: flex; justify-content: space-between; align-items: center;">
        {{-- Efecto decorativo de fondo --}}
        <div style="position:absolute; top:-50px; right:-50px; width:150px; height:150px; background:var(--emerald); filter:blur(100px); opacity:0.1; pointer-events:none;"></div>
        
        <div class="page-header-content" style="display: flex; gap: 16px; align-items: center; z-index: 1;">
            <div class="page-header-icon shadow-premium" style="background: var(--emerald-bg); border: 1px solid rgba(16,185,129,0.3); color: var(--emerald);">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                    <ellipse cx="12" cy="5" rx="9" ry="3"/>
                    <path d="M21 12c0 1.66-4 3-9 3s-9-1.34-9-3"/>
                    <path d="M3 5v14c0 1.66 4 3 9 3s9-1.34 9-3V5"/>
                </svg>
            </div>
            <div>
                <h1 class="page-title" style="letter-spacing: -0.01em; margin:0;">DB Master</h1>
                <p class="page-subtitle" style="color: var(--text-secondary); margin:4px 0 0;">Catálogo Maestro Independiente</p>
            </div>
        </div>
        <div class="page-header-actions" style="display:flex;gap:12px;align-items:center; z-index: 1;">
            <a href="{{ route('db_master.export') }}" class="btn btn--secondary shadow-premium" style="border: 1px solid rgba(16,185,129,0.3); background:rgba(16,185,129,0.1); color:var(--emerald); display:flex; align-items:center; gap:8px; text-decoration:none; padding: 8px 16px; border-radius: 8px; font-size: 13px; font-weight: 600;">
                <svg viewBox="0 0 24 24" fill="none" width="16" height="16" stroke="currentColor" stroke-width="2.5"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
                Exportar Excel
            </a>
            <button onclick="openHistoryModal()" class="btn btn--ghost shadow-premium" style="border: 1px solid var(--border); background:rgba(255,255,255,0.05); color:var(--text-secondary); display:flex; align-items:center; gap:8px;">
                <svg viewBox="0 0 24 24" fill="none" width="16" height="16" stroke="currentColor" stroke-width="2"><path d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                Historial
            </button>
            <button onclick="startSyncMaster()" class="btn btn--primary shadow-premium" style="background:var(--emerald); border:none; color:white; display:flex; align-items:center; gap:8px;">
                <svg viewBox="0 0 24 24" fill="none" width="16" height="16" stroke="currentColor" stroke-width="2.5"><path d="M21 2v6h-6"/><path d="M3 12a9 9 0 0 1 15-6.7L21 8"/><path d="M3 22v-6h6"/><path d="M21 12a9 9 0 0 1-15 6.7L3 16"/></svg>
                Sincronizar Maestro
            </button>
        </div>
    </div>

    {{-- ── SIMPLE SEARCH BAR ────────────────────────────────────────── --}}
    <form method="GET" action="{{ route('db_master.index') }}" style="margin-bottom: 12px; flex-shrink: 0;">
        <input type="hidden" name="per_page" id="per_page_input" value="{{ request('per_page', 50) }}">

        <div class="glass-card shadow-premium" style="padding: 10px 16px; display: flex; align-items: center; flex-wrap: wrap; gap: 12px;">
            <div class="search-input-wrap" style="flex:1; min-width:250px; margin: 0; display:flex; align-items:center; background:var(--bg-root); border-radius:8px; border:1px solid var(--border); overflow:hidden;">
                <span class="search-icon" style="padding: 0 12px; color:var(--text-muted); display:flex; align-items:center;">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="16" height="16"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.35-4.35"/></svg>
                </span>
                <input type="text" name="q" value="{{ $search }}" placeholder="Buscar por código maestro o descripción..." class="search-input" autocomplete="off" style="width: 100%; border:none; background:transparent; padding:9px 12px 9px 0; color:white; outline:none; font-size:12px; font-weight:600;">
            </div>

            <div style="display:flex; align-items:center; gap:8px;">
                <button type="submit" class="btn btn--primary btn--sm shadow-premium" style="background:var(--grad-premium); border-color:transparent; color:white; padding:7px 16px; font-size:12px;">Buscar</button>
                @if($search)
                    <a href="{{ route('db_master.index') }}" class="btn btn--ghost btn--sm" style="font-size:12px;">Limpiar</a>
                @endif
            </div>
        </div>
    </form>

    {{-- ── ALERTS (Success/Errores) ────────────────────────────── --}}
    @if(session('success'))
    <div class="alert alert--success shadow-premium" style="margin-bottom:16px; border-left: 4px solid #10b981; padding: 12px 16px; background: rgba(16, 185, 129, 0.1); color: #10b981; border-radius: 6px;">
        <strong>¡Éxito!</strong> {{ session('success') }}
    </div>
    @endif

    @if(session('error') || $error)
    <div class="alert alert--error shadow-premium" style="margin-bottom:16px; border-left: 4px solid var(--rose); padding: 12px 16px; background: rgba(244, 63, 94, 0.1); color: var(--rose); border-radius: 6px;">
        <p style="font-weight: 700; font-size: 13px; margin:0;">{{ session('error') ?? $error }}</p>
    </div>
    @endif

</div>{{-- /end wrapper --}}

{{-- ── RESULTS TABLE (Intelligent Grid) ──────────────────────── --}}
<div class="glass-card shadow-premium" id="db-master-table-card" style="display: flex; flex-direction: column; margin-bottom: 0;">
    <div style="padding: 10px 14px; border-bottom: 1px solid var(--border); display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 8px;">
        <div style="display:flex; align-items:center; gap:10px;">
            <h2 style="font-size: 13px; font-weight: 800; color: var(--text-primary); margin:0;">Catálogo Universal</h2>
            <span style="font-size: 10px; color: var(--text-muted);">
                · Última sincronización: <span style="color:var(--emerald);font-weight:700;">{{ $stats['last_sync'] ?? 'Nunca' }}</span>
                @if($search)
                    · "<span style="color: var(--violet-light);">{{ $search }}</span>" · {{ number_format($articles->total()) }}
                @else
                    · {{ number_format($stats['universo'] ?? 0) }} artículos
                @endif
            </span>
        </div>
        <div style="background: rgba(255,255,255,0.03); padding: 4px 10px; border-radius: 20px; border: 1px solid var(--border); display: flex; align-items: center; gap: 8px;">
            <label for="page-selector" style="font-size: 10px; font-weight: 700; color: var(--text-muted); text-transform: uppercase;">Mostrar:</label>
            <select id="page-selector" 
                    onchange="document.getElementById('per_page_input').value=this.value; window.location.href='{{ route('db_master.index') }}?per_page='+this.value+'&q={{ $search }}';" 
                    style="background: transparent; border: none; color: var(--emerald); font-size: 11px; font-weight: 800; cursor: pointer; outline: none; -webkit-appearance: none; padding-right: 12px; background-image: url('data:image/svg+xml;utf8,<svg fill=%22%2310b981%22 height=%2214%22 viewBox=%220 0 24 24%22 width=%2214%22 xmlns=%22http://www.w3.org/2000/svg%22><path d=%22M7 10l5 5 5-5z%22/></svg>'); background-repeat: no-repeat; background-position-x: 100%; background-position-y: center;">
                <option value="50" style="background:var(--bg-root);color:white;" @if($per_page == 50) selected @endif>50</option>
                <option value="100" style="background:var(--bg-root);color:white;" @if($per_page == 100) selected @endif>100</option>
                <option value="250" style="background:var(--bg-root);color:white;" @if($per_page == 250) selected @endif>250</option>
                <option value="500" style="background:var(--bg-root);color:white;" @if($per_page == 500) selected @endif>500</option>
            </select>
        </div>
    </div>

    <div class="table-responsive-wide" id="table-inner-wrap" style="background: rgba(0,0,0,0.1); max-height: 600px; overflow-y: auto;">
        <table class="data-table table-wide" style="width: 100%; border-collapse: separate; border-spacing: 0;">
            <thead style="position: sticky; top: 0; z-index: 20;">
                <tr style="background: var(--bg-card-2);">
                    <th class="sticky-col" style="padding: 14px 20px; text-align: left; font-size: 11px; font-weight: 800; color: var(--text-secondary); text-transform: uppercase; letter-spacing: 0.05em; border-bottom: 2px solid var(--border); min-width: 120px; left: 0; z-index: 21; background: var(--bg-card-2);">clave</th>
                    <th class="sticky-col-2" style="padding: 14px 20px; text-align: left; font-size: 11px; font-weight: 800; color: var(--text-secondary); text-transform: uppercase; letter-spacing: 0.05em; border-bottom: 2px solid var(--border); min-width: 250px; left: 120px; z-index: 21; background: var(--bg-card-2);">descripcion</th>
                    <th style="padding: 14px 20px; text-align: center; font-size: 11px; font-weight: 800; color: var(--text-secondary); text-transform: uppercase; letter-spacing: 0.05em; border-bottom: 2px solid var(--border);">unidad_medida</th>
                    <th style="padding: 14px 20px; text-align: left; font-size: 11px; font-weight: 800; color: var(--text-secondary); text-transform: uppercase; letter-spacing: 0.05em; border-bottom: 2px solid var(--border);">linea</th>
                    <th style="padding: 14px 20px; text-align: left; font-size: 11px; font-weight: 800; color: var(--text-secondary); text-transform: uppercase; letter-spacing: 0.05em; border-bottom: 2px solid var(--border);">clasificacion</th>
                    <th style="padding: 14px 20px; text-align: center; font-size: 11px; font-weight: 800; color: var(--text-secondary); text-transform: uppercase; letter-spacing: 0.05em; border-bottom: 2px solid var(--border);">area</th>
                    <th style="padding: 14px 20px; text-align: center; font-size: 11px; font-weight: 800; color: var(--text-secondary); text-transform: uppercase; letter-spacing: 0.05em; border-bottom: 2px solid var(--border);">habilitado</th>
                    {{-- Precios --}}
                    <th style="padding: 14px 20px; text-align: right; font-size: 11px; font-weight: 800; color: var(--text-secondary); text-transform: uppercase; letter-spacing: 0.05em; border-bottom: 2px solid var(--border);">precio_lista</th>
                    <th style="padding: 14px 20px; text-align: right; font-size: 11px; font-weight: 800; color: var(--emerald); text-transform: uppercase; letter-spacing: 0.05em; border-bottom: 2px solid var(--border);">precio_venta</th>
                    <th style="padding: 14px 20px; text-align: center; font-size: 11px; font-weight: 800; color: var(--text-secondary); text-transform: uppercase; letter-spacing: 0.05em; border-bottom: 2px solid var(--border);">des_precio_venta</th>
                    <th style="padding: 14px 20px; text-align: right; font-size: 11px; font-weight: 800; color: var(--text-secondary); text-transform: uppercase; letter-spacing: 0.05em; border-bottom: 2px solid var(--border);">precio_especial</th>
                    <th style="padding: 14px 20px; text-align: center; font-size: 11px; font-weight: 800; color: var(--text-secondary); text-transform: uppercase; letter-spacing: 0.05em; border-bottom: 2px solid var(--border);">desc_precio_espec</th>
                    <th style="padding: 14px 20px; text-align: right; font-size: 11px; font-weight: 800; color: var(--text-secondary); text-transform: uppercase; letter-spacing: 0.05em; border-bottom: 2px solid var(--border);">precio4</th>
                    <th style="padding: 14px 20px; text-align: center; font-size: 11px; font-weight: 800; color: var(--text-secondary); text-transform: uppercase; letter-spacing: 0.05em; border-bottom: 2px solid var(--border);">desc_precio4</th>
                    <th style="padding: 14px 20px; text-align: right; font-size: 11px; font-weight: 800; color: var(--text-secondary); text-transform: uppercase; letter-spacing: 0.05em; border-bottom: 2px solid var(--border);">precio_minimo</th>
                    <th style="padding: 14px 20px; text-align: center; font-size: 11px; font-weight: 800; color: var(--text-secondary); text-transform: uppercase; letter-spacing: 0.05em; border-bottom: 2px solid var(--border);">desc_precio_minimo</th>
                    <th style="padding: 14px 20px; text-align: right; font-size: 11px; font-weight: 800; color: var(--text-secondary); text-transform: uppercase; letter-spacing: 0.05em; border-bottom: 2px solid var(--border);">precio_tope</th>
                    <th style="padding: 14px 20px; text-align: center; font-size: 11px; font-weight: 800; color: var(--text-secondary); text-transform: uppercase; letter-spacing: 0.05em; border-bottom: 2px solid var(--border);">mn_usd</th>
                    {{-- SAT --}}
                    <th style="padding: 14px 20px; text-align: center; font-size: 11px; font-weight: 800; color: var(--text-secondary); text-transform: uppercase; letter-spacing: 0.05em; border-bottom: 2px solid var(--border);">idsat</th>
                    <th style="padding: 14px 20px; text-align: center; font-size: 11px; font-weight: 800; color: var(--text-secondary); text-transform: uppercase; letter-spacing: 0.05em; border-bottom: 2px solid var(--border);">iva</th>
                    <th style="padding: 14px 20px; text-align: center; font-size: 11px; font-weight: 800; color: var(--text-secondary); text-transform: uppercase; letter-spacing: 0.05em; border-bottom: 2px solid var(--border);">id_impuesto_sat</th>
                    <th style="padding: 14px 20px; text-align: center; font-size: 11px; font-weight: 800; color: var(--text-secondary); text-transform: uppercase; letter-spacing: 0.05em; border-bottom: 2px solid var(--border);">id_tipo_factor</th>
                    <th style="padding: 14px 20px; text-align: center; font-size: 11px; font-weight: 800; color: var(--text-secondary); text-transform: uppercase; letter-spacing: 0.05em; border-bottom: 2px solid var(--border);">control_pedimentos</th>
                    {{-- Inventario --}}
                    <th style="padding: 14px 20px; text-align: right; font-size: 11px; font-weight: 800; color: var(--text-secondary); text-transform: uppercase; letter-spacing: 0.05em; border-bottom: 2px solid var(--border);">existencia_teorica</th>
                    <th style="padding: 14px 20px; text-align: right; font-size: 11px; font-weight: 800; color: var(--text-secondary); text-transform: uppercase; letter-spacing: 0.05em; border-bottom: 2px solid var(--border);">existencia_fisica</th>
                    <th style="padding: 14px 20px; text-align: right; font-size: 11px; font-weight: 800; color: var(--text-secondary); text-transform: uppercase; letter-spacing: 0.05em; border-bottom: 2px solid var(--border);">punto_reorden</th>
                    <th style="padding: 14px 20px; text-align: right; font-size: 11px; font-weight: 800; color: var(--text-secondary); text-transform: uppercase; letter-spacing: 0.05em; border-bottom: 2px solid var(--border);">inventario_minimo</th>
                    <th style="padding: 14px 20px; text-align: right; font-size: 11px; font-weight: 800; color: var(--text-secondary); text-transform: uppercase; letter-spacing: 0.05em; border-bottom: 2px solid var(--border);">inventario_maximo</th>
                    <th style="padding: 14px 20px; text-align: left; font-size: 11px; font-weight: 800; color: var(--text-secondary); text-transform: uppercase; letter-spacing: 0.05em; border-bottom: 2px solid var(--border);">ubicacion</th>
                    <th style="padding: 14px 20px; text-align: center; font-size: 11px; font-weight: 800; color: var(--text-secondary); text-transform: uppercase; letter-spacing: 0.05em; border-bottom: 2px solid var(--border);">std_pack</th>
                    <th style="padding: 14px 20px; text-align: center; font-size: 11px; font-weight: 800; color: var(--text-secondary); text-transform: uppercase; letter-spacing: 0.05em; border-bottom: 2px solid var(--border);">peso</th>
                    <th style="padding: 14px 20px; text-align: center; font-size: 11px; font-weight: 800; color: var(--text-secondary); text-transform: uppercase; letter-spacing: 0.05em; border-bottom: 2px solid var(--border);">articulo_kit</th>
                    <th style="padding: 14px 20px; text-align: center; font-size: 11px; font-weight: 800; color: var(--text-secondary); text-transform: uppercase; letter-spacing: 0.05em; border-bottom: 2px solid var(--border);">articulo_serie</th>
                    {{-- Costos --}}
                    <th style="padding: 14px 20px; text-align: right; font-size: 11px; font-weight: 800; color: var(--rose); text-transform: uppercase; letter-spacing: 0.05em; border-bottom: 2px solid var(--border);">costo_venta</th>
                    <th style="padding: 14px 20px; text-align: center; font-size: 11px; font-weight: 800; color: var(--text-secondary); text-transform: uppercase; letter-spacing: 0.05em; border-bottom: 2px solid var(--border);">porcetaje_descuento</th>
                    <th style="padding: 14px 20px; text-align: right; font-size: 11px; font-weight: 800; color: var(--text-secondary); text-transform: uppercase; letter-spacing: 0.05em; border-bottom: 2px solid var(--border);">costo_promedio</th>
                    <th style="padding: 14px 20px; text-align: right; font-size: 11px; font-weight: 800; color: var(--text-secondary); text-transform: uppercase; letter-spacing: 0.05em; border-bottom: 2px solid var(--border);">costo_promedio_ant</th>
                    <th style="padding: 14px 20px; text-align: right; font-size: 11px; font-weight: 800; color: var(--text-secondary); text-transform: uppercase; letter-spacing: 0.05em; border-bottom: 2px solid var(--border);">costo_ult_compra</th>
                    <th style="padding: 14px 20px; text-align: center; font-size: 11px; font-weight: 800; color: var(--text-secondary); text-transform: uppercase; letter-spacing: 0.05em; border-bottom: 2px solid var(--border);">fecha_ult_compra</th>
                    <th style="padding: 14px 20px; text-align: center; font-size: 11px; font-weight: 800; color: var(--text-secondary); text-transform: uppercase; letter-spacing: 0.05em; border-bottom: 2px solid var(--border);">fecha_alta</th>
                    <th style="padding: 14px 20px; text-align: center; font-size: 11px; font-weight: 800; color: var(--text-secondary); text-transform: uppercase; letter-spacing: 0.05em; border-bottom: 2px solid var(--border);">en_promocion</th>
                    <th style="padding: 14px 20px; text-align: center; font-size: 11px; font-weight: 800; color: var(--text-secondary); text-transform: uppercase; letter-spacing: 0.05em; border-bottom: 2px solid var(--border);">critico</th>
                    {{-- Otros --}}
                    <th style="padding: 14px 20px; text-align: left; font-size: 11px; font-weight: 800; color: var(--text-secondary); text-transform: uppercase; letter-spacing: 0.05em; border-bottom: 2px solid var(--border);">sustituto</th>
                    <th style="padding: 14px 20px; text-align: left; font-size: 11px; font-weight: 800; color: var(--text-secondary); text-transform: uppercase; letter-spacing: 0.05em; border-bottom: 2px solid var(--border);">sustituto1</th>
                    <th style="padding: 14px 20px; text-align: center; font-size: 11px; font-weight: 800; color: var(--text-secondary); text-transform: uppercase; letter-spacing: 0.05em; border-bottom: 2px solid var(--border);">acciones</th>
                </tr>
            </thead>
            <tbody>
                @forelse($articles as $row)
                    <tr style="transition: background 0.1s;" onmouseover="this.style.background='rgba(255,255,255,0.02)'" onmouseout="this.style.background='transparent'">
                        <td class="sticky-col" style="padding: 12px 20px; font-family: 'JetBrains Mono', monospace; font-size: 12px; color: var(--emerald); font-weight: 600; border-bottom: 1px solid var(--border-light); white-space: nowrap; left: 0; background: var(--bg-card); z-index: 5;">{{ $row->clave }}</td>
                        <td class="sticky-col-2" style="padding: 12px 20px; font-size: 13px; color: var(--text-primary); border-bottom: 1px solid var(--border-light); min-width: 250px; left: 120px; background: var(--bg-card); z-index: 5;">{{ $row->descripcion }}</td>
                        <td style="padding: 12px 20px; text-align: center; font-size: 12px; color: var(--text-secondary); border-bottom: 1px solid var(--border-light);">{{ $row->unidad_medida }}</td>
                        <td style="padding: 12px 20px; text-align: left; font-size: 11px; color: var(--text-secondary); border-bottom: 1px solid var(--border-light);">{{ $row->linea }}</td>
                        <td style="padding: 12px 20px; text-align: left; font-size: 11px; color: var(--text-secondary); border-bottom: 1px solid var(--border-light);">{{ $row->clasificacion }}</td>
                        <td style="padding: 12px 20px; text-align: center; font-size: 11px; color: var(--text-secondary); border-bottom: 1px solid var(--border-light);">{{ $row->area }}</td>
                        <td style="padding: 12px 20px; text-align: center; border-bottom: 1px solid var(--border-light);">
                             <span class="homo-pill {{ $row->habilitado == 1 ? 'homo-pill--ok' : 'homo-pill--miss' }}" style="font-size: 10px; padding: 2px 8px;">{{ $row->habilitado == 1 ? 'SI' : 'NO' }}</span>
                        </td>
                        {{-- Precios --}}
                        <td style="padding: 12px 20px; text-align: right; font-size: 12px; font-family: 'JetBrains Mono', monospace; color: var(--text-primary); border-bottom: 1px solid var(--border-light); font-variant-numeric: tabular-nums;">${{ number_format((float)($row->precio_lista ?? 0), 2) }}</td>
                        <td style="padding: 12px 20px; text-align: right; font-size: 12px; font-family: 'JetBrains Mono', monospace; color: var(--emerald); border-bottom: 1px solid var(--border-light); font-variant-numeric: tabular-nums; font-weight: 700;">${{ number_format((float)($row->precio_venta ?? 0), 2) }}</td>
                        <td style="padding: 12px 20px; text-align: center; font-size: 11px; color: var(--text-muted); border-bottom: 1px solid var(--border-light);">{{ number_format((float)($row->des_precio_venta ?? 0), 2) }}%</td>
                        <td style="padding: 12px 20px; text-align: right; font-size: 12px; font-family: 'JetBrains Mono', monospace; border-bottom: 1px solid var(--border-light); font-variant-numeric: tabular-nums;">${{ number_format((float)($row->precio_especial ?? 0), 2) }}</td>
                        <td style="padding: 12px 20px; text-align: center; font-size: 11px; color: var(--text-muted); border-bottom: 1px solid var(--border-light);">{{ number_format((float)($row->desc_precio_espec ?? 0), 2) }}%</td>
                        <td style="padding: 12px 20px; text-align: right; font-size: 12px; font-family: 'JetBrains Mono', monospace; border-bottom: 1px solid var(--border-light); font-variant-numeric: tabular-nums;">${{ number_format((float)($row->precio4 ?? 0), 2) }}</td>
                        <td style="padding: 12px 20px; text-align: center; font-size: 11px; color: var(--text-muted); border-bottom: 1px solid var(--border-light);">{{ number_format((float)($row->desc_precio4 ?? 0), 2) }}%</td>
                        <td style="padding: 12px 20px; text-align: right; font-size: 12px; font-family: 'JetBrains Mono', monospace; border-bottom: 1px solid var(--border-light); font-variant-numeric: tabular-nums;">${{ number_format((float)($row->precio_minimo ?? 0), 2) }}</td>
                        <td style="padding: 12px 20px; text-align: center; font-size: 11px; color: var(--text-muted); border-bottom: 1px solid var(--border-light);">{{ number_format((float)($row->desc_precio_minimo ?? 0), 2) }}%</td>
                        <td style="padding: 12px 20px; text-align: right; font-size: 12px; font-family: 'JetBrains Mono', monospace; border-bottom: 1px solid var(--border-light); font-variant-numeric: tabular-nums;">${{ number_format((float)($row->precio_tope ?? 0), 2) }}</td>
                        <td style="padding: 12px 20px; text-align: center; font-size: 11px; color: var(--text-muted); border-bottom: 1px solid var(--border-light);">{{ $row->mn_usd == 1 ? 'USD' : 'MXN' }}</td>
                        {{-- SAT --}}
                        <td style="padding: 12px 20px; text-align: center; font-size: 11px; color: var(--text-muted); border-bottom: 1px solid var(--border-light);">{{ $row->idsat }}</td>
                        <td style="padding: 12px 20px; text-align: center; font-size: 11px; color: var(--text-muted); border-bottom: 1px solid var(--border-light);">{{ $row->iva }}%</td>
                        <td style="padding: 12px 20px; text-align: center; font-size: 11px; color: var(--text-muted); border-bottom: 1px solid var(--border-light);">{{ $row->id_impuesto_sat }}</td>
                        <td style="padding: 12px 20px; text-align: center; font-size: 11px; color: var(--text-muted); border-bottom: 1px solid var(--border-light);">{{ $row->id_tipo_factor }}</td>
                        <td style="padding: 12px 20px; text-align: center; border-bottom: 1px solid var(--border-light);">{{ $row->control_pedimentos == 1 ? 'S' : 'N' }}</td>
                        {{-- Inventario --}}
                        <td style="padding: 12px 20px; text-align: right; font-size: 11px; color: var(--text-secondary); border-bottom: 1px solid var(--border-light);">{{ number_format($row->existencia_teorica, 2) }}</td>
                        <td style="padding: 12px 20px; text-align: right; font-size: 11px; color: var(--violet-light); border-bottom: 1px solid var(--border-light);">{{ number_format($row->existencia_fisica, 2) }}</td>
                        <td style="padding: 12px 20px; text-align: right; font-size: 11px; color: var(--text-secondary); border-bottom: 1px solid var(--border-light);">{{ number_format($row->punto_reorden, 2) }}</td>
                        <td style="padding: 12px 20px; text-align: right; font-size: 11px; color: var(--text-secondary); border-bottom: 1px solid var(--border-light);">{{ number_format($row->inventario_minimo, 2) }}</td>
                        <td style="padding: 12px 20px; text-align: right; font-size: 11px; color: var(--text-secondary); border-bottom: 1px solid var(--border-light);">{{ number_format($row->inventario_maximo, 2) }}</td>
                        <td style="padding: 12px 20px; text-align: left; font-size: 11px; color: var(--text-secondary); border-bottom: 1px solid var(--border-light);">{{ $row->ubicacion }}</td>
                        <td style="padding: 12px 20px; text-align: center; font-size: 11px; color: var(--text-secondary); border-bottom: 1px solid var(--border-light);">{{ $row->std_pack }}</td>
                        <td style="padding: 12px 20px; text-align: center; font-size: 11px; color: var(--text-secondary); border-bottom: 1px solid var(--border-light);">{{ $row->peso }}</td>
                        <td style="padding: 12px 20px; text-align: center; border-bottom: 1px solid var(--border-light);">{{ $row->articulo_kit == 1 ? 'S' : 'N' }}</td>
                        <td style="padding: 12px 20px; text-align: center; border-bottom: 1px solid var(--border-light);">{{ $row->articulo_serie == 1 ? 'S' : 'N' }}</td>
                        {{-- Costos --}}
                        <td style="padding: 12px 20px; text-align: right; font-size: 12px; font-family: 'JetBrains Mono', monospace; color: var(--rose); border-bottom: 1px solid var(--border-light); font-variant-numeric: tabular-nums;">${{ number_format((float)($row->costo_venta ?? 0), 2) }}</td>
                        <td style="padding: 12px 20px; text-align: center; font-size: 11px; color: var(--text-muted); border-bottom: 1px solid var(--border-light);">{{ number_format((float)($row->porcetaje_descuento ?? 0), 2) }}%</td>
                        <td style="padding: 12px 20px; text-align: right; font-size: 11px; color: var(--text-secondary); border-bottom: 1px solid var(--border-light);">{{ number_format($row->costo_promedio, 2) }}</td>
                        <td style="padding: 12px 20px; text-align: right; font-size: 11px; color: var(--text-secondary); border-bottom: 1px solid var(--border-light);">{{ number_format($row->costo_promedio_ant, 2) }}</td>
                        <td style="padding: 12px 20px; text-align: right; font-size: 11px; color: var(--text-secondary); border-bottom: 1px solid var(--border-light);">{{ number_format($row->costo_ult_compra, 2) }}</td>
                        <td style="padding: 12px 20px; text-align: center; font-size: 11px; color: var(--text-muted); border-bottom: 1px solid var(--border-light);">{{ $row->fecha_ult_compra }}</td>
                        <td style="padding: 12px 20px; text-align: center; font-size: 11px; color: var(--text-muted); border-bottom: 1px solid var(--border-light);">{{ $row->fecha_alta }}</td>
                        <td style="padding: 12px 20px; text-align: center; border-bottom: 1px solid var(--border-light);">{{ $row->en_promocion == 1 ? 'S' : 'N' }}</td>
                        <td style="padding: 12px 20px; text-align: center; border-bottom: 1px solid var(--border-light);">{{ $row->critico == 1 ? 'S' : 'N' }}</td>
                        {{-- Otros --}}
                        <td style="padding: 12px 20px; text-align: left; font-size: 11px; color: var(--text-secondary); border-bottom: 1px solid var(--border-light);">{{ $row->sustituto }}</td>
                        <td style="padding: 12px 20px; text-align: left; font-size: 11px; color: var(--text-secondary); border-bottom: 1px solid var(--border-light);">{{ $row->sustituto1 }}</td>
                        <td style="padding: 12px 20px; text-align: center; border-bottom: 1px solid var(--border-light); white-space: nowrap; z-index: 10;">
                            <button onclick='openEditModal({!! json_encode($row) !!})' class="btn btn--sm btn--ghost shadow-premium" style="padding: 6px 10px; border: 1px solid var(--border); background:rgba(255,255,255,0.05); color:var(--violet-light);" title="Editar Artículo">
                                <svg viewBox="0 0 24 24" fill="none" width="14" height="14" stroke="currentColor" stroke-width="2.5"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
                            </button>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="60" style="padding: 60px; text-align: center; color: var(--text-muted); font-size: 14px;">
                            <svg style="opacity: 0.2; margin-bottom: 12px;" viewBox="0 0 24 24" fill="none" width="48" height="48" stroke="currentColor" stroke-width="1"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.35-4.35"/></svg>
                            <p>No hay artículos en la base maestra. Pulsa "Sincronizar Maestro" para actualizar.</p>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($articles->hasPages())
        <div style="padding: 16px 24px; background: var(--bg-card); display: flex; justify-content: space-between; align-items: center; border-top: 1px solid var(--border); flex-wrap: wrap; gap: 12px;">
            <p style="font-size: 12px; color: var(--text-muted); margin:0;">
                Mostrando página <span style="color:var(--text-primary); font-weight:700;">{{ $articles->currentPage() }}</span> de {{ $articles->lastPage() }}
            </p>
            <div class="premium-pagination">
                {{ $articles->links('pagination::bootstrap-4') }}
            </div>
        </div>
    @endif
</div>

{{-- ── HISTORY MODAL ────────────────────────────────────────── --}}
<div id="history-modal" style="display:none; position:fixed; inset:0; z-index:9999; background:rgba(2,6,23,0.85); backdrop-filter:blur(8px); align-items:center; justify-content:center;">
    <div class="glass-card shadow-premium" style="width:90%; max-width:600px; padding:0; overflow:hidden; border:1px solid rgba(16,185,129,0.3);">
        <div style="padding:20px 24px; background:rgba(16,185,129,0.1); border-bottom:1px solid rgba(16,185,129,0.2); display:flex; justify-content:space-between; align-items:center;">
            <div style="display:flex; align-items:center; gap:12px;">
                <div style="width:32px; height:32px; border-radius:8px; background:var(--emerald); color:white; display:flex; align-items:center; justify-content:center;">
                    <svg viewBox="0 0 24 24" fill="none" width="18" height="18" stroke="currentColor" stroke-width="2.5"><path d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                </div>
                <h3 style="margin:0; font-size:16px; font-weight:800; color:white;">Historial de Sincronización</h3>
            </div>
            <button onclick="closeHistoryModal()" style="background:transparent; border:none; color:var(--text-muted); cursor:pointer;"><svg viewBox="0 0 24 24" fill="none" width="20" height="20" stroke="currentColor" stroke-width="2.5"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg></button>
        </div>
        <div id="history-content" style="max-height:400px; overflow-y:auto; padding:12px;">
            <div style="padding:40px; text-align:center; color:var(--text-muted);">Cargando historial...</div>
        </div>
        <div style="padding:16px 24px; text-align:right; background:rgba(0,0,0,0.2); border-top:1px solid var(--border);">
            <button onclick="closeHistoryModal()" class="btn btn--ghost" style="font-size:12px; border:1px solid var(--border);">Cerrar</button>
        </div>
    </div>
</div>

<script>
function startSyncMaster() {
    Swal.fire({
        title: '¿Sincronizar DB Master?',
        text: 'Se actualizará la base maestra con los artículos que tengan cobertura total en este momento.',
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#10b981',
        cancelButtonColor: '#64748b',
        confirmButtonText: 'Sí, sincronizar ahora',
        cancelButtonText: 'Cancelar',
        background: '#0f172a',
        color: '#f8fafc',
        backdrop: 'rgba(0,0,0,0.6)',
        customClass: { popup: 'shadow-premium' }
    }).then((result) => {
        if (result.isConfirmed) {
            Swal.fire({
                title: 'Procesando...',
                text: 'Aislando artículos con cobertura total',
                allowOutsideClick: false,
                didOpen: () => { Swal.showLoading(); }
            });

            fetch('{{ route('db_master.sync') }}', {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Accept': 'application/json'
                }
            })
            .then(r => r.json())
            .then(data => {
                if (data.status === 'success') {
                    Swal.fire({
                        icon: 'success',
                        title: 'Sincronización Exitosa',
                        text: data.message + ' (Total: ' + data.total + ' artículos)',
                        background: '#0f172a',
                        color: '#f8fafc'
                    }).then(() => location.reload());
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: data.message,
                        background: '#0f172a',
                        color: '#f8fafc'
                    });
                }
            })
            .catch(e => {
                Swal.fire({ icon: 'error', title: 'Error Fatal', text: 'Error en la petición: ' + e.message });
            });
        }
    });
}

function openHistoryModal() {
    const modal = document.getElementById('history-modal');
    modal.style.display = 'flex';
    const content = document.getElementById('history-content');
    
    fetch('{{ route('db_master.history') }}')
    .then(r => r.json())
    .then(data => {
        if (!data || data.length === 0) {
            content.innerHTML = '<div style="padding:40px; text-align:center; color:var(--text-muted); font-size:12px;">No hay registros de sincronización aún.</div>';
            return;
        }

        let html = '<table style="width:100%; font-size:12px; border-collapse:collapse;">';
        html += '<thead style="background:rgba(255,255,255,0.03);"><tr style="border-bottom:1px solid var(--border);">';
        html += '<th style="padding:10px;text-align:left;color:var(--text-muted);">FECHA / HORA</th>';
        html += '<th style="padding:10px;text-align:right;color:var(--text-muted);">ARTÍCULOS</th>';
        html += '</tr></thead><tbody>';

        data.forEach(h => {
            const date = new Date(h.created_at).toLocaleString();
            html += `<tr style="border-bottom:1px solid rgba(255,255,255,0.05);">
                <td style="padding:12px 10px; color:white; font-weight:600;">${date}</td>
                <td style="padding:12px 10px; text-align:right; color:var(--emerald); font-weight:800; font-family:\'JetBrains Mono\', monospace;">${h.total_articulos.toLocaleString()}</td>
            </tr>`;
        });
        html += '</tbody></table>';
        content.innerHTML = html;
    });
}

function closeHistoryModal() {
    document.getElementById('history-modal').style.display = 'none';
}

function switchModalTab(event, tabId) {
    // Hide all panels
    document.querySelectorAll('.modal-tab-panel').forEach(panel => panel.style.display = 'none');
    // Deactivate all tabs
    document.querySelectorAll('.modal-tab').forEach(tab => {
        tab.classList.remove('active');
        tab.style.color = 'var(--text-muted)';
        tab.style.borderBottomColor = 'transparent';
    });
    
    // Show selected panel
    document.getElementById(tabId).style.display = 'block';
    // Activate clicked tab
    event.currentTarget.classList.add('active');
    event.currentTarget.style.color = 'white';
    event.currentTarget.style.borderBottomColor = 'var(--violet)';
}

function openEditModal(row) {
    console.log("Opening edit modal for:", row);
    
    // Set Header
    document.getElementById('modal-clave-badge').textContent = row.clave;
    
    // General
    document.getElementById('edit-article-id').value = row.id;
    document.getElementById('edit-clave').value = row.clave;
    document.getElementById('edit-descripcion').value = row.descripcion || '';
    document.getElementById('edit-linea').value = row.linea || '';
    document.getElementById('edit-clasificacion').value = row.clasificacion || '';
    document.getElementById('edit-area').value = row.area || '';
    document.getElementById('edit-unidad_medida').value = row.unidad_medida || '';
    document.getElementById('edit-color').value = row.color || '';
    document.getElementById('edit-protocolo').value = row.protocolo || '';
    document.getElementById('edit-articulo_kit').checked = !!row.articulo_kit;
    document.getElementById('edit-articulo_serie').checked = !!row.articulo_serie;
    document.getElementById('edit-habilitado').checked = (row.habilitado == 1);
    
    // Precios
    document.getElementById('edit-mn_usd').value = row.mn_usd || 0;
    document.getElementById('edit-precio_lista').value = row.precio_lista || 0;
    document.getElementById('edit-precio_venta').value = row.precio_venta || 0;
    document.getElementById('edit-des_precio_venta').value = row.des_precio_venta || 0;
    document.getElementById('edit-precio_especial').value = row.precio_especial || 0;
    document.getElementById('edit-desc_precio_espec').value = row.desc_precio_espec || 0;
    document.getElementById('edit-precio4').value = row.precio4 || 0;
    document.getElementById('edit-desc_precio4').value = row.desc_precio4 || 0;
    document.getElementById('edit-precio_minimo').value = row.precio_minimo || 0;
    document.getElementById('edit-desc_precio_minimo').value = row.desc_precio_minimo || 0;
    document.getElementById('edit-precio_tope').value = row.precio_tope || 0;
    document.getElementById('edit-margen_minimo').value = row.margen_minimo || 0;
    document.getElementById('edit-costo_venta').value = row.costo_venta || 0;
    document.getElementById('edit-costo_promedio').value = row.costo_promedio || 0;
    document.getElementById('edit-costo_promedio_ant').value = row.costo_promedio_ant || 0;
    
    // Inventario
    document.getElementById('edit-inventario_maximo').value = row.inventario_maximo || 0;
    document.getElementById('edit-inventario_minimo').value = row.inventario_minimo || 0;
    document.getElementById('edit-punto_reorden').value = row.punto_reorden || 0;
    document.getElementById('edit-existencia_teorica').value = row.existencia_teorica || 0;
    document.getElementById('edit-existencia_fisica').value = row.existencia_fisica || 0;
    document.getElementById('edit-ubicacion').value = row.ubicacion || '';
    document.getElementById('edit-peso').value = row.peso || 0;
    document.getElementById('edit-std_pack').value = row.std_pack || 0;
    document.getElementById('edit-costo_ult_compra').value = row.costo_ult_compra || 0;
    document.getElementById('edit-fecha_ult_compra').value = row.fecha_ult_compra || '';
    document.getElementById('edit-costo_compra_ant').value = row.costo_compra_ant || 0;
    
    // Extra
    document.getElementById('edit-idsat').value = row.idsat || '';
    document.getElementById('edit-id_impuesto_sat').value = row.id_impuesto_sat || '';
    document.getElementById('edit-iva').value = row.iva || 16;
    document.getElementById('edit-sustituto').value = row.sustituto || '';
    document.getElementById('edit-sustituto1').value = row.sustituto1 || '';
    document.getElementById('edit-sustituto2').value = row.sustituto2 || '';
    document.getElementById('edit-en_promocion').checked = !!row.en_promocion;
    document.getElementById('edit-critico').checked = !!row.critico;
    document.getElementById('edit-control_pedimentos').checked = !!row.control_pedimentos;

    // Reset tabs to first one
    document.querySelector('.modal-tab').click();
    
    document.getElementById('edit-modal').style.display = 'flex';
}

function closeEditModal() {
    document.getElementById('edit-modal').style.display = 'none';
}

// Form Submission handling
document.getElementById('edit-article-form').addEventListener('submit', function(e) {
    e.preventDefault();
    const formData = new FormData(this);
    
    // Convert checkboxes to values
    const checks = ['articulo_kit', 'articulo_serie', 'habilitado', 'en_promocion', 'critico', 'control_pedimentos'];
    checks.forEach(c => {
        formData.set(c, document.getElementById('edit-' + c).checked ? 1 : 0);
    });

    Swal.fire({
        title: 'Guardando cambios...',
        text: 'Se actualizará el Maestro y se replicará a todas las sucursales.',
        allowOutsideClick: false,
        didOpen: () => { Swal.showLoading(); }
    });

    const articleId = document.getElementById('edit-article-id').value;
    fetch('/db-master/item/' + articleId, {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': '{{ csrf_token() }}',
            'Accept': 'application/json'
        },
        body: formData
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            Swal.fire('Éxito', data.message, 'success').then(() => location.reload());
        } else {
            Swal.fire('Error', data.message || 'Error desconocido', 'error');
        }
    })
    .catch(err => {
        console.error(err);
        Swal.fire('Error', 'Fallo en la comunicación con el servidor', 'error');
    });
});

function adjustTableHeight() {
    const wrap = document.getElementById('table-inner-wrap');
    const card = document.getElementById('db-master-table-card');
    if (!wrap || !card) return;
    const cardTop = card.getBoundingClientRect().top;
    const available = window.innerHeight - cardTop - 64; 
    wrap.style.height = Math.max(250, available) + 'px';
}
document.addEventListener('DOMContentLoaded', adjustTableHeight);
window.addEventListener('resize', adjustTableHeight);
</script>

{{-- ── EDIT MODAL HTML ────────────────────────────────────────── --}}
<div id="edit-modal" style="display:none; position:fixed; inset:0; z-index:9999; background:rgba(2,6,23,0.85); backdrop-filter:blur(8px); align-items:center; justify-content:center; padding: 20px;">
    <div class="glass-card shadow-premium" style="width:100%; max-width:950px; padding:0; overflow:hidden; border:1px solid rgba(139,92,246,0.3);">
        <div style="padding:16px 24px; background:rgba(139,92,246,0.1); border-bottom:1px solid rgba(139,92,246,0.2); display:flex; justify-content:space-between; align-items:center;">
            <div style="display:flex; align-items:center; gap:12px;">
                <div style="width:32px; height:32px; border-radius:8px; background:var(--violet); color:white; display:flex; align-items:center; justify-content:center;">
                    <svg viewBox="0 0 24 24" fill="none" width="18" height="18" stroke="currentColor" stroke-width="2.5"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
                </div>
                <h3 style="margin:0; font-size:16px; font-weight:800; color:white;">Editar Artículo Maestro <span id="modal-clave-badge" style="background:rgba(255,255,255,0.1); padding:2px 8px; border-radius:4px; font-size:12px; margin-left:10px; color:var(--violet-light);"></span></h3>
            </div>
            <button onclick="closeEditModal()" style="background:transparent; border:none; color:var(--text-muted); cursor:pointer;"><svg viewBox="0 0 24 24" fill="none" width="20" height="20" stroke="currentColor" stroke-width="2.5"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg></button>
        </div>

        <!-- TABS HEADER -->
        <div style="display:flex; background:rgba(0,0,0,0.2); border-bottom:1px solid var(--border); padding:0 24px;">
            <button class="modal-tab active" onclick="switchModalTab(event, 'tab-general')" style="padding:12px 20px; background:none; border:none; color:white; border-bottom:2px solid var(--violet); cursor:pointer; font-size:12px; font-weight:600;">General</button>
            <button class="modal-tab" onclick="switchModalTab(event, 'tab-precios')" style="padding:12px 20px; background:none; border:none; color:var(--text-muted); border-bottom:2px solid transparent; cursor:pointer; font-size:12px; font-weight:600;">Precios y Costos</button>
            <button class="modal-tab" onclick="switchModalTab(event, 'tab-inventario')" style="padding:12px 20px; background:none; border:none; color:var(--text-muted); border-bottom:2px solid transparent; cursor:pointer; font-size:12px; font-weight:600;">Inventario y Logística</button>
            <button class="modal-tab" onclick="switchModalTab(event, 'tab-extra')" style="padding:12px 20px; background:none; border:none; color:var(--text-muted); border-bottom:2px solid transparent; cursor:pointer; font-size:12px; font-weight:600;">SAT y Sustitutos</button>
        </div>
        
        <form id="edit-article-form" style="padding: 24px;">
            <input type="hidden" name="id" id="edit-article-id">
            
            <div id="modal-tab-content" style="max-height: 500px; overflow-y: auto; padding-right: 10px;">
                <!-- TAB GENERAL -->
                <div id="tab-general" class="modal-tab-panel">
                    <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 16px;">
                        <div class="form-group">
                            <label class="modal-label">Clave (No editable)</label>
                            <input type="text" id="edit-clave" readonly class="modal-input readonly">
                        </div>
                        <div class="form-group" style="grid-column: span 2;">
                            <label class="modal-label">Descripción</label>
                            <input type="text" name="descripcion" id="edit-descripcion" maxlength="200" class="modal-input">
                        </div>
                        <div class="form-group">
                            <label class="modal-label">Línea</label>
                            <input type="text" name="linea" id="edit-linea" required maxlength="4" class="modal-input">
                        </div>
                        <div class="form-group">
                            <label class="modal-label">Clasificación</label>
                            <input type="text" name="clasificacion" id="edit-clasificacion" required maxlength="6" class="modal-input">
                        </div>
                        <div class="form-group">
                            <label class="modal-label">Área</label>
                            <input type="number" name="area" id="edit-area" required class="modal-input">
                        </div>
                        <div class="form-group">
                            <label class="modal-label">Unidad de Medida</label>
                            <input type="text" name="unidad_medida" id="edit-unidad_medida" required maxlength="4" class="modal-input">
                        </div>
                        <div class="form-group" style="display: flex; align-items: center; gap: 20px; padding-top: 20px;">
                            <label style="display:flex; align-items:center; gap:8px; cursor:pointer;">
                                <input type="checkbox" name="color" id="edit-color" value="1" style="width:16px; height:16px;">
                                <span style="font-size: 11px; font-weight: 700; color: white;">Color</span>
                            </label>
                            <label style="display:flex; align-items:center; gap:8px; cursor:pointer;">
                                <input type="checkbox" name="protocolo" id="edit-protocolo" value="1" style="width:16px; height:16px;">
                                <span style="font-size: 11px; font-weight: 700; color: white;">Protocolo</span>
                            </label>
                        </div>
                        <div class="form-group" style="display:flex; align-items:center; gap:10px; padding-top:20px;">
                            <input type="checkbox" name="articulo_kit" id="edit-articulo_kit" style="width:16px; height:16px;">
                            <label style="font-size:11px; font-weight:700; color:white;">Es KIT</label>
                        </div>
                        <div class="form-group" style="display:flex; align-items:center; gap:10px; padding-top:20px;">
                            <input type="checkbox" name="articulo_serie" id="edit-articulo_serie" style="width:16px; height:16px;">
                            <label style="font-size:11px; font-weight:700; color:white;">Maneja Series</label>
                        </div>
                        <div class="form-group" style="display:flex; align-items:center; gap:10px; padding-top:20px;">
                            <input type="checkbox" name="habilitado" id="edit-habilitado" value="1" style="width:16px; height:16px;">
                            <label style="font-size:11px; font-weight:700; color:white;">Habilitado</label>
                        </div>
                    </div>
                </div>

                <!-- TAB PRECIOS -->
                <div id="tab-precios" class="modal-tab-panel" style="display:none;">
                    <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 16px;">
                        <div class="form-group">
                            <label class="modal-label">Moneda (0=MXN, 1=USD)</label>
                            <select name="mn_usd" id="edit-mn_usd" class="modal-input">
                                <option value="0">MXN</option>
                                <option value="1">USD</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label class="modal-label">Precio Lista</label>
                            <input type="number" step="0.0001" name="precio_lista" id="edit-precio_lista" class="modal-input">
                        </div>
                        <div class="form-group">
                            <label class="modal-label">Precio Venta</label>
                            <input type="number" step="0.0001" name="precio_venta" id="edit-precio_venta" class="modal-input">
                        </div>
                        <div class="form-group">
                            <label class="modal-label">Desc. Venta (%)</label>
                            <input type="number" step="0.01" name="des_precio_venta" id="edit-des_precio_venta" class="modal-input">
                        </div>
                        <div class="form-group">
                            <label class="modal-label">Precio Especial</label>
                            <input type="number" step="0.0001" name="precio_especial" id="edit-precio_especial" class="modal-input">
                        </div>
                        <div class="form-group">
                            <label class="modal-label">Desc. Especial (%)</label>
                            <input type="number" step="0.01" name="desc_precio_espec" id="edit-desc_precio_espec" class="modal-input">
                        </div>
                        <div class="form-group">
                            <label class="modal-label">Precio 4</label>
                            <input type="number" step="0.0001" name="precio4" id="edit-precio4" class="modal-input">
                        </div>
                        <div class="form-group">
                            <label class="modal-label">Desc. Precio 4 (%)</label>
                            <input type="number" step="0.01" name="desc_precio4" id="edit-desc_precio4" class="modal-input">
                        </div>
                         <div class="form-group">
                            <label class="modal-label">Precio Mínimo</label>
                            <input type="number" step="0.0001" name="precio_minimo" id="edit-precio_minimo" class="modal-input">
                        </div>
                        <div class="form-group">
                            <label class="modal-label">Desc. Mínimo (%)</label>
                            <input type="number" step="0.01" name="desc_precio_minimo" id="edit-desc_precio_minimo" class="modal-input">
                        </div>
                        <div class="form-group">
                            <label class="modal-label">Precio Tope</label>
                            <input type="number" step="0.0001" name="precio_tope" id="edit-precio_tope" class="modal-input">
                        </div>
                        <div class="form-group">
                            <label class="modal-label">Margen Mínimo (%)</label>
                            <input type="number" step="0.01" name="margen_minimo" id="edit-margen_minimo" class="modal-input">
                        </div>
                        <div class="form-group">
                            <label class="modal-label">Costo Venta</label>
                            <input type="number" step="0.0001" name="costo_venta" id="edit-costo_venta" class="modal-input">
                        </div>
                        <div class="form-group">
                            <label class="modal-label">Costo Promedio</label>
                            <input type="number" step="0.0001" name="costo_promedio" id="edit-costo_promedio" class="modal-input">
                        </div>
                        <div class="form-group">
                            <label class="modal-label">Costo Promedio Ant.</label>
                            <input type="number" step="0.0001" name="costo_promedio_ant" id="edit-costo_promedio_ant" class="modal-input">
                        </div>
                    </div>
                </div>

                <!-- TAB INVENTARIO -->
                <div id="tab-inventario" class="modal-tab-panel" style="display:none;">
                    <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 16px;">
                        <div class="form-group">
                            <label class="modal-label">Inventario Máximo</label>
                            <input type="number" step="0.01" name="inventario_maximo" id="edit-inventario_maximo" class="modal-input">
                        </div>
                        <div class="form-group">
                            <label class="modal-label">Inventario Mínimo</label>
                            <input type="number" step="0.01" name="inventario_minimo" id="edit-inventario_minimo" class="modal-input">
                        </div>
                        <div class="form-group">
                            <label class="modal-label">Punto de Reorden</label>
                            <input type="number" step="0.01" name="punto_reorden" id="edit-punto_reorden" class="modal-input">
                        </div>
                        <div class="form-group">
                            <label class="modal-label">Existencia Teórica</label>
                            <input type="number" step="0.01" name="existencia_teorica" id="edit-existencia_teorica" class="modal-input readonly" readonly>
                        </div>
                        <div class="form-group">
                            <label class="modal-label">Existencia Física</label>
                            <input type="number" step="0.01" name="existencia_fisica" id="edit-existencia_fisica" class="modal-input readonly" readonly>
                        </div>
                        <div class="form-group">
                            <label class="modal-label">Ubicación</label>
                            <input type="text" name="ubicacion" id="edit-ubicacion" class="modal-input">
                        </div>
                        <div class="form-group">
                            <label class="modal-label">Peso</label>
                            <input type="number" step="0.001" name="peso" id="edit-peso" class="modal-input">
                        </div>
                        <div class="form-group">
                            <label class="modal-label">Std Pack</label>
                            <input type="number" step="0.01" name="std_pack" id="edit-std_pack" class="modal-input">
                        </div>
                        <div class="form-group" style="grid-column: span 3; border-top: 1px solid var(--border); margin-top: 10px; padding-top: 15px;">
                             <label style="color:var(--violet-light); font-size:11px; font-weight:800; text-transform:uppercase;">Últimas Compras</label>
                        </div>
                        <div class="form-group">
                            <label class="modal-label">Último Costo</label>
                            <input type="number" step="0.0001" name="costo_ult_compra" id="edit-costo_ult_compra" class="modal-input">
                        </div>
                        <div class="form-group">
                            <label class="modal-label">Fecha Últ. Compra</label>
                            <input type="date" name="fecha_ult_compra" id="edit-fecha_ult_compra" class="modal-input">
                        </div>
                        <div class="form-group">
                            <label class="modal-label">Costo Compra Ant.</label>
                            <input type="number" step="0.0001" name="costo_compra_ant" id="edit-costo_compra_ant" class="modal-input">
                        </div>
                    </div>
                </div>

                <!-- TAB EXTRA -->
                <div id="tab-extra" class="modal-tab-panel" style="display:none;">
                    <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 16px;">
                        <div class="form-group">
                            <label class="modal-label">IDSAT</label>
                            <input type="text" name="idsat" id="edit-idsat" maxlength="25" class="modal-input">
                        </div>
                        <div class="form-group">
                            <label class="modal-label">ID Impuesto SAT</label>
                            <input type="text" name="id_impuesto_sat" id="edit-id_impuesto_sat" maxlength="3" class="modal-input">
                        </div>
                        <div class="form-group">
                            <label class="modal-label">IVA (%)</label>
                            <input type="number" step="0.01" name="iva" id="edit-iva" class="modal-input">
                        </div>
                        <div class="form-group">
                            <label class="modal-label">Sustituto</label>
                            <input type="text" name="sustituto" id="edit-sustituto" class="modal-input">
                        </div>
                        <div class="form-group">
                            <label class="modal-label">Sustituto 1</label>
                            <input type="text" name="sustituto1" id="edit-sustituto1" class="modal-input">
                        </div>
                        <div class="form-group">
                            <label class="modal-label">Sustituto 2</label>
                            <input type="text" name="sustituto2" id="edit-sustituto2" class="modal-input">
                        </div>
                         <div class="form-group" style="grid-column: span 3; border-top: 1px solid var(--border); margin-top: 10px; padding-top: 15px;">
                             <label style="color:var(--violet-light); font-size:11px; font-weight:800; text-transform:uppercase;">Banderas y Control</label>
                        </div>
                        <div class="form-group" style="display:flex; align-items:center; gap:10px;">
                            <input type="checkbox" name="en_promocion" id="edit-en_promocion" style="width:16px; height:16px;">
                            <label style="font-size:11px; font-weight:700; color:white;">En Promoción</label>
                        </div>
                        <div class="form-group" style="display:flex; align-items:center; gap:10px;">
                            <input type="checkbox" name="critico" id="edit-critico" style="width:16px; height:16px;">
                            <label style="font-size:11px; font-weight:700; color:white;">Crítico</label>
                        </div>
                        <div class="form-group" style="display:flex; align-items:center; gap:10px;">
                            <input type="checkbox" name="control_pedimentos" id="edit-control_pedimentos" style="width:16px; height:16px;">
                            <label style="font-size:11px; font-weight:700; color:white;">Pedimentos</label>
                        </div>
                    </div>
                </div>
            </div>

            <div style="margin-top: 24px; display: flex; justify-content: flex-end; gap: 12px;">
                <button type="button" onclick="closeEditModal()" class="btn btn--ghost">Cancelar</button>
                <button type="submit" class="btn btn--primary" style="background:var(--grad-premium); border:none;">Guardar y Replicar</button>
            </div>
        </form>
    </div>
</div>

<style>
.modal-label { font-size:11px; font-weight:700; color:var(--text-muted); display:block; margin-bottom:4px; }
.modal-input { width:100%; background:var(--bg-root); border:1px solid var(--border); padding:8px 12px; border-radius:6px; color:white; font-size:12px; }
.modal-input.readonly { background:rgba(0,0,0,0.2); color:var(--text-muted); cursor:not-allowed; }
.modal-input:focus { border-color:var(--violet); outline:none; box-shadow:0 0 0 2px rgba(139,92,246,0.2); }
</style>

@endsection
