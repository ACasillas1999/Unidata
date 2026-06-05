<?php
$file = '\\\\192.168.60.117\\xampp\\htdocs\\Unidata\\resources\\views\\clientes\\index.blade.php';
$content = file_get_contents($file);

// 1. Add Homologar Clientes button
$searchBtn = '<div style="display:flex; gap:10px; align-items:center;">
        <a href="{{ route(\'clientes.campos\') }}" class="btn btn--ghost btn--sm">';
$replaceBtn = '<div style="display:flex; gap:10px; align-items:center;">
        <button type="button" id="sync-btn" onclick="startSync()" class="btn btn--primary btn--sm shadow-premium" style="background:linear-gradient(135deg, #10b981, #059669); border:none; cursor:pointer;">
            <svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2.5" style="margin-right:5px"><path d="M21.5 2v6h-6M2.5 22v-6h6M2 11.5a10 10 0 0 1 18.8-4.3M22 12.5a10 10 0 0 1-18.8 4.2"/></svg>
            Homologar Clientes
        </button>
        <a href="{{ route(\'clientes.campos\') }}" class="btn btn--ghost btn--sm">';

$content = str_replace($searchBtn, $replaceBtn, $content);

// 2. Add Modal and JS
$modalHtml = <<<'HTML'

{{-- ═══════════════════════════════════════════════════════════
     SYNC LOADER OVERLAY
════════════════════════════════════════════════════════════ --}}
<div id="sync-overlay" style="
    display: none;
    position: fixed;
    inset: 0;
    z-index: 9999;
    background: rgba(2, 6, 23, 0.88);
    backdrop-filter: blur(12px);
    -webkit-backdrop-filter: blur(12px);
    align-items: center;
    justify-content: center;
    flex-direction: column;
    gap: 0;
">
    {{-- Glow decorativo --}}
    <div style="position:absolute; top:30%; left:50%; transform:translate(-50%,-50%); width:400px; height:400px;
                background: radial-gradient(circle, rgba(16,185,129,0.15) 0%, transparent 70%);
                pointer-events:none;"></div>

    <div style="
        background: rgba(15,23,42,0.9);
        border: 1px solid rgba(16,185,129,0.25);
        border-radius: 20px;
        padding: 40px 48px;
        text-align: center;
        max-width: 480px;
        width: 90%;
        box-shadow: 0 25px 60px rgba(0,0,0,0.5), 0 0 0 1px rgba(16,185,129,0.1);
        position: relative;
    ">
        {{-- Spinner animado --}}
        <div style="position:relative; width:72px; height:72px; margin:0 auto 24px;">
            <svg viewBox="0 0 72 72" style="width:72px; height:72px; animation: spin 1.2s linear infinite;">
                <circle cx="36" cy="36" r="30" fill="none" stroke="rgba(16,185,129,0.15)" stroke-width="5"/>
                <circle cx="36" cy="36" r="30" fill="none" stroke="url(#syncGrad)" stroke-width="5"
                        stroke-linecap="round" stroke-dasharray="50 140"/>
                <defs>
                    <linearGradient id="syncGrad" x1="0%" y1="0%" x2="100%" y2="0%">
                        <stop offset="0%" stop-color="#34d399"/>
                        <stop offset="100%" stop-color="#059669"/>
                    </linearGradient>
                </defs>
            </svg>
            <div style="position:absolute; inset:0; display:flex; align-items:center; justify-content:center;">
                <svg viewBox="0 0 24 24" fill="none" width="24" height="24" stroke="#10b981" stroke-width="2">
                    <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/>
                    <circle cx="9" cy="7" r="4"/>
                    <path d="M23 21v-2a4 4 0 0 0-3-3.87M16 3.13a4 4 0 0 1 0 7.75"/>
                </svg>
            </div>
        </div>

        <h2 style="font-size:20px; font-weight:800; color:white; margin:0 0 6px; letter-spacing:-0.01em;">Homologando Clientes</h2>
        <p id="sync-step-label" style="font-size:13px; color:#94a3b8; margin:0 0 28px;">Preparando sincronización...</p>

        {{-- Pasos del proceso --}}
        <div style="display:flex; flex-direction:column; gap:10px; text-align:left; margin-bottom:28px;">
            <div class="sync-step" id="step-1" style="display:flex; align-items:center; gap:10px;">
                <div class="step-dot" style="width:8px;height:8px;border-radius:50%;background:#10b981;flex-shrink:0;"></div>
                <span style="font-size:12px; color:#cbd5e1;">Conectando a las bases de datos</span>
            </div>
            <div class="sync-step" id="step-2" style="display:flex; align-items:center; gap:10px; opacity:0.4;">
                <div class="step-dot" style="width:8px;height:8px;border-radius:50%;background:#475569;flex-shrink:0;"></div>
                <span style="font-size:12px; color:#64748b;">Escaneando catálogos de clientes</span>
            </div>
            <div class="sync-step" id="step-3" style="display:flex; align-items:center; gap:10px; opacity:0.4;">
                <div class="step-dot" style="width:8px;height:8px;border-radius:50%;background:#475569;flex-shrink:0;"></div>
                <span style="font-size:12px; color:#64748b;">Agrupando y unificando por RFC</span>
            </div>
            <div class="sync-step" id="step-4" style="display:flex; align-items:center; gap:10px; opacity:0.4;">
                <div class="step-dot" style="width:8px;height:8px;border-radius:50%;background:#475569;flex-shrink:0;"></div>
                <span style="font-size:12px; color:#64748b;">Guardando en base de datos maestra</span>
            </div>
        </div>

        {{-- Barra de progreso animada --}}
        <div style="height:4px; background:rgba(255,255,255,0.06); border-radius:4px; overflow:hidden; margin-bottom:16px;">
            <div id="sync-progress-bar" style="height:100%; width:0%; background:linear-gradient(90deg,#34d399,#059669); border-radius:4px; transition:width 0.6s ease;"></div>
        </div>

        <div style="display:flex; justify-content:space-between; align-items:center;">
            <p id="sync-elapsed" style="font-size:11px; color:#475569; margin:0;">Tiempo transcurrido: 0s</p>
        </div>
    </div>
</div>

<style>
@keyframes spin { to { transform: rotate(360deg); } }
.sync-step.active span { color: #e2e8f0 !important; }
.sync-step.active .step-dot { background: #10b981 !important; box-shadow: 0 0 8px rgba(16,185,129,0.6); }
.sync-step.done .step-dot { background: #059669 !important; }
.sync-step.done span { color: #6ee7b7 !important; }
</style>

<script>
function startSync() {
    const overlay = document.getElementById('sync-overlay');
    const bar     = document.getElementById('sync-progress-bar');
    const elapsed = document.getElementById('sync-elapsed');
    const stepLbl = document.getElementById('sync-step-label');
    const btn     = document.getElementById('sync-btn');

    overlay.style.display = 'flex';
    btn.disabled = true;

    const startTs   = Date.now();
    let   pollTimer = null;
    let   elapsedT  = null;

    elapsedT = setInterval(() => {
        elapsed.textContent = 'Tiempo transcurrido: ' + Math.round((Date.now() - startTs) / 1000) + 's';
    }, 1000);

    activateStep('step-1');
    bar.style.width = '5%';

    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content || '{{ csrf_token() }}';

    fetch('{{ route("clientes.sync") }}', {
        method: 'POST',
        headers: { 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json' }
    })
    .then(r => r.json())
    .then(data => {
        pollTimer = setInterval(pollStatus, 1500);
    })
    .catch(() => {
        stepLbl.textContent = 'Error al iniciar. Recargando...';
        setTimeout(() => location.reload(), 3000);
    });

    function pollStatus() {
        fetch('{{ route("clientes.sync.status") }}', { headers: { 'Accept': 'application/json' } })
        .then(r => r.json())
        .then(data => {
            const step  = parseInt(data.step  ?? 0);
            const total = parseInt(data.total ?? 1);
            let pct     = total > 0 ? Math.round((step / total) * 95) : 5;
            
            if (data.status === 'done') pct = 100;
            bar.style.width = pct + '%';
            stepLbl.textContent = data.message || 'Procesando...';

            if (pct >= 25 && pct < 50) activateStep('step-2');
            if (pct >= 50 && pct < 80) activateStep('step-3');
            if (pct >= 80) activateStep('step-4');

            if (data.status === 'done' || data.status === 'error') {
                clearInterval(pollTimer);
                clearInterval(elapsedT);
                markAllDone();
                bar.style.width = '100%';
                
                setTimeout(() => location.reload(), 2000);
            }
        });
    }

    function activateStep(id) {
        document.querySelectorAll('.sync-step').forEach(el => {
            el.classList.remove('active');
            if (parseInt(el.id.split('-')[1]) < parseInt(id.split('-')[1])) {
                el.classList.add('done');
                el.style.opacity = '1';
            }
        });
        const curr = document.getElementById(id);
        if (curr) {
            curr.classList.add('active');
            curr.style.opacity = '1';
        }
    }

    function markAllDone() {
        document.querySelectorAll('.sync-step').forEach(el => {
            el.classList.remove('active');
            el.classList.add('done');
            el.style.opacity = '1';
        });
    }
}
</script>

HTML;

$content = str_replace('@endsection', $modalHtml . "\n@endsection", $content);

file_put_contents($file, $content);
echo "Injected index.blade.php changes";
