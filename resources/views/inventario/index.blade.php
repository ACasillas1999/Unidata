@extends('layouts.app')

@section('title', 'Inventario por Sucursal')
@section('breadcrumb', 'Artículos / Inventario')

@section('content')

<div class="page-header shadow-premium" style="margin-bottom: 24px; padding: 20px 24px; background: var(--grad-surface); border-radius: var(--radius-xl); border: 1px solid var(--glass-border); display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 16px;">
    <div style="display: flex; gap: 20px; align-items: center;">
        <div class="page-header-icon shadow-premium" style="background: rgba(14, 165, 233, 0.1); border: 1px solid rgba(14, 165, 233, 0.3); color: #38bdf8;">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" width="24" height="24">
                <path d="M20 7h-9a2 2 0 0 0-2 2v9"/><path d="M3 9v10a2 2 0 0 0 2 2h9"/>
                <rect x="12" y="2" width="10" height="10" rx="2"/>
            </svg>
        </div>
        <div>
            <h1 class="page-title" style="margin:0;">Inventario por Sucursal</h1>
            <p class="page-subtitle" style="margin:4px 0 0; color: var(--text-secondary);">Gestión de existencias, mínimos y máximos por almacén en el ERP</p>
        </div>
    </div>

    <div class="page-header-actions" style="display:flex; gap:10px; align-items:center; flex-wrap:wrap;">
        <button type="button" onclick="abrirModalSingle('', '', 0, 0)" class="btn btn--sm shadow-premium" style="background: rgba(139,92,246,0.15); color: #a78bfa; border: 1px solid rgba(139,92,246,0.3); font-weight:700;">
            <svg style="margin-right:6px;" viewBox="0 0 24 24" fill="none" width="14" height="14" stroke="currentColor" stroke-width="2.5"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
            Editar Mín/Máx Manual
        </button>

        <button type="button" onclick="abrirModalCsv()" class="btn btn--sm shadow-premium" style="background: rgba(59,130,246,0.15); color: #60a5fa; border: 1px solid rgba(59,130,246,0.3); font-weight:700;">
            <svg style="margin-right:6px;" viewBox="0 0 24 24" fill="none" width="14" height="14" stroke="currentColor" stroke-width="2.5"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="17 8 12 3 7 8"/><line x1="12" y1="3" x2="12" y2="15"/></svg>
            Carga Masiva (CSV)
        </button>

        <a href="{{ route('inventario.plantilla') }}" class="btn btn--sm btn--ghost" style="font-weight:600; font-size:12px;" title="Descargar plantilla CSV con encabezados clave_articulo, inventario_maximo, inventario_minimo">
            <svg style="margin-right:4px;" viewBox="0 0 24 24" fill="none" width="14" height="14" stroke="currentColor" stroke-width="2.5"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="12" y1="18" x2="12" y2="12"/><line x1="9" y1="15" x2="15" y2="15"/></svg>
            Plantilla CSV
        </a>

        <div style="height: 24px; width: 1px; background: var(--border); margin: 0 4px;"></div>

        <span style="font-size:11px; font-weight:700; color:var(--text-muted); text-transform:uppercase;">Exportar PowerSales:</span>
        <a href="{{ route('inventario.export', ['sucursal' => $sucursal]) }}" class="btn btn--sm shadow-premium" style="background:rgba(16,185,129,0.1); color:var(--emerald); border:1px solid rgba(16,185,129,0.3);">
            {{ $branchesMap[$sucursal] ?? $sucursal }}
        </a>
        <a href="{{ route('inventario.export', ['sucursal' => 'todas']) }}" class="btn btn--sm shadow-premium" style="background:rgba(59,130,246,0.1); color:#60a5fa; border:1px solid rgba(59,130,246,0.3);">
            Todas
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
            <input type="text" name="q" value="{{ $search }}" placeholder="Buscar por clave de artículo o descripción…" class="search-input" autocomplete="off" style="width: 100%; border:none; background:transparent; padding:9px 12px 9px 0; color:white; outline:none; font-size:13px; font-weight:600;">
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
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
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
                <th style="padding: 12px 16px; text-align: right; font-size: 11px; font-weight: 800; color: #a78bfa; text-transform: uppercase; white-space:nowrap; background: rgba(139,92,246,0.05);">
                    <a href="{{ request()->fullUrlWithQuery(['sort' => 'minimo', 'dir' => ($sort === 'minimo' && $dir === 'asc') ? 'desc' : 'asc']) }}" style="color:inherit; text-decoration:none; display:inline-flex; align-items:center; gap:4px; justify-content:flex-end;">
                        Mín.
                        @if($sort === 'minimo')
                            <span style="color:var(--emerald); font-weight:bold;">{{ $dir === 'asc' ? '↑' : '↓' }}</span>
                        @else
                            <span style="opacity:0.3; font-size:10px;">↕</span>
                        @endif
                    </a>
                </th>
                <th style="padding: 12px 16px; text-align: right; font-size: 11px; font-weight: 800; color: #a78bfa; text-transform: uppercase; white-space:nowrap; background: rgba(139,92,246,0.05);">
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
                <th style="padding: 12px 16px; text-align: center; font-size: 11px; font-weight: 800; color: var(--text-secondary); text-transform: uppercase; white-space:nowrap;">Acciones</th>
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
                <td style="padding: 10px 16px; text-align:right; color:#c4b5fd; font-weight:700; white-space:nowrap; background: rgba(139,92,246,0.03);">{{ number_format((float) $row->Inventario_Minimo, 2) }}</td>
                <td style="padding: 10px 16px; text-align:right; color:#c4b5fd; font-weight:700; white-space:nowrap; background: rgba(139,92,246,0.03);">{{ number_format((float) $row->Inventario_Maximo, 2) }}</td>
                <td style="padding: 10px 16px; text-align:right; color:var(--text-muted); white-space:nowrap;">{{ number_format((float) $row->Punto_Reorden, 2) }}</td>
                <td style="padding: 10px 16px; text-align:center; white-space:nowrap;">
                    <button type="button" class="btn btn--sm shadow-premium" style="background: rgba(139,92,246,0.1); color: #a78bfa; border: 1px solid rgba(139,92,246,0.3); padding: 4px 10px; font-size: 11px;"
                            onclick="abrirModalSingle('{{ $row->Clave_Articulo }}', '{{ addslashes($row->descripcion) }}', {{ (float)$row->Inventario_Minimo }}, {{ (float)$row->Inventario_Maximo }})"
                            title="Editar Mínimo y Máximo de este artículo">
                        <svg style="margin-right:3px;" viewBox="0 0 24 24" fill="none" width="12" height="12" stroke="currentColor" stroke-width="2.5"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
                        Editar Mín/Máx
                    </button>
                </td>
            </tr>
            @empty
            <tr><td colspan="11" style="padding: 40px; text-align: center; color: var(--text-muted);">Sin existencias para mostrar.</td></tr>
            @endforelse
        </tbody>
    </table>
    </div>
    <div id="inv-pagination" style="padding: 12px 20px;">
        {{ $items->links() }}
    </div>
</div>

{{-- MODAL EDITAR SINGLE --}}
<div id="modal-single-edit" style="display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.85); backdrop-filter: blur(10px); z-index: 99999; align-items: center; justify-content: center; padding: 20px;">
    <div class="glass-card shadow-premium" style="width: 100%; max-width: 580px; display: flex; flex-direction: column; overflow: hidden; border: 1px solid rgba(139,92,246,0.4);">
        <div style="padding: 18px 24px; border-bottom: 1px solid var(--border); display: flex; justify-content: space-between; align-items: center; background: rgba(139,92,246,0.08);">
            <div>
                <h3 style="margin:0; font-size: 16px; color: #a78bfa; font-weight:700;">Editar Mínimo y Máximo (ERP)</h3>
                <p id="single-desc-sub" style="margin:4px 0 0; font-size: 12px; color: var(--text-muted);">Actualizar inventario mínimo y máximo por sucursal</p>
            </div>
            <button type="button" onclick="cerrarModalSingle()" style="background: rgba(255,255,255,0.05); border: 1px solid rgba(255,255,255,0.1); color: white; border-radius: 8px; padding: 6px; cursor: pointer;">
                <svg viewBox="0 0 24 24" fill="none" width="18" height="18" stroke="currentColor" stroke-width="2.5"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
            </button>
        </div>

        <form action="{{ route('inventario.update_item') }}" method="POST" style="padding: 20px 24px;">
            @csrf

            <div style="margin-bottom: 16px;">
                <label style="display:block; font-size: 11px; font-weight:800; color: var(--text-secondary); text-transform:uppercase; margin-bottom: 6px;">Clave del Artículo</label>
                <input type="text" name="clave_articulo" id="single-clave" required style="width:100%; background: var(--bg-root); border: 1px solid var(--border); color: #60a5fa; font-family: 'JetBrains Mono', monospace; font-weight:700; border-radius: 8px; padding: 10px 14px; font-size: 14px;">
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px; margin-bottom: 20px;">
                <div>
                    <label style="display:block; font-size: 11px; font-weight:800; color: #a78bfa; text-transform:uppercase; margin-bottom: 6px;">Inventario Mínimo</label>
                    <input type="number" step="0.01" min="0" name="inventario_minimo" id="single-min" required style="width:100%; background: var(--bg-root); border: 1px solid var(--border); color: white; font-weight:700; border-radius: 8px; padding: 10px 14px; font-size: 14px;">
                </div>
                <div>
                    <label style="display:block; font-size: 11px; font-weight:800; color: #a78bfa; text-transform:uppercase; margin-bottom: 6px;">Inventario Máximo</label>
                    <input type="number" step="0.01" min="0" name="inventario_maximo" id="single-max" required style="width:100%; background: var(--bg-root); border: 1px solid var(--border); color: white; font-weight:700; border-radius: 8px; padding: 10px 14px; font-size: 14px;">
                </div>
            </div>

            {{-- SELECTOR DE SUCURSALES DESTINO --}}
            <div style="background: rgba(255,255,255,0.02); border: 1px solid var(--border); border-radius: 10px; padding: 14px; margin-bottom: 24px;">
                <label style="display:block; font-size: 11px; font-weight:800; color: var(--amber); text-transform:uppercase; margin-bottom: 10px;">Sucursales Destino</label>
                
                <div style="display: flex; flex-direction: column; gap: 10px;">
                    <label style="display: flex; align-items: center; gap: 8px; font-size: 13px; color: white; cursor: pointer;">
                        <input type="radio" name="target_type" value="todas" checked onchange="toggleSucursalesSingle('todas')">
                        <span style="font-weight:700;">Todas las sucursales activas</span>
                    </label>

                    <label style="display: flex; align-items: center; gap: 8px; font-size: 13px; color: white; cursor: pointer;">
                        <input type="radio" name="target_type" value="especifica" onchange="toggleSucursalesSingle('especifica')">
                        <span>Una sucursal en específico:</span>
                    </label>
                    <div id="single-branch-select-wrap" style="display: none; padding-left: 24px;">
                        <select name="branch_code" class="form-select" style="background: var(--bg-root); border: 1px solid var(--border); color: white; border-radius: 8px; padding: 6px 12px; font-size: 13px; width: 100%;">
                            @foreach($branchesMap as $code => $name)
                                <option value="{{ $code }}">{{ $name }} ({{ $code }})</option>
                            @endforeach
                        </select>
                    </div>

                    <label style="display: flex; align-items: center; gap: 8px; font-size: 13px; color: white; cursor: pointer;">
                        <input type="radio" name="target_type" value="seleccionadas" onchange="toggleSucursalesSingle('seleccionadas')">
                        <span>Seleccionar sucursales específicas:</span>
                    </label>
                    <div id="single-branch-checkboxes-wrap" style="display: none; padding-left: 24px; display: flex; flex-wrap: wrap; gap: 12px; margin-top: 4px;">
                        @foreach($branchesMap as $code => $name)
                            <label style="display: inline-flex; align-items: center; gap: 6px; font-size: 12px; color: #93c5fd; background: rgba(59,130,246,0.1); padding: 4px 10px; border-radius: 6px; border: 1px solid rgba(59,130,246,0.2); cursor: pointer;">
                                <input type="checkbox" name="branch_codes[]" value="{{ $code }}" checked>
                                <span>{{ $name }}</span>
                            </label>
                        @endforeach
                    </div>
                </div>
            </div>

            <div style="display: flex; justify-content: flex-end; gap: 12px;">
                <button type="button" onclick="cerrarModalSingle()" class="btn btn--ghost" style="padding: 8px 18px;">Cancelar</button>
                <button type="submit" class="btn btn--primary shadow-premium" style="background: var(--grad-premium); border:none; padding: 8px 24px; font-weight:700;">Guardar Cambios</button>
            </div>
        </form>
    </div>
</div>

{{-- MODAL CARGA MASIVA CSV --}}
<div id="modal-csv-upload" style="display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.85); backdrop-filter: blur(10px); z-index: 99999; align-items: center; justify-content: center; padding: 20px;">
    <div class="glass-card shadow-premium" style="width: 100%; max-width: 720px; display: flex; flex-direction: column; overflow: hidden; border: 1px solid rgba(59,130,246,0.4);">
        <div style="padding: 18px 24px; border-bottom: 1px solid var(--border); display: flex; justify-content: space-between; align-items: center; background: rgba(59,130,246,0.08);">
            <div>
                <h3 style="margin:0; font-size: 16px; color: #60a5fa; font-weight:700;">Carga Masiva de Mínimos y Máximos (CSV)</h3>
                <p style="margin:4px 0 0; font-size: 12px; color: var(--text-muted);">Actualizar existencias mínimas y máximas desde archivo CSV</p>
            </div>
            <button type="button" onclick="cerrarModalCsv()" style="background: rgba(255,255,255,0.05); border: 1px solid rgba(255,255,255,0.1); color: white; border-radius: 8px; padding: 6px; cursor: pointer;">
                <svg viewBox="0 0 24 24" fill="none" width="18" height="18" stroke="currentColor" stroke-width="2.5"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
            </button>
        </div>

        <form action="{{ route('inventario.update_masivo') }}" method="POST" enctype="multipart/form-data" style="padding: 20px 24px;">
            @csrf

            <div style="background: rgba(139,92,246,0.05); border: 1px dashed rgba(139,92,246,0.3); border-radius: 10px; padding: 16px; margin-bottom: 20px; text-align: center;">
                <svg viewBox="0 0 24 24" fill="none" width="36" height="36" stroke="#a78bfa" stroke-width="2" style="margin-bottom: 8px;"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="17 8 12 3 7 8"/><line x1="12" y1="3" x2="12" y2="15"/></svg>
                <p style="margin: 0 0 6px; font-size: 13px; color: white; font-weight: 700;">Selecciona o arrastra el archivo CSV</p>
                <p style="margin: 0 0 12px; font-size: 12px; color: var(--text-muted);">El CSV debe incluir 3 columnas: <code style="color: #60a5fa;">clave_articulo</code>, <code style="color: #a78bfa;">inventario_maximo</code>, <code style="color: #a78bfa;">inventario_minimo</code></p>
                <input type="file" id="csv-file-input" name="file" accept=".csv,.txt" required onchange="previewCsvFile(this)" style="display: block; margin: 0 auto; color: #a78bfa; font-size: 13px;">
            </div>

            <div style="display: flex; justify-content: flex-end; margin-bottom: 16px;">
                <a href="{{ route('inventario.plantilla') }}" style="font-size: 12px; color: #60a5fa; text-decoration: none; display: flex; align-items: center; gap: 4px; font-weight: 600;">
                    <svg viewBox="0 0 24 24" fill="none" width="14" height="14" stroke="currentColor" stroke-width="2.5"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
                    Descargar Plantilla CSV de Ejemplo
                </a>
            </div>

            {{-- VISTA PREVIA DEL CSV --}}
            <div id="csv-preview-container" style="display: none; background: rgba(0,0,0,0.3); border: 1px solid rgba(59,130,246,0.3); border-radius: 10px; padding: 14px; margin-bottom: 20px;">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 10px;">
                    <span style="font-size: 11px; font-weight: 800; color: #60a5fa; text-transform: uppercase;">
                        Vista Previa del Archivo CSV
                    </span>
                    <div style="display: flex; gap: 6px;">
                        <span id="csv-badge-total" style="font-size: 11px; background: rgba(59,130,246,0.2); color: #93c5fd; padding: 2px 8px; border-radius: 12px; font-weight: 700;">0 Filas</span>
                        <span id="csv-badge-valid" style="font-size: 11px; background: rgba(16,185,129,0.2); color: #86efac; padding: 2px 8px; border-radius: 12px; font-weight: 700;">0 Válidas</span>
                        <span id="csv-badge-invalid" style="display: none; font-size: 11px; background: rgba(239,68,68,0.2); color: #fca5a5; padding: 2px 8px; border-radius: 12px; font-weight: 700;">0 Alertas</span>
                    </div>
                </div>
                <div style="max-height: 180px; overflow-y: auto; border: 1px solid var(--border); border-radius: 8px; background: var(--bg-root);">
                    <table style="width: 100%; border-collapse: collapse;">
                        <thead>
                            <tr style="border-bottom: 1px solid var(--border); background: rgba(255,255,255,0.03); font-size: 11px; color: var(--text-muted); text-transform: uppercase;">
                                <th style="padding: 6px 10px; text-align: center;">#</th>
                                <th style="padding: 6px 10px; text-align: left;">Clave Artículo</th>
                                <th style="padding: 6px 10px; text-align: left;">Inv. Mínimo</th>
                                <th style="padding: 6px 10px; text-align: left;">Inv. Máximo</th>
                                <th style="padding: 6px 10px; text-align: right;">Estado</th>
                            </tr>
                        </thead>
                        <tbody id="csv-preview-tbody">
                        </tbody>
                    </table>
                </div>
            </div>

            {{-- SELECTOR DE SUCURSALES DESTINO --}}
            <div style="background: rgba(255,255,255,0.02); border: 1px solid var(--border); border-radius: 10px; padding: 14px; margin-bottom: 24px;">
                <label style="display:block; font-size: 11px; font-weight:800; color: var(--amber); text-transform:uppercase; margin-bottom: 10px;">Sucursales Destino</label>
                
                <div style="display: flex; flex-direction: column; gap: 10px;">
                    <label style="display: flex; align-items: center; gap: 8px; font-size: 13px; color: white; cursor: pointer;">
                        <input type="radio" name="target_type" value="todas" checked onchange="toggleSucursalesCsv('todas')">
                        <span style="font-weight:700;">Todas las sucursales activas</span>
                    </label>

                    <label style="display: flex; align-items: center; gap: 8px; font-size: 13px; color: white; cursor: pointer;">
                        <input type="radio" name="target_type" value="especifica" onchange="toggleSucursalesCsv('especifica')">
                        <span>Una sucursal en específico:</span>
                    </label>
                    <div id="csv-branch-select-wrap" style="display: none; padding-left: 24px;">
                        <select name="branch_code" class="form-select" style="background: var(--bg-root); border: 1px solid var(--border); color: white; border-radius: 8px; padding: 6px 12px; font-size: 13px; width: 100%;">
                            @foreach($branchesMap as $code => $name)
                                <option value="{{ $code }}">{{ $name }} ({{ $code }})</option>
                            @endforeach
                        </select>
                    </div>

                    <label style="display: flex; align-items: center; gap: 8px; font-size: 13px; color: white; cursor: pointer;">
                        <input type="radio" name="target_type" value="seleccionadas" onchange="toggleSucursalesCsv('seleccionadas')">
                        <span>Seleccionar sucursales específicas:</span>
                    </label>
                    <div id="csv-branch-checkboxes-wrap" style="display: none; padding-left: 24px; display: flex; flex-wrap: wrap; gap: 12px; margin-top: 4px;">
                        @foreach($branchesMap as $code => $name)
                            <label style="display: inline-flex; align-items: center; gap: 6px; font-size: 12px; color: #93c5fd; background: rgba(59,130,246,0.1); padding: 4px 10px; border-radius: 6px; border: 1px solid rgba(59,130,246,0.2); cursor: pointer;">
                                <input type="checkbox" name="branch_codes[]" value="{{ $code }}" checked>
                                <span>{{ $name }}</span>
                            </label>
                        @endforeach
                    </div>
                </div>
            </div>

            <div style="display: flex; justify-content: flex-end; gap: 12px;">
                <button type="button" onclick="cerrarModalCsv()" class="btn btn--ghost" style="padding: 8px 18px;">Cancelar</button>
                <button type="submit" id="btn-submit-csv" class="btn btn--primary shadow-premium" style="background: var(--grad-premium); border:none; padding: 8px 24px; font-weight:700;">Procesar Carga Masiva</button>
            </div>
        </form>
    </div>
</div>

{{-- SWEETALERT RESULTADOS DE CARGA MASIVA O EDICIÓN INDIVIDUAL --}}
@if(session('csv_modal'))
@php $resCsv = session('csv_modal'); @endphp
<script>
document.addEventListener('DOMContentLoaded', () => {
    const totalFilas  = {{ $resCsv['totalFilas'] ?? 0 }};
    const exitosos    = {{ $resCsv['exitosos'] ?? 0 }};
    const totalSuc    = {{ $resCsv['totalSuc'] ?? 0 }};
    const isFullOk    = (exitosos === totalSuc);

    let htmlContent = `
        <div style="text-align: left; font-size: 13px;">
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-bottom: 16px;">
                <div style="background: rgba(59,130,246,0.12); border: 1px solid rgba(59,130,246,0.3); padding: 12px; border-radius: 10px; text-align: center;">
                    <span style="font-size: 11px; color: #93c5fd; font-weight: 800; display: block; text-transform: uppercase; margin-bottom: 2px;">Filas CSV Procesadas</span>
                    <span style="font-size: 20px; font-weight: 900; color: white;">${totalFilas}</span>
                </div>
                <div style="background: rgba(16,185,129,0.12); border: 1px solid rgba(16,185,129,0.3); padding: 12px; border-radius: 10px; text-align: center;">
                    <span style="font-size: 11px; color: #86efac; font-weight: 800; display: block; text-transform: uppercase; margin-bottom: 2px;">Sucursales Exitosas</span>
                    <span style="font-size: 20px; font-weight: 900; color: white;">${exitosos}/${totalSuc}</span>
                </div>
            </div>

            <div style="font-size: 11px; font-weight: 800; color: #94a3b8; text-transform: uppercase; margin-bottom: 8px;">Detalle por Sucursal:</div>
            <div style="max-height: 180px; overflow-y: auto; display: flex; flex-direction: column; gap: 8px; padding-right: 4px;">
                @foreach($resCsv['resultados'] as $r)
                    <div style="background: rgba(255,255,255,0.03); border: 1px solid rgba(255,255,255,0.08); padding: 10px 14px; border-radius: 8px; display: flex; justify-content: space-between; align-items: center;">
                        <span style="font-weight: 700; color: white;">{{ $r['sucursal'] }}</span>
                        @if(($r['status'] ?? '') === 'ok')
                            <span style="color: #86efac; font-weight: 600; font-size: 12px;">✓ {{ $r['actualizados'] }} actualizados {{ ($r['noEncontrados'] > 0) ? "({$r['noEncontrados']} no hallados)" : "" }}</span>
                        @else
                            <span style="color: #fca5a5; font-weight: 600; font-size: 12px;">✗ {{ $r['message'] ?? 'Error de conexión' }}</span>
                        @endif
                    </div>
                @endforeach
            </div>
        </div>
    `;

    Swal.fire({
        title: isFullOk ? '¡Carga Masiva Completada!' : 'Carga Masiva Finalizada con Advertencias',
        html: htmlContent,
        icon: isFullOk ? 'success' : 'warning',
        background: '#0f172a',
        color: '#ffffff',
        confirmButtonText: 'Entendido',
        confirmButtonColor: '#3b82f6',
        width: '580px'
    });
});
</script>
@endif

@if(session('minmax_modal'))
@php $resSingle = session('minmax_modal'); @endphp
<script>
document.addEventListener('DOMContentLoaded', () => {
    let htmlContent = `
        <div style="text-align: left; font-size: 13px;">
            <p style="margin: 0 0 14px; color: #94a3b8;">
                Se ha actualizado el inventario para el artículo <strong style="color: #60a5fa;">[{{ $resSingle['clave'] }}]</strong><br>
                (Mínimo: <strong style="color:#86efac;">{{ $resSingle['min'] }}</strong> | Máximo: <strong style="color:#86efac;">{{ $resSingle['max'] }}</strong>).
            </p>
            <div style="font-size: 11px; font-weight: 800; color: #94a3b8; text-transform: uppercase; margin-bottom: 8px;">Detalle por Sucursal:</div>
            <div style="max-height: 180px; overflow-y: auto; display: flex; flex-direction: column; gap: 8px; padding-right: 4px;">
                @foreach($resSingle['resultados'] as $r)
                    <div style="background: rgba(255,255,255,0.03); border: 1px solid rgba(255,255,255,0.08); padding: 10px 14px; border-radius: 8px; display: flex; justify-content: space-between; align-items: center;">
                        <span style="font-weight: 700; color: white;">{{ $r['sucursal'] }}</span>
                        @if(($r['status'] ?? '') === 'ok')
                            <span style="color: #86efac; font-weight: 600; font-size: 12px;">✓ {{ $r['message'] }}</span>
                        @else
                            <span style="color: #fca5a5; font-weight: 600; font-size: 12px;">✗ {{ $r['message'] }}</span>
                        @endif
                    </div>
                @endforeach
            </div>
        </div>
    `;

    Swal.fire({
        title: '¡Mínimo / Máximo Actualizado!',
        html: htmlContent,
        icon: 'success',
        background: '#0f172a',
        color: '#ffffff',
        confirmButtonText: 'Aceptar',
        confirmButtonColor: '#10b981',
        width: '540px'
    });
});
</script>
@endif

<style>
.sticky-col-1 {
    position: sticky !important;
    box-shadow: 2px 0 5px rgba(0,0,0,0.3);
}
</style>

<script>
function previewCsvFile(input) {
    const file = input.files[0];
    const previewContainer = document.getElementById('csv-preview-container');
    const tbody = document.getElementById('csv-preview-tbody');
    const badgeTotal = document.getElementById('csv-badge-total');
    const badgeValid = document.getElementById('csv-badge-valid');
    const badgeInvalid = document.getElementById('csv-badge-invalid');
    const btnSubmit = document.getElementById('btn-submit-csv');

    if (!file) {
        previewContainer.style.display = 'none';
        btnSubmit.textContent = 'Procesar Carga Masiva';
        return;
    }

    const reader = new FileReader();
    reader.onload = function(e) {
        const text = e.target.result;
        const lines = text.split(/\r\n|\n/).map(l => l.trim()).filter(l => l.length > 0);

        if (lines.length === 0) {
            previewContainer.style.display = 'none';
            return;
        }

        let sep = ',';
        if (lines[0].includes(';')) sep = ';';
        else if (lines[0].includes('\t')) sep = '\t';

        const rawHeaders = lines[0].split(sep).map(h => h.replace(/^["']|["']$/g, '').trim());
        let claveIdx = 0, maxIdx = 1, minIdx = 2;

        rawHeaders.forEach((h, idx) => {
            const clean = h.toLowerCase().replace(/[^a-z0-9]/g, '');
            if (['clavearticulo', 'clave', 'sku', 'codigo', 'productid'].includes(clean)) claveIdx = idx;
            else if (['inventariomaximo', 'inventariomax', 'maximo', 'max'].includes(clean)) maxIdx = idx;
            else if (['inventariominimo', 'inventariomin', 'minimo', 'min'].includes(clean)) minIdx = idx;
        });

        let startIndex = 1;
        const firstColClean = rawHeaders[0].toLowerCase().replace(/[^a-z0-9]/g, '');
        if (['clavearticulo', 'clave', 'sku', 'codigo', 'productid', 'clave_articulo'].includes(firstColClean)) {
            startIndex = 1;
        } else {
            if (isNaN(parseFloat(rawHeaders[1])) || isNaN(parseFloat(rawHeaders[2]))) {
                startIndex = 1;
            } else {
                startIndex = 0;
            }
        }

        tbody.innerHTML = '';
        let totalRows = 0;
        let validRows = 0;
        let invalidRows = 0;

        for (let i = startIndex; i < lines.length; i++) {
            const rowStr = lines[i];
            if (!rowStr) continue;

            const cols = rowStr.split(sep).map(c => c.replace(/^["']|["']$/g, '').trim());
            const clave = cols[claveIdx] || '';
            const minVal = parseFloat(cols[minIdx] || 0);
            const maxVal = parseFloat(cols[maxIdx] || 0);

            totalRows++;
            const isValid = (clave !== '') && !isNaN(minVal) && !isNaN(maxVal);
            if (isValid) validRows++; else invalidRows++;

            const tr = document.createElement('tr');
            tr.style.borderBottom = '1px solid rgba(255,255,255,0.05)';
            tr.style.fontSize = '12px';

            tr.innerHTML = `
                <td style="padding: 6px 10px; color: var(--text-muted); text-align: center;">${totalRows}</td>
                <td style="padding: 6px 10px; font-weight: 700; color: #93c5fd;">${clave || '<i style="color:#fca5a5;">(Vacío)</i>'}</td>
                <td style="padding: 6px 10px; color: white;">${isNaN(minVal) ? '-' : minVal}</td>
                <td style="padding: 6px 10px; color: white;">${isNaN(maxVal) ? '-' : maxVal}</td>
                <td style="padding: 6px 10px; text-align: right;">
                    ${isValid 
                        ? '<span style="color: #86efac; font-weight: 600; font-size: 11px;">✓ Listo</span>' 
                        : '<span style="color: #fca5a5; font-weight: 600; font-size: 11px;">⚠ Inválido</span>'}
                </td>
            `;
            tbody.appendChild(tr);
        }

        badgeTotal.textContent = `${totalRows} Filas`;
        badgeValid.textContent = `${validRows} Válidas`;
        badgeInvalid.textContent = `${invalidRows} Alertas`;
        badgeInvalid.style.display = invalidRows > 0 ? 'inline-block' : 'none';

        previewContainer.style.display = 'block';
        btnSubmit.textContent = `Procesar Carga Masiva (${validRows} Artículos)`;
    };

    reader.readAsText(file);
}

function abrirModalSingle(clave, desc, min, max) {
    document.getElementById('single-clave').value = clave;
    document.getElementById('single-min').value = min || 0;
    document.getElementById('single-max').value = max || 0;
    document.getElementById('single-desc-sub').textContent = desc ? `${clave} · ${desc}` : 'Actualizar inventario mínimo y máximo en las sucursales elegidas';
    document.getElementById('modal-single-edit').style.display = 'flex';
}

function cerrarModalSingle() {
    document.getElementById('modal-single-edit').style.display = 'none';
}

function abrirModalCsv() {
    document.getElementById('modal-csv-upload').style.display = 'flex';
}

function cerrarModalCsv() {
    document.getElementById('modal-csv-upload').style.display = 'none';
}

function toggleSucursalesSingle(type) {
    document.getElementById('single-branch-select-wrap').style.display = (type === 'especifica') ? 'block' : 'none';
    document.getElementById('single-branch-checkboxes-wrap').style.display = (type === 'seleccionadas') ? 'flex' : 'none';
}

function toggleSucursalesCsv(type) {
    document.getElementById('csv-branch-select-wrap').style.display = (type === 'especifica') ? 'block' : 'none';
    document.getElementById('csv-branch-checkboxes-wrap').style.display = (type === 'seleccionadas') ? 'flex' : 'none';
}

window.addEventListener('click', (e) => {
    if (e.target === document.getElementById('modal-single-edit')) cerrarModalSingle();
    if (e.target === document.getElementById('modal-csv-upload')) cerrarModalCsv();
});
window.addEventListener('keydown', (e) => {
    if (e.key === 'Escape') {
        cerrarModalSingle();
        cerrarModalCsv();
    }
});

document.addEventListener('DOMContentLoaded', () => {
    const csvForm = document.querySelector('#modal-csv-upload form');
    if (csvForm) {
        csvForm.addEventListener('submit', function() {
            cerrarModalCsv();
            Swal.fire({
                title: 'Procesando Carga Masiva...',
                html: `
                    <div style="font-size: 13px; color: #94a3b8; margin: 10px 0;">
                        Actualizando existencias mínimas y máximas en las sucursales seleccionadas.<br>
                        <strong style="color: #60a5fa;">Por favor espera un momento sin cerrar o recargar la página.</strong>
                    </div>
                `,
                background: '#0f172a',
                color: '#ffffff',
                allowOutsideClick: false,
                allowEscapeKey: false,
                showConfirmButton: false,
                didOpen: () => {
                    Swal.showLoading();
                }
            });
        });
    }

    const singleForm = document.querySelector('#modal-single-edit form');
    if (singleForm) {
        singleForm.addEventListener('submit', function() {
            cerrarModalSingle();
            Swal.fire({
                title: 'Actualizando Mínimo y Máximo...',
                html: `
                    <div style="font-size: 13px; color: #94a3b8; margin: 10px 0;">
                        Enviando cambios a las sucursales seleccionadas...
                    </div>
                `,
                background: '#0f172a',
                color: '#ffffff',
                allowOutsideClick: false,
                allowEscapeKey: false,
                showConfirmButton: false,
                didOpen: () => {
                    Swal.showLoading();
                }
            });
        });
    }
});
</script>
@endsection
