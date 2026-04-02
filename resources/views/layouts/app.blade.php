<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="Sistema Unidata - Portal de gestión centralizada de artículos, clientes y proveedores.">
    <title>@yield('title', 'Unidata') · Portal Central</title>

    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700,800|instrument-sans:400,500,600,700" rel="stylesheet" />

    @vite(['resources/css/app.css', 'resources/js/app.js'])
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
                               class="sidebar-link {{ request()->routeIs('articulos.*') ? 'active' : '' }}"
                               id="nav-articulos">
                                <span class="sidebar-link-icon">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <rect x="2" y="3" width="20" height="14" rx="2"/>
                                        <path d="M8 21h8M12 17v4"/>
                                    </svg>
                                </span>
                                <span class="sidebar-link-label">Artículos</span>
                                @if(request()->routeIs('articulos.*'))
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
                <div class="topbar-right">
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
        })();
    </script>
</body>
</html>
