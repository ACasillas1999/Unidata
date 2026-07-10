@extends('layouts.app')

@section('title', 'Inventario por Sucursal')
@section('breadcrumb', 'Artículos / Inventario')

@section('content')

<style>
/* Igual patron que db_master: el propio contenedor de tabla maneja su scroll,
   no la pagina completa, asi la tabla ancha no rompe el layout. */
.page-content {
    overflow: hidden !important;
    padding-bottom: 0 !important;
    display: flex;
    flex-direction: column;
}
#inv-table-card {
    flex: 1;
    min-height: 0;
    display: flex;
    flex-direction: column;
    overflow: hidden;
}
#inv-table-wrap {
    overflow-y: auto !important;
    overflow-x: auto !important;
    flex: 1;
    min-height: 200px;
}
#inv-pagination {
    flex-shrink: 0;
    overflow-x: auto;
}
#inv-pagination .pagination {
    flex-wrap: wrap;
    row-gap: 6px;
    justify-content: flex-end;
}
</style>

<div style="flex-shrink: 0;">

<div class="page-header">
    <div class="page-header-content">
        <div class="page-header-icon" style="background:#f0f9ff; color:#0ea5e9;">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M20 7h-9a2 2 0 0 0-2 2v9"/><path d="M3 9v10a2 2 0 0 0 2 2h9"/>
                <rect x="12" y="2" width="10" height="10" rx="2"/>
            </svg>
        </div>
        <div>
            <h1 class="page-title">Inventario por Sucursal</h1>
            <p class="page-subtitle">Existencia real por almacén, consultada directo a cada base de datos</p>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-body" style="padding:16px 20px">
        <form method="GET" action="{{ route('inventario.index') }}" id="inv-form" class="homo-filter-bar">
            <input type="hidden" name="per_page" value="{{ request('per_page', 50) }}">

            <div class="search-input-wrap" style="flex:1;min-width:200px">
                <span class="search-icon">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.35-4.35"/></svg>
                </span>
                <input
                    type="text"
                    name="q"
                    value="{{ $search }}"
                    placeholder="Buscar por código o descripción…"
                    class="search-input"
                    autocomplete="off"
                >
            </div>

            <div class="homo-filter-row">
                <select name="sucursal" class="form-select" onchange="this.form.submit()">
                    @foreach($branchesMap as $key => $label)
                        <option value="{{ $key }}" @selected($sucursal === $key)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>

            <div style="display:flex;gap:8px;flex-shrink:0">
                <button type="submit" class="btn btn--primary btn--sm">Buscar</button>
                <a href="{{ route('inventario.index') }}" class="btn btn--ghost btn--sm">Limpiar</a>
            </div>
        </form>
    </div>
</div>

<div class="card" style="margin-top:12px; padding:14px 20px; display:flex; gap:12px; align-items:center; flex-wrap:wrap;">
    <span style="font-size:12px; font-weight:700; color:var(--text-secondary); text-transform:uppercase;">Exportar (mapeo PowerSales):</span>
    <a href="{{ route('inventario.export', ['sucursal' => $sucursal]) }}" class="btn btn--sm shadow-premium" style="background:rgba(16,185,129,0.1); color:var(--emerald); border:1px solid rgba(16,185,129,0.3);">
        <svg style="margin-right:4px;" viewBox="0 0 24 24" fill="none" width="14" height="14" stroke="currentColor" stroke-width="2.5"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
        Esta sucursal ({{ $branchesMap[$sucursal] ?? $sucursal }})
    </a>
    <a href="{{ route('inventario.export', ['sucursal' => 'todas']) }}" class="btn btn--sm shadow-premium" style="background:rgba(59,130,246,0.1); color:#60a5fa; border:1px solid rgba(59,130,246,0.3);">
        <svg style="margin-right:4px;" viewBox="0 0 24 24" fill="none" width="14" height="14" stroke="currentColor" stroke-width="2.5"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
        Todas las sucursales
    </a>
</div>

@if($error)
<div class="alert alert--error" style="margin-top: 12px;">
    <span class="alert-icon">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
    </span>
    <div>
        <p class="alert-title">Problema de conexión</p>
        <p class="alert-body">{{ $error }}</p>
    </div>
</div>
@endif

</div>

<div class="card" id="inv-table-card" style="margin-top:12px;">
    <div class="card-header card-header--row" style="flex-shrink:0;">
        <div>
            <h2 class="card-title">Existencia en {{ $branchesMap[$sucursal] ?? 'Base de datos' }}</h2>
        </div>
    </div>
    <div id="inv-table-wrap">
    <table style="width: 100%; border-collapse: collapse;">
        <thead style="position: sticky; top: 0; z-index: 5; background: var(--bg-card, #0f172a);">
            <tr style="background: rgba(255,255,255,0.03); border-bottom: 1px solid var(--border);">
                <th style="padding: 12px 16px; text-align: left; font-size: 11px; font-weight: 800; color: var(--text-secondary); text-transform: uppercase; white-space:nowrap;">Clave</th>
                <th style="padding: 12px 16px; text-align: left; font-size: 11px; font-weight: 800; color: var(--text-secondary); text-transform: uppercase; white-space:nowrap;">Descripción</th>
                <th style="padding: 12px 16px; text-align: left; font-size: 11px; font-weight: 800; color: var(--text-secondary); text-transform: uppercase; white-space:nowrap;">Almacén</th>
                <th style="padding: 12px 16px; text-align: right; font-size: 11px; font-weight: 800; color: var(--text-secondary); text-transform: uppercase; white-space:nowrap;">Ex. Física</th>
                <th style="padding: 12px 16px; text-align: right; font-size: 11px; font-weight: 800; color: var(--text-secondary); text-transform: uppercase; white-space:nowrap;">Ex. Teórica</th>
                <th style="padding: 12px 16px; text-align: right; font-size: 11px; font-weight: 800; color: var(--text-secondary); text-transform: uppercase; white-space:nowrap;">Apartado</th>
                <th style="padding: 12px 16px; text-align: right; font-size: 11px; font-weight: 800; color: var(--text-secondary); text-transform: uppercase; white-space:nowrap;">Pend. Entrega</th>
                <th style="padding: 12px 16px; text-align: right; font-size: 11px; font-weight: 800; color: var(--text-secondary); text-transform: uppercase; white-space:nowrap;">Mín.</th>
                <th style="padding: 12px 16px; text-align: right; font-size: 11px; font-weight: 800; color: var(--text-secondary); text-transform: uppercase; white-space:nowrap;">Máx.</th>
                <th style="padding: 12px 16px; text-align: right; font-size: 11px; font-weight: 800; color: var(--text-secondary); text-transform: uppercase; white-space:nowrap;">Reorden</th>
            </tr>
        </thead>
        <tbody>
            @forelse($items as $row)
            <tr style="border-bottom: 1px solid rgba(255,255,255,0.05);">
                <td style="padding: 10px 16px; font-family: 'JetBrains Mono', monospace; font-size: 12px; font-weight:600; color:var(--emerald); white-space:nowrap;">{{ $row->Clave_Articulo }}</td>
                <td style="padding: 10px 16px; font-size: 13px; white-space:nowrap;">{{ $row->descripcion }}</td>
                <td style="padding: 10px 16px; white-space:nowrap; font-size: 13px;">
                    <span style="font-family: 'JetBrains Mono', monospace; font-size: 12px; color:var(--text-muted);">{{ $row->Almacen }}</span>
                    @if($row->almacen_nombre)
                        <span style="margin-left:6px;">{{ $row->almacen_nombre }}</span>
                    @endif
                </td>
                <td style="padding: 10px 16px; text-align:right; font-weight:700; white-space:nowrap;">{{ number_format((float) $row->Existencia_Fisica, 2) }}</td>
                <td style="padding: 10px 16px; text-align:right; color:var(--text-secondary); white-space:nowrap;">{{ number_format((float) $row->Existencia_Teorica, 2) }}</td>
                <td style="padding: 10px 16px; text-align:right; color:var(--text-secondary); white-space:nowrap;">{{ number_format((float) $row->Apartado, 2) }}</td>
                <td style="padding: 10px 16px; text-align:right; color:var(--text-secondary); white-space:nowrap;">{{ number_format((float) $row->PendienteDeEntrega, 2) }}</td>
                <td style="padding: 10px 16px; text-align:right; color:var(--text-muted); white-space:nowrap;">{{ number_format((float) $row->Inventario_Minimo, 2) }}</td>
                <td style="padding: 10px 16px; text-align:right; color:var(--text-muted); white-space:nowrap;">{{ number_format((float) $row->Inventario_Maximo, 2) }}</td>
                <td style="padding: 10px 16px; text-align:right; color:var(--text-muted); white-space:nowrap;">{{ number_format((float) $row->Punto_Reorden, 2) }}</td>
            </tr>
            @empty
            <tr><td colspan="10" style="padding: 40px; text-align: center; color: var(--text-muted);">Sin existencias para mostrar.</td></tr>
            @endforelse
        </tbody>
    </table>
    </div>
    <div id="inv-pagination" style="padding: 12px 20px;">
        {{ $items->links() }}
    </div>
</div>
@endsection
