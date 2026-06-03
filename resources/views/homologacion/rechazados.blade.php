@extends('layouts.app')

@section('title', 'Artículos Rechazados')
@section('breadcrumb', 'Artículos Rechazados')

@section('content')

<div class="page-header">
    <div class="page-header-content">
        <div class="page-header-icon" style="background:rgba(239,68,68,0.15); color:#f87171;">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/>
            </svg>
        </div>
        <div>
            <h1 class="page-title">Artículos Rechazados por Filtros</h1>
            <p class="page-subtitle">Estos artículos no se incluyeron en la matriz durante el último sync porque pertenecen a una línea "No se pasa".</p>
        </div>
    </div>
    <div style="display:flex; gap:10px;">
        <a href="{{ route('homologacion.lineas') }}" class="btn btn--ghost btn--sm">
            Configurar Líneas
        </a>
        <a href="{{ route('homologacion.index') }}" class="btn btn--primary btn--sm" style="background:var(--grad-premium); border:none;">
            Volver a Matriz
        </a>
    </div>
</div>

<div class="card">
    <div class="card-header card-header--row" style="background:rgba(255,255,255,0.02); border-bottom:1px solid var(--border); padding:12px 16px;">
        <form method="GET" action="{{ route('homologacion.rechazados') }}" style="display:flex; gap:12px; width:100%; align-items:center; margin:0;">
            <div class="search-input-wrap" style="flex:1; margin:0; background:var(--bg-root); border-radius:8px; border:1px solid var(--border); display:flex; align-items:center; overflow:hidden;">
                <span style="padding:0 12px; color:var(--text-muted);"><svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.35-4.35"/></svg></span>
                <input type="text" name="q" value="{{ request('q') }}" placeholder="Buscar código, descripción o línea..." style="width:100%; border:none; background:transparent; padding:9px 12px 9px 0; color:white; outline:none; font-size:12px;">
            </div>
            <select name="sucursal" style="background:var(--bg-root); border:1px solid var(--border); border-radius:8px; padding:8px 12px; color:white; font-size:12px; outline:none;">
                <option value="">Todas las sucursales</option>
                @foreach($sucursales as $suc)
                    <option value="{{ $suc }}" {{ request('sucursal') == $suc ? 'selected' : '' }}>{{ $suc }}</option>
                @endforeach
            </select>
            <button type="submit" class="btn btn--sm" style="background:rgba(255,255,255,0.1); border:1px solid var(--border); color:white;">Buscar</button>
            <a href="{{ route('homologacion.rechazados') }}" class="btn btn--ghost btn--sm">Limpiar</a>
        </form>
    </div>

    <div style="overflow-x:auto;">
        <table class="data-table" style="width:100%;">
            <thead>
                <tr>
                    <th style="background:#111827;">Clave</th>
                    <th style="background:#111827;">Descripción</th>
                    <th style="background:#111827;">Línea</th>
                    <th style="background:#111827;">Sucursal</th>
                    <th style="background:#111827;">Motivo</th>
                </tr>
            </thead>
            <tbody>
                @forelse($rechazados as $item)
                <tr>
                    <td style="font-family:monospace; font-weight:700; color:var(--violet-light);">{{ $item->clave }}</td>
                    <td style="color:var(--text-primary); font-size:13px;">{{ $item->descripcion }}</td>
                    <td style="font-family:monospace; color:#f87171;">{{ $item->linea }}</td>
                    <td style="font-size:12px; font-weight:700; text-transform:uppercase; color:var(--text-muted);">{{ $item->sucursal }}</td>
                    <td style="font-size:12px; color:var(--rose);">{{ $item->motivo }}</td>
                </tr>
                @empty
                <tr>
                    <td colspan="5" style="padding:40px; text-align:center; color:var(--text-muted);">
                        No hay artículos rechazados con los filtros actuales.
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    
    @if($rechazados->hasPages())
    <div style="padding:12px 16px; border-top:1px solid var(--border); background:var(--bg-card);">
        {{ $rechazados->links('pagination::bootstrap-4') }}
    </div>
    @endif
</div>

<style>
.data-table th { font-size:11px; font-weight:800; color:var(--text-muted); text-transform:uppercase; padding:12px 16px; border-bottom:1px solid var(--border); text-align:left; }
.data-table td { padding:10px 16px; border-bottom:1px solid rgba(255,255,255,0.04); }
.data-table tbody tr:hover td { background:rgba(255,255,255,0.02); }
</style>

@endsection
