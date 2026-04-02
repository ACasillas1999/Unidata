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

<div class="card" style="margin-top:20px;">
    <div class="card-header card-header--row">
        <div>
            <h2 class="card-title">Catálogo en {{ $branches[$sucursal] ?? 'Base de datos' }}</h2>
            <p class="card-subtitle">
                @if($search !== '')
                    Mostrando resultados para: <strong>{{ $search }}</strong>
                @else
                    Listado general de artículos
                @endif
            </p>
        </div>
        <span class="badge badge--slate">{{ method_exists($articles, 'total') ? $articles->total() : count($articles->items()) }} registros</span>
    </div>

    <div class="table-wrap" style="max-height: 60vh; overflow-y: auto; border-bottom: 1px solid #1e293b;">
        <table class="data-table">
            <thead>
                <tr>
                    <th style="width: 15%">Clave</th>
                    <th style="width: 60%">Descripción</th>
                    <th style="width: 25%">Estatus</th>
                </tr>
            </thead>
            <tbody>
                @forelse($articles as $row)
                    <tr>
                        <td class="td--code">{{ $row->Clave_Articulo }}</td>
                        <td class="td--desc">{{ $row->Descripcion }}</td>
                        <td>
                            @if(isset($row->Habilitado) && $row->Habilitado)
                                <span class="homo-pill homo-pill--ok">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>
                                    Habilitado
                                </span>
                            @else
                                <span class="homo-pill homo-pill--miss">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                                    Inactivo
                                </span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="3" class="td--empty" style="padding:40px; text-align:center;">
                            No se encontraron artículos en esta base de datos
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($articles->hasPages())
        <div class="card-footer" style="display:flex;justify-content:space-between;align-items:center;gap:12px;flex-wrap:wrap; padding: 15px 20px;">
            <p style="font-size:14px;color:var(--text-muted);margin:0;">
                Página <strong>{{ $articles->currentPage() }}</strong> de {{ $articles->lastPage() }}
            </p>
            <div style="background:var(--surface-color); border-radius: 8px; padding:4px;">
                {{ $articles->links('pagination::bootstrap-4') }}
            </div>
        </div>
    @endif
</div>

@endsection
