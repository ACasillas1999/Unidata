@extends('layouts.app')

@section('title', 'Homologación')
@section('breadcrumb', 'Homologación')

@section('content')

{{-- ── PAGE HEADER ──────────────────────────────────────────── --}}
<div class="page-header">
    <div class="page-header-content">
        <div class="page-header-icon page-header-icon--rose">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <polyline points="22 12 18 12 15 21 9 3 6 12 2 12"/>
            </svg>
        </div>
        <div>
            <h1 class="page-title">Homologación de Artículos</h1>
            <p class="page-subtitle">Matriz transversal y consolidada de todas las sucursales (Universo Completo)</p>
        </div>
    </div>
    <div class="page-header-actions" style="display:flex;gap:12px;align-items:center;">
        <div class="stat-chip stat-chip--green">
            <span class="stat-chip-dot"></span>
            Matriz Local
        </div>
        <form method="POST" action="{{ route('homologacion.sync') }}" id="sync-form" style="margin:0;">
            @csrf
            <button type="submit" class="btn btn--primary" style="display:flex;align-items:center;gap:6px;" onclick="this.innerHTML='Sincronizando (Tardará minutos)...'; this.disabled=true; this.style.opacity='0.7'; document.getElementById('sync-form').submit();">
                <svg viewBox="0 0 24 24" fill="none" width="18" height="18" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 2v6h-6"/><path d="M3 12a9 9 0 0 1 15-6.7L21 8"/><path d="M3 22v-6h6"/><path d="M21 12a9 9 0 0 1-15 6.7L3 16"/></svg>
                Sincronizar Maestro
            </button>
        </form>
    </div>
</div>

{{-- ── STATS STRIP (coverage por sucursal) ────────────────────── --}}
@if(!empty($stats) && $stats['total'] > 0)
<details class="card" style="margin-bottom: 12px; padding: 0; background-color: var(--surface-color); overflow: visible;">
    <summary style="padding: 12px 20px; font-weight: 600; cursor: pointer; display: flex; align-items: center; gap: 8px; user-select: none; border-bottom: 1px solid #1e293b; color: var(--text-color);">
        <svg viewBox="0 0 24 24" fill="none" width="18" height="18" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
        Mostrar / Ocultar Estadísticas Globales por Sucursal
    </summary>
    <div style="padding: 16px 20px;">
        <div class="homo-stats" style="display: flex; flex-wrap: wrap; gap: 12px;">
    <div class="homo-stat-card homo-stat--total" style="flex: 1; min-width: 140px;">
        <p class="homo-stat-label">Total Deasa</p>
        <p class="homo-stat-value">{{ number_format($stats['total']) }}</p>
        <p class="homo-stat-hint">artículos habilitados</p>
    </div>
    @php
        $cols = [];
        foreach($branches as $name => $info) {
            $key = strtolower($info['conn']);
            $cols[$name] = $stats[$key] ?? 0;
        }
    @endphp
    @foreach($cols as $label => $cnt)
        @php $pct = $stats['total'] > 0 ? round($cnt / $stats['total'] * 100) : 0; @endphp
        <div class="homo-stat-card" style="flex: 1; min-width: 140px;">
            <p class="homo-stat-label">{{ $label }}</p>
            <p class="homo-stat-value">{{ number_format($cnt) }}</p>
            <div class="homo-stat-bar-wrap">
                <div class="homo-stat-bar" style="width:{{ $pct }}%"></div>
            </div>
            <p class="homo-stat-pct">{{ $pct }}% activos</p>
        </div>
    @endforeach
        </div>
    </div>
</details>
@endif

{{-- ── SEARCH + FILTERS ─────────────────────────────────────── --}}
<div class="card" style="margin-bottom: 12px;">
    <div class="card-body" style="padding:12px 20px">
        <form method="GET" action="{{ route('homologacion.index') }}" id="homo-form" class="homo-filter-bar">
            {{-- Búsqueda --}}
            <div class="search-input-wrap" style="flex:1;min-width:200px">
                <span class="search-icon">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.35-4.35"/></svg>
                </span>
                <input
                    type="text"
                    name="q"
                    id="homo-q"
                    value="{{ $search }}"
                    placeholder="Código o descripción…"
                    class="search-input"
                    autocomplete="off"
                >
            </div>

            {{-- Filtro: sucursal --}}
            <div class="homo-filter-row">
                <select name="filtro" id="homo-filtro" class="form-select">
                    <option value="">Todas las sucursales</option>
                    @foreach($branches as $name => $info)
                        <option value="{{ $info['col'] }}" @selected($filterCol === $info['col'])>{{ $name }}</option>
                    @endforeach
                </select>

                <select name="estado" id="homo-estado" class="form-select">
                    <option value="">Cualquier estado</option>
                    <option value="ACTIVO"         @selected($filterVal==='ACTIVO')>Activo</option>
                    <option value="INACTIVO/FALTA" @selected($filterVal==='INACTIVO/FALTA')>Inactivo / Falta</option>
                </select>
            </div>

            <div style="display:flex;gap:8px;flex-shrink:0">
                <button type="submit" class="btn btn--primary btn--sm" id="homo-submit">Filtrar</button>
                <a href="{{ route('homologacion.index') }}" class="btn btn--ghost btn--sm">Limpiar</a>
            </div>
        </form>
    </div>
</div>

{{-- ── ALERTS (Success/Errores) ────────────────────────────── --}}
@if(session('success'))
<div class="alert alert--success" style="margin-bottom:16px; border-left: 4px solid #10b981; padding: 12px 16px; background: rgba(16, 185, 129, 0.1); color: #10b981; border-radius: 6px;">
    <strong>¡Éxito!</strong> {{ session('success') }}
</div>
@endif

@if(session('error') || $error)
<div class="alert alert--error" style="margin-bottom:16px;">
    <span class="alert-icon">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
    </span>
    <div>
        <p class="alert-title">¡Atención!</p>
        <p class="alert-body">{{ session('error') ?? $error }}</p>
    </div>
</div>
@endif

{{-- ── RESULTS TABLE ───────────────────────────────────────── --}}
<div class="card">
    <div class="card-header card-header--row">
        <div>
            <h2 class="card-title">Resultado de homologación</h2>
            <p class="card-subtitle">
                @if($search !== '')
                    Buscando: <strong>{{ $search }}</strong>
                    @if($filterCol && $filterVal)
                        · {{ $filterCol }} = <strong>{{ $filterVal }}</strong>
                    @endif
                @elseif($filterCol && $filterVal)
                    Filtrando por <strong>{{ $filterCol }}</strong> = <strong>{{ $filterVal }}</strong>
                @else
                    Universo de artículos consolidados
                @endif
            </p>
        </div>
        <span class="badge badge--slate">{{ count($articles->items()) }} en esta página</span>
    </div>

    <div class="table-wrap" style="height: 32vh; overflow-y: auto; overflow-x: auto; border-bottom: 1px solid #1e293b;">
        <table class="data-table" id="homo-table">
            <thead>
                <tr>
                    <th>Código Maestro</th>
                    <th>Descripción Universal</th>
                    @foreach($branches as $name => $info)
                        <th>{{ $name }}</th>
                    @endforeach
                </tr>
            </thead>
            <tbody>
                @forelse($articles as $row)
                    <tr>
                        <td class="td--code">{{ $row->Codigo_Deasa }}</td>
                        <td class="td--desc">{{ $row->Descripcion_Deasa }}</td>
                        @foreach($branches as $name => $info)
                            @php $val = $row->{$info['col']}; @endphp
                            <td>
                                @php
                                    $pillClass = '';
                                    if ($val === 'ACTIVO') $pillClass = 'homo-pill--ok';
                                    elseif ($val === 'INACTIVO') $pillClass = 'homo-pill--warn';
                                    else $pillClass = 'homo-pill--miss';
                                @endphp
                                <span class="homo-pill {{ $pillClass }}">
                                    @if($val === 'ACTIVO')
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>
                                        Activo
                                    @elseif($val === 'INACTIVO')
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
                                        Inactivo
                                    @else
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                                        Falta
                                    @endif
                                </span>
                            </td>
                        @endforeach
                    </tr>
                @empty
                    <tr>
                        <td colspan="{{ 2 + count($branches) }}" class="td--empty">
                            No hay artículos que coincidan con los filtros aplicados.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{-- Paginación --}}
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
