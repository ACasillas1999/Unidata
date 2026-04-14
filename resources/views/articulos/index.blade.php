@extends('layouts.app')

@section('title', 'Artículos')
@section('breadcrumb', 'Artículos')

@section('content')

<div class="page-header">
    <div class="page-header-content">
        <div class="page-header-icon" style="background:#f0f9ff; color:#0ea5e9;">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <rect x="2" y="3" width="20" height="14" rx="2"/>
                <path d="M8 21h8M12 17v4"/>
            </svg>
        </div>
        <div>
            <h1 class="page-title">Directorio de Artículos</h1>
            <p class="page-subtitle">Explora y consulta el catálogo desde cualquier base de datos conectada</p>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-body" style="padding:16px 20px">
        <form method="GET" action="{{ route('articulos.index') }}" id="art-form" class="homo-filter-bar">
            <input type="hidden" name="per_page" id="per_page_input" value="{{ request('per_page', 50) }}">
            
            {{-- Búsqueda --}}
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

            {{-- Filtro: sucursal --}}
            <div class="homo-filter-row">
                <select name="sucursal" class="form-select" onchange="this.form.submit()">
                    @foreach($branches as $key => $label)
                        <option value="{{ $key }}" @selected($sucursal === $key)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>

            <div style="display:flex;gap:8px;flex-shrink:0">
                <button type="submit" class="btn btn--primary btn--sm">Buscar</button>
                <a href="{{ route('articulos.index') }}" class="btn btn--ghost btn--sm">Limpiar</a>
                <a href="{{ route('articulos.export', request()->all()) }}" class="btn btn--primary btn--sm shadow-premium" style="background:var(--emerald); border-color:var(--emerald); color:white;" target="_blank">
                    <svg style="margin-right:4px;" viewBox="0 0 24 24" fill="none" width="14" height="14" stroke="currentColor" stroke-width="2.5"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
                    Excel
                </a>
            </div>
        </form>
    </div>
</div>

@if($error)
<div class="alert alert--error" style="margin-top: 20px;">
    <span class="alert-icon">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
    </span>
    <div>
        <p class="alert-title">Problema de conexión</p>
        <p class="alert-body">{{ $error }}</p>
    </div>
</div>
@endif

    {{-- Table Card --}}
    <div class="card" id="branch-catalog-card" style="margin-top:20px; overflow:hidden;">
        <div class="card-header card-header--row">
            <div>
                <h2 class="card-title">Catálogo en {{ $branches[$sucursal] ?? 'Base de datos' }}</h2>
                <p class="card-subtitle">
                    @if($search !== '')
                        Mostrando resultados para: <strong>{{ $search }}</strong>
                    @else
                        Listado general con esquema expandido (60+ campos)
                    @endif
                </p>
            </div>
            <div style="display:flex; align-items:center; gap:12px;">
                <div style="background: rgba(255,255,255,0.03); padding: 4px 10px; border-radius: 20px; border: 1px solid var(--border); display: flex; align-items: center; gap: 8px;">
                    <label style="font-size: 10px; font-weight: 700; color: var(--text-muted); text-transform: uppercase;">Mostrar:</label>
                    <select onchange="document.getElementById('per_page_input').value=this.value; document.getElementById('art-form').submit();" 
                            style="background: transparent; border: none; color: var(--violet-light); font-size: 11px; font-weight: 800; cursor: pointer; outline: none;">
                        <option value="50" @if($per_page == 50) selected @endif>50</option>
                        <option value="100" @if($per_page == 100) selected @endif>100</option>
                        <option value="250" @if($per_page == 250) selected @endif>250</option>
                        <option value="500" @if($per_page == 500) selected @endif>500</option>
                    </select>
                </div>
                <span class="badge badge--slate">{{ method_exists($articles, 'total') ? $articles->total() : count($articles->items()) }} registros</span>
            </div>
        </div>

        {{-- High Density Scroll Container --}}
        <div id="table-inner-wrap" style="overflow:auto; max-height: 70vh; position:relative; background: #0b0f1a;">
            <table class="data-table" style="border-collapse: separate; border-spacing: 0; width: 100%;">
                <thead style="position: sticky; top: 0; z-index: 10;">
                    <tr>
                        <th class="sticky-col-1" style="min-width: 140px; background: #1a1f2e; left: 0; z-index: 11;">Clave_Articulo</th>
                        <th class="sticky-col-2" style="min-width: 300px; background: #1a1f2e; left: 140px; z-index: 11;">Descripcion</th>
                        {{-- General --}}
                        <th style="min-width: 80px;">Unidad_Medida</th>
                        <th style="min-width: 100px;">Linea</th>
                        <th style="min-width: 100px;">Clasificacion</th>
                        <th style="min-width: 60px;">Area</th>
                        <th style="min-width: 100px;">Habilitado</th>
                        {{-- Precios --}}
                        <th style="min-width: 120px;">Precio_Lista</th>
                        <th style="min-width: 120px; color:var(--emerald);">Precio_Venta</th>
                        <th style="min-width: 120px;">Desc_Precio_Venta</th>
                        <th style="min-width: 120px;">Precio_Especial</th>
                        <th style="min-width: 120px;">Desc_Precio_Espec</th>
                        <th style="min-width: 120px;">Precio4</th>
                        <th style="min-width: 120px;">Desc_Precio4</th>
                        <th style="min-width: 120px;">Precio_Minimo</th>
                        <th style="min-width: 120px;">Desc_Precio_Minimo</th>
                        <th style="min-width: 120px;">PrecioTope</th>
                        <th style="min-width: 80px;">MN_USD</th>
                        {{-- SAT --}}
                        <th style="min-width: 100px;">IDSAT</th>
                        <th style="min-width: 60px;">IVA</th>
                        <th style="min-width: 100px;">IDImpuestoSAT</th>
                        <th style="min-width: 100px;">IDTipoFactor</th>
                        <th style="min-width: 100px;">ControlPedimentos</th>
                        {{-- Inventario --}}
                        <th style="min-width: 120px;">Existencia_Teorica</th>
                        <th style="min-width: 120px;">Existencia_Fisica</th>
                        <th style="min-width: 120px;">Punto_Reorden</th>
                        <th style="min-width: 120px;">Inventario_Minimo</th>
                        <th style="min-width: 120px;">Inventario_Maximo</th>
                        <th style="min-width: 120px;">Ubicacion</th>
                        <th style="min-width: 80px;">StdPack</th>
                        <th style="min-width: 80px;">Peso</th>
                        <th style="min-width: 80px;">Articulo_Kit</th>
                        <th style="min-width: 80px;">Articulo_Serie</th>
                        {{-- Costos --}}
                        <th style="min-width: 120px; color:var(--amber);">CostoVenta</th>
                        <th style="min-width: 120px;">PorcentajeDescuento</th>
                        <th style="min-width: 120px;">Costo_Promedio</th>
                        <th style="min-width: 120px;">Costo_Promedio_Ant</th>
                        <th style="min-width: 120px;">Costo_Ult_Compra</th>
                        <th style="min-width: 120px;">Fecha_Ult_Compra</th>
                        <th style="min-width: 120px;">Fecha_Alta</th>
                        <th style="min-width: 80px;">En_Promocion</th>
                        <th style="min-width: 80px;">Critico</th>
                        {{-- Proveedores --}}
                        <th style="min-width: 200px;">Desc_Proveedor</th>
                        <th style="min-width: 120px;">Clave_Proveedor_1</th>
                        <th style="min-width: 120px;">Costo_Act_Prov_1</th>
                        <th style="min-width: 120px;">Clave_Prov_2</th>
                        <th style="min-width: 120px;">Costo_Act_Prov_2</th>
                        <th style="min-width: 120px;">Fecha_Costo_Act_P</th>
                        {{-- Otros --}}
                        <th style="min-width: 120px;">Sustituto</th>
                        <th style="min-width: 120px;">Sustituto1</th>
                        <th style="min-width: 120px;">ArticuloConversion</th>
                        <th style="min-width: 80px;">Conversion</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($articles as $row)
                        <tr>
                            <td class="sticky-col-1 td--code" style="left: 0; background: #0f172a; border-right: 1px solid rgba(255,255,255,0.05);">{{ $row->Clave_Articulo }}</td>
                            <td class="sticky-col-2 td--desc" style="left: 140px; background: #0f172a; border-right: 1px solid rgba(255,255,255,0.05);">{{ $row->Descripcion }}</td>
                            {{-- General --}}
                            <td style="text-align: center;">{{ $row->Unidad_Medida }}</td>
                            <td>{{ $row->Linea }}</td>
                            <td>{{ $row->Clasificacion }}</td>
                            <td style="text-align: center;">{{ $row->Area }}</td>
                            <td>
                                <span class="homo-pill {{ $row->Habilitado == 1 ? 'homo-pill--ok' : 'homo-pill--miss' }}" style="font-size: 10px; padding: 2px 8px;">
                                    {{ $row->Habilitado == 1 ? 'Activo' : 'Inactivo' }}
                                </span>
                            </td>
                            {{-- Precios --}}
                            <td style="text-align: right; font-family: 'JetBrains Mono', monospace;">{{ number_format((float)($row->Precio_Lista ?? 0), 2) }}</td>
                            <td style="text-align: right; font-family: 'JetBrains Mono', monospace; color: var(--emerald); font-weight: 700;">{{ number_format((float)($row->Precio_Venta ?? 0), 2) }}</td>
                            <td style="text-align: center;">{{ number_format((float)($row->Desc_Precio_Venta ?? 0), 2) }}%</td>
                            <td style="text-align: right;">{{ number_format((float)($row->Precio_Especial ?? 0), 2) }}</td>
                            <td style="text-align: center;">{{ number_format((float)($row->Desc_Precio_Espec ?? 0), 2) }}%</td>
                            <td style="text-align: right;">{{ number_format((float)($row->Precio4 ?? 0), 2) }}</td>
                            <td style="text-align: center;">{{ number_format((float)($row->Desc_Precio4 ?? 0), 2) }}%</td>
                            <td style="text-align: right;">{{ number_format((float)($row->Precio_Minimo ?? 0), 2) }}</td>
                            <td style="text-align: center;">{{ number_format((float)($row->Desc_Precio_Minimo ?? 0), 2) }}%</td>
                            <td style="text-align: right;">{{ number_format((float)($row->PrecioTope ?? 0), 2) }}</td>
                            <td style="text-align: center;">{{ $row->MN_USD == 1 ? 'USD' : 'MXN' }}</td>
                            {{-- SAT --}}
                            <td style="font-size: 11px;">{{ $row->IDSAT }}</td>
                            <td style="text-align: center;">{{ number_format((float)($row->IVA ?? 16), 0) }}%</td>
                            <td style="font-size: 11px;">{{ $row->IDImpuestoSAT }}</td>
                            <td style="font-size: 11px; text-align: center;">{{ $row->IDTipoFactor }}</td>
                            <td style="text-align: center;">{!! $row->ControlPedimentos == 1 ? '<span style="color:var(--violet-light)">●</span>' : '' !!}</td>
                            {{-- Inventario --}}
                            <td style="text-align: right; font-family: 'JetBrains Mono', monospace;">{{ number_format((float)($row->Existencia_Teorica ?? 0), 2) }}</td>
                            <td style="text-align: right; font-family: 'JetBrains Mono', monospace; color: var(--violet-light);">{{ number_format((float)($row->Existencia_Fisica ?? 0), 2) }}</td>
                            <td style="text-align: right;">{{ number_format((float)($row->Punto_Reorden ?? 0), 2) }}</td>
                            <td style="text-align: right;">{{ number_format((float)($row->Inventario_Minimo ?? 0), 2) }}</td>
                            <td style="text-align: right;">{{ number_format((float)($row->Inventario_Maximo ?? 0), 2) }}</td>
                            <td>{{ $row->Ubicacion }}</td>
                            <td style="text-align: center;">{{ $row->StdPack }}</td>
                            <td style="text-align: center;">{{ $row->Peso }}</td>
                            <td style="text-align: center;">{!! $row->Articulo_Kit == 1 ? '<span style="color:var(--violet-light)">●</span>' : '' !!}</td>
                            <td style="text-align: center;">{!! $row->Articulo_Serie == 1 ? '<span style="color:var(--violet-light)">●</span>' : '' !!}</td>
                            {{-- Costos --}}
                            <td style="text-align: right; font-family: 'JetBrains Mono', monospace; color: var(--amber);">{{ number_format((float)($row->CostoVenta ?? 0), 2) }}</td>
                            <td style="text-align: center;">{{ number_format((float)($row->PorcentajeDescuento ?? 0), 2) }}%</td>
                            <td style="text-align: right;">{{ number_format((float)($row->Costo_Promedio ?? 0), 2) }}</td>
                            <td style="text-align: right;">{{ number_format((float)($row->Costo_Promedio_Ant ?? 0), 2) }}</td>
                            <td style="text-align: right;">{{ number_format((float)($row->Costo_Ult_Compra ?? 0), 2) }}</td>
                            <td style="text-align: center; font-size: 11px;">{{ $row->Fecha_Ult_Compra }}</td>
                            <td style="text-align: center; font-size: 11px;">{{ $row->Fecha_Alta }}</td>
                            <td style="text-align: center;">{!! $row->En_Promocion == 1 ? 'S' : '' !!}</td>
                            <td style="text-align: center;">{!! $row->Critico == 1 ? 'S' : '' !!}</td>
                            {{-- Proveedores --}}
                            <td>{{ $row->Desc_Proveedor }}</td>
                            <td>{{ $row->Clave_Proveedor_1 }}</td>
                            <td style="text-align: right;">{{ number_format((float)($row->Costo_Act_Prov_1 ?? 0), 2) }}</td>
                            <td>{{ $row->Clave_Prov_2 }}</td>
                            <td style="text-align: right;">{{ number_format((float)($row->Costo_Act_Prov_2 ?? 0), 2) }}</td>
                            <td style="text-align: center; font-size: 11px;">{{ $row->Fecha_Costo_Act_P }}</td>
                            {{-- Otros --}}
                            <td>{{ $row->Sustituto }}</td>
                            <td>{{ $row->Sustituto1 }}</td>
                            <td>{{ $row->ArticuloConversion }}</td>
                            <td style="text-align: center;">{{ $row->Conversion }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="60" style="padding: 40px; text-align: center; color: var(--text-muted);">No se encontraron artículos</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($articles->hasPages())
            <div class="card-footer" style="padding: 15px 20px; display: flex; justify-content: space-between; align-items: center; border-top: 1px solid var(--border);">
                <p style="font-size: 13px; color: var(--text-muted);">Página {{ $articles->currentPage() }} de {{ $articles->lastPage() }}</p>
                <div>{{ $articles->links('pagination::bootstrap-4') }}</div>
            </div>
        @endif
    </div>
</div>

<style>
.sticky-col-1, .sticky-col-2 {
    position: sticky !important;
    box-shadow: 2px 0 5px rgba(0,0,0,0.3);
}
.data-table thead th {
    background: #1a1f2e;
    color: var(--text-muted);
    font-size: 11px;
    font-weight: 800;
    text-transform: uppercase;
    padding: 12px 15px;
    border-bottom: 2px solid var(--border);
}
.data-table tbody td {
    padding: 10px 15px;
    font-size: 12px;
    border-bottom: 1px solid rgba(255,255,255,0.05);
    white-space: nowrap;
}
.data-table tbody tr:hover td {
    background: rgba(139, 92, 246, 0.05) !important;
}
</style>

<script>
function adjustTableHeight() {
    const wrap = document.getElementById('table-inner-wrap');
    const card = document.getElementById('branch-catalog-card');
    if (!wrap || !card) return;
    const cardTop = card.getBoundingClientRect().top;
    const available = window.innerHeight - cardTop - 100; 
    wrap.style.height = Math.max(300, available) + 'px';
}
document.addEventListener('DOMContentLoaded', adjustTableHeight);
window.addEventListener('resize', adjustTableHeight);
</script>

@endsection
