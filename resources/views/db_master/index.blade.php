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

    <div class="table-wrap" id="table-inner-wrap" style="background: rgba(0,0,0,0.1);">
        <table class="data-table" style="width: 100%; border-collapse: separate; border-spacing: 0;">
            <thead style="position: sticky; top: 0; z-index: 10;">
                <tr style="background: var(--bg-card-2);">
                    <th style="padding: 14px 20px; text-align: left; font-size: 11px; font-weight: 800; color: var(--text-secondary); text-transform: uppercase; letter-spacing: 0.05em; border-bottom: 2px solid var(--border);">Clave_Articulo</th>
                    <th style="padding: 14px 20px; text-align: left; font-size: 11px; font-weight: 800; color: var(--text-secondary); text-transform: uppercase; letter-spacing: 0.05em; border-bottom: 2px solid var(--border); min-width: 250px;">Descripcion</th>
                    <th style="padding: 14px 20px; text-align: center; font-size: 11px; font-weight: 800; color: var(--text-secondary); text-transform: uppercase; letter-spacing: 0.05em; border-bottom: 2px solid var(--border);">Unidad_Medida</th>
                    <th style="padding: 14px 20px; text-align: left; font-size: 11px; font-weight: 800; color: var(--text-secondary); text-transform: uppercase; letter-spacing: 0.05em; border-bottom: 2px solid var(--border);">Linea</th>
                    <th style="padding: 14px 20px; text-align: left; font-size: 11px; font-weight: 800; color: var(--text-secondary); text-transform: uppercase; letter-spacing: 0.05em; border-bottom: 2px solid var(--border);">Clasificacion</th>
                    <th style="padding: 14px 20px; text-align: center; font-size: 11px; font-weight: 800; color: var(--text-secondary); text-transform: uppercase; letter-spacing: 0.05em; border-bottom: 2px solid var(--border);">MN_USD</th>
                    <th style="padding: 14px 20px; text-align: right; font-size: 11px; font-weight: 800; color: var(--text-secondary); text-transform: uppercase; letter-spacing: 0.05em; border-bottom: 2px solid var(--border);">Precio_Lista</th>
                    <th style="padding: 14px 20px; text-align: right; font-size: 11px; font-weight: 800; color: var(--text-secondary); text-transform: uppercase; letter-spacing: 0.05em; border-bottom: 2px solid var(--border);">Precio_Venta</th>
                    <th style="padding: 14px 20px; text-align: right; font-size: 11px; font-weight: 800; color: var(--text-secondary); text-transform: uppercase; letter-spacing: 0.05em; border-bottom: 2px solid var(--border);">Desc_Precio_Venta</th>
                    <th style="padding: 14px 20px; text-align: right; font-size: 11px; font-weight: 800; color: var(--text-secondary); text-transform: uppercase; letter-spacing: 0.05em; border-bottom: 2px solid var(--border);">Precio_Especial</th>
                    <th style="padding: 14px 20px; text-align: right; font-size: 11px; font-weight: 800; color: var(--text-secondary); text-transform: uppercase; letter-spacing: 0.05em; border-bottom: 2px solid var(--border);">Desc_Precio_Espec</th>
                    <th style="padding: 14px 20px; text-align: right; font-size: 11px; font-weight: 800; color: var(--text-secondary); text-transform: uppercase; letter-spacing: 0.05em; border-bottom: 2px solid var(--border);">Precio4</th>
                    <th style="padding: 14px 20px; text-align: right; font-size: 11px; font-weight: 800; color: var(--text-secondary); text-transform: uppercase; letter-spacing: 0.05em; border-bottom: 2px solid var(--border);">Desc_Precio4</th>
                    <th style="padding: 14px 20px; text-align: center; font-size: 11px; font-weight: 800; color: var(--text-secondary); text-transform: uppercase; letter-spacing: 0.05em; border-bottom: 2px solid var(--border);">Articulo_Kit</th>
                    <th style="padding: 14px 20px; text-align: center; font-size: 11px; font-weight: 800; color: var(--text-secondary); text-transform: uppercase; letter-spacing: 0.05em; border-bottom: 2px solid var(--border);">Articulo_Serie</th>
                    <th style="padding: 14px 20px; text-align: right; font-size: 11px; font-weight: 800; color: var(--text-secondary); text-transform: uppercase; letter-spacing: 0.05em; border-bottom: 2px solid var(--border);">Margen_Minimo</th>
                    <th style="padding: 14px 20px; text-align: center; font-size: 11px; font-weight: 800; color: var(--text-secondary); text-transform: uppercase; letter-spacing: 0.05em; border-bottom: 2px solid var(--border);">Color</th>
                    <th style="padding: 14px 20px; text-align: center; font-size: 11px; font-weight: 800; color: var(--text-secondary); text-transform: uppercase; letter-spacing: 0.05em; border-bottom: 2px solid var(--border);">Protocolo</th>
                    <th style="padding: 14px 20px; text-align: left; font-size: 11px; font-weight: 800; color: var(--text-secondary); text-transform: uppercase; letter-spacing: 0.05em; border-bottom: 2px solid var(--border);">IDSAT</th>
                    <th style="padding: 14px 20px; text-align: right; font-size: 11px; font-weight: 800; color: var(--text-secondary); text-transform: uppercase; letter-spacing: 0.05em; border-bottom: 2px solid var(--border);">CostoVenta</th>
                    <th style="padding: 14px 20px; text-align: center; font-size: 11px; font-weight: 800; color: var(--text-secondary); text-transform: uppercase; letter-spacing: 0.05em; border-bottom: 2px solid var(--border);">Habilitado</th>
                </tr>
            </thead>
            <tbody>
                @forelse($articles as $row)
                    <tr style="transition: background 0.1s;" onmouseover="this.style.background='rgba(255,255,255,0.02)'" onmouseout="this.style.background='transparent'">
                        <td style="padding: 12px 20px; font-family: 'JetBrains Mono', monospace; font-size: 12px; color: var(--emerald); font-weight: 600; border-bottom: 1px solid var(--border-light); white-space: nowrap;">{{ $row->clave }}</td>
                        <td style="padding: 12px 20px; font-size: 13px; color: var(--text-primary); border-bottom: 1px solid var(--border-light); min-width: 250px;">{{ $row->descripcion }}</td>
                        <td style="padding: 12px 20px; text-align: center; font-size: 12px; color: var(--text-secondary); border-bottom: 1px solid var(--border-light);">{{ $row->unidad_medida ?: 'N/A' }}</td>
                        <td style="padding: 12px 20px; text-align: left; font-size: 11px; color: var(--text-secondary); border-bottom: 1px solid var(--border-light);">{{ $row->linea ?: '-' }}</td>
                        <td style="padding: 12px 20px; text-align: left; font-size: 11px; color: var(--text-secondary); border-bottom: 1px solid var(--border-light);">{{ $row->clasificacion ?: '-' }}</td>
                        <td style="padding: 12px 20px; text-align: center; font-size: 11px; color: var(--text-secondary); border-bottom: 1px solid var(--border-light); font-weight:bold;">{{ $row->mn_usd }}</td>
                        <td style="padding: 12px 20px; text-align: right; font-size: 12px; font-family: 'JetBrains Mono', monospace; font-weight: 600; color: var(--text-primary); border-bottom: 1px solid var(--border-light); font-variant-numeric: tabular-nums;">${{ number_format((float)($row->precio_lista ?? 0), 2) }}</td>
                        <td style="padding: 12px 20px; text-align: right; font-size: 12px; font-family: 'JetBrains Mono', monospace; color: var(--text-primary); border-bottom: 1px solid var(--border-light); font-variant-numeric: tabular-nums;">${{ number_format((float)($row->precio_venta ?? 0), 2) }}</td>
                        <td style="padding: 12px 20px; text-align: right; font-size: 11px; color: var(--violet-light); border-bottom: 1px solid var(--border-light);">{{ number_format((float)($row->des_precio_venta ?? 0), 2) }}%</td>
                        <td style="padding: 12px 20px; text-align: right; font-size: 12px; font-family: 'JetBrains Mono', monospace; color: var(--text-primary); border-bottom: 1px solid var(--border-light); font-variant-numeric: tabular-nums;">${{ number_format((float)($row->precio_especial ?? 0), 2) }}</td>
                        <td style="padding: 12px 20px; text-align: right; font-size: 11px; color: var(--violet-light); border-bottom: 1px solid var(--border-light);">{{ number_format((float)($row->desc_precio_espec ?? 0), 2) }}%</td>
                        <td style="padding: 12px 20px; text-align: right; font-size: 12px; font-family: 'JetBrains Mono', monospace; color: var(--text-primary); border-bottom: 1px solid var(--border-light); font-variant-numeric: tabular-nums;">${{ number_format((float)($row->precio4 ?? 0), 2) }}</td>
                        <td style="padding: 12px 20px; text-align: right; font-size: 11px; color: var(--violet-light); border-bottom: 1px solid var(--border-light);">{{ number_format((float)($row->desc_precio4 ?? 0), 2) }}%</td>
                        <td style="padding: 12px 20px; text-align: center; font-size: 11px; color: var(--text-secondary); border-bottom: 1px solid var(--border-light);">{{ $row->articulo_kit }}</td>
                        <td style="padding: 12px 20px; text-align: center; font-size: 11px; color: var(--text-secondary); border-bottom: 1px solid var(--border-light);">{{ $row->articulo_serie }}</td>
                        <td style="padding: 12px 20px; text-align: right; font-size: 11px; color: var(--text-secondary); border-bottom: 1px solid var(--border-light);">{{ number_format((float)($row->margen_minimo ?? 0), 2) }}%</td>
                        <td style="padding: 12px 20px; text-align: center; font-size: 11px; color: var(--text-secondary); border-bottom: 1px solid var(--border-light);">{{ $row->color ?: '-' }}</td>
                        <td style="padding: 12px 20px; text-align: center; font-size: 11px; color: var(--text-secondary); border-bottom: 1px solid var(--border-light);">{{ $row->protocolo ?: '-' }}</td>
                        <td style="padding: 12px 20px; text-align: left; font-size: 11px; color: var(--text-muted); border-bottom: 1px solid var(--border-light);">{{ $row->idsat ?: '-' }}</td>
                        <td style="padding: 12px 20px; text-align: right; font-size: 12px; font-family: 'JetBrains Mono', monospace; color: var(--rose); border-bottom: 1px solid var(--border-light); font-variant-numeric: tabular-nums;">${{ number_format((float)($row->costo_venta ?? 0), 2) }}</td>
                        <td style="padding: 12px 20px; text-align: center; border-bottom: 1px solid var(--border-light);">
                            <span style="display: inline-flex; align-items: center; padding: 4px 10px; border-radius: 8px; font-size: 10px; font-weight: 800; text-transform: uppercase; background: var(--emerald-bg); color: var(--emerald); border: 1px solid transparent;">
                                <svg style="margin-right:4px" viewBox="0 0 24 24" fill="none" width="12" height="12" stroke="currentColor" stroke-width="3"><polyline points="20 6 9 17 4 12"/></svg>
                                MAESTRO
                            </span>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="21" style="padding: 60px; text-align: center; color: var(--text-muted); font-size: 14px;">
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

@endsection
