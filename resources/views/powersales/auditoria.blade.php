@extends('layouts.app')

@section('title', 'Auditoría PowerSales')
@section('breadcrumb', 'PowerSales / Auditoría')

@section('content')
<div class="page-header shadow-premium" style="margin-bottom: 24px; padding: 20px 24px; background: var(--grad-surface); border-radius: var(--radius-xl); border: 1px solid var(--glass-border); display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 16px;">
    <div style="display: flex; gap: 20px; align-items: center;">
        <div class="page-header-icon shadow-premium" style="background: rgba(139, 92, 246, 0.1); border: 1px solid rgba(139, 92, 246, 0.3); color: #a78bfa;">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" width="24" height="24">
                <path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"/>
                <polyline points="3.27 6.96 12 12.01 20.73 6.96"/>
                <line x1="12" y1="22.08" x2="12" y2="12"/>
            </svg>
        </div>
        <div>
            <h1 class="page-title" style="margin:0;">Auditoría PowerSales</h1>
            <p class="page-subtitle" style="margin:4px 0 0; color: var(--text-secondary);">Qué se mandó a PowerSales, cuándo, y qué contestó</p>
        </div>
    </div>
    <a href="{{ route('powersales.mapeo') }}" class="btn btn--ghost" style="display: flex; align-items: center; gap: 8px;">
        <svg viewBox="0 0 24 24" fill="none" width="16" height="16" stroke="currentColor" stroke-width="2.5"><path d="M9 3H5a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h4m0-18h10a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H9m0-18v18"/></svg>
        Ver Mapeo de Campos
    </a>
</div>

<div class="glass-card shadow-premium" style="padding: 16px 20px; margin-bottom: 20px;">
    <form method="GET" style="display: flex; gap: 12px; flex-wrap: wrap; align-items: center;">
        <select name="entity" style="background: rgba(255,255,255,0.03); border: 1px solid var(--border); color: white; border-radius: 8px; padding: 8px 12px; font-size: 13px;">
            <option value="">Todas las entidades</option>
            <option value="articulo" {{ $entity === 'articulo' ? 'selected' : '' }}>Artículo</option>
            <option value="cliente" {{ $entity === 'cliente' ? 'selected' : '' }}>Cliente</option>
        </select>
        <select name="status" style="background: rgba(255,255,255,0.03); border: 1px solid var(--border); color: white; border-radius: 8px; padding: 8px 12px; font-size: 13px;">
            <option value="">Todos los estados</option>
            <option value="ok" {{ $status === 'ok' ? 'selected' : '' }}>Solo OK</option>
            <option value="error" {{ $status === 'error' ? 'selected' : '' }}>Solo Errores</option>
        </select>
        <input type="text" name="q" value="{{ $q }}" placeholder="Buscar por referencia (SKU / RFC)..." style="flex: 1; min-width: 220px; background: rgba(255,255,255,0.03); border: 1px solid var(--border); color: white; border-radius: 8px; padding: 8px 12px; font-size: 13px;">
        <button type="submit" class="btn btn--sm shadow-premium" style="background: rgba(139,92,246,0.15); color: #a78bfa; border: 1px solid rgba(139,92,246,0.3); padding: 8px 16px;">Filtrar</button>
        @if($entity || $status || $q)
        <a href="{{ route('powersales.auditoria') }}" class="btn btn--sm btn--ghost" style="padding: 8px 16px;">Limpiar</a>
        @endif
    </form>
</div>

<div class="glass-card shadow-premium" style="padding: 0; overflow: hidden;">
    <table style="width: 100%; border-collapse: collapse;">
        <thead>
            <tr style="background: rgba(255,255,255,0.03); border-bottom: 1px solid var(--border);">
                <th style="padding: 14px 20px; text-align: left; font-size: 11px; font-weight: 800; color: var(--text-secondary); text-transform: uppercase;">Fecha</th>
                <th style="padding: 14px 20px; text-align: left; font-size: 11px; font-weight: 800; color: var(--text-secondary); text-transform: uppercase;">Entidad</th>
                <th style="padding: 14px 20px; text-align: left; font-size: 11px; font-weight: 800; color: var(--text-secondary); text-transform: uppercase;">Endpoint</th>
                <th style="padding: 14px 20px; text-align: left; font-size: 11px; font-weight: 800; color: var(--text-secondary); text-transform: uppercase;">Referencia</th>
                <th style="padding: 14px 20px; text-align: center; font-size: 11px; font-weight: 800; color: var(--text-secondary); text-transform: uppercase;">Estado</th>
                <th style="padding: 14px 20px; text-align: center; font-size: 11px; font-weight: 800; color: var(--text-secondary); text-transform: uppercase;">Código</th>
                <th style="padding: 14px 20px; text-align: center; font-size: 11px; font-weight: 800; color: var(--text-secondary); text-transform: uppercase;">Detalle</th>
            </tr>
        </thead>
        <tbody>
            @forelse($logs as $log)
            <tr style="border-bottom: 1px solid rgba(255,255,255,0.05);">
                <td style="padding: 14px 20px; color: var(--text-secondary); font-size: 12.5px; white-space: nowrap;">{{ \Illuminate\Support\Carbon::parse($log->created_at)->format('d/m/Y H:i:s') }}</td>
                <td style="padding: 14px 20px;">
                    <span style="font-size: 10px; background: rgba(59,130,246,0.1); color: #60a5fa; padding: 3px 8px; border-radius: 4px; border: 1px solid rgba(59,130,246,0.2); text-transform: uppercase; font-weight: 700;">{{ $log->entity }}</span>
                </td>
                <td style="padding: 14px 20px; font-family: monospace; font-size: 12px; color: var(--text-muted);">{{ $log->endpoint }}</td>
                <td style="padding: 14px 20px; font-weight: 700; font-family: monospace; color: var(--amber);">{{ $log->referencia }}</td>
                <td style="padding: 14px 20px; text-align: center;">
                    @if($log->success)
                        <span style="color: var(--emerald); font-size: 11px; font-weight: 700;">✓ OK</span>
                    @else
                        <span style="color: var(--rose); font-size: 11px; font-weight: 700;">✗ ERROR</span>
                    @endif
                </td>
                <td style="padding: 14px 20px; text-align: center; font-family: monospace; font-size: 12px; color: var(--text-muted);">{{ $log->status_code ?? '—' }}</td>
                <td style="padding: 14px 20px; text-align: center;">
                    <button
                        class="btn btn--sm shadow-premium"
                        style="background: rgba(139,92,246,0.1); color:#a78bfa; border:1px solid rgba(139,92,246,0.2); padding:6px; width:32px; height:32px;"
                        title="Ver payload y respuesta"
                        onclick='verDetalle(@json($log))'>
                        <svg viewBox="0 0 24 24" fill="none" width="16" height="16" stroke="currentColor" stroke-width="2.5"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                    </button>
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="7" style="padding: 60px; text-align: center; color: var(--text-muted); font-size: 13px;">
                    Sin registros de sync todavía. Crea o edita un artículo/cliente para ver la auditoría aquí.
                </td>
            </tr>
            @endforelse
        </tbody>
    </table>

    <div style="padding: 20px;">
        {{ $logs->links() }}
    </div>
</div>

{{-- MODAL DE DETALLE --}}
<div id="modal-detalle" style="display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.85); backdrop-filter: blur(10px); z-index: 99999; align-items: center; justify-content: center; padding: 20px;">
    <div class="glass-card shadow-premium" style="width: 100%; max-width: 800px; max-height: 85vh; display: flex; flex-direction: column; overflow: hidden; border: 1px solid rgba(139,92,246,0.3);">
        <div style="padding: 20px 24px; border-bottom: 1px solid var(--border); display: flex; justify-content: space-between; align-items: center; background: rgba(139,92,246,0.05);">
            <div>
                <h3 id="modal-detalle-title" style="margin:0; font-size: 16px; color: #a78bfa;">Detalle de Sync</h3>
                <p id="modal-detalle-sub" style="margin:4px 0 0; font-size: 12px; color: var(--text-muted);"></p>
            </div>
            <button onclick="cerrarModalDetalle()" style="background: rgba(255,255,255,0.05); border: 1px solid rgba(255,255,255,0.1); color: white; border-radius: 10px; padding: 8px; cursor: pointer;">
                <svg viewBox="0 0 24 24" fill="none" width="18" height="18" stroke="currentColor" stroke-width="2.5"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
            </button>
        </div>
        <div style="flex: 1; overflow: auto; padding: 20px 24px;">
            <p style="font-size: 11px; font-weight: 800; color: var(--text-secondary); text-transform: uppercase; margin: 0 0 8px;">Payload enviado</p>
            <pre id="modal-detalle-payload" style="background: rgba(0,0,0,0.3); border: 1px solid var(--border); border-radius: 10px; padding: 14px; font-size: 12px; color: #93c5fd; white-space: pre-wrap; word-break: break-all; margin: 0 0 20px;"></pre>

            <p style="font-size: 11px; font-weight: 800; color: var(--text-secondary); text-transform: uppercase; margin: 0 0 8px;">Respuesta de PowerSales</p>
            <pre id="modal-detalle-response" style="background: rgba(0,0,0,0.3); border: 1px solid var(--border); border-radius: 10px; padding: 14px; font-size: 12px; color: #86efac; white-space: pre-wrap; word-break: break-all; margin: 0;"></pre>
        </div>
    </div>
</div>

<script>
function prettyJson(raw) {
    if (!raw) return '(vacío)';
    try { return JSON.stringify(JSON.parse(raw), null, 2); } catch (e) { return raw; }
}

function verDetalle(log) {
    document.getElementById('modal-detalle-title').textContent = `${log.entity.toUpperCase()} · ${log.referencia}`;
    document.getElementById('modal-detalle-sub').textContent = `${log.endpoint} · ${log.success ? 'OK' : 'ERROR'} · HTTP ${log.status_code ?? '—'} · ${log.created_at}`;
    document.getElementById('modal-detalle-payload').textContent = prettyJson(log.payload);
    document.getElementById('modal-detalle-response').textContent = prettyJson(log.response_body) === log.response_body ? (log.response_body || '(vacío)') : prettyJson(log.response_body);
    document.getElementById('modal-detalle').style.display = 'flex';
}

function cerrarModalDetalle() {
    document.getElementById('modal-detalle').style.display = 'none';
}

window.addEventListener('click', (e) => {
    const modal = document.getElementById('modal-detalle');
    if (e.target === modal) cerrarModalDetalle();
});
window.addEventListener('keydown', (e) => {
    if (e.key === 'Escape') cerrarModalDetalle();
});
</script>
@endsection
