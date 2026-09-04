@extends('layouts.app')

@section('title', 'Inventario por Sucursal')
@section('breadcrumb', 'Artículos / Inventario')

@section('content')

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
    <div class="page-header-actions" style="display:flex; gap:8px; align-items:center; flex-wrap:wrap;">
        <span style="font-size:11px; font-weight:700; color:var(--text-muted); text-transform:uppercase; margin-right:4px;">Exportar (Mapeo PowerSales):</span>
        <a href="{{ route('inventario.export', ['sucursal' => $sucursal]) }}" class="btn btn--sm shadow-premium" style="background:rgba(16,185,129,0.1); color:var(--emerald); border:1px solid rgba(16,185,129,0.3);">
            <svg style="margin-right:4px;" viewBox="0 0 24 24" fill="none" width="14" height="14" stroke="currentColor" stroke-width="2.5"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
            Esta sucursal ({{ $branchesMap[$sucursal] ?? $sucursal }})
        </a>
        <a href="{{ route('inventario.export', ['sucursal' => 'todas']) }}" class="btn btn--sm shadow-premium" style="background:rgba(59,130,246,0.1); color:#60a5fa; border:1px solid rgba(59,130,246,0.3);">
            <svg style="margin-right:4px;" viewBox="0 0 24 24" fill="none" width="14" height="14" stroke="currentColor" stroke-width="2.5"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
            Todas las sucursales
        </a>
    </div>
</div>

<form method="GET" action="{{ route('inventario.index') }}" style="margin-top: 20px; flex-shrink: 0;">
    <input type="hidden" name="per_page" value="{{ request('per_page', 50) }}">

    <div class="glass-card shadow-premium" style="padding: 12px 16px; display: flex; align-items: center; flex-wrap: wrap; gap: 12px; border: 1px solid var(--border); background: var(--bg-card);">
        <div class="search-input-wrap" style="flex:1; min-width:250px; margin: 0; display:flex; align-items:center; background:var(--bg-root); border-radius:8px; border:1px solid var(--border); overflow:hidden;">
            <span class="search-icon" style="padding: 0 12px; color:var(--text-muted); display:flex; align-items:center;">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="16" height="16"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.35-4.35"/></svg>
            </span>
            <input type="text" name="q" value="{{ $search }}" placeholder="Buscar por código o descripción…" class="search-input" autocomplete="off" style="width: 100%; border:none; background:transparent; padding:9px 12px 9px 0; color:white; outline:none; font-size:13px; font-weight:600;">
        </div>

        <div style="display:flex; align-items:center; gap:8px;">
            <select name="sucursal" class="form-select" onchange="this.form.submit()" style="background:var(--bg-root); border:1px solid var(--border); color:white; padding:8px 16px; border-radius:8px; font-size:13px; font-weight:700; cursor:pointer;">
                @foreach($branchesMap as $key => $label)
                    <option value="{{ $key }}" @selected($sucursal === $key) style="background:#1a1d27; color:white;">{{ $label }}</option>
                @endforeach
            </select>

            <button type="submit" class="btn btn--primary btn--sm shadow-premium" style="background:var(--grad-premium); border-color:transparent; color:white; padding:8px 18px; font-size:13px; font-weight:700;">Buscar</button>
            @if($search)
                <a href="{{ route('inventario.index') }}" class="btn btn--ghost btn--sm" style="font-size:13px;">Limpiar</a>
            @endif
        </div>
    </div>
</form>

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

<div class="card" id="inv-table-card" style="margin-top:20px; overflow:visible;">
    <div class="card-header card-header--row">
        <div>
            <h2 class="card-title">Existencia en {{ $branchesMap[$sucursal] ?? 'Base de datos' }}</h2>
        </div>
    </div>
    <div id="inv-table-wrap" style="overflow-x: auto; overflow-y: hidden !important; max-height: none !important; height: auto !important; width: 100%; max-width: 100%; position:relative; background: #0b0f1a;">
    <table class="data-table" style="width: 100%; border-collapse: separate; border-spacing: 0;">
        <thead>
            <tr style="background: rgba(255,255,255,0.03); border-bottom: 1px solid var(--border);">
                <th class="sticky-col-1" style="min-width: 140px; background: #1a1f2e; position: sticky !important; left: 0; z-index: 11; padding: 12px 16px; text-align: left; font-size: 11px; font-weight: 800; color: var(--text-secondary); text-transform: uppercase;">
                    <a href="{{ request()->fullUrlWithQuery(['sort' => 'clave', 'dir' => ($sort === 'clave' && $dir === 'asc') ? 'desc' : 'asc']) }}" style="color:inherit; text-decoration:none; display:inline-flex; align-items:center; gap:4px;">
                        Clave
                        @if($sort === 'clave')
                            <span style="color:var(--emerald); font-weight:bold;">{{ $dir === 'asc' ? '↑' : '↓' }}</span>
                        @else
                            <span style="opacity:0.3; font-size:10px;">↕</span>
                        @endif
                    </a>
                </th>
                <th style="padding: 12px 16px; text-align: left; font-size: 11px; font-weight: 800; color: var(--text-secondary); text-transform: uppercase; white-space:nowrap; min-width: 250px;">
                    <a href="{{ request()->fullUrlWithQuery(['sort' => 'descripcion', 'dir' => ($sort === 'descripcion' && $dir === 'asc') ? 'desc' : 'asc']) }}" style="color:inherit; text-decoration:none; display:inline-flex; align-items:center; gap:4px;">
                        Descripción
                        @if($sort === 'descripcion')
                            <span style="color:var(--emerald); font-weight:bold;">{{ $dir === 'asc' ? '↑' : '↓' }}</span>
                        @else
                            <span style="opacity:0.3; font-size:10px;">↕</span>
                        @endif
                    </a>
                </th>
                <th style="padding: 12px 16px; text-align: left; font-size: 11px; font-weight: 800; color: var(--text-secondary); text-transform: uppercase; white-space:nowrap;">
                    <a href="{{ request()->fullUrlWithQuery(['sort' => 'almacen', 'dir' => ($sort === 'almacen' && $dir === 'asc') ? 'desc' : 'asc']) }}" style="color:inherit; text-decoration:none; display:inline-flex; align-items:center; gap:4px;">
                        Almacén
                        @if($sort === 'almacen')
                            <span style="color:var(--emerald); font-weight:bold;">{{ $dir === 'asc' ? '↑' : '↓' }}</span>
                        @else
                            <span style="opacity:0.3; font-size:10px;">↕</span>
                        @endif
                    </a>
                </th>
                <th style="padding: 12px 16px; text-align: right; font-size: 11px; font-weight: 800; color: var(--text-secondary); text-transform: uppercase; white-space:nowrap;">
                    <a href="{{ request()->fullUrlWithQuery(['sort' => 'existencia_fisica', 'dir' => ($sort === 'existencia_fisica' && $dir === 'asc') ? 'desc' : 'asc']) }}" style="color:inherit; text-decoration:none; display:inline-flex; align-items:center; gap:4px; justify-content:flex-end;">
                        Ex. Física
                        @if($sort === 'existencia_fisica')
                            <span style="color:var(--emerald); font-weight:bold;">{{ $dir === 'asc' ? '↑' : '↓' }}</span>
                        @else
                            <span style="opacity:0.3; font-size:10px;">↕</span>
                        @endif
                    </a>
                </th>
                <th style="padding: 12px 16px; text-align: right; font-size: 11px; font-weight: 800; color: var(--text-secondary); text-transform: uppercase; white-space:nowrap;">
                    <a href="{{ request()->fullUrlWithQuery(['sort' => 'existencia_teorica', 'dir' => ($sort === 'existencia_teorica' && $dir === 'asc') ? 'desc' : 'asc']) }}" style="color:inherit; text-decoration:none; display:inline-flex; align-items:center; gap:4px; justify-content:flex-end;">
                        Ex. Teórica
                        @if($sort === 'existencia_teorica')
                            <span style="color:var(--emerald); font-weight:bold;">{{ $dir === 'asc' ? '↑' : '↓' }}</span>
                        @else
                            <span style="opacity:0.3; font-size:10px;">↕</span>
                        @endif
                    </a>
                </th>
                <th style="padding: 12px 16px; text-align: right; font-size: 11px; font-weight: 800; color: var(--text-secondary); text-transform: uppercase; white-space:nowrap;">
                    <a href="{{ request()->fullUrlWithQuery(['sort' => 'apartado', 'dir' => ($sort === 'apartado' && $dir === 'asc') ? 'desc' : 'asc']) }}" style="color:inherit; text-decoration:none; display:inline-flex; align-items:center; gap:4px; justify-content:flex-end;">
                        Apartado
                        @if($sort === 'apartado')
                            <span style="color:var(--emerald); font-weight:bold;">{{ $dir === 'asc' ? '↑' : '↓' }}</span>
                        @else
                            <span style="opacity:0.3; font-size:10px;">↕</span>
                        @endif
                    </a>
                </th>
                <th style="padding: 12px 16px; text-align: right; font-size: 11px; font-weight: 800; color: var(--text-secondary); text-transform: uppercase; white-space:nowrap;">
                    <a href="{{ request()->fullUrlWithQuery(['sort' => 'pendiente_entrega', 'dir' => ($sort === 'pendiente_entrega' && $dir === 'asc') ? 'desc' : 'asc']) }}" style="color:inherit; text-decoration:none; display:inline-flex; align-items:center; gap:4px; justify-content:flex-end;">
                        Pend. Entrega
                        @if($sort === 'pendiente_entrega')
                            <span style="color:var(--emerald); font-weight:bold;">{{ $dir === 'asc' ? '↑' : '↓' }}</span>
                        @else
                            <span style="opacity:0.3; font-size:10px;">↕</span>
                        @endif
                    </a>
                </th>
                <th style="padding: 12px 16px; text-align: right; font-size: 11px; font-weight: 800; color: var(--text-secondary); text-transform: uppercase; white-space:nowrap;">
                    <a href="{{ request()->fullUrlWithQuery(['sort' => 'minimo', 'dir' => ($sort === 'minimo' && $dir === 'asc') ? 'desc' : 'asc']) }}" style="color:inherit; text-decoration:none; display:inline-flex; align-items:center; gap:4px; justify-content:flex-end;">
                        Mín.
                        @if($sort === 'minimo')
                            <span style="color:var(--emerald); font-weight:bold;">{{ $dir === 'asc' ? '↑' : '↓' }}</span>
                        @else
                            <span style="opacity:0.3; font-size:10px;">↕</span>
                        @endif
                    </a>
                </th>
                <th style="padding: 12px 16px; text-align: right; font-size: 11px; font-weight: 800; color: var(--text-secondary); text-transform: uppercase; white-space:nowrap;">
                    <a href="{{ request()->fullUrlWithQuery(['sort' => 'maximo', 'dir' => ($sort === 'maximo' && $dir === 'asc') ? 'desc' : 'asc']) }}" style="color:inherit; text-decoration:none; display:inline-flex; align-items:center; gap:4px; justify-content:flex-end;">
                        Máx.
                        @if($sort === 'maximo')
                            <span style="color:var(--emerald); font-weight:bold;">{{ $dir === 'asc' ? '↑' : '↓' }}</span>
                        @else
                            <span style="opacity:0.3; font-size:10px;">↕</span>
                        @endif
                    </a>
                </th>
                <th style="padding: 12px 16px; text-align: right; font-size: 11px; font-weight: 800; color: var(--text-secondary); text-transform: uppercase; white-space:nowrap;">
                    <a href="{{ request()->fullUrlWithQuery(['sort' => 'reorden', 'dir' => ($sort === 'reorden' && $dir === 'asc') ? 'desc' : 'asc']) }}" style="color:inherit; text-decoration:none; display:inline-flex; align-items:center; gap:4px; justify-content:flex-end;">
                        Reorden
                        @if($sort === 'reorden')
                            <span style="color:var(--emerald); font-weight:bold;">{{ $dir === 'asc' ? '↑' : '↓' }}</span>
                        @else
                            <span style="opacity:0.3; font-size:10px;">↕</span>
                        @endif
                    </a>
                </th>
            </tr>
        </thead>
        <tbody>
            @forelse($items as $row)
            <tr style="border-bottom: 1px solid rgba(255,255,255,0.05);">
                <td class="sticky-col-1 td--code" style="position: sticky !important; left: 0; background: #0f172a; border-right: 1px solid rgba(255,255,255,0.05); padding: 10px 16px; font-family: 'JetBrains Mono', monospace; font-size: 12px; font-weight:600; color:var(--emerald); white-space:nowrap;">{{ $row->Clave_Articulo }}</td>
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

<style>
.sticky-col-1 {
    position: sticky !important;
    box-shadow: 2px 0 5px rgba(0,0,0,0.3);
}
</style>
@endsection
