@if(session('sync_modal'))
    @php
        $sm = session('sync_modal');
        $accionText = ($sm['accion'] ?? '') === 'crear' ? 'creado' : 'actualizado';
        $exitosos = $sm['exitosos'] ?? 0;
        $total = $sm['total'] ?? 0;
        $nombre = addslashes($sm['nombre'] ?? '');
        $rfc = addslashes($sm['rfc'] ?? '');
        $resultados = $sm['resultados'] ?? [];
    @endphp

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        let branchListHtml = '';
        @foreach($resultados as $res)
            branchListHtml += `<div style="padding:6px 10px; border-radius:8px; background:{{ ($res['status'] ?? '') === 'ok' ? 'rgba(16,185,129,0.08)' : 'rgba(239,68,68,0.12)' }}; border:1px solid {{ ($res['status'] ?? '') === 'ok' ? 'rgba(16,185,129,0.2)' : 'rgba(239,68,68,0.3)' }}; display:flex; align-items:center; justify-content:space-between; font-size:11px; font-weight:700; color:white;">
                <span>{{ addslashes($res['sucursal'] ?? '') }}</span>
                <span style="color:{{ ($res['status'] ?? '') === 'ok' ? '#34d399' : '#f87171' }}">{{ ($res['status'] ?? '') === 'ok' ? '✓ OK' : '✗ Error' }}</span>
            </div>`;
        @endforeach

        const htmlContent = `
            <div style="text-align:left; font-family:inherit; color:#cbd5e1; font-size:13px; margin-top:8px;">
                <p style="margin:0 0 16px; font-size:13.5px; color:#e2e8f0; font-weight:600; line-height:1.5;">
                    El cliente <strong style="color:white;">"${nombre}"</strong> (<span style="font-family:monospace; color:#a78bfa;">${rfc}</span>) fue ${accionText} correctamente.
                </p>

                {{-- Bloque Multi-Sucursal --}}
                <div style="background:rgba(255,255,255,0.03); border:1px solid rgba(255,255,255,0.08); border-radius:12px; padding:14px 16px; margin-bottom:14px;">
                    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:10px;">
                        <span style="font-size:12px; font-weight:700; color:white; display:flex; align-items:center; gap:6px;">
                            🏢 Replicación Multi-Sucursal
                        </span>
                        <span style="font-size:11px; font-weight:800; background:rgba(16,185,129,0.15); color:#34d399; padding:3px 10px; border-radius:20px; border:1px solid rgba(16,185,129,0.3);">
                            ${exitosos}/${total} Sucursales OK
                        </span>
                    </div>
                    <div style="display:grid; grid-template-columns:repeat(auto-fill, minmax(130px, 1fr)); gap:6px;">
                        ${branchListHtml}
                    </div>
                </div>

                {{-- Bloque PowerSales --}}
                <div style="background:rgba(139,92,246,0.06); border:1px solid rgba(139,92,246,0.25); border-radius:12px; padding:14px 16px; display:flex; align-items:center; justify-content:space-between;">
                    <div style="display:flex; align-items:center; gap:10px;">
                        <div style="width:30px; height:30px; border-radius:8px; background:rgba(139,92,246,0.2); color:#a78bfa; display:flex; align-items:center; justify-content:center; font-size:14px;">
                            ⚡
                        </div>
                        <div>
                            <div style="font-size:12px; font-weight:700; color:white;">Integración PowerSales</div>
                            <div style="font-size:11px; color:#a78bfa;">Sincronizado a AIESA (Branch #9)</div>
                        </div>
                    </div>
                    <span style="font-size:11px; font-weight:800; color:#34d399; background:rgba(16,185,129,0.15); padding:3px 10px; border-radius:20px; border:1px solid rgba(16,185,129,0.3);">
                        ✓ Enviado
                    </span>
                </div>
            </div>
        `;

        Swal.fire({
            title: '<span style="color:white; font-size:18px; font-weight:800;">¡Cliente {{ ucfirst($accionText) }} con Éxito!</span>',
            icon: 'success',
            html: htmlContent,
            background: '#0f172a',
            border: '1px solid rgba(16,185,129,0.3)',
            color: '#fff',
            confirmButtonText: 'Entendido',
            confirmButtonColor: '#8b5cf6',
            padding: '24px',
            customClass: {
                popup: 'shadow-premium',
                confirmButton: 'btn btn--primary'
            }
        });
    });
    </script>
@endif
