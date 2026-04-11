@extends('layouts.app')

@section('title', 'Historial de Subidas')
@section('breadcrumb', 'Artículos / Historial')

@section('content')
<div class="page-header shadow-premium" style="margin-bottom: 24px; padding: 20px 24px; background: var(--grad-surface); border-radius: var(--radius-xl); border: 1px solid var(--glass-border); display: flex; justify-content: space-between; align-items: center;">
    <div style="display: flex; gap: 20px; align-items: center;">
        <div class="page-header-icon shadow-premium" style="background: rgba(245, 158, 11, 0.1); border: 1px solid rgba(245, 158, 11, 0.3); color: var(--amber);">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" width="24" height="24">
                <path d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
        </div>
        <div>
            <h1 class="page-title" style="margin:0;">Historial de Subidas</h1>
            <p class="page-subtitle" style="margin:4px 0 0; color: var(--text-secondary);">Auditoría y reversión de cambios masivos</p>
        </div>
    </div>
    <a href="{{ route('articulos.subir') }}" class="btn btn--ghost" style="display: flex; align-items: center; gap: 8px;">
        <svg viewBox="0 0 24 24" fill="none" width="16" height="16" stroke="currentColor" stroke-width="2.5"><path d="M19 12H5M12 19l-7-7 7-7"/></svg>
        Volver a Subir
    </a>
</div>

<div class="glass-card shadow-premium" style="padding: 0; overflow: hidden;">
    <table style="width: 100%; border-collapse: collapse;">
        <thead>
            <tr style="background: rgba(255,255,255,0.03); border-bottom: 1px solid var(--border);">
                <th style="padding: 16px 20px; text-align: left; font-size: 11px; font-weight: 800; color: var(--text-secondary); text-transform: uppercase;">ID</th>
                <th style="padding: 16px 20px; text-align: left; font-size: 11px; font-weight: 800; color: var(--text-secondary); text-transform: uppercase;">Archivo</th>
                <th style="padding: 16px 20px; text-align: left; font-size: 11px; font-weight: 800; color: var(--text-secondary); text-transform: uppercase;">Fecha</th>
                <th style="padding: 16px 20px; text-align: center; font-size: 11px; font-weight: 800; color: var(--text-secondary); text-transform: uppercase;">Artículos</th>
                <th style="padding: 16px 20px; text-align: left; font-size: 11px; font-weight: 800; color: var(--text-secondary); text-transform: uppercase;">Sucursales</th>
                <th style="padding: 16px 20px; text-align: center; font-size: 11px; font-weight: 800; color: var(--text-secondary); text-transform: uppercase;">Estado</th>
                <th style="padding: 16px 20px; text-align: center; font-size: 11px; font-weight: 800; color: var(--text-secondary); text-transform: uppercase;">Acciones</th>
            </tr>
        </thead>
        <tbody>
            @foreach($historial as $item)
            <tr style="border-bottom: 1px solid rgba(255,255,255,0.05); transition: background 0.2s;" onmouseover="this.style.background='rgba(255,255,255,0.02)'" onmouseout="this.style.background='transparent'">
                <td style="padding: 16px 20px; font-family: monospace; color: var(--text-muted);">#{{ $item->id }}</td>
                <td style="padding: 16px 20px; font-weight: 600;">
                    <div style="display: flex; align-items: center; gap: 10px;">
                        <svg viewBox="0 0 24 24" fill="none" width="16" height="16" stroke="var(--emerald)" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
                        {{ $item->archivo_nombre }}
                    </div>
                </td>
                <td style="padding: 16px 20px; color: var(--text-secondary); font-size: 13px;">{{ \Illuminate\Support\Carbon::parse($item->fecha)->format('d/m/Y H:i') }}</td>
                <td style="padding: 16px 20px; text-align: center; font-weight: 700; color: var(--emerald);">{{ $item->articulos_afectados }}</td>
                <td style="padding: 16px 20px;">
                    <div style="display: flex; flex-wrap: wrap; gap: 4px;">
                        @php $sucs = json_decode($item->sucursales_json, true) ?: []; @endphp
                        @foreach($sucs as $s)
                            <span style="font-size: 10px; background: rgba(59, 130, 246, 0.1); color: #60a5fa; padding: 2px 6px; border-radius: 4px; border: 1px solid rgba(59, 130, 246, 0.2); text-transform: uppercase;">{{ $s }}</span>
                        @endforeach
                        <span style="font-size: 10px; background: rgba(16, 185, 129, 0.1); color: var(--emerald); padding: 2px 6px; border-radius: 4px; border: 1px solid rgba(16, 185, 129, 0.2); text-transform: uppercase;">Maestro</span>
                    </div>
                </td>
                <td style="padding: 16px 20px; text-align: center;">
                    @if($item->revertido)
                        <span style="color: var(--rose); font-size: 12px; font-weight: 700; display: flex; align-items: center; justify-content: center; gap: 4px;">
                            <svg viewBox="0 0 24 24" fill="none" width="14" height="14" stroke="currentColor" stroke-width="3"><path d="M11 15h2m-1-5v3m-6 3h12a2 2 0 0 0 2-2V6a2 2 0 0 0-2-2H6a2 2 0 0 0-2 2v10a2 2 0 0 0 2 2z"/></svg>
                            REVERTIDO
                        </span>
                    @else
                        <span style="color: var(--emerald); font-size: 12px; font-weight: 700;">APLICADO</span>
                    @endif
                </td>
                <td style="padding: 16px 20px; text-align: center;">
                    <div style="display: flex; gap: 8px; justify-content: center;">
                        {{-- Botón de Detalle --}}
                        <button onclick="verDetalles({{ $item->id }}, '{{ $item->archivo_nombre }}')" class="btn btn--sm shadow-premium" title="Ver Auditoría" style="background: rgba(139, 92, 246, 0.1); color: #a78bfa; border: 1px solid rgba(139, 92, 246, 0.2); padding: 6px; width: 32px; height: 32px; display: flex; align-items: center; justify-content: center;">
                            <svg viewBox="0 0 24 24" fill="none" width="16" height="16" stroke="currentColor" stroke-width="2.5"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                        </button>

                        {{-- Botón de Descargar --}}
                        <a href="{{ route('articulos.historial.descargar', $item->id) }}" class="btn btn--sm shadow-premium" title="Descargar CSV Original" style="background: rgba(59, 130, 246, 0.1); color: #60a5fa; border: 1px solid rgba(59, 130, 246, 0.2); padding: 6px; width: 32px; height: 32px; display: flex; align-items: center; justify-content: center;">
                            <svg viewBox="0 0 24 24" fill="none" width="16" height="16" stroke="currentColor" stroke-width="2.5"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
                        </a>

                        {{-- Botón de Revertir --}}
                        @if(!$item->revertido)
                        <button onclick="revertirSubida({{ $item->id }}, '{{ $item->archivo_nombre }}')" class="btn btn--sm shadow-premium" title="Revertir todo" style="background: rgba(244, 63, 94, 0.1); color: var(--rose); border: 1px solid rgba(244, 63, 94, 0.2); font-size: 11px; padding: 6px 12px; height: 32px;">
                            Revertir
                        </button>
                        @endif
                    </div>
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>
    
    <div style="padding: 20px;">
        {{ $historial->links() }}
    </div>
</div>

{{-- MODAL DE DETALLES --}}
<div id="modal-detalles" class="modal-backdrop" style="display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.85); backdrop-filter: blur(10px); z-index: 99999; align-items: center; justify-content: center; padding: 20px;">
    <div class="glass-card shadow-premium" style="width: 100%; max-width: 1000px; max-height: 85vh; display: flex; flex-direction: column; overflow: hidden; border: 1px solid var(--amber-border); position: relative;">
        <div style="padding: 20px 24px; border-bottom: 1px solid var(--border); display: flex; justify-content: space-between; align-items: center; background: rgba(245,158,11,0.05);">
            <div style="display: flex; align-items: center; gap: 12px;">
                <div style="width: 32px; height: 32px; background: var(--amber-bg); color: var(--amber); border-radius: 8px; display: flex; align-items: center; justify-content: center;">
                    <svg viewBox="0 0 24 24" fill="none" width="18" height="18" stroke="currentColor" stroke-width="2.5"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
                </div>
                <div>
                    <h3 style="margin:0; font-size: 18px; color: var(--amber-light);">Detalle de Auditoría</h3>
                    <p id="modal-filename" style="margin:4px 0 0; font-size: 12px; color: var(--text-muted);"></p>
                </div>
            </div>
            <button onclick="cerrarModal()" class="btn btn--ghost close-modal-btn" style="padding: 8px; border-radius: 10px; background: rgba(255,255,255,0.05); color: white; border: 1px solid rgba(255,255,255,0.1); cursor: pointer !important; z-index: 100;">
                <svg viewBox="0 0 24 24" fill="none" width="20" height="20" stroke="currentColor" stroke-width="2.5"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
            </button>
        </div>
        
        <div id="modal-content" style="flex: 1; overflow: auto; padding: 0;">
            <table style="width: 100%; border-collapse: collapse;">
                <thead style="position: sticky; top: 0; background: #0f172a; z-index: 10;">
                    <tr>
                        <th style="padding: 12px 20px; text-align: left; font-size: 10px; color: var(--text-muted); text-transform: uppercase; border-bottom: 1px solid var(--border);">Artículo</th>
                        <th style="padding: 12px 20px; text-align: left; font-size: 10px; color: var(--text-muted); text-transform: uppercase; border-bottom: 1px solid var(--border);">Sucursal</th>
                        <th style="padding: 12px 20px; text-align: left; font-size: 10px; color: var(--text-muted); text-transform: uppercase; border-bottom: 1px solid var(--border);">Columna</th>
                        <th style="padding: 12px 20px; text-align: left; font-size: 10px; color: var(--text-muted); text-transform: uppercase; border-bottom: 1px solid var(--border);">Cambio (Viejo ➜ Nuevo)</th>
                    </tr>
                </thead>
                <tbody id="detalles-body">
                    {{-- Se llena con AJAX --}}
                </tbody>
            </table>
            <div id="modal-loading" style="padding: 60px; text-align: center; display: none;">
                <div class="spinner" style="width: 40px; height: 40px; border: 3px solid rgba(245,158,11,0.1); border-top-color: var(--amber); border-radius: 50%; animation: spin 0.8s linear infinite; margin: 0 auto 16px;"></div>
                <p style="color: var(--text-muted); font-size: 14px;">Cargando auditoría...</p>
            </div>
            <div id="modal-empty" style="padding: 60px; text-align: center; display: none;">
                <p style="color: var(--text-muted); font-size: 14px;">No se encontraron registros de cambios detallados para esta subida.</p>
            </div>
        </div>
    </div>
</div>

<style>
    @keyframes spin { to { transform: rotate(360deg); } }
    .modal-backdrop { opacity: 0; transition: opacity 0.2s ease-in-out; }
    .modal-backdrop.show { opacity: 1; }
    @keyframes fadeIn { from { opacity: 0; } to { opacity: 1; } }
</style>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
async function revertirSubida(id, nombre) {
    const result = await Swal.fire({
        title: '¿Revertir Cambios?',
        text: `Se restaurarán los valores originales de los artículos afectados por el archivo "${nombre}". Esta acción no se puede deshacer.`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#f43f5e',
        cancelButtonColor: '#475569',
        confirmButtonText: 'Sí, revertir todo',
        cancelButtonText: 'Cancelar',
        background: '#0f172a',
        color: '#f8fafc'
    });

    if (result.isConfirmed) {
        Swal.fire({
            title: 'Revirtiendo...',
            text: 'Restaurando valores originales en Maestro y Sucursales',
            allowOutsideClick: false,
            didOpen: () => { Swal.showLoading(); }
        });

        try {
            const response = await fetch(`/articulos/revertir/${id}`, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Accept': 'application/json'
                }
            });

            const data = await response.json();

            if (data.success) {
                await Swal.fire({
                    title: '¡Éxito!',
                    text: data.message,
                    icon: 'success',
                    background: '#0f172a',
                    color: '#f8fafc'
                });
                location.reload();
            } else {
                Swal.fire({
                    title: 'Error',
                    text: data.message,
                    icon: 'error',
                    background: '#0f172a',
                    color: '#f8fafc'
                });
            }
        } catch (error) {
            Swal.fire({
                title: 'Error Fatal',
                text: 'Hubo un error al conectar con el servidor.',
                icon: 'error',
                background: '#0f172a',
                color: '#f8fafc'
            });
        }
    }
}

async function verDetalles(id, archivo) {
    const modal = document.getElementById('modal-detalles');
    const body = document.getElementById('detalles-body');
    const loading = document.getElementById('modal-loading');
    const empty = document.getElementById('modal-empty');
    const filenameLabel = document.getElementById('modal-filename');

    filenameLabel.textContent = `Archivo: ${archivo}`;
    body.innerHTML = '';
    modal.style.display = 'flex';
    // Pequeño delay para que la transición de opacidad funcione
    setTimeout(() => modal.classList.add('show'), 10);
    
    loading.style.display = 'block';
    empty.style.display = 'none';

    try {
        const response = await fetch(`/articulos/historial/${id}/detalles`);
        const data = await response.json();
        
        loading.style.display = 'none';

        if (!data || data.length === 0) {
            empty.style.display = 'block';
            return;
        }

        data.forEach(log => {
            const tr = document.createElement('tr');
            tr.style.borderBottom = '1px solid rgba(255,255,255,0.03)';
            
            const sucursalBadge = log.sucursal === 'maestro' 
                ? '<span style="color:var(--emerald); background:rgba(16,185,129,0.1); padding:2px 6px; border-radius:4px; font-size:10px; font-weight:bold;">MAESTRO</span>'
                : `<span style="color:#60a5fa; background:rgba(59,130,246,0.1); padding:2px 6px; border-radius:4px; font-size:10px; font-weight:bold; text-transform:uppercase;">${log.sucursal}</span>`;

            tr.innerHTML = `
                <td style="padding: 12px 20px; font-family: monospace; font-size: 12px; font-weight:bold; color:var(--amber);">${log.clave}</td>
                <td style="padding: 12px 20px;">${sucursalBadge}</td>
                <td style="padding: 12px 20px; font-size: 11px; color: var(--text-muted);">${log.columna.toUpperCase()}</td>
                <td style="padding: 12px 20px; font-size: 12px;">
                    <span style="text-decoration: line-through; color: var(--rose); opacity: 0.6; margin-right: 8px;">${log.valor_anterior || 'NULL'}</span>
                    <span style="color: var(--emerald); font-weight: bold;">➜ ${log.valor_nuevo || 'NULL'}</span>
                </td>
            `;
            body.appendChild(tr);
        });

    } catch (e) {
        loading.style.display = 'none';
        Swal.fire('Error', 'No se pudo cargar el detalle de auditoría.', 'error');
    }
}

function cerrarModal() {
    const modal = document.getElementById('modal-detalles');
    modal.classList.remove('show');
    setTimeout(() => {
        modal.style.display = 'none';
    }, 200);
}

// Cerrar modal al hacer clic fuera o con ESC
window.addEventListener('click', (event) => {
    const modal = document.getElementById('modal-detalles');
    if (event.target == modal) {
        cerrarModal();
    }
});

window.addEventListener('keydown', (event) => {
    if (event.key === 'Escape') {
        const modal = document.getElementById('modal-detalles');
        if (modal.style.display === 'flex') {
            cerrarModal();
        }
    }
});
</script>
@endsection
