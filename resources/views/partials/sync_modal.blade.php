<script>
window.renderUnidataSyncModal = function(syncData, callback) {
    if (typeof Swal === 'undefined') {
        console.warn('SweetAlert2 no se encuentra disponible para mostrar el modal de sincronización.');
        if (callback) callback();
        return;
    }

    const accion = syncData.accion || 'guardar';
    const isEdit = accion === 'editar';
    const accionText = accion === 'crear' ? 'creado' : 'actualizado';
    const exitosos = syncData.exitosos || 0;
    const total = syncData.total || 0;

    let psSuccess = true;
    let psMessage = 'Sincronizado a PowerSales';
    let psBranchId = 9;

    if (syncData.powersales && typeof syncData.powersales === 'object') {
        psSuccess = syncData.powersales.success !== undefined ? Boolean(syncData.powersales.success) : true;
        psMessage = syncData.powersales.message || 'Sincronizado a PowerSales';
        psBranchId = syncData.powersales.branch_id || 9;
    } else if (syncData.psSuccess !== undefined) {
        psSuccess = Boolean(syncData.psSuccess);
        psMessage = syncData.psMessage || 'Sincronizado a PowerSales';
        psBranchId = syncData.psBranchId || 9;
    }

    const todoExitoso = (exitosos === total) && psSuccess;
    const swalIcon = todoExitoso ? 'success' : (exitosos > 0 ? 'warning' : 'error');

    // Generar listado de sucursales
    let branchListHtml = '';
    const resultados = syncData.resultados || [];
    if (Array.isArray(resultados) && resultados.length > 0) {
        resultados.forEach(res => {
            const isOk = res.status === 'ok';
            const bgColor = isOk ? 'rgba(16,185,129,0.08)' : 'rgba(239,68,68,0.12)';
            const borderColor = isOk ? 'rgba(16,185,129,0.25)' : 'rgba(239,68,68,0.3)';
            const statusColor = isOk ? '#34d399' : '#f87171';
            const statusText = isOk ? (res.local_id ? `✓ ID #${res.local_id}` : '✓ OK') : '✗ Error';
            const errorDetail = !isOk && res.message ? `<div style="font-size:10px; color:#fca5a5; margin-top:2px;">${res.message.substring(0, 45)}</div>` : '';

            branchListHtml += `
                <div style="padding:8px 12px; border-radius:10px; background:${bgColor}; border:1px solid ${borderColor}; display:flex; flex-direction:column; justify-content:center;">
                    <div style="display:flex; align-items:center; justify-content:space-between; font-size:12px; font-weight:700; color:#f8fafc;">
                        <span>${res.sucursal || 'Sucursal'}</span>
                        <span style="color:${statusColor}; font-weight:800;">${statusText}</span>
                    </div>
                    ${errorDetail}
                </div>
            `;
        });
    } else {
        branchListHtml = `<div style="font-size:12px; color:#94a3b8; text-align:center; grid-column:span 2;">No se registraron detalles de sucursal.</div>`;
    }

    // PowerSales box
    const psBg = psSuccess ? 'rgba(16,185,129,0.06)' : 'rgba(245,158,11,0.08)';
    const psBorder = psSuccess ? 'rgba(16,185,129,0.25)' : 'rgba(245,158,11,0.3)';
    const psBadgeBg = psSuccess ? 'rgba(16,185,129,0.15)' : 'rgba(245,158,11,0.15)';
    const psBadgeColor = psSuccess ? '#34d399' : '#fbbf24';
    const psBadgeText = psSuccess ? '✓ Enviado y Sincronizado' : '⚠️ Pendiente / Detalle';
    const psSubtitle = psSuccess 
        ? 'Se mandó a PowerSales correctamente al endpoint /customers.'
        : (psMessage || 'No se pudo confirmar la sincronización.');

    const htmlContent = `
        <div style="text-align:left; font-family:inherit; color:#cbd5e1; font-size:13px; margin-top:10px;">
            
            {{-- Encabezado del cliente --}}
            <div style="background:rgba(255,255,255,0.03); border:1px solid rgba(255,255,255,0.08); border-radius:12px; padding:14px 16px; margin-bottom:14px;">
                <div style="font-size:14.5px; font-weight:700; color:#f8fafc; line-height:1.4; margin-bottom:4px;">
                    ${syncData.nombre || 'Cliente'}
                </div>
                <div style="display:flex; flex-wrap:wrap; gap:8px; align-items:center; font-size:11.5px; color:#94a3b8;">
                    <span>RFC: <strong style="font-family:monospace; color:#a78bfa;">${syncData.rfc || 'S/N'}</strong></span>
                    ${syncData.id_global || syncData.idGlobal ? `<span>&bull; ID Global: <strong style="font-family:monospace; color:#34d399;">#${syncData.id_global || syncData.idGlobal}</strong></span>` : ''}
                    <span>&bull; Estado: <strong style="color:#38bdf8;">${accionText.toUpperCase()}</strong></span>
                </div>
            </div>

            {{-- Bloque Multi-Sucursal --}}
            <div style="background:rgba(255,255,255,0.03); border:1px solid rgba(255,255,255,0.08); border-radius:12px; padding:14px 16px; margin-bottom:14px;">
                <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:10px;">
                    <span style="font-size:12.5px; font-weight:700; color:#f8fafc; display:flex; align-items:center; gap:7px;">
                        <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="#818cf8" stroke-width="2.2"><rect x="2" y="3" width="7" height="7"/><rect x="15" y="3" width="7" height="7"/><rect x="15" y="14" width="7" height="7"/><rect x="2" y="14" width="7" height="7"/></svg>
                        Replicación en Sucursales
                    </span>
                    <span style="font-size:11px; font-weight:800; background:${exitosos === total ? 'rgba(16,185,129,0.15)' : 'rgba(245,158,11,0.15)'}; color:${exitosos === total ? '#34d399' : '#fbbf24'}; padding:3px 10px; border-radius:20px; border:1px solid ${exitosos === total ? 'rgba(16,185,129,0.3)' : 'rgba(245,158,11,0.3)'};">
                        ${exitosos}/${total} Sucursales OK
                    </span>
                </div>
                <div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(140px, 1fr)); gap:8px;">
                    ${branchListHtml}
                </div>
            </div>

            {{-- Bloque PowerSales --}}
            <div style="background:${psBg}; border:1px solid ${psBorder}; border-radius:12px; padding:14px 16px;">
                <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:6px;">
                    <div style="display:flex; align-items:center; gap:8px;">
                        <div style="width:26px; height:26px; border-radius:7px; background:rgba(139,92,246,0.2); color:#a78bfa; display:flex; align-items:center; justify-content:center; font-size:13px; font-weight:800;">
                            ⚡
                        </div>
                        <div>
                            <span style="font-size:12.5px; font-weight:700; color:#f8fafc;">Integración PowerSales</span>
                            <span style="font-size:11px; color:#a78bfa; margin-left:4px;">(Branch #${psBranchId} - AIESA)</span>
                        </div>
                    </div>
                    <span style="font-size:11px; font-weight:800; color:${psBadgeColor}; background:${psBadgeBg}; padding:3px 10px; border-radius:20px; border:1px solid ${psBorder};">
                        ${psBadgeText}
                    </span>
                </div>
                <p style="margin:4px 0 0; font-size:11.5px; color:#94a3b8; line-height:1.4;">
                    ${psSubtitle}
                </p>
            </div>

        </div>
    `;

    Swal.fire({
        title: '<span style="color:#ffffff; font-size:20px; font-weight:800;">¡Terminó con Éxito!</span>',
        icon: swalIcon,
        html: htmlContent,
        background: '#0f172a',
        color: '#ffffff',
        border: '1px solid rgba(255,255,255,0.12)',
        confirmButtonText: isEdit ? 'Aceptar y continuar' : 'Entendido',
        confirmButtonColor: '#6366f1',
        showDenyButton: isEdit,
        denyButtonText: 'Ver lista de clientes',
        denyButtonColor: 'rgba(255,255,255,0.08)',
        padding: '24px',
        customClass: {
            popup: 'shadow-premium',
            confirmButton: 'btn btn--primary',
            denyButton: 'btn btn--ghost'
        }
    }).then(result => {
        if (result.isDenied && isEdit) {
            window.location.href = "{{ route('clientes.index') }}";
        } else if (callback) {
            callback();
        }
    });
};
</script>

@if(session('sync_modal'))
    @php
        $sm = session('sync_modal');
        $syncJson = json_encode($sm, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP | JSON_INVALID_UTF8_SUBSTITUTE | JSON_UNESCAPED_UNICODE) ?: '{}';
    @endphp

    <script>
    (function() {
        const sessionSyncData = {!! $syncJson !!};
        function runSyncModal() {
            if (window.renderUnidataSyncModal) {
                window.renderUnidataSyncModal(sessionSyncData);
            }
        }

        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', runSyncModal);
        } else {
            runSyncModal();
        }
    })();
    </script>
@endif
