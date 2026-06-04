@extends('layouts.app')

@section('title', 'Artículos Pendientes')
@section('breadcrumb', 'Artículos Pendientes')

@section('content')

<div class="page-header">
    <div class="page-header-content">
        <div class="page-header-icon" style="background:rgba(234,179,8,0.15); color:#facc15;">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/>
            </svg>
        </div>
        <div>
            <h1 class="page-title">Artículos Pendientes (Sin Configurar)</h1>
            <p class="page-subtitle">Estos artículos no entraron a la matriz porque su línea no ha sido configurada como "Sí se pasa".</p>
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

<div class="card" style="flex: 1; min-height: 0; display: flex; flex-direction: column; margin-bottom: 24px;">
    <div class="card-header card-header--row" style="background:rgba(255,255,255,0.02); border-bottom:1px solid var(--border); padding:12px 16px; flex-shrink: 0;">
        <form method="GET" action="{{ route('homologacion.pendientes') }}" style="display:flex; gap:12px; width:100%; align-items:center; margin:0;">
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
            <a href="{{ route('homologacion.pendientes') }}" class="btn btn--ghost btn--sm">Limpiar</a>
        </form>
    </div>

    <div style="flex: 1; overflow: auto; min-height: 0;">
        <table class="data-table" style="width:100%;">
            <thead>
                <tr>
                    <th style="background:#111827;">Clave</th>
                    <th style="background:#111827;">Descripción</th>
                    <th style="background:#111827;">Línea</th>
                    <th style="background:#111827;">Sucursal</th>
                    <th style="background:#111827; text-align:center;">Acción</th>
                </tr>
            </thead>
            <tbody>
                @forelse($pendientes as $item)
                <tr>
                    <td style="font-family:monospace; font-weight:700; color:var(--violet-light);">{{ $item->clave }}</td>
                    <td style="color:var(--text-primary); font-size:13px;">{{ $item->descripcion }}</td>
                    <td style="font-family:monospace; color:#facc15;">{{ $item->linea }}</td>
                    <td style="font-size:12px; font-weight:700; text-transform:uppercase; color:var(--text-muted);">{{ $item->sucursal }}</td>
                    <td style="text-align:center;">
                        <form method="POST" action="{{ route('homologacion.lineas.store') }}">
                            @csrf
                            <input type="hidden" name="linea" value="{{ $item->linea }}">
                            <input type="hidden" name="tipo" value="si">
                            <button type="submit" class="btn btn--sm" style="background:rgba(16,185,129,0.1); border:1px solid rgba(16,185,129,0.3); color:#34d399; font-size:10px; padding:4px 8px;">Aceptar Línea</button>
                        </form>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="5" style="padding:40px; text-align:center; color:var(--text-muted);">
                        No hay artículos pendientes.
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    
    @if($pendientes->hasPages())
    <div style="padding:12px 16px; border-top:1px solid var(--border); background:var(--bg-card); flex-shrink: 0;">
        {{ $pendientes->links('pagination::bootstrap-4') }}
    </div>
    @endif
</div>

<style>
.data-table th { font-size:11px; font-weight:800; color:var(--text-muted); text-transform:uppercase; padding:12px 16px; border-bottom:1px solid var(--border); text-align:left; }
.data-table td { padding:10px 16px; border-bottom:1px solid rgba(255,255,255,0.04); }
.data-table tbody tr:hover td { background:rgba(255,255,255,0.02); }
</style>

@endsection
