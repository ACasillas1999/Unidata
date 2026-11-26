@extends('layouts.app')

@section('title', 'Homologación de Clientes')
@section('breadcrumb', 'Clientes / Homologación')

@section('content')

{{-- Page Header --}}
<div class="page-header shadow-premium" style="margin-bottom: 20px; padding: 18px 24px; background: var(--grad-surface); border-radius: var(--radius-xl); border: 1px solid var(--glass-border); display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 16px;">
    <div class="page-header-content" style="display:flex; gap:16px; align-items:center;">
        <div class="page-header-icon shadow-premium" style="background: rgba(16,185,129,0.15); border: 1px solid rgba(16,185,129,0.3); color: #34d399;">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21.5 2v6h-6M2.5 22v-6h6M2 11.5a10 10 0 0 1 18.8-4.3M22 12.5a10 10 0 0 1-18.8 4.2"/></svg>
        </div>
        <div>
            <h1 class="page-title" style="margin:0;">Homologación de Clientes</h1>
            <p class="page-subtitle" style="margin:4px 0 0; color:var(--text-secondary);">Busca directo en las sucursales · encuentra clientes que no están en Lista de Clientes</p>
        </div>
    </div>
    <a href="{{ route('clientes.index') }}" class="btn btn--ghost btn--sm" style="border:1px solid var(--border); padding:9px 16px;">Lista de Clientes</a>
</div>

<div class="glass-card shadow-premium" style="display:flex; flex-direction:column; overflow:hidden; border:1px solid var(--border);">

    <form method="GET" action="{{ route('clientes.homologacion') }}" style="margin:0;">
        <div style="padding:16px 20px; background:rgba(255,255,255,0.02); border-bottom:1px solid var(--border); display:flex; flex-direction:column; gap:14px;">
            <div>
                <h2 class="card-title" style="margin:0; font-size:16px; font-weight:800; color:white;">Buscar en sucursales</h2>
                <p class="card-subtitle" style="margin:4px 0 0; font-size:12px; color:var(--text-secondary);">
                    RFC (con o sin espacios: "CVA 050825H75" = "CVA050825H75") o parte de la razón social.
                    Consulta {{ $branches->pluck('name')->map(fn($n) => ucfirst(strtolower($n)))->implode(', ') }}.
                </p>
            </div>
            <div style="display:flex; gap:10px; align-items:center; flex-wrap:wrap;">
                <div class="search-input-wrap" style="flex:1; min-width:240px; display:flex; align-items:center; background:rgba(0,0,0,0.25); border-radius:8px; border:1px solid var(--border); overflow:hidden;">
                    <span style="padding:0 12px; color:var(--text-muted); display:flex; align-items:center;">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="16" height="16"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.35-4.35"/></svg>
                    </span>
                    <input type="text" name="q" value="{{ $search }}" placeholder="RFC o Razón Social…" autocomplete="off" autofocus
                           style="width:100%; border:none; background:transparent; padding:9px 12px 9px 0; color:white; outline:none; font-size:12.5px; font-weight:600;">
                </div>
                <button type="submit" class="btn btn--primary btn--sm shadow-premium" style="background:var(--grad-premium); border:none; padding:8px 18px; font-size:12px;">Buscar</button>
                @if($search)
                    <a href="{{ route('clientes.homologacion') }}" class="btn btn--ghost btn--sm" style="border:1px solid var(--border); padding:8px 16px; font-size:12px;">Limpiar</a>
                @endif
            </div>
        </div>
    </form>

    @if($aviso)
        <div style="margin:14px 20px 0; background:rgba(251,191,36,0.08); border:1px solid rgba(251,191,36,0.25); border-radius:10px; padding:10px 14px; color:#fbbf24; font-size:12.5px;">{{ $aviso }}</div>
    @endif

    @foreach($erroresSucursal as $code => $msg)
        <div style="margin:10px 20px 0; background:rgba(239,68,68,0.08); border:1px solid rgba(239,68,68,0.25); border-radius:10px; padding:10px 14px; color:#f87171; font-size:12.5px;">
            No se pudo consultar <strong>{{ $code }}</strong>: {{ \Illuminate\Support\Str::limit($msg, 180) }}
        </div>
    @endforeach

    @if($search === '')
        <div style="padding:40px 20px; text-align:center; color:var(--text-muted); font-size:13px;">Escribe un RFC o razón social para ver en qué sucursales existe el cliente.</div>
    @elseif(empty($clientes) && !$aviso)
        <div style="padding:40px 20px; text-align:center; color:var(--text-muted); font-size:13px;">Sin resultados para "{{ $search }}" en ninguna sucursal.</div>
    @elseif(!empty($clientes))
        <div style="padding:10px 20px; font-size:12px; color:var(--text-secondary);">{{ count($clientes) }} {{ count($clientes) === 1 ? 'cliente encontrado' : 'clientes encontrados' }}</div>
        <div class="custom-scroll" style="overflow:auto; max-height:calc(100vh - 330px); width:100%; background:#0b0f1a;">
            <table class="data-table" style="border-collapse:separate; border-spacing:0; width:100%;">
                <thead>
                    <tr>
                        <th style="position:sticky; top:0; background:#1a1f2e; min-width:140px;">RFC</th>
                        <th style="position:sticky; top:0; background:#1a1f2e; min-width:220px;">Razón social</th>
                        @foreach($branches as $b)
                            <th style="position:sticky; top:0; background:#1a1f2e; text-align:center; min-width:130px;">{{ ucfirst(strtolower($b->name)) }}</th>
                        @endforeach
                        <th style="position:sticky; top:0; background:#1a1f2e; text-align:center; min-width:110px;">Maestro</th>
                        <th style="position:sticky; top:0; background:#1a1f2e; text-align:center; min-width:110px;">Alta más antigua</th>
                        <th style="position:sticky; top:0; background:#1a1f2e; text-align:center; min-width:140px;">Estado</th>
                        <th style="position:sticky; top:0; background:#1a1f2e; text-align:center; min-width:110px;">Acción</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($clientes as $c)
                        @php
                            $faltan = $branches->filter(fn($b) => !isset($c['sucursales'][$b->code]) && !isset($erroresSucursal[$b->code]))->count();
                            $inactivo = collect($c['sucursales'])->contains(fn($s) => $s['status'] !== 'A');
                        @endphp
                        <tr>
                            <td style="font-family:monospace; font-weight:700; white-space:nowrap;">{{ $c['rfc'] }}</td>
                            <td>{{ $c['razon_social'] }}</td>
                            @foreach($branches as $b)
                                @php($s = $c['sucursales'][$b->code] ?? null)
                                <td style="text-align:center; font-size:11.5px; line-height:1.5;">
                                    @if(isset($erroresSucursal[$b->code]))
                                        <span style="color:#f87171;" title="No se pudo consultar">⚠️</span>
                                    @elseif($s)
                                        <div style="color:#34d399; font-weight:800;">✅ #{{ $s['cliente'] }}</div>
                                        <div style="color:var(--text-muted);">IdGlobal {{ $s['id_global'] }}</div>
                                        <div style="color:{{ $s['status'] === 'A' ? 'var(--text-muted)' : '#fbbf24' }};">
                                            {{ $s['status'] === 'A' ? 'Activo' : ($s['status'] === '' ? 'Sin estatus' : "Status Magic ({$s['status']})") }} · {{ $s['fecha_alta'] ?: 's/f' }}
                                        </div>
                                        @if($s['duplicado'])
                                            <div style="color:#fbbf24;" title="El mismo RFC aparece en más de un cliente de esta sucursal">RFC repetido</div>
                                        @endif
                                    @else
                                        <span style="color:#f87171; font-weight:800;">❌</span>
                                    @endif
                                </td>
                            @endforeach
                            <td style="text-align:center; font-size:11.5px;">
                                @if($c['maestro'])
                                    <div style="color:#34d399; font-weight:800;">✅</div>
                                    <div style="color:var(--text-muted);">IdGlobal {{ $c['maestro']['id_global'] }}</div>
                                @else
                                    <span style="color:#f87171; font-weight:800;">❌</span>
                                @endif
                            </td>
                            <td style="text-align:center; font-size:11.5px;">{{ $c['fecha_alta_min'] ?? '—' }}</td>
                            <td style="text-align:center; font-size:11.5px; line-height:1.5;">
                                @if($c['maestro'] && $faltan === 0)
                                    <span style="color:#34d399; font-weight:700;">Homologado</span>
                                @else
                                    <span style="color:#fbbf24; font-weight:700;">
                                        {{ $c['maestro'] ? '' : 'Falta en maestro' }}{{ !$c['maestro'] && $faltan ? ' · ' : '' }}{{ $faltan ? "Falta en {$faltan} " . ($faltan === 1 ? 'sucursal' : 'sucursales') : '' }}
                                    </span>
                                @endif
                                @if($inactivo)
                                    <div style="color:#fbbf24;">No activo en alguna sucursal</div>
                                @endif
                            </td>
                            <td style="text-align:center;">
                                @if(!($c['maestro'] && $faltan === 0 && collect($c['sucursales'])->every(fn($s) => $s['id_global'] === $c['maestro']['id_global'])))
                                    <button type="button" class="btn btn--primary btn--sm" data-rfc="{{ $c['rfc'] }}" onclick="abrirHomologar(this.dataset.rfc)"
                                            style="background:linear-gradient(135deg, #10b981, #059669); border:none; padding:6px 12px; font-size:11.5px; cursor:pointer;">Homologar</button>
                                @else
                                    <span style="color:var(--text-muted); font-size:11.5px;">—</span>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
</div>
@endsection

@push('scripts')
<script>
(() => {
    const URL_PLAN = @json(route('clientes.homologacion.plan'));
    const URL_HOMOLOGAR = @json(route('clientes.homologacion.homologar'));
    const esc = s => String(s ?? '').replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
    const estatus = s => s === 'A' ? 'Activo' : (s === '' || s == null ? 'Sin estatus' : `Status Magic (${esc(s)})`);

    function accionPlan(s, plan) {
        switch (s.accion) {
            case 'alta':      return `<span style="color:#34d399">Se da de alta como cliente <b>#${esc(s.siguiente_cliente)}</b></span>`;
            case 'id_global': return `<span style="color:#60a5fa">Cliente #${esc(s.cliente)}: IdGlobal ${esc(s.id_global_actual)} → <b>${esc(plan.id_global)}</b> (sus datos no cambian)</span>`;
            case 'ninguna':   return `<span style="color:var(--text-muted)">Cliente #${esc(s.cliente)}: ya tiene IdGlobal ${esc(plan.id_global)}</span>`;
            case 'bloqueada': return `<span style="color:#f87171">⛔ ${esc(s.mensaje)}</span>`;
            default:          return `<span style="color:#f87171">⚠️ No se pudo consultar: ${esc((s.mensaje || '').slice(0, 160))}</span>`;
        }
    }

    function accionResultado(r) {
        if (r.status === 'error')   return `<span style="color:#f87171">⚠️ Error: ${esc((r.mensaje || '').slice(0, 200))}</span>`;
        if (r.status === 'omitida') return `<span style="color:#fbbf24">⛔ Omitida: ${esc(r.mensaje)}</span>`;
        switch (r.accion) {
            case 'creado':    return `<span style="color:#34d399">✅ Dado de alta como cliente #${esc(r.cliente)}</span>`;
            case 'id_global': return `<span style="color:#34d399">✅ Cliente #${esc(r.cliente)}: IdGlobal ${esc(r.de)} → ${esc(r.a)}</span>`;
            default:          return `<span style="color:var(--text-muted)">Sin cambios (cliente #${esc(r.cliente)})</span>`;
        }
    }

    const fila = (k, v) => `<tr><td style="padding:6px 10px; font-weight:700; white-space:nowrap; vertical-align:top;">${k}</td><td style="padding:6px 10px;">${v}</td></tr>`;

    window.abrirHomologar = async function (rfc, origen = null) {
        Swal.fire({ title: 'Calculando…', text: 'Consultando las sucursales (no se escribe nada).', allowOutsideClick: false, didOpen: () => Swal.showLoading() });

        let data;
        try {
            const qs = new URLSearchParams({ rfc, ...(origen ? { origen } : {}) });
            const res = await fetch(`${URL_PLAN}?${qs}`, { headers: { 'Accept': 'application/json' } });
            data = await res.json();
            if (!res.ok || !data.success) throw new Error(data.message || `HTTP ${res.status}`);
        } catch (e) {
            Swal.fire({ icon: 'error', title: 'No se pudo calcular', text: e.message });
            return;
        }

        const p = data.plan;
        const suc = Object.values(p.sucursales);
        let html = `<div style="text-align:left; font-size:13px;">`;

        if (p.origenes.length) {
            html += `<div style="margin-bottom:10px; font-weight:700;">Sucursal de origen de los datos:</div><div style="display:flex; flex-direction:column; gap:6px; margin-bottom:12px;">`;
            for (const code of p.origenes) {
                const s = p.sucursales[code];
                html += `<label style="display:flex; gap:8px; align-items:center; cursor:pointer;">
                    <input type="radio" name="hom-origen" value="${esc(code)}" ${code === p.origen ? 'checked' : ''}>
                    <span><b>${esc(s.name)}</b> · #${esc(s.cliente)} · alta ${esc(s.fecha_alta || 's/f')} · ${estatus(s.status)}<br>
                    <span style="color:var(--text-muted); font-size:12px;">${esc(s.razon_social)}</span></span></label>`;
            }
            html += `</div>`;
        }

        if (p.bloqueo) {
            html += `<div style="background:rgba(239,68,68,0.1); border:1px solid rgba(239,68,68,0.3); border-radius:8px; padding:10px; color:#f87171; margin-bottom:10px;">⛔ ${esc(p.bloqueo)}</div>`;
        }
        for (const a of (p.avisos || [])) {
            html += `<div style="background:rgba(251,191,36,0.08); border:1px solid rgba(251,191,36,0.3); border-radius:8px; padding:10px; color:#fbbf24; margin-bottom:10px;">⚠️ ${esc(a)}</div>`;
        }

        if (!p.bloqueo) {
            html += `<table style="width:100%; border-collapse:collapse; font-size:12.5px;">`;
            html += fila('Maestro', p.maestro.existe
                ? `Ya existe (IdGlobal ${esc(p.maestro.id_global)})${p.maestro.id_global !== p.id_global ? ` → se cambia a <b>${esc(p.id_global)}</b>` : ''}`
                : `<span style="color:#34d399">Se crea con IdGlobal <b>${esc(p.id_global)}</b> y fecha de alta <b>${esc(p.fecha_alta || 'hoy')}</b></span>`);
            for (const s of suc) html += fila(esc(s.name), accionPlan(s, p));
            html += `</table>`;
            for (const x of (p.saltos || [])) html += `<div style="color:var(--text-muted); font-size:12px; margin-top:6px;">ℹ️ ${esc(x)}</div>`;
            html += `<div style="color:var(--text-muted); font-size:11.5px; margin-top:10px;">El IdGlobal y los números de cliente se vuelven a calcular al confirmar.</div>`;
        }
        html += `</div>`;

        const hayAlgo = !p.bloqueo && (!p.maestro.existe || p.maestro.id_global !== p.id_global || suc.some(s => ['alta', 'id_global'].includes(s.accion)));

        const r = await Swal.fire({
            title: `Homologar ${esc(p.rfc || rfc)}`,
            html,
            width: 720,
            showCancelButton: true,
            showConfirmButton: hayAlgo,
            confirmButtonText: 'Homologar',
            cancelButtonText: hayAlgo ? 'Cancelar' : 'Cerrar',
            confirmButtonColor: '#059669',
            reverseButtons: true,
            focusCancel: true,
            didOpen: () => {
                document.querySelectorAll('input[name="hom-origen"]').forEach(el =>
                    el.addEventListener('change', () => abrirHomologar(rfc, el.value)));
            },
        });
        if (!r.isConfirmed) return;

        Swal.fire({ title: 'Homologando…', text: 'Escribiendo en el maestro y en las sucursales.', allowOutsideClick: false, didOpen: () => Swal.showLoading() });
        let out;
        try {
            const res = await fetch(URL_HOMOLOGAR, {
                method: 'POST',
                headers: { 'Accept': 'application/json', 'Content-Type': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content },
                body: JSON.stringify({ rfc, origen: p.origen }),
            });
            out = await res.json();
            if (!res.ok && !out.resultado) throw new Error(out.message || `HTTP ${res.status}`);
        } catch (e) {
            Swal.fire({ icon: 'error', title: 'No se pudo homologar', text: e.message });
            return;
        }

        const m = out.resultado.maestro;
        let rh = `<table style="width:100%; border-collapse:collapse; font-size:12.5px; text-align:left;">`;
        rh += fila('Maestro', m.status === 'ok'
            ? `<span style="color:#34d399">✅ ${m.accion === 'creado' ? 'Creado' : (m.accion === 'id_global' ? 'IdGlobal actualizado' : 'Ya existía')} · IdGlobal ${esc(m.id_global)}</span>`
            : `<span style="color:#f87171">⚠️ Error: ${esc((m.mensaje || '').slice(0, 200))}</span>`);
        for (const s of Object.values(out.resultado.sucursales || {})) rh += fila(esc(s.name), accionResultado(s));
        rh += `</table>`;

        await Swal.fire({ icon: out.success ? 'success' : 'error', title: out.message, html: rh, width: 720 });
        window.location.reload();
    };
})();
</script>
@endpush
