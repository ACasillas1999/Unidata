<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="Sistema Unidata - Portal de gestión centralizada de artículos, clientes y proveedores.">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Unidata') · Portal Central</title>

    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700,800|instrument-sans:400,500,600,700" rel="stylesheet" />

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    {{-- Fix scroll: min-height:0 es necesario en columnas flex para que overflow-y:auto funcione --}}
    <style>
        html, body { height: 100%; overflow: hidden; }

        .app-layout {
            display: flex;
            height: 100vh;
            overflow: hidden;
        }

        .main-wrapper {
            flex: 1;
            min-width: 0;
            display: flex;
            flex-direction: column;
            height: 100vh;
            overflow: hidden;
        }

        .page-content {
            flex: 1;
            min-height: 0;
            overflow-y: auto;     /* default: scroll for regular pages */
            overflow-x: auto;
            padding: 24px 28px 0;
            display: flex;
            flex-direction: column;
        }
        @stack('page-content-style')
    </style>
</head>
<body>
    <div class="app-layout">

        {{-- ===== SIDEBAR ===== --}}
        <aside class="sidebar" id="sidebar">
            <div class="sidebar-header">
                <div class="sidebar-logo">
                    <div class="sidebar-logo-icon">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M12 2L2 7l10 5 10-5-10-5z"/>
                            <path d="M2 17l10 5 10-5"/>
                            <path d="M2 12l10 5 10-5"/>
                        </svg>
                    </div>
                    <div class="sidebar-logo-text">
                        <span class="sidebar-brand">Unidata</span>
                        <span class="sidebar-tagline">Portal Central</span>
                    </div>
                </div>

                <button class="sidebar-toggle-btn" id="sidebarToggle" aria-label="Colapsar sidebar">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M11 19l-7-7 7-7M18 19l-7-7 7-7"/>
                    </svg>
                </button>
            </div>

            <nav class="sidebar-nav">
                <div class="sidebar-section">
                    <span class="sidebar-section-label">Módulos</span>
                    <ul class="sidebar-menu">
                        <li>
                            <a href="{{ route('articulos.index') }}"
                               class="sidebar-link {{ request()->routeIs('articulos.index') ? 'active' : '' }}"
                               id="nav-articulos">
                                <span class="sidebar-link-icon">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <rect x="2" y="3" width="20" height="14" rx="2"/>
                                        <path d="M8 21h8M12 17v4"/>
                                    </svg>
                                </span>
                                <span class="sidebar-link-label">Artículos</span>
                                @if(request()->routeIs('articulos.index'))
                                    <span class="sidebar-link-dot"></span>
                                @endif
                            </a>
                        </li>
                        <li>
                            <a href="{{ route('articulos.subir') }}"
                               class="sidebar-link {{ request()->routeIs('articulos.subir') ? 'active' : '' }}"
                               id="nav-subir-articulos">
                                <span class="sidebar-link-icon">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/>
                                        <polyline points="17 8 12 3 7 8"/>
                                        <line x1="12" y1="3" x2="12" y2="15"/>
                                    </svg>
                                </span>
                                <span class="sidebar-link-label">Subir Artículos</span>
                                @if(request()->routeIs('articulos.subir'))
                                    <span class="sidebar-link-dot"></span>
                                @endif
                            </a>
                        </li>
                        <li>
                            <a href="{{ route('homologacion.index') }}"
                               class="sidebar-link {{ request()->routeIs('homologacion.*') ? 'active' : '' }}"
                               id="nav-homologacion">
                                <span class="sidebar-link-icon">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <polyline points="22 12 18 12 15 21 9 3 6 12 2 12"/>
                                    </svg>
                                </span>
                                <span class="sidebar-link-label">Homologación</span>
                                @if(request()->routeIs('homologacion.*'))
                                    <span class="sidebar-link-dot"></span>
                                @endif
                            </a>
                        </li>
                        <li>
                            <a href="{{ route('db_master.index') }}"
                               class="sidebar-link {{ request()->routeIs('db_master.*') ? 'active' : '' }}"
                               id="nav-db-master">
                                <span class="sidebar-link-icon">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <ellipse cx="12" cy="5" rx="9" ry="3"/>
                                        <path d="M21 12c0 1.66-4 3-9 3s-9-1.34-9-3"/>
                                        <path d="M3 5v14c0 1.66 4 3 9 3s9-1.34 9-3V5"/>
                                    </svg>
                                </span>
                                <span class="sidebar-link-label">DB Master</span>
                                @if(request()->routeIs('db_master.*'))
                                    <span class="sidebar-link-dot"></span>
                                @endif
                            </a>
                        </li>
                        <li>
                            <a href="{{ route('estadisticas.index') }}"
                               class="sidebar-link {{ request()->routeIs('estadisticas.*') ? 'active' : '' }}"
                               id="nav-estadisticas">
                                <span class="sidebar-link-icon">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <line x1="18" y1="20" x2="18" y2="10"/>
                                        <line x1="12" y1="20" x2="12" y2="4"/>
                                        <line x1="6" y1="20" x2="6" y2="14"/>
                                    </svg>
                                </span>
                                <span class="sidebar-link-label">Estadísticas</span>
                                @if(request()->routeIs('estadisticas.*'))
                                    <span class="sidebar-link-dot"></span>
                                @endif
                            </a>
                        </li>
                        <li>
                            <a href="{{ route('clientes.index') }}"
                               class="sidebar-link {{ request()->routeIs('clientes.*') ? 'active' : '' }}"
                               id="nav-clientes">
                                <span class="sidebar-link-icon">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/>
                                        <circle cx="9" cy="7" r="4"/>
                                        <path d="M23 21v-2a4 4 0 0 0-3-3.87"/>
                                        <path d="M16 3.13a4 4 0 0 1 0 7.75"/>
                                    </svg>
                                </span>
                                <span class="sidebar-link-label">Clientes</span>
                                @if(request()->routeIs('clientes.*'))
                                    <span class="sidebar-link-dot"></span>
                                @endif
                            </a>
                        </li>
                        <li>
                            <a href="{{ route('proveedores.index') }}"
                               class="sidebar-link {{ request()->routeIs('proveedores.*') ? 'active' : '' }}"
                               id="nav-proveedores">
                                <span class="sidebar-link-icon">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/>
                                        <polyline points="9 22 9 12 15 12 15 22"/>
                                    </svg>
                                </span>
                                <span class="sidebar-link-label">Proveedores</span>
                                @if(request()->routeIs('proveedores.*'))
                                    <span class="sidebar-link-dot"></span>
                                @endif
                            </a>
                        </li>
                        <li>
                            <a href="{{ route('conexiones.index') }}"
                               class="sidebar-link {{ request()->routeIs('conexiones.*') ? 'active' : '' }}"
                               id="nav-conexiones">
                                <span class="sidebar-link-icon">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <path d="M5 12.55a11 11 0 0 1 14.08 0"/>
                                        <path d="M1.42 9a16 16 0 0 1 21.16 0"/>
                                        <path d="M8.53 16.11a6 6 0 0 1 6.95 0"/>
                                        <circle cx="12" cy="20" r="1" fill="currentColor"/>
                                    </svg>
                                </span>
                                <span class="sidebar-link-label">Conexiones</span>
                                @if(request()->routeIs('conexiones.*'))
                                    <span class="sidebar-link-dot"></span>
                                @endif
                            </a>
                        </li>
                    </ul>
                </div>

                <div class="sidebar-section sidebar-section--bottom">
                    <span class="sidebar-section-label">Sistema</span>
                    <ul class="sidebar-menu">
                        <li>
                            <a href="#" class="sidebar-link" id="nav-config">
                                <span class="sidebar-link-icon">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <circle cx="12" cy="12" r="3"/>
                                        <path d="M19.07 4.93a10 10 0 0 0-14.14 0M4.93 19.07a10 10 0 0 0 14.14 0"/>
                                        <path d="M12 2v2M12 20v2M2 12h2M20 12h2"/>
                                    </svg>
                                </span>
                                <span class="sidebar-link-label">Configuración</span>
                            </a>
                        </li>
                    </ul>
                </div>
            </nav>

            <div class="sidebar-footer">
                <div class="sidebar-footer-info">
                    <div class="sidebar-footer-avatar">U</div>
                    <div class="sidebar-footer-text">
                        <p class="sidebar-footer-name">Unidata v1.0</p>
                        <p class="sidebar-footer-role">Sistema central</p>
                    </div>
                </div>
            </div>
        </aside>

        {{-- ===== OVERLAY MOBILE ===== --}}
        <div class="sidebar-overlay" id="sidebarOverlay"></div>

        {{-- ===== MAIN CONTENT ===== --}}
        <div class="main-wrapper">

            {{-- TOP BAR --}}
            <header class="topbar">
                <div class="topbar-left">
                    <button class="topbar-menu-btn" id="mobileMenuBtn" aria-label="Abrir menú">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <line x1="3" y1="6" x2="21" y2="6"/>
                            <line x1="3" y1="12" x2="21" y2="12"/>
                            <line x1="3" y1="18" x2="21" y2="18"/>
                        </svg>
                    </button>
                    <nav class="topbar-breadcrumb" aria-label="Breadcrumb">
                        <span class="topbar-breadcrumb-root">Portal</span>
                        <span class="topbar-breadcrumb-sep">/</span>
                        <span class="topbar-breadcrumb-current">@yield('breadcrumb', 'Inicio')</span>
                    </nav>
                </div>
                <div class="topbar-right" style="display:flex; align-items:center; gap:16px;">
                    
                    {{-- ── GLOBAL DOWNLOADS CENTER (GDC) ── --}}
                    <div style="position:relative;">
                        <button id="gdc-toggle-btn" class="topbar-menu-btn" style="border:none; background:transparent; color:var(--text-secondary); cursor:pointer; padding:8px; border-radius:8px; display:flex; align-items:center; position:relative; transition:background 0.2s;" onmouseover="this.style.backgroundColor='rgba(255,255,255,0.05)'" onmouseout="this.style.backgroundColor='transparent'">
                            <svg viewBox="0 0 24 24" fill="none" width="18" height="18" stroke="currentColor" stroke-width="2.5">
                                <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/>
                            </svg>
                            <span id="gdc-badge" style="display:none; position:absolute; top:2px; right:2px; width:9px; height:9px; background:var(--emerald); border-radius:50%; border:2px solid var(--bg-root);"></span>
                        </button>
                        
                        <div id="gdc-panel" class="shadow-premium" style="display:none; position:absolute; top:46px; right:0; width:340px; background:var(--bg-card); border:1px solid var(--border); border-radius:12px; z-index:1000; overflow:hidden;">
                            <div style="padding:14px 16px; border-bottom:1px solid var(--border); display:flex; justify-content:space-between; align-items:center; background:rgba(255,255,255,0.02);">
                                <span style="font-size:13px; font-weight:800; color:white;">Centro de Descargas</span>
                            </div>
                            <div id="gdc-list" style="max-height:400px; overflow-y:auto; padding:12px;">
                                <div style="padding:20px; text-align:center; color:var(--text-muted); font-size:12px;">Cargando...</div>
                            </div>
                        </div>
                    </div>

                    <div class="topbar-badge">
                        <span class="topbar-badge-dot"></span>
                        Conectado
                    </div>
                </div>
            </header>

            {{-- PAGE CONTENT --}}
            <main class="page-content">
                @yield('content')
            </main>
        </div>
    </div>

    @stack('scripts')

    <script>
        (() => {
            const sidebar   = document.getElementById('sidebar');
            const overlay   = document.getElementById('sidebarOverlay');
            const toggleBtn = document.getElementById('sidebarToggle');
            const mobileBtn = document.getElementById('mobileMenuBtn');

            // Persist collapsed state
            const collapsed = localStorage.getItem('sidebar-collapsed') === 'true';
            if (collapsed) sidebar.classList.add('collapsed');

            toggleBtn?.addEventListener('click', () => {
                sidebar.classList.toggle('collapsed');
                localStorage.setItem('sidebar-collapsed', sidebar.classList.contains('collapsed'));
            });

            mobileBtn?.addEventListener('click', () => {
                sidebar.classList.add('mobile-open');
                overlay.classList.add('active');
            });

            overlay?.addEventListener('click', () => {
                sidebar.classList.remove('mobile-open');
                overlay.classList.remove('active');
            });

            // Accordion Logic
            document.querySelectorAll('.sidebar-dropdown-toggle').forEach(btn => {
                btn.addEventListener('click', (e) => {
                    const dropdown = btn.closest('.sidebar-dropdown');
                    const isOpen = dropdown.classList.contains('open');
                    
                    // Close other dropdowns (optional, but cleaner)
                    document.querySelectorAll('.sidebar-dropdown').forEach(d => {
                        if (d !== dropdown) d.classList.remove('open');
                    });

                    dropdown.classList.toggle('open');
                });
            });
        })();
    </script>

    {{-- ═══════════════════════════════════════════════════════
         NOTIFICACIÓN GLOBAL DE SINCRONIZACIÓN (todos los usuarios)
    ════════════════════════════════════════════════════════════ --}}
    <div id="sync-toast" style="
        display: none;
        position: fixed;
        top: 18px;
        right: 22px;
        z-index: 99999;
        max-width: 340px;
        width: 100%;
        animation: toastSlideIn 0.35s cubic-bezier(.16,1,.3,1) both;
    ">
        <div id="sync-toast-inner" style="
            background: rgba(15,23,42,0.97);
            border: 1px solid rgba(245,158,11,0.35);
            border-radius: 14px;
            padding: 14px 18px;
            display: flex;
            align-items: flex-start;
            gap: 13px;
            box-shadow: 0 20px 50px rgba(0,0,0,0.5), 0 0 0 1px rgba(245,158,11,0.08);
            backdrop-filter: blur(12px);
            position: relative;
        ">
            {{-- Icono / spinner --}}
            <div id="sync-toast-icon" style="flex-shrink:0; margin-top:1px;">
                <svg id="sync-toast-spinner" viewBox="0 0 24 24" fill="none" width="20" height="20"
                     stroke="#f59e0b" stroke-width="2.5"
                     style="animation: spin 1.2s linear infinite;">
                    <path d="M21 2v6h-6"/><path d="M3 12a9 9 0 0 1 15-6.7L21 8"/>
                    <path d="M3 22v-6h6"/><path d="M21 12a9 9 0 0 1-15 6.7L3 16"/>
                </svg>
                <svg id="sync-toast-check" viewBox="0 0 24 24" fill="none" width="20" height="20"
                     stroke="#10b981" stroke-width="2.5" style="display:none;">
                    <polyline points="20 6 9 17 4 12"/>
                </svg>
            </div>
            {{-- Texto --}}
            <div style="flex:1; min-width:0;">
                <p id="sync-toast-title" style="font-size:13px; font-weight:800; color:white; margin:0 0 2px;">Sincronizando datos</p>
                <p id="sync-toast-msg"   style="font-size:11px; color:#94a3b8; margin:0; white-space:nowrap; overflow:hidden; text-overflow:ellipsis;">Actualizando catálogos desde las 12 sucursales...</p>
            </div>
            {{-- Indicador pulsante --}}
            <div id="sync-toast-pulse" style="flex-shrink:0; width:8px; height:8px; border-radius:50%; background:#f59e0b; margin-top:5px; animation: pulse 1.4s infinite;"></div>
            
            {{-- Botón Cancelar Global --}}
            <button onclick="globalCancelSync()" id="sync-toast-close" title="Interrumpir proceso" style="background:transparent; border:none; color:#f43f5e; cursor:pointer; padding:2px; height:18px; position:absolute; top:10px; right:12px; display:none;">
                <svg viewBox="0 0 24 24" fill="none" width="14" height="14" stroke="currentColor" stroke-width="3"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
            </button>
        </div>
    </div>

    <style>
    @keyframes toastSlideIn {
        from { opacity:0; transform: translateY(-16px) scale(0.96); }
        to   { opacity:1; transform: translateY(0)     scale(1); }
    }
    @keyframes toastSlideOut {
        from { opacity:1; transform: translateY(0)     scale(1); }
        to   { opacity:0; transform: translateY(-16px) scale(0.96); }
    }
    @keyframes spin   { to { transform: rotate(360deg); } }
    @keyframes pulse  {
        0%,100% { opacity:1; transform:scale(1); }
        50%     { opacity:.4; transform:scale(1.3); }
    }
    </style>

    <script>
    (function globalSyncWatcher() {
        const STATUS_URL = '{{ route("homologacion.sync.status") }}';
        const toast      = document.getElementById('sync-toast');
        const inner      = document.getElementById('sync-toast-inner');
        const title      = document.getElementById('sync-toast-title');
        const msg        = document.getElementById('sync-toast-msg');
        const spinner    = document.getElementById('sync-toast-spinner');
        const check      = document.getElementById('sync-toast-check');
        const pulse      = document.getElementById('sync-toast-pulse');

        let lastStatus   = null;
        let hideTimer    = null;

        function poll() {
            fetch(STATUS_URL, { headers: { Accept: 'application/json' }, cache: 'no-store' })
            .then(r => r.json())
            .then(data => {
                const s = data.status ?? 'idle';

                if (s === 'running') {
                    showRunning(data);
                } else if (s === 'done' && lastStatus === 'running') {
                    showDone();
                } else if (s === 'error' || s === 'cancelled') {
                    showErrorOrCancelled(data);
                } else if (s === 'idle' || s === 'done') {
                    // No hay sync activa — asegurarse que el toast no quede visible
                    if (lastStatus !== 'running') hideToast();
                }

                lastStatus = s;
            })
            .catch(() => {}); // ignorar errores de red
        }

        function showRunning(data) {
            clearTimeout(hideTimer);
            title.textContent = 'Sincronizando datos';
            msg.textContent   = data.message ?? 'Actualizando catálogos...';

            // Estilo ambar (en proceso)
            inner.style.borderColor  = 'rgba(245,158,11,0.35)';
            spinner.style.display    = 'block';
            check.style.display      = 'none';
            document.getElementById('sync-toast-close').style.display = 'block';
            pulse.style.background   = '#f59e0b';
            pulse.style.animation    = 'pulse 1.4s infinite';

            toast.style.display = 'block';
            toast.style.animation = 'toastSlideIn 0.35s cubic-bezier(.16,1,.3,1) both';
        }

        function showDone() {
            title.textContent = '¡Sincronización completada!';
            msg.textContent   = 'Los datos ya están actualizados.';

            // Estilo verde (listo)
            inner.style.borderColor  = 'rgba(16,185,129,0.35)';
            spinner.style.display    = 'none';
            check.style.display      = 'block';
            check.setAttribute('stroke', '#10b981');
            check.innerHTML = '<polyline points="20 6 9 17 4 12"/>';
            document.getElementById('sync-toast-close').style.display = 'none';
            pulse.style.background   = '#10b981';
            pulse.style.animation    = 'none';

            toast.style.display = 'block';
            // Auto-ocultar en 5 segundos
            hideTimer = setTimeout(hideToast, 5000);
        }

        function hideToast() {
            toast.style.animation = 'toastSlideOut 0.3s ease both';
            setTimeout(() => { toast.style.display = 'none'; toast.style.animation = ''; }, 300);
        }

        function showErrorOrCancelled(data) {
            title.textContent = data.status === 'cancelled' ? 'Sincronización Interrumpida' : 'Error en sincronización';
            msg.textContent   = data.message ?? 'Proceso detenido.';
            
            inner.style.borderColor  = 'rgba(244,63,94,0.35)';
            spinner.style.display    = 'none';
            check.style.display      = 'block';
            check.setAttribute('stroke', '#f43f5e');
            check.innerHTML = '<line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/>';
            document.getElementById('sync-toast-close').style.display = 'none';

            pulse.style.background   = '#f43f5e';
            pulse.style.animation    = 'none';

            toast.style.display = 'block';
            hideTimer = setTimeout(hideToast, 6000);
        }

        window.globalCancelSync = function() {
            Swal.fire({
                title: '¿Detener proceso?',
                text: 'La información quedará parcialmente vacía para las sucursales restantes hasta la próxima actualización.',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#f43f5e',
                cancelButtonColor: '#64748b',
                confirmButtonText: 'Sí, detener',
                cancelButtonText: 'Continuar',
                background: '#0f172a',
                color: '#f8fafc',
                backdrop: 'rgba(0,0,0,0.6)',
                customClass: {
                    popup: 'shadow-premium'
                }
            }).then((result) => {
                if (result.isConfirmed) {
                    const btn = document.getElementById('sync-toast-close');
                    if(btn) btn.style.opacity = '0.3';
                    
                    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content;
                    if(!csrfToken) {
                         // Si no hay token en esta pantalla, tratamos de cerrar el toast de todos modos.
                         showErrorOrCancelled({status: 'cancelled', message: 'Cancelado localmente.'});
                         return;
                    }

                    fetch('{{ route("homologacion.sync.cancel") }}', {
                        method: 'POST',
                        headers: { 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json' }
                    }).catch(() => {});
                }
            });
        };

        // Iniciar polling (cada 6 segundos para no sobrecargar)
        poll();
        setInterval(poll, 6000);
    })();
    </script>

    {{-- ═══════════════════════════════════════════════════════
         GLOBAL DOWNLOADS CENTER SCRIPT
    ════════════════════════════════════════════════════════════ --}}
    <script>
    (function GlobalDownloadsCenter() {
        const toggleBtn = document.getElementById('gdc-toggle-btn');
        const panel     = document.getElementById('gdc-panel');
        const list      = document.getElementById('gdc-list');
        const badge     = document.getElementById('gdc-badge');
        
        let isPanelOpen = false;

        toggleBtn?.addEventListener('click', (e) => {
            e.stopPropagation();
            isPanelOpen = !isPanelOpen;
            panel.style.display = isPanelOpen ? 'block' : 'none';
            if (isPanelOpen) fetchGDCData();
        });

        document.addEventListener('click', (e) => {
            if (isPanelOpen && !panel.contains(e.target) && !toggleBtn.contains(e.target)) {
                isPanelOpen = false;
                panel.style.display = 'none';
            }
        });

        window.removeGdcItem = function(id) {
            const el = document.getElementById('gdc-item-'+id);
            if(el) el.style.opacity = '0.5';
            
            fetch('/descargas/' + id, {
                method: 'DELETE',
                headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content }
            }).then(() => fetchGDCData());
        };

        function fetchGDCData() {
            fetch('/descargas', { headers: { 'Accept': 'application/json' }, cache: 'no-store' })
            .then(r => r.json())
            .then(data => {
                if (!Array.isArray(data)) return;
                
                let html = '';
                let isAnyProcessing = false;

                if (data.length === 0) {
                    html = '<div style="padding:30px 20px; text-align:center; color:var(--text-muted);"><svg style="opacity:0.2; margin-bottom:8px;" viewBox="0 0 24 24" width="32" height="32" stroke="currentColor" fill="none"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg><p style="font-size:12px; margin:0;">No hay historial de descargas recientess.</p></div>';
                }

                data.forEach(job => {
                    if (job.status === 'processing') isAnyProcessing = true;

                    html += `<div id="gdc-item-${job.id}" style="padding:12px; border-radius:8px; margin-bottom:8px; background:rgba(255,255,255,0.02); border:1px solid rgba(255,255,255,0.05); position:relative; transition:opacity 0.2s;">`;
                    
                    // Header row
                    html += `<div style="display:flex; justify-content:space-between; margin-bottom:8px; align-items:center;">
                                <span style="font-size:12px; font-weight:700; color:white; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; padding-right:20px; display:flex; align-items:center; gap:6px;">
                                    <svg viewBox="0 0 24 24" width="14" height="14" stroke="currentColor" fill="none" stroke-width="2" style="color:var(--violet-light);"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/><polyline points="10 9 9 9 8 9"/></svg>
                                    ${job.name}
                                </span>
                                <button onclick="removeGdcItem('${job.id}')" style="background:transparent; border:none; color:var(--text-muted); cursor:pointer; position:absolute; top:12px; right:8px; padding:0;" title="Eliminar fila y archivo del servidor">
                                    <svg viewBox="0 0 24 24" width="14" height="14" stroke="currentColor" fill="none" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                                </button>
                             </div>`;

                    if (job.status === 'done') {
                        html += `<div style="display:flex; justify-content:space-between; align-items:center;">
                                    <span style="font-size:11px; color:var(--text-muted);">${job.size_mb} MB · Finalizada</span>
                                    <a href="${job.file_url}" style="font-size:11px; background:var(--emerald-bg); color:var(--emerald); padding:4px 10px; border-radius:6px; text-decoration:none; font-weight:800; border:1px solid rgba(16,185,129,0.2);">Descargar</a>
                                 </div>`;
                    } else if (job.status === 'processing') {
                        const totalDisp = job.total > 0 ? job.total.toLocaleString() : '...';
                        html += `<div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:6px;">
                                    <span style="font-size:11px; color:var(--text-muted);">Procesando ${job.processed.toLocaleString()} / ${totalDisp}</span>
                                    <span style="font-size:11px; font-weight:800; color:var(--emerald);">${job.progress}%</span>
                                 </div>
                                 <div style="height:4px; background:rgba(255,255,255,0.05); border-radius:2px; overflow:hidden;">
                                    <div style="height:100%; width:${job.progress}%; background:var(--emerald); transition:width 0.4s ease;"></div>
                                 </div>`;
                    } else {
                        html += `<span style="font-size:11px; color:var(--rose);">Error: ${job.error || 'Cancelado'}</span>`;
                    }

                    html += `</div>`;
                });

                list.innerHTML = html;
                badge.style.display = isAnyProcessing ? 'block' : 'none';
            })
            .catch(() => {});
        }

        // Start background polling: faster if open, slower if closed
        setInterval(() => {
            fetchGDCData();
        }, 3000); // We keep it at 3s so the green notification badge in the navbar updates in real time globally!

        // Initial fetch
        setTimeout(fetchGDCData, 500); 
    })();
    </script>
</body>
</html>
