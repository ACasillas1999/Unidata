{{--
    Cambio 2 (carga masiva): tabla de resultados por fila.
    Lo usan articulos/subir (al terminar la carga) y articulos/historial (botón Resultados).
    Expone window.ResultadosCarga con: cargar(id), pintar(contenedor, datos), abrir(id).
--}}
<style>
    .rc-wrap { text-align: left; font-size: 12.5px; color: #e2e8f0; }
    .rc-wrap table { width: 100%; border-collapse: collapse; }
    .rc-wrap th { text-align: left; font-size: 10.5px; text-transform: uppercase; letter-spacing: .06em; color: #94a3b8; padding: 6px 8px; border-bottom: 1px solid rgba(255,255,255,0.08); position: sticky; top: 0; background: #1a1d27; }
    .rc-wrap td { padding: 6px 8px; border-bottom: 1px solid rgba(255,255,255,0.05); vertical-align: top; }
    .rc-resumen tr { cursor: pointer; }
    .rc-resumen tr:hover td { background: rgba(255,255,255,0.04); }
    .rc-pill { display: inline-block; padding: 1px 7px; border-radius: 10px; font-size: 10.5px; font-weight: 700; white-space: nowrap; }
    .rc-controles { display: flex; gap: 8px; flex-wrap: wrap; align-items: center; margin: 14px 0 8px; }
    .rc-controles select, .rc-controles input { background: #0f172a; color: #e2e8f0; border: 1px solid rgba(255,255,255,0.12); border-radius: 6px; padding: 6px 8px; font-size: 12px; }
    .rc-controles input { flex: 1; min-width: 160px; }
    .rc-detalle-scroll { max-height: 42vh; overflow: auto; border: 1px solid rgba(255,255,255,0.06); border-radius: 8px; }
    .rc-pie { display: flex; justify-content: space-between; align-items: center; gap: 8px; margin-top: 8px; font-size: 11.5px; color: #94a3b8; flex-wrap: wrap; }
    .rc-pie button { background: #334155; color: #fff; border: none; border-radius: 6px; padding: 4px 10px; cursor: pointer; font-size: 11.5px; }
    .rc-pie button:disabled { opacity: .4; cursor: default; }
    .rc-descargar { display: inline-flex; align-items: center; gap: 6px; color: #60a5fa; text-decoration: none; font-weight: 700; font-size: 12px; }
</style>
<script>
(function () {
    const URL_DATOS     = '{{ route("articulos.historial.resultados", "__ID__") }}';
    const URL_DESCARGAR = '{{ route("articulos.historial.resultados.descargar", "__ID__") }}';
    const POR_PAGINA = 100;

    const esc = v => String(v ?? '').replace(/[&<>"'`]/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;','`':'&#96;'}[c]));
    const color = m => m === 'actualizado' ? ['rgba(16,185,129,0.15)', '#34d399']
        : (m === 'error' || m === 'error_sucursal') ? ['rgba(244,63,94,0.15)', '#fb7185']
        : m === 'no_procesado' ? ['rgba(148,163,184,0.15)', '#cbd5e1']
        : ['rgba(245,158,11,0.15)', '#fbbf24'];
    const pill = (m, texto) => { const [bg, fg] = color(m); return `<span class="rc-pill" style="background:${bg}; color:${fg};">${esc(texto)}</span>`; };

    async function cargar(id) {
        const r = await fetch(URL_DATOS.replace('__ID__', id), { headers: { Accept: 'application/json' }, cache: 'no-store' });
        const d = await r.json().catch(() => ({ success: false, message: 'Respuesta inválida del servidor.' }));
        if (!r.ok || !d.success) throw new Error(d.message || 'No se pudieron leer los resultados.');
        return d;
    }

    function pintar(cont, d) {
        const motivos = d.motivos || {};
        let filtro = '', texto = '', pagina = 0;

        cont.innerHTML = `
            <div class="rc-wrap">
                <div style="display:flex; justify-content:space-between; align-items:center; gap:8px; margin-bottom:6px; flex-wrap:wrap;">
                    <b>Resumen (${Number(d.total).toLocaleString()} filas)</b>
                    <a class="rc-descargar" href="${URL_DESCARGAR.replace('__ID__', d.historial_id)}">
                        <svg viewBox="0 0 24 24" fill="none" width="14" height="14" stroke="currentColor" stroke-width="2.5"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
                        Descargar resultados (CSV)
                    </a>
                </div>
                <table class="rc-resumen">
                    <thead><tr><th>Motivo</th><th style="text-align:right;">Filas</th></tr></thead>
                    <tbody>
                        ${(d.resumen || []).map(r => `<tr data-motivo="${esc(r.codigo)}" title="Ver estas filas"><td>${pill(r.codigo, r.texto)}</td><td style="text-align:right; font-weight:700;">${Number(r.filas).toLocaleString()}</td></tr>`).join('')}
                        ${d.repetidas ? `<tr data-motivo="__repetidas"><td style="color:#94a3b8;">Claves repetidas en el archivo <span style="font-size:10.5px;">(ya contadas arriba)</span></td><td style="text-align:right; color:#94a3b8;">${Number(d.repetidas).toLocaleString()}</td></tr>` : ''}
                    </tbody>
                </table>
                <div class="rc-controles">
                    <select class="rc-filtro">
                        <option value="">Todos los motivos</option>
                        ${(d.resumen || []).map(r => `<option value="${esc(r.codigo)}">${esc(r.texto)} (${r.filas})</option>`).join('')}
                        ${d.repetidas ? '<option value="__repetidas">Claves repetidas</option>' : ''}
                    </select>
                    <input type="search" class="rc-buscar" placeholder="Buscar clave, descripción o detalle…">
                </div>
                <div class="rc-detalle-scroll">
                    <table>
                        <thead><tr><th>Fila</th><th>Clave</th><th>Descripción</th><th>Resultado</th><th>Detalle</th></tr></thead>
                        <tbody class="rc-cuerpo"></tbody>
                    </table>
                </div>
                <div class="rc-pie">
                    <span class="rc-cuenta"></span>
                    <span><button class="rc-ant">‹ Anterior</button> <button class="rc-sig">Siguiente ›</button></span>
                </div>
            </div>`;

        const cuerpo = cont.querySelector('.rc-cuerpo');
        const cuenta = cont.querySelector('.rc-cuenta');
        const sel = cont.querySelector('.rc-filtro');
        const buscar = cont.querySelector('.rc-buscar');
        const ant = cont.querySelector('.rc-ant');
        const sig = cont.querySelector('.rc-sig');

        function filtradas() {
            const t = texto.toLowerCase();
            return d.filas.filter(f =>
                (!filtro || (filtro === '__repetidas' ? f.detalle.includes('Clave repetida en el archivo') : f.motivo === filtro)) &&
                (!t || `${f.clave} ${f.descripcion} ${f.detalle}`.toLowerCase().includes(t))
            );
        }
        function render() {
            const lista = filtradas();
            const paginas = Math.max(1, Math.ceil(lista.length / POR_PAGINA));
            pagina = Math.min(pagina, paginas - 1);
            const desde = pagina * POR_PAGINA;
            cuerpo.innerHTML = lista.slice(desde, desde + POR_PAGINA).map(f => `
                <tr>
                    <td style="color:#94a3b8;">${f.fila}</td>
                    <td style="font-family:monospace; white-space:nowrap;">${esc(f.clave) || '<span style="color:#64748b;">(vacía)</span>'}</td>
                    <td style="max-width:240px;">${esc(f.descripcion)}</td>
                    <td>${pill(f.motivo, motivos[f.motivo] || f.motivo)}</td>
                    <td style="color:#cbd5e1; max-width:280px;">${esc(f.detalle)}</td>
                </tr>`).join('') || '<tr><td colspan="5" style="text-align:center; color:#94a3b8; padding:16px;">Sin filas con ese filtro.</td></tr>';
            cuenta.textContent = lista.length
                ? `Mostrando ${(desde + 1).toLocaleString()}–${Math.min(desde + POR_PAGINA, lista.length).toLocaleString()} de ${lista.length.toLocaleString()}`
                : '0 filas';
            ant.disabled = pagina === 0;
            sig.disabled = pagina >= paginas - 1;
        }

        sel.addEventListener('change', () => { filtro = sel.value; pagina = 0; render(); });
        buscar.addEventListener('input', () => { texto = buscar.value.trim(); pagina = 0; render(); });
        ant.addEventListener('click', () => { pagina--; render(); });
        sig.addEventListener('click', () => { pagina++; render(); });
        cont.querySelectorAll('.rc-resumen tr[data-motivo]').forEach(tr => tr.addEventListener('click', () => {
            sel.value = tr.dataset.motivo; filtro = sel.value; pagina = 0; render();
        }));
        render();
    }

    /** Carga y pinta los resultados dentro de un contenedor; si no hay tabla, muestra el aviso. */
    async function llenar(cont, id) {
        cont.innerHTML = '<div style="color:#94a3b8; font-size:12px; padding:10px 0;">Cargando resultados…</div>';
        try {
            pintar(cont, await cargar(id));
        } catch (e) {
            cont.innerHTML = `<div style="color:#94a3b8; font-size:12px; padding:10px 0;">${esc(e.message)}</div>`;
        }
    }

    /** Modal independiente (historial). */
    function abrir(id, archivo) {
        return Swal.fire({
            title: `Resultados de la subida #${id}`,
            html: `${archivo ? `<div style="font-size:12px; color:#94a3b8; margin-bottom:10px;">${esc(archivo)}</div>` : ''}<div class="rc-zona"></div>`,
            width: 980,
            background: '#1a1d27',
            color: '#fff',
            confirmButtonText: 'Cerrar',
            confirmButtonColor: '#334155',
            didOpen: (popup) => llenar(popup.querySelector('.rc-zona'), id),
        });
    }

    window.ResultadosCarga = { cargar, pintar, llenar, abrir };
})();
</script>
