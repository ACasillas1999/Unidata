@extends('layouts.app')

@section('title', 'Clientes')
@section('breadcrumb', 'Clientes')

@section('content')

{{-- Page Header --}}
<div class="page-header">
    <div class="page-header-content">
        <div class="page-header-icon" style="background: rgba(99,102,241,0.15); color: #818cf8;">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/>
                <circle cx="9" cy="7" r="4"/>
                <path d="M23 21v-2a4 4 0 0 0-3-3.87M16 3.13a4 4 0 0 1 0 7.75"/>
            </svg>
        </div>
        <div>
            <h1 class="page-title">Clientes Homologados</h1>
            <p class="page-subtitle">Catálogo maestro · gestión global en todas las sucursales</p>
        </div>
    </div>
    <div style="display:flex; gap:10px; align-items:center;">
        <a href="{{ route('clientes.campos') }}" class="btn btn--ghost btn--sm">
            <svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2.5" style="margin-right:5px"><path d="M12 20h9"/><path d="M16.5 3.5a2.121 2.121 0 0 1 3 3L7 19l-4 1 1-4L16.5 3.5z"/></svg>
            Configurar Campos
        </a>
        <a href="{{ route('clientes.create') }}" class="btn btn--primary btn--sm shadow-premium" style="background:var(--grad-premium);">
            <svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2.5" style="margin-right:5px"><path d="M12 5v14M5 12h14"/></svg>
            Nuevo Cliente
        </a>
    </div>
</div>

{{-- Alertas --}}
@if(session('success'))
    <div class="alert alert--success" style="margin-bottom:16px;">
        <span class="alert-icon">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
        </span>
        <div><p class="alert-body">{{ session('success') }}</p></div>
    </div>
@endif

@if(session('warning'))
    <div class="alert" style="background:rgba(251,191,36,0.08); border:1px solid rgba(251,191,36,0.25); border-radius:10px; padding:12px 16px; margin-bottom:16px; display:flex; gap:10px; align-items:center; color:#fbbf24; font-size:13px;">
        <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2.5" style="flex-shrink:0"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>
        <span>{!! session('warning') !!}</span>
    </div>
@endif

{{-- Barra de búsqueda --}}
<div class="card">
    <div class="card-body" style="padding:14px 20px;">
        <form method="GET" action="{{ route('clientes.index') }}" id="cli-form" class="homo-filter-bar">
            <input type="hidden" name="per_page" id="per_page_input" value="{{ $per_page }}">

            <div class="search-input-wrap" style="flex:1; min-width:220px;">
                <span class="search-icon">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.35-4.35"/></svg>
                </span>
                <input type="text" name="q" value="{{ $search }}" placeholder="Buscar por RFC o Razón Social…" class="search-input" autocomplete="off">
            </div>

            <div style="display:flex; gap:8px; flex-shrink:0;">
                <button type="submit" class="btn btn--primary btn--sm">Buscar</button>
                @if($search)
                    <a href="{{ route('clientes.index') }}" class="btn btn--ghost btn--sm">Limpiar</a>
                @endif
            </div>
        </form>
    </div>
</div>

{{-- Tabla --}}
<div class="card" id="cli-card" style="margin-top:20px; overflow:hidden;">
    <div class="card-header card-header--row">
        <div>
            <h2 class="card-title">Catálogo de Clientes</h2>
            <p class="card-subtitle">
                @if($search)
                    Resultados para: <strong>{{ $search }}</strong>
                @else
                    Listado completo del maestro homologado
                @endif
            </p>
        </div>
        <div style="display:flex; align-items:center; gap:12px;">
            <div style="background:rgba(255,255,255,0.03); padding:4px 10px; border-radius:20px; border:1px solid var(--border); display:flex; align-items:center; gap:8px;">
                <label style="font-size:10px; font-weight:700; color:var(--text-muted); text-transform:uppercase;">Mostrar:</label>
                <select onchange="document.getElementById('per_page_input').value=this.value; document.getElementById('cli-form').submit();"
                        style="background:transparent; border:none; color:var(--violet-light); font-size:11px; font-weight:800; cursor:pointer; outline:none;">
                    @foreach([50,100,250,500] as $pp)
                        <option value="{{ $pp }}" @if($per_page==$pp) selected @endif>{{ $pp }}</option>
                    @endforeach
                </select>
            </div>
            <span class="badge badge--slate">{{ $clientes->total() }} registros</span>
        </div>
    </div>

    <div id="cli-table-wrap" style="overflow:auto; max-height:70vh; background:#0b0f1a;">
        <table class="data-table" style="border-collapse:separate; border-spacing:0; width:100%;">
            <thead style="position:sticky; top:0; z-index:10;">
                <tr>
                    <th style="min-width:90px; background:#1a1f2e;">ID Global</th>
                    <th class="sticky-col-1" style="min-width:140px; background:#1a1f2e; position:sticky; left:0; z-index:11;">RFC</th>
                    <th class="sticky-col-2" style="min-width:280px; background:#1a1f2e; position:sticky; left:140px; z-index:11;">Razón Social</th>
                    <th style="min-width:130px; background:#1a1f2e;">Teléfono</th>
                    <th style="min-width:100px; background:#1a1f2e;">Ciudad</th>
                    <th style="min-width:120px; background:#1a1f2e;">Cta. Contable</th>
                    <th style="min-width:100px; background:#1a1f2e;">Cond. Pago</th>
                    <th style="min-width:110px; background:#1a1f2e; text-align:right;">Límite Crédito</th>
                    <th style="min-width:100px; background:#1a1f2e;">Fecha Alta</th>
                    <th style="min-width:90px; background:#1a1f2e; text-align:center;">Estatus</th>
                    <th style="min-width:90px; background:#1a1f2e; text-align:center;">Acciones</th>
                </tr>
            </thead>
            <tbody>
                @forelse($clientes as $c)
                    <tr>
                        <td style="font-family:'JetBrains Mono',monospace; font-size:12px; color:#34d399; font-weight:700; text-align:center;">
                            {{ $c->id_global > 0 ? '#'.$c->id_global : '—' }}
                        </td>
                        <td class="sticky-col-1" style="position:sticky; left:0; background:#0f172a; border-right:1px solid rgba(255,255,255,0.05); font-family:'JetBrains Mono',monospace; font-size:11px; color:#a78bfa; white-space:nowrap;">
                            {{ $c->rfc }}
                        </td>
                        <td class="sticky-col-2" style="position:sticky; left:140px; background:#0f172a; border-right:1px solid rgba(255,255,255,0.05); font-weight:600; white-space:nowrap;">
                            {{ $c->razon_social }}
                        </td>
                        <td style="font-size:12px; color:var(--text-muted);">{{ $c->telefono1 ?: '—' }}</td>
                        <td style="font-size:12px; color:var(--text-muted);">{{ $c->ciudad ?: '—' }}</td>
                        <td style="font-size:11px; font-family:monospace; color:var(--text-muted);">{{ $c->cta_contable ?: '—' }}</td>
                        <td style="font-size:12px; text-align:center;">{{ $c->condicion_pago ?: '—' }}</td>
                        <td style="font-size:12px; text-align:right; font-family:'JetBrains Mono',monospace; color:var(--amber);">
                            {{ $c->limite_credito > 0 ? '$'.number_format($c->limite_credito, 2) : '—' }}
                        </td>
                        <td style="font-size:11px; color:var(--text-muted); text-align:center;">
                            {{ $c->fecha_alta && $c->fecha_alta !== '0000-00-00' ? $c->fecha_alta : '—' }}
                        </td>
                        <td style="text-align:center;">
                            @if($c->status === 'A')
                                <span class="homo-pill homo-pill--ok" style="font-size:10px; padding:2px 8px;">ACTIVO</span>
                            @else
                                <span class="homo-pill homo-pill--miss" style="font-size:10px; padding:2px 8px;">INACTIVO</span>
                            @endif
                        </td>
                        <td style="text-align:center; white-space:nowrap;">
                            <div style="display:flex; gap:5px; justify-content:center;">
                                @if($c->rfc)
                                <a href="{{ route('clientes.edit', $c->rfc) }}" title="Editar"
                                   style="width:28px; height:28px; background:rgba(99,102,241,0.15); border:1px solid rgba(99,102,241,0.3); border-radius:6px; display:inline-flex; align-items:center; justify-content:center; color:#818cf8; text-decoration:none;"
                                   onmouseover="this.style.background='rgba(99,102,241,0.3)'"
                                   onmouseout="this.style.background='rgba(99,102,241,0.15)'">
                                    <svg viewBox="0 0 24 24" width="13" height="13" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
                                </a>
                                <button onclick="confirmarBloqueo('{{ addslashes($c->rfc) }}','{{ addslashes($c->razon_social) }}')"
                                        title="Bloquear"
                                        style="width:28px; height:28px; background:rgba(239,68,68,0.1); border:1px solid rgba(239,68,68,0.25); border-radius:6px; display:inline-flex; align-items:center; justify-content:center; color:#f87171; cursor:pointer;"
                                        onmouseover="this.style.background='rgba(239,68,68,0.25)'"
                                        onmouseout="this.style.background='rgba(239,68,68,0.1)'">
                                    <svg viewBox="0 0 24 24" width="13" height="13" fill="none" stroke="currentColor" stroke-width="2.5"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
                                </button>
                                @endif
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="11" style="padding:60px; text-align:center; color:var(--text-muted);">
                            <svg viewBox="0 0 24 24" width="36" height="36" fill="none" stroke="currentColor" stroke-width="1.5" style="display:block; margin:0 auto 10px; opacity:0.4;"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/></svg>
                            {{ $search ? 'Sin resultados para "'.$search.'"' : 'No hay clientes homologados aún. ¡Crea el primero!' }}
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($clientes->hasPages())
        <div class="card-footer" style="padding:14px 20px; display:flex; justify-content:space-between; align-items:center; border-top:1px solid var(--border);">
            <p style="font-size:13px; color:var(--text-muted);">Página {{ $clientes->currentPage() }} de {{ $clientes->lastPage() }}</p>
            <div>{{ $clientes->links('pagination::bootstrap-4') }}</div>
        </div>
    @endif
</div>

{{-- Form oculto para bloquear --}}
<form id="form-bloqueo" method="POST" style="display:none;">
    @csrf
    @method('DELETE')
</form>

<style>
.data-table thead th {
    background: #1a1f2e;
    color: var(--text-muted);
    font-size: 11px;
    font-weight: 800;
    text-transform: uppercase;
    padding: 11px 14px;
    border-bottom: 2px solid var(--border);
    white-space: nowrap;
}
.data-table tbody td {
    padding: 10px 14px;
    font-size: 13px;
    border-bottom: 1px solid rgba(255,255,255,0.05);
}
.data-table tbody tr:hover td {
    background: rgba(139,92,246,0.05) !important;
}
</style>

<script>
function adjustTableHeight() {
    const wrap = document.getElementById('cli-table-wrap');
    const card = document.getElementById('cli-card');
    if (!wrap || !card) return;
    const top = card.getBoundingClientRect().top;
    wrap.style.height = Math.max(300, window.innerHeight - top - 110) + 'px';
}
document.addEventListener('DOMContentLoaded', adjustTableHeight);
window.addEventListener('resize', adjustTableHeight);

function confirmarBloqueo(rfc, nombre) {
    if (!confirm('¿Bloquear a ' + nombre + ' en todas las sucursales?')) return;
    const form = document.getElementById('form-bloqueo');
    form.action = '/clientes/' + encodeURIComponent(rfc);
    form.submit();
}
</script>

@endsection
