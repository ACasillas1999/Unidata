@extends('layouts.app')

@section('title', 'Configurar Líneas')
@section('breadcrumb', 'Líneas de Homologación')

@section('content')

<div class="page-header">
    <div class="page-header-content">
        <div class="page-header-icon" style="background:rgba(99,102,241,0.15); color:#818cf8;">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <line x1="8" y1="6" x2="21" y2="6"/><line x1="8" y1="12" x2="21" y2="12"/>
                <line x1="8" y1="18" x2="21" y2="18"/><line x1="3" y1="6" x2="3.01" y2="6"/>
                <line x1="3" y1="12" x2="3.01" y2="12"/><line x1="3" y1="18" x2="3.01" y2="18"/>
            </svg>
        </div>
        <div>
            <h1 class="page-title">Configurar Líneas de Homologación</h1>
            <p class="page-subtitle">Controla qué líneas de artículos se incluyen en el sync</p>
        </div>
    </div>
    <a href="{{ route('homologacion.index') }}" class="btn btn--ghost btn--sm">
        ← Volver a Homologación
    </a>
</div>

{{-- Alerta de éxito --}}
@if(session('success'))
    <div class="alert alert--success" style="margin-bottom:16px;">
        <span class="alert-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="20 6 9 17 4 12"/></svg></span>
        <div><p class="alert-body">{{ session('success') }}</p></div>
    </div>
@endif
@if($errors->any())
    <div class="alert alert--error" style="margin-bottom:16px;">
        <span class="alert-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg></span>
        <div>@foreach($errors->all() as $e)<p class="alert-body">{{ $e }}</p>@endforeach</div>
    </div>
@endif

{{-- Leyenda --}}
<div class="card" style="margin-bottom:20px;">
    <div class="card-body" style="padding:16px 20px; display:flex; gap:32px; flex-wrap:wrap; align-items:center;">
        <div style="display:flex; align-items:center; gap:8px;">
            <span style="width:12px; height:12px; background:#10b981; border-radius:50%; display:inline-block;"></span>
            <span style="font-size:13px; color:var(--text-secondary);"><strong style="color:#34d399;">Sí se pasa</strong> — incluye artículos activos <em>e inactivos</em></span>
        </div>
        <div style="display:flex; align-items:center; gap:8px;">
            <span style="width:12px; height:12px; background:#ef4444; border-radius:50%; display:inline-block;"></span>
            <span style="font-size:13px; color:var(--text-secondary);"><strong style="color:#f87171;">No se pasa</strong> — excluye todos los artículos aunque estén activos</span>
        </div>
        <div style="display:flex; align-items:center; gap:8px;">
            <span style="width:12px; height:12px; background:#64748b; border-radius:50%; display:inline-block;"></span>
            <span style="font-size:13px; color:var(--text-secondary);"><strong style="color:#94a3b8;">Sin configurar</strong> — comportamiento normal (solo activos)</span>
        </div>
    </div>
</div>

<div style="display:grid; grid-template-columns:1fr 340px; gap:20px; align-items:start;">

    {{-- ══ COLUMNA IZQUIERDA: tablas de líneas ══ --}}
    <div style="display:flex; flex-direction:column; gap:16px;">

        {{-- Sí se pasan --}}
        <div class="card" style="overflow:hidden;">
            <div class="card-header card-header--row" style="background:rgba(16,185,129,0.06); border-bottom:1px solid rgba(16,185,129,0.15);">
                <div>
                    <h2 class="card-title" style="color:#34d399;">✅ Sí se pasan</h2>
                    <p class="card-subtitle">{{ $si->count() }} líneas · activos e inactivos incluidos</p>
                </div>
            </div>
            @if($si->isNotEmpty())
            <div style="overflow-x:auto; max-height:320px; overflow-y:auto;">
                <table class="data-table" style="width:100%;">
                    <thead style="position:sticky; top:0; z-index:5;">
                        <tr>
                            <th style="background:#0f2420; min-width:120px;">Línea</th>
                            <th style="background:#0f2420;">Descripción</th>
                            <th style="background:#0f2420; width:60px; text-align:center;">Quitar</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($si as $item)
                        <tr>
                            <td style="font-family:monospace; font-weight:700; color:#34d399;">{{ $item->linea }}</td>
                            <td style="color:var(--text-secondary); font-size:12px;">{{ $item->descripcion ?: '—' }}</td>
                            <td style="text-align:center;">
                                <form method="POST" action="{{ route('homologacion.lineas.destroy', $item->id) }}" onsubmit="return confirm('¿Quitar esta línea de la configuración?');">
                                    @csrf @method('DELETE')
                                    <button type="submit" style="background:transparent; border:none; color:#f87171; cursor:pointer; padding:4px;" title="Eliminar">
                                        <svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="3 6 5 6 21 6"/><path d="M19 6l-1 14H6L5 6"/><path d="M10 11v6M14 11v6"/></svg>
                                    </button>
                                </form>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            @else
            <div style="padding:30px; text-align:center; color:var(--text-muted); font-size:13px;">Sin líneas configuradas como "sí se pasan"</div>
            @endif
        </div>

        {{-- No se pasan --}}
        <div class="card" style="overflow:hidden;">
            <div class="card-header card-header--row" style="background:rgba(239,68,68,0.06); border-bottom:1px solid rgba(239,68,68,0.15);">
                <div>
                    <h2 class="card-title" style="color:#f87171;">❌ No se pasan</h2>
                    <p class="card-subtitle">{{ $no->count() }} líneas · excluidas aunque tengan artículos activos</p>
                </div>
            </div>
            @if($no->isNotEmpty())
            <div style="overflow-x:auto; max-height:320px; overflow-y:auto;">
                <table class="data-table" style="width:100%;">
                    <thead style="position:sticky; top:0; z-index:5;">
                        <tr>
                            <th style="background:#200f0f; min-width:120px;">Línea</th>
                            <th style="background:#200f0f;">Descripción</th>
                            <th style="background:#200f0f; width:60px; text-align:center;">Quitar</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($no as $item)
                        <tr>
                            <td style="font-family:monospace; font-weight:700; color:#f87171;">{{ $item->linea }}</td>
                            <td style="color:var(--text-secondary); font-size:12px;">{{ $item->descripcion ?: '—' }}</td>
                            <td style="text-align:center;">
                                <form method="POST" action="{{ route('homologacion.lineas.destroy', $item->id) }}" onsubmit="return confirm('¿Quitar esta línea?');">
                                    @csrf @method('DELETE')
                                    <button type="submit" style="background:transparent; border:none; color:#f87171; cursor:pointer; padding:4px;" title="Eliminar">
                                        <svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="3 6 5 6 21 6"/><path d="M19 6l-1 14H6L5 6"/><path d="M10 11v6M14 11v6"/></svg>
                                    </button>
                                </form>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            @else
            <div style="padding:30px; text-align:center; color:var(--text-muted); font-size:13px;">Sin líneas configuradas como "no se pasan"</div>
            @endif
        </div>

        {{-- Sin configurar --}}
        @if($sinConfig->isNotEmpty())
        <div class="card" style="overflow:hidden;">
            <div class="card-header card-header--row" style="background:rgba(100,116,139,0.06); border-bottom:1px solid rgba(100,116,139,0.15);">
                <div>
                    <h2 class="card-title" style="color:#94a3b8;">⚪ Sin configurar</h2>
                    <p class="card-subtitle">{{ $sinConfig->count() }} líneas · solo artículos activos (comportamiento por defecto)</p>
                </div>
            </div>
            <div style="overflow-x:auto; max-height:280px; overflow-y:auto;">
                <table class="data-table" style="width:100%;">
                    <thead style="position:sticky; top:0; z-index:5;">
                        <tr>
                            <th style="background:#111827; min-width:120px;">Línea</th>
                            <th style="background:#111827; text-align:center;">Configurar</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($sinConfig as $linea)
                        <tr>
                            <td style="font-family:monospace; color:#94a3b8;">{{ $linea }}</td>
                            <td style="text-align:center;">
                                <div style="display:flex; gap:6px; justify-content:center;">
                                    <form method="POST" action="{{ route('homologacion.lineas.store') }}">
                                        @csrf
                                        <input type="hidden" name="linea" value="{{ $linea }}">
                                        <input type="hidden" name="tipo" value="si">
                                        <button type="submit" class="btn btn--sm" style="background:rgba(16,185,129,0.15); border:1px solid rgba(16,185,129,0.3); color:#34d399; padding:4px 10px; font-size:11px;">✅ Sí pasa</button>
                                    </form>
                                    <form method="POST" action="{{ route('homologacion.lineas.store') }}">
                                        @csrf
                                        <input type="hidden" name="linea" value="{{ $linea }}">
                                        <input type="hidden" name="tipo" value="no">
                                        <button type="submit" class="btn btn--sm" style="background:rgba(239,68,68,0.1); border:1px solid rgba(239,68,68,0.25); color:#f87171; padding:4px 10px; font-size:11px;">❌ No pasa</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
        @endif
    </div>

    {{-- ══ COLUMNA DERECHA: formularios ══ --}}
    <div style="display:flex; flex-direction:column; gap:16px;">

        {{-- Agregar línea manual --}}
        <div class="card">
            <div class="card-header"><h2 class="card-title">Agregar línea</h2></div>
            <div class="card-body" style="padding:16px;">
                <form method="POST" action="{{ route('homologacion.lineas.store') }}">
                    @csrf
                    <div style="margin-bottom:12px;">
                        <label class="modal-label">Código de línea *</label>
                        <input type="text" name="linea" required placeholder="Ej: 10300"
                               style="width:100%; background:var(--bg-root); border:1px solid var(--border); border-radius:8px; padding:9px 12px; color:white; font-size:13px; box-sizing:border-box; font-family:monospace;">
                    </div>
                    <div style="margin-bottom:12px;">
                        <label class="modal-label">Tipo *</label>
                        <select name="tipo" required style="width:100%; background:var(--bg-root); border:1px solid var(--border); border-radius:8px; padding:9px 12px; color:white; font-size:13px; box-sizing:border-box;">
                            <option value="si">✅ Sí se pasa</option>
                            <option value="no">❌ No se pasa</option>
                        </select>
                    </div>
                    <div style="margin-bottom:14px;">
                        <label class="modal-label">Descripción (opcional)</label>
                        <input type="text" name="descripcion" placeholder="Nombre descriptivo..."
                               style="width:100%; background:var(--bg-root); border:1px solid var(--border); border-radius:8px; padding:9px 12px; color:white; font-size:13px; box-sizing:border-box;">
                    </div>
                    <button type="submit" class="btn btn--primary btn--sm" style="width:100%;">Guardar línea</button>
                </form>
            </div>
        </div>

        {{-- Importar CSV --}}
        <div class="card">
            <div class="card-header"><h2 class="card-title">Importar desde CSV</h2></div>
            <div class="card-body" style="padding:16px;">
                <div style="background:rgba(99,102,241,0.08); border:1px solid rgba(99,102,241,0.2); border-radius:8px; padding:12px; margin-bottom:14px; font-size:12px; color:var(--text-secondary);">
                    <p style="margin:0 0 6px; font-weight:700; color:#818cf8;">Formato del CSV:</p>
                    <code style="display:block; background:rgba(0,0,0,0.3); padding:8px; border-radius:6px; color:#e2e8f0; font-size:11px; line-height:1.8;">linea,tipo<br>10300,si<br>10260,si<br>00217,no<br>00255,no</code>
                    <p style="margin:8px 0 0; font-size:11px;">La primera fila debe ser el encabezado. <br>Tipo: <code>si</code> o <code>no</code>.</p>
                </div>
                <form method="POST" action="{{ route('homologacion.lineas.import') }}" enctype="multipart/form-data">
                    @csrf
                    <div style="margin-bottom:12px;">
                        <label class="modal-label">Archivo CSV *</label>
                        <input type="file" name="archivo" accept=".csv,.txt" required
                               style="width:100%; background:var(--bg-root); border:1px solid var(--border); border-radius:8px; padding:9px 12px; color:white; font-size:13px; box-sizing:border-box;">
                    </div>
                    <button type="submit" class="btn btn--primary btn--sm" style="width:100%; background:var(--grad-premium);">
                        <svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2.5" style="margin-right:5px;"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="17 8 12 3 7 8"/><line x1="12" y1="3" x2="12" y2="15"/></svg>
                        Importar CSV
                    </button>
                </form>
            </div>
        </div>

    </div>
</div>

<style>
.data-table thead th {
    background: #1a1f2e;
    color: var(--text-muted);
    font-size: 11px;
    font-weight: 800;
    text-transform: uppercase;
    padding: 10px 14px;
    border-bottom: 1px solid var(--border);
    white-space: nowrap;
}
.data-table tbody td {
    padding: 9px 14px;
    font-size: 13px;
    border-bottom: 1px solid rgba(255,255,255,0.04);
}
.data-table tbody tr:hover td { background: rgba(255,255,255,0.02) !important; }
.modal-label { font-size:11px; font-weight:800; color:var(--text-secondary); display:block; margin-bottom:5px; text-transform:uppercase; letter-spacing:0.05em; }
</style>

@endsection
