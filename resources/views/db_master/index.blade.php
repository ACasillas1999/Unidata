@extends('layouts.app')

@section('title', 'DB Master')
@section('breadcrumb', 'DB Master')

@section('content')






    <div class="page-header">
        <div class="page-header-content">
            <div class="page-header-icon" style="background: var(--emerald-bg); border: 1px solid rgba(16,185,129,0.3); color: var(--emerald);">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                    <ellipse cx="12" cy="5" rx="9" ry="3"/>
                    <path d="M21 12c0 1.66-4 3-9 3s-9-1.34-9-3"/>
                    <path d="M3 5v14c0 1.66 4 3 9 3s9-1.34 9-3V5"/>
                </svg>
            </div>
            <div>
                <h1 class="page-title">DB Master</h1>
                <p class="page-subtitle">Catálogo Maestro Independiente</p>
            </div>
        </div>
        <div class="page-header-actions">
            <a href="{{ route('db_master.export') }}" class="btn btn--secondary btn--sm shadow-premium" style="border: 1px solid rgba(16,185,129,0.3); background:rgba(16,185,129,0.1); color:var(--emerald);">
                <svg viewBox="0 0 24 24" fill="none" width="14" height="14" stroke="currentColor" stroke-width="2.5"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
                Exportar Excel
            </a>
            <a href="{{ route('db_master.export_powersales') }}" class="btn btn--secondary btn--sm shadow-premium" title="Exporta con nombres de campo PowerSales, 2 hojas: Articulos y Listas de Precios" style="border: 1px solid rgba(59,130,246,0.3); background:rgba(59,130,246,0.1); color:#60a5fa;">
                <svg viewBox="0 0 24 24" fill="none" width="14" height="14" stroke="currentColor" stroke-width="2.5">
                    <path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"/>
                    <polyline points="3.27 6.96 12 12.01 20.73 6.96"/>
                    <line x1="12" y1="22.08" x2="12" y2="12"/>
                </svg>
                Exportar PowerSales
            </a>
            <button onclick="openHistoryModal()" class="btn btn--ghost btn--sm shadow-premium">
                <svg viewBox="0 0 24 24" fill="none" width="14" height="14" stroke="currentColor" stroke-width="2"><path d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                Historial
            </button>
            <button onclick="startSyncMaster()" class="btn btn--primary btn--sm shadow-premium" style="background:var(--emerald); border-color:var(--emerald); color:white;">
                <svg viewBox="0 0 24 24" fill="none" width="14" height="14" stroke="currentColor" stroke-width="2.5"><path d="M21 2v6h-6"/><path d="M3 12a9 9 0 0 1 15-6.7L21 8"/><path d="M3 22v-6h6"/><path d="M21 12a9 9 0 0 1-15 6.7L3 16"/></svg>
                Sincronizar Maestro
            </button>
        </div>
    </div>

    
    <form method="GET" action="{{ route('db_master.index') }}" style="margin-bottom: 12px; flex-shrink: 0;">
        <input type="hidden" name="per_page" id="per_page_input" value="{{ request('per_page', 50) }}">

        <div class="glass-card shadow-premium" style="padding: 10px 16px; display: flex; align-items: center; flex-wrap: wrap; gap: 12px;">
            <div class="search-input-wrap" style="flex:1; min-width:250px; margin: 0; display:flex; align-items:center; background:var(--bg-root); border-radius:8px; border:1px solid var(--border); overflow:hidden;">
                <span class="search-icon" style="padding: 0 12px; color:var(--text-muted); display:flex; align-items:center;">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="16" height="16"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.35-4.35"/></svg>
                </span>
                <input type="text" name="q" value="{{ $search }}" placeholder="Buscar por código maestro o descripción..." class="search-input" autocomplete="off" style="width: 100%; border:none; background:transparent; padding:9px 12px 9px 0; color:white; outline:none; font-size:12px; font-weight:600;">
            </div>

            <div style="display:flex; align-items:center; gap:8px;">
                <button type="submit" class="btn btn--primary btn--sm shadow-premium" style="background:var(--grad-premium); border-color:transparent; color:white; padding:7px 16px; font-size:12px;">Buscar</button>
                @if($search)
                    <a href="{{ route('db_master.index') }}" class="btn btn--ghost btn--sm" style="font-size:12px;">Limpiar</a>
                @endif
            </div>
        </div>
    </form>

    {{-- ALERTS (Success/Errores) --}}
    @if(session('success'))
    <div class="alert alert--success shadow-premium" style="margin-bottom:16px; border-left: 4px solid #10b981; padding: 12px 16px; background: rgba(16, 185, 129, 0.1); color: #10b981; border-radius: 6px;">
        <strong>¡Éxito!</strong> {{ session('success') }}
    </div>
    @endif

    @if(session('error') || $error)
    <div class="alert alert--error shadow-premium" style="margin-bottom:16px; border-left: 4px solid var(--rose); padding: 12px 16px; background: rgba(244, 63, 94, 0.1); color: var(--rose); border-radius: 6px;">
        <p style="font-weight: 700; font-size: 13px; margin:0;">{{ session('error') ?? $error }}</p>
    </div>
    @endif


{{-- RESULTS TABLE --}}
<div class="card shadow-premium" id="db-master-table-card" style="margin-top:20px; overflow:visible;">
    <div style="padding: 10px 14px; border-bottom: 1px solid var(--border); display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 8px;">
        <div style="display:flex; align-items:center; gap:10px;">
            <h2 style="font-size: 13px; font-weight: 800; color: var(--text-primary); margin:0;">Catálogo Universal</h2>
            <span style="font-size: 10px; color: var(--text-muted);">
                · Última sincronización: <span style="color:var(--emerald);font-weight:700;">{{ $stats['last_sync'] ?? 'Nunca' }}</span>
                @if($search)
                    · "<span style="color: var(--violet-light);">{{ $search }}</span>" · {{ number_format($articles->total()) }}
                @else
                    · {{ number_format($stats['universo'] ?? 0) }} artículos
                @endif
            </span>
        </div>
        <div style="background: rgba(255,255,255,0.03); padding: 4px 10px; border-radius: 20px; border: 1px solid var(--border); display: flex; align-items: center; gap: 8px;">
            <label for="page-selector" style="font-size: 10px; font-weight: 700; color: var(--text-muted); text-transform: uppercase;">Mostrar:</label>
            <select id="page-selector" 
                    onchange="document.getElementById('per_page_input').value=this.value; window.location.href='{{ route('db_master.index') }}?per_page='+this.value+'&q={{ $search }}';" 
                    style="background: transparent; border: none; color: var(--emerald); font-size: 11px; font-weight: 800; cursor: pointer; outline: none; -webkit-appearance: none; padding-right: 12px; background-image: url('data:image/svg+xml;utf8,<svg fill=%22%2310b981%22 height=%2214%22 viewBox=%220 0 24 24%22 width=%2214%22 xmlns=%22http://www.w3.org/2000/svg%22><path d=%22M7 10l5 5 5-5z%22/></svg>'); background-repeat: no-repeat; background-position-x: 100%; background-position-y: center;">
                <option value="50" style="background:var(--bg-root);color:white;" @if($per_page == 50) selected @endif>50</option>
                <option value="100" style="background:var(--bg-root);color:white;" @if($per_page == 100) selected @endif>100</option>
                <option value="250" style="background:var(--bg-root);color:white;" @if($per_page == 250) selected @endif>250</option>
                <option value="500" style="background:var(--bg-root);color:white;" @if($per_page == 500) selected @endif>500</option>
            </select>
        </div>
    </div>

    <div class="table-responsive-wide" id="table-inner-wrap" style="overflow-x: auto; overflow-y: hidden !important; max-height: none !important; height: auto !important; width: 100%; max-width: 100%; position:relative; background: rgba(0,0,0,0.1);">
        <table class="data-table table-wide" style="width: 100%; border-collapse: separate; border-spacing: 0;">
            <thead>
                <tr style="background: var(--bg-card-2);">
                    <th class="sticky-col" style="padding: 14px 16px; text-align: center; font-size: 11px; font-weight: 800; color: var(--text-secondary); text-transform: uppercase; letter-spacing: 0.05em; border-bottom: 2px solid var(--border); position: sticky !important; left: 0; z-index: 12; background: var(--bg-card-2); width: 100px; min-width: 100px;">acciones</th>
                    <th class="sticky-col" style="padding: 14px 20px; text-align: left; font-size: 11px; font-weight: 800; color: var(--text-secondary); text-transform: uppercase; letter-spacing: 0.05em; border-bottom: 2px solid var(--border); min-width: 140px; position: sticky !important; left: 100px; z-index: 11; background: var(--bg-card-2);">
                        <a href="{{ request()->fullUrlWithQuery(['sort' => 'clave', 'dir' => ($sort === 'clave' && $dir === 'asc') ? 'desc' : 'asc']) }}" style="color:inherit; text-decoration:none; display:inline-flex; align-items:center; gap:4px;">
                            clave
                            @if($sort === 'clave')
                                <span style="color:var(--emerald); font-weight:bold;">{{ $dir === 'asc' ? '↑' : '↓' }}</span>
                            @else
                                <span style="opacity:0.3; font-size:10px;">↕</span>
                            @endif
                        </a>
                    </th>
                    <th style="padding: 14px 20px; text-align: left; font-size: 11px; font-weight: 800; color: var(--text-secondary); text-transform: uppercase; letter-spacing: 0.05em; border-bottom: 2px solid var(--border); min-width: 250px;">
                        <a href="{{ request()->fullUrlWithQuery(['sort' => 'descripcion', 'dir' => ($sort === 'descripcion' && $dir === 'asc') ? 'desc' : 'asc']) }}" style="color:inherit; text-decoration:none; display:inline-flex; align-items:center; gap:4px;">
                            descripcion
                            @if($sort === 'descripcion')
                                <span style="color:var(--emerald); font-weight:bold;">{{ $dir === 'asc' ? '↑' : '↓' }}</span>
                            @else
                                <span style="opacity:0.3; font-size:10px;">↕</span>
                            @endif
                        </a>
                    </th>
                    <th style="padding: 14px 20px; text-align: center; font-size: 11px; font-weight: 800; color: var(--text-secondary); text-transform: uppercase; letter-spacing: 0.05em; border-bottom: 2px solid var(--border);">
                        <a href="{{ request()->fullUrlWithQuery(['sort' => 'unidad_medida', 'dir' => ($sort === 'unidad_medida' && $dir === 'asc') ? 'desc' : 'asc']) }}" style="color:inherit; text-decoration:none; display:inline-flex; align-items:center; gap:4px;">
                            unidad_medida
                            @if($sort === 'unidad_medida')
                                <span style="color:var(--emerald); font-weight:bold;">{{ $dir === 'asc' ? '↑' : '↓' }}</span>
                            @else
                                <span style="opacity:0.3; font-size:10px;">↕</span>
                            @endif
                        </a>
                    </th>
                    <th style="padding: 14px 20px; text-align: left; font-size: 11px; font-weight: 800; color: var(--text-secondary); text-transform: uppercase; letter-spacing: 0.05em; border-bottom: 2px solid var(--border);">
                        <a href="{{ request()->fullUrlWithQuery(['sort' => 'linea', 'dir' => ($sort === 'linea' && $dir === 'asc') ? 'desc' : 'asc']) }}" style="color:inherit; text-decoration:none; display:inline-flex; align-items:center; gap:4px;">
                            linea
                            @if($sort === 'linea')
                                <span style="color:var(--emerald); font-weight:bold;">{{ $dir === 'asc' ? '↑' : '↓' }}</span>
                            @else
                                <span style="opacity:0.3; font-size:10px;">↕</span>
                            @endif
                        </a>
                    </th>
                    <th style="padding: 14px 20px; text-align: left; font-size: 11px; font-weight: 800; color: var(--text-secondary); text-transform: uppercase; letter-spacing: 0.05em; border-bottom: 2px solid var(--border);">
                        <a href="{{ request()->fullUrlWithQuery(['sort' => 'clasificacion', 'dir' => ($sort === 'clasificacion' && $dir === 'asc') ? 'desc' : 'asc']) }}" style="color:inherit; text-decoration:none; display:inline-flex; align-items:center; gap:4px;">
                            clasificacion
                            @if($sort === 'clasificacion')
                                <span style="color:var(--emerald); font-weight:bold;">{{ $dir === 'asc' ? '↑' : '↓' }}</span>
                            @else
                                <span style="opacity:0.3; font-size:10px;">↕</span>
                            @endif
                        </a>
                    </th>
                    <th style="padding: 14px 20px; text-align: center; font-size: 11px; font-weight: 800; color: var(--text-secondary); text-transform: uppercase; letter-spacing: 0.05em; border-bottom: 2px solid var(--border);">
                        <a href="{{ request()->fullUrlWithQuery(['sort' => 'area', 'dir' => ($sort === 'area' && $dir === 'asc') ? 'desc' : 'asc']) }}" style="color:inherit; text-decoration:none; display:inline-flex; align-items:center; gap:4px;">
                            area
                            @if($sort === 'area')
                                <span style="color:var(--emerald); font-weight:bold;">{{ $dir === 'asc' ? '↑' : '↓' }}</span>
                            @else
                                <span style="opacity:0.3; font-size:10px;">↕</span>
                            @endif
                        </a>
                    </th>
                    <th style="padding: 14px 20px; text-align: center; font-size: 11px; font-weight: 800; color: var(--text-secondary); text-transform: uppercase; letter-spacing: 0.05em; border-bottom: 2px solid var(--border);">habilitado</th>
                    {{-- Precios --}}
                    <th style="padding: 14px 20px; text-align: right; font-size: 11px; font-weight: 800; color: var(--text-secondary); text-transform: uppercase; letter-spacing: 0.05em; border-bottom: 2px solid var(--border);">
                        <a href="{{ request()->fullUrlWithQuery(['sort' => 'precio_lista', 'dir' => ($sort === 'precio_lista' && $dir === 'asc') ? 'desc' : 'asc']) }}" style="color:inherit; text-decoration:none; display:inline-flex; align-items:center; gap:4px;">
                            precio_lista
                            @if($sort === 'precio_lista')
                                <span style="color:var(--emerald); font-weight:bold;">{{ $dir === 'asc' ? '↑' : '↓' }}</span>
                            @else
                                <span style="opacity:0.3; font-size:10px;">↕</span>
                            @endif
                        </a>
                    </th>
                    <th style="padding: 14px 20px; text-align: right; font-size: 11px; font-weight: 800; color: var(--emerald); text-transform: uppercase; letter-spacing: 0.05em; border-bottom: 2px solid var(--border);">
                        <a href="{{ request()->fullUrlWithQuery(['sort' => 'precio_venta', 'dir' => ($sort === 'precio_venta' && $dir === 'asc') ? 'desc' : 'asc']) }}" style="color:inherit; text-decoration:none; display:inline-flex; align-items:center; gap:4px;">
                            precio_venta
                            @if($sort === 'precio_venta')
                                <span style="color:var(--emerald); font-weight:bold;">{{ $dir === 'asc' ? '↑' : '↓' }}</span>
                            @else
                                <span style="opacity:0.3; font-size:10px;">↕</span>
                            @endif
                        </a>
                    </th>
                    <th style="padding: 14px 20px; text-align: center; font-size: 11px; font-weight: 800; color: var(--text-secondary); text-transform: uppercase; letter-spacing: 0.05em; border-bottom: 2px solid var(--border);">
                        <a href="{{ request()->fullUrlWithQuery(['sort' => 'des_precio_venta', 'dir' => ($sort === 'des_precio_venta' && $dir === 'asc') ? 'desc' : 'asc']) }}" style="color:inherit; text-decoration:none; display:inline-flex; align-items:center; gap:4px;">
                            des_precio_venta
                            @if($sort === 'des_precio_venta')
                                <span style="color:var(--emerald); font-weight:bold;">{{ $dir === 'asc' ? '↑' : '↓' }}</span>
                            @else
                                <span style="opacity:0.3; font-size:10px;">↕</span>
                            @endif
                        </a>
                    </th>
                    <th style="padding: 14px 20px; text-align: right; font-size: 11px; font-weight: 800; color: var(--text-secondary); text-transform: uppercase; letter-spacing: 0.05em; border-bottom: 2px solid var(--border);">
                        <a href="{{ request()->fullUrlWithQuery(['sort' => 'precio_especial', 'dir' => ($sort === 'precio_especial' && $dir === 'asc') ? 'desc' : 'asc']) }}" style="color:inherit; text-decoration:none; display:inline-flex; align-items:center; gap:4px;">
                            precio_especial
                            @if($sort === 'precio_especial')
                                <span style="color:var(--emerald); font-weight:bold;">{{ $dir === 'asc' ? '↑' : '↓' }}</span>
                            @else
                                <span style="opacity:0.3; font-size:10px;">↕</span>
                            @endif
                        </a>
                    </th>
                    <th style="padding: 14px 20px; text-align: center; font-size: 11px; font-weight: 800; color: var(--text-secondary); text-transform: uppercase; letter-spacing: 0.05em; border-bottom: 2px solid var(--border);">desc_precio_espec</th>
                    <th style="padding: 14px 20px; text-align: right; font-size: 11px; font-weight: 800; color: var(--text-secondary); text-transform: uppercase; letter-spacing: 0.05em; border-bottom: 2px solid var(--border);">precio4</th>
                    <th style="padding: 14px 20px; text-align: center; font-size: 11px; font-weight: 800; color: var(--text-secondary); text-transform: uppercase; letter-spacing: 0.05em; border-bottom: 2px solid var(--border);">desc_precio4</th>
                    <th style="padding: 14px 20px; text-align: right; font-size: 11px; font-weight: 800; color: var(--text-secondary); text-transform: uppercase; letter-spacing: 0.05em; border-bottom: 2px solid var(--border);">precio_minimo</th>
                    <th style="padding: 14px 20px; text-align: center; font-size: 11px; font-weight: 800; color: var(--text-secondary); text-transform: uppercase; letter-spacing: 0.05em; border-bottom: 2px solid var(--border);">desc_precio_minimo</th>
                    <th style="padding: 14px 20px; text-align: right; font-size: 11px; font-weight: 800; color: var(--text-secondary); text-transform: uppercase; letter-spacing: 0.05em; border-bottom: 2px solid var(--border);">precio_tope</th>
                    <th style="padding: 14px 20px; text-align: center; font-size: 11px; font-weight: 800; color: var(--text-secondary); text-transform: uppercase; letter-spacing: 0.05em; border-bottom: 2px solid var(--border);">mn_usd</th>
                    {{-- SAT --}}
                    <th style="padding: 14px 20px; text-align: center; font-size: 11px; font-weight: 800; color: var(--text-secondary); text-transform: uppercase; letter-spacing: 0.05em; border-bottom: 2px solid var(--border);">idsat</th>
                    <th style="padding: 14px 20px; text-align: center; font-size: 11px; font-weight: 800; color: var(--text-secondary); text-transform: uppercase; letter-spacing: 0.05em; border-bottom: 2px solid var(--border);">iva</th>
                    <th style="padding: 14px 20px; text-align: center; font-size: 11px; font-weight: 800; color: var(--text-secondary); text-transform: uppercase; letter-spacing: 0.05em; border-bottom: 2px solid var(--border);">id_impuesto_sat</th>
                    <th style="padding: 14px 20px; text-align: center; font-size: 11px; font-weight: 800; color: var(--text-secondary); text-transform: uppercase; letter-spacing: 0.05em; border-bottom: 2px solid var(--border);">id_tipo_factor</th>
                    <th style="padding: 14px 20px; text-align: center; font-size: 11px; font-weight: 800; color: var(--text-secondary); text-transform: uppercase; letter-spacing: 0.05em; border-bottom: 2px solid var(--border);">control_pedimentos</th>
                    {{-- Inventario --}}
                    <th style="padding: 14px 20px; text-align: right; font-size: 11px; font-weight: 800; color: var(--text-secondary); text-transform: uppercase; letter-spacing: 0.05em; border-bottom: 2px solid var(--border);">existencia_teorica</th>
                    <th style="padding: 14px 20px; text-align: right; font-size: 11px; font-weight: 800; color: var(--text-secondary); text-transform: uppercase; letter-spacing: 0.05em; border-bottom: 2px solid var(--border);">existencia_fisica</th>
                    <th style="padding: 14px 20px; text-align: right; font-size: 11px; font-weight: 800; color: var(--text-secondary); text-transform: uppercase; letter-spacing: 0.05em; border-bottom: 2px solid var(--border);">punto_reorden</th>
                    <th style="padding: 14px 20px; text-align: right; font-size: 11px; font-weight: 800; color: var(--text-secondary); text-transform: uppercase; letter-spacing: 0.05em; border-bottom: 2px solid var(--border);">inventario_minimo</th>
                    <th style="padding: 14px 20px; text-align: right; font-size: 11px; font-weight: 800; color: var(--text-secondary); text-transform: uppercase; letter-spacing: 0.05em; border-bottom: 2px solid var(--border);">inventario_maximo</th>
                    <th style="padding: 14px 20px; text-align: left; font-size: 11px; font-weight: 800; color: var(--text-secondary); text-transform: uppercase; letter-spacing: 0.05em; border-bottom: 2px solid var(--border);">ubicacion</th>
                    <th style="padding: 14px 20px; text-align: center; font-size: 11px; font-weight: 800; color: var(--text-secondary); text-transform: uppercase; letter-spacing: 0.05em; border-bottom: 2px solid var(--border);">std_pack</th>
                    <th style="padding: 14px 20px; text-align: center; font-size: 11px; font-weight: 800; color: var(--text-secondary); text-transform: uppercase; letter-spacing: 0.05em; border-bottom: 2px solid var(--border);">peso</th>
                    <th style="padding: 14px 20px; text-align: center; font-size: 11px; font-weight: 800; color: var(--text-secondary); text-transform: uppercase; letter-spacing: 0.05em; border-bottom: 2px solid var(--border);">articulo_kit</th>
                    <th style="padding: 14px 20px; text-align: center; font-size: 11px; font-weight: 800; color: var(--text-secondary); text-transform: uppercase; letter-spacing: 0.05em; border-bottom: 2px solid var(--border);">articulo_serie</th>
                    {{-- Costos --}}
                    <th style="padding: 14px 20px; text-align: right; font-size: 11px; font-weight: 800; color: var(--rose); text-transform: uppercase; letter-spacing: 0.05em; border-bottom: 2px solid var(--border);">costo_venta</th>
                    <th style="padding: 14px 20px; text-align: center; font-size: 11px; font-weight: 800; color: var(--text-secondary); text-transform: uppercase; letter-spacing: 0.05em; border-bottom: 2px solid var(--border);">porcetaje_descuento</th>
                    <th style="padding: 14px 20px; text-align: right; font-size: 11px; font-weight: 800; color: var(--text-secondary); text-transform: uppercase; letter-spacing: 0.05em; border-bottom: 2px solid var(--border);">costo_promedio</th>
                    <th style="padding: 14px 20px; text-align: right; font-size: 11px; font-weight: 800; color: var(--text-secondary); text-transform: uppercase; letter-spacing: 0.05em; border-bottom: 2px solid var(--border);">costo_promedio_ant</th>
                    <th style="padding: 14px 20px; text-align: right; font-size: 11px; font-weight: 800; color: var(--text-secondary); text-transform: uppercase; letter-spacing: 0.05em; border-bottom: 2px solid var(--border);">costo_ult_compra</th>
                    <th style="padding: 14px 20px; text-align: center; font-size: 11px; font-weight: 800; color: var(--text-secondary); text-transform: uppercase; letter-spacing: 0.05em; border-bottom: 2px solid var(--border);">fecha_ult_compra</th>
                    <th style="padding: 14px 20px; text-align: center; font-size: 11px; font-weight: 800; color: var(--text-secondary); text-transform: uppercase; letter-spacing: 0.05em; border-bottom: 2px solid var(--border);">fecha_alta</th>
                    <th style="padding: 14px 20px; text-align: center; font-size: 11px; font-weight: 800; color: var(--text-secondary); text-transform: uppercase; letter-spacing: 0.05em; border-bottom: 2px solid var(--border);">en_promocion</th>
                    <th style="padding: 14px 20px; text-align: center; font-size: 11px; font-weight: 800; color: var(--text-secondary); text-transform: uppercase; letter-spacing: 0.05em; border-bottom: 2px solid var(--border);">critico</th>
                    {{-- Otros --}}
                    <th style="padding: 14px 20px; text-align: left; font-size: 11px; font-weight: 800; color: var(--text-secondary); text-transform: uppercase; letter-spacing: 0.05em; border-bottom: 2px solid var(--border);">sustituto</th>
                    <th style="padding: 14px 20px; text-align: left; font-size: 11px; font-weight: 800; color: var(--text-secondary); text-transform: uppercase; letter-spacing: 0.05em; border-bottom: 2px solid var(--border);">sustituto1</th>
                </tr>
            </thead>
            <tbody>
                @forelse($articles as $row)
                    <tr style="transition: background 0.1s;" onmouseover="this.style.background='rgba(255,255,255,0.02)'" onmouseout="this.style.background='transparent'">
                        <td class="sticky-col" style="padding: 10px 14px; text-align: center; border-bottom: 1px solid var(--border-light); white-space: nowrap; position: sticky !important; left: 0; background: var(--bg-card); z-index: 6;">
                            <button onclick="openEditModalFromEl(this)" data-row='@json($row)' class="btn btn--sm btn--ghost shadow-premium" style="padding: 5px 10px; border: 1px solid rgba(139,92,246,0.3); background:rgba(139,92,246,0.15); color:var(--violet-light); font-weight:700;" title="Editar Artículo">
                                <svg viewBox="0 0 24 24" fill="none" width="14" height="14" stroke="currentColor" stroke-width="2.5" style="margin-right:4px; vertical-align:middle;"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
                                Editar
                            </button>
                        </td>
                        <td class="sticky-col" style="padding: 12px 20px; font-family: 'JetBrains Mono', monospace; font-size: 12px; color: var(--emerald); font-weight: 600; border-bottom: 1px solid var(--border-light); white-space: nowrap; position: sticky !important; left: 100px; background: var(--bg-card); z-index: 5;">{{ $row->clave }}</td>
                        <td style="padding: 12px 20px; font-size: 13px; color: var(--text-primary); border-bottom: 1px solid var(--border-light); min-width: 250px;">{{ $row->descripcion }}</td>
                        <td style="padding: 12px 20px; text-align: center; font-size: 12px; color: var(--text-secondary); border-bottom: 1px solid var(--border-light);">{{ $row->unidad_medida }}</td>
                        <td style="padding: 12px 20px; text-align: left; font-size: 11px; color: var(--text-secondary); border-bottom: 1px solid var(--border-light);">{{ $row->linea }}</td>
                        <td style="padding: 12px 20px; text-align: left; font-size: 11px; color: var(--text-secondary); border-bottom: 1px solid var(--border-light);">{{ $row->clasificacion }}</td>
                        <td style="padding: 12px 20px; text-align: center; font-size: 11px; color: var(--text-secondary); border-bottom: 1px solid var(--border-light);">{{ $row->area }}</td>
                        <td style="padding: 12px 20px; text-align: center; border-bottom: 1px solid var(--border-light);">
                             <span class="homo-pill {{ $row->habilitado == 1 ? 'homo-pill--ok' : 'homo-pill--miss' }}" style="font-size: 10px; padding: 2px 8px;">{{ $row->habilitado == 1 ? 'SI' : 'NO' }}</span>
                        </td>
                        {{-- Precios --}}
                        <td style="padding: 12px 20px; text-align: right; font-size: 12px; font-family: 'JetBrains Mono', monospace; color: var(--text-primary); border-bottom: 1px solid var(--border-light); font-variant-numeric: tabular-nums;">${{ number_format((float)($row->precio_lista ?? 0), 2) }}</td>
                        <td style="padding: 12px 20px; text-align: right; font-size: 12px; font-family: 'JetBrains Mono', monospace; color: var(--emerald); border-bottom: 1px solid var(--border-light); font-variant-numeric: tabular-nums; font-weight: 700;">${{ number_format((float)($row->precio_venta ?? 0), 2) }}</td>
                        <td style="padding: 12px 20px; text-align: center; font-size: 11px; color: var(--text-muted); border-bottom: 1px solid var(--border-light);">{{ number_format((float)($row->des_precio_venta ?? 0), 2) }}%</td>
                        <td style="padding: 12px 20px; text-align: right; font-size: 12px; font-family: 'JetBrains Mono', monospace; border-bottom: 1px solid var(--border-light); font-variant-numeric: tabular-nums;">${{ number_format((float)($row->precio_especial ?? 0), 2) }}</td>
                        <td style="padding: 12px 20px; text-align: center; font-size: 11px; color: var(--text-muted); border-bottom: 1px solid var(--border-light);">{{ number_format((float)($row->desc_precio_espec ?? 0), 2) }}%</td>
                        <td style="padding: 12px 20px; text-align: right; font-size: 12px; font-family: 'JetBrains Mono', monospace; border-bottom: 1px solid var(--border-light); font-variant-numeric: tabular-nums;">${{ number_format((float)($row->precio4 ?? 0), 2) }}</td>
                        <td style="padding: 12px 20px; text-align: center; font-size: 11px; color: var(--text-muted); border-bottom: 1px solid var(--border-light);">{{ number_format((float)($row->desc_precio4 ?? 0), 2) }}%</td>
                        <td style="padding: 12px 20px; text-align: right; font-size: 12px; font-family: 'JetBrains Mono', monospace; border-bottom: 1px solid var(--border-light); font-variant-numeric: tabular-nums;">${{ number_format((float)($row->precio_minimo ?? 0), 2) }}</td>
                        <td style="padding: 12px 20px; text-align: center; font-size: 11px; color: var(--text-muted); border-bottom: 1px solid var(--border-light);">{{ number_format((float)($row->desc_precio_minimo ?? 0), 2) }}%</td>
                        <td style="padding: 12px 20px; text-align: right; font-size: 12px; font-family: 'JetBrains Mono', monospace; border-bottom: 1px solid var(--border-light); font-variant-numeric: tabular-nums;">${{ number_format((float)($row->precio_tope ?? 0), 2) }}</td>
                        <td style="padding: 12px 20px; text-align: center; font-size: 11px; color: var(--text-muted); border-bottom: 1px solid var(--border-light);">{{ in_array(strtoupper(trim((string)($row->mn_usd ?? ''))), ['U', '1', 'USD'], true) ? 'USD' : 'MXN' }}</td>
                        {{-- SAT --}}
                        <td style="padding: 12px 20px; text-align: center; font-size: 11px; color: var(--text-muted); border-bottom: 1px solid var(--border-light);">{{ $row->idsat }}</td>
                        <td style="padding: 12px 20px; text-align: center; font-size: 11px; color: var(--text-muted); border-bottom: 1px solid var(--border-light);">{{ $row->iva }}%</td>
                        <td style="padding: 12px 20px; text-align: center; font-size: 11px; color: var(--text-muted); border-bottom: 1px solid var(--border-light);">{{ $row->id_impuesto_sat }}</td>
                        <td style="padding: 12px 20px; text-align: center; font-size: 11px; color: var(--text-muted); border-bottom: 1px solid var(--border-light);">{{ $row->id_tipo_factor }}</td>
                        <td style="padding: 12px 20px; text-align: center; border-bottom: 1px solid var(--border-light);">{{ $row->control_pedimentos == 1 ? 'S' : 'N' }}</td>
                        {{-- Inventario --}}
                        <td style="padding: 12px 20px; text-align: right; font-size: 11px; color: var(--text-secondary); border-bottom: 1px solid var(--border-light);">{{ number_format($row->existencia_teorica, 2) }}</td>
                        <td style="padding: 12px 20px; text-align: right; font-size: 11px; color: var(--violet-light); border-bottom: 1px solid var(--border-light);">{{ number_format($row->existencia_fisica, 2) }}</td>
                        <td style="padding: 12px 20px; text-align: right; font-size: 11px; color: var(--text-secondary); border-bottom: 1px solid var(--border-light);">{{ number_format($row->punto_reorden, 2) }}</td>
                        <td style="padding: 12px 20px; text-align: right; font-size: 11px; color: var(--text-secondary); border-bottom: 1px solid var(--border-light);">{{ number_format($row->inventario_minimo, 2) }}</td>
                        <td style="padding: 12px 20px; text-align: right; font-size: 11px; color: var(--text-secondary); border-bottom: 1px solid var(--border-light);">{{ number_format($row->inventario_maximo, 2) }}</td>
                        <td style="padding: 12px 20px; text-align: left; font-size: 11px; color: var(--text-secondary); border-bottom: 1px solid var(--border-light);">{{ $row->ubicacion }}</td>
                        <td style="padding: 12px 20px; text-align: center; font-size: 11px; color: var(--text-secondary); border-bottom: 1px solid var(--border-light);">{{ $row->std_pack }}</td>
                        <td style="padding: 12px 20px; text-align: center; font-size: 11px; color: var(--text-secondary); border-bottom: 1px solid var(--border-light);">{{ $row->peso }}</td>
                        <td style="padding: 12px 20px; text-align: center; border-bottom: 1px solid var(--border-light);">{{ $row->articulo_kit == 1 ? 'S' : 'N' }}</td>
                        <td style="padding: 12px 20px; text-align: center; border-bottom: 1px solid var(--border-light);">{{ $row->articulo_serie == 1 ? 'S' : 'N' }}</td>
                        {{-- Costos --}}
                        <td style="padding: 12px 20px; text-align: right; font-size: 12px; font-family: 'JetBrains Mono', monospace; color: var(--rose); border-bottom: 1px solid var(--border-light); font-variant-numeric: tabular-nums;">${{ number_format((float)($row->costo_venta ?? 0), 2) }}</td>
                        <td style="padding: 12px 20px; text-align: center; font-size: 11px; color: var(--text-muted); border-bottom: 1px solid var(--border-light);">{{ number_format((float)($row->porcetaje_descuento ?? 0), 2) }}%</td>
                        <td style="padding: 12px 20px; text-align: right; font-size: 11px; color: var(--text-secondary); border-bottom: 1px solid var(--border-light);">{{ number_format($row->costo_promedio, 2) }}</td>
                        <td style="padding: 12px 20px; text-align: right; font-size: 11px; color: var(--text-secondary); border-bottom: 1px solid var(--border-light);">{{ number_format($row->costo_promedio_ant, 2) }}</td>
                        <td style="padding: 12px 20px; text-align: right; font-size: 11px; color: var(--text-secondary); border-bottom: 1px solid var(--border-light);">{{ number_format($row->costo_ult_compra, 2) }}</td>
                        <td style="padding: 12px 20px; text-align: center; font-size: 11px; color: var(--text-muted); border-bottom: 1px solid var(--border-light);">{{ $row->fecha_ult_compra }}</td>
                        <td style="padding: 12px 20px; text-align: center; font-size: 11px; color: var(--text-muted); border-bottom: 1px solid var(--border-light);">{{ $row->fecha_alta }}</td>
                        <td style="padding: 12px 20px; text-align: center; border-bottom: 1px solid var(--border-light);">{{ $row->en_promocion == 1 ? 'S' : 'N' }}</td>
                        <td style="padding: 12px 20px; text-align: center; border-bottom: 1px solid var(--border-light);">{{ $row->critico == 1 ? 'S' : 'N' }}</td>
                        {{-- Otros --}}
                        <td style="padding: 12px 20px; text-align: left; font-size: 11px; color: var(--text-secondary); border-bottom: 1px solid var(--border-light);">{{ $row->sustituto }}</td>
                        <td style="padding: 12px 20px; text-align: left; font-size: 11px; color: var(--text-secondary); border-bottom: 1px solid var(--border-light);">{{ $row->sustituto1 }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="60" style="padding: 60px; text-align: center; color: var(--text-muted); font-size: 14px;">
                            <svg style="opacity: 0.2; margin-bottom: 12px;" viewBox="0 0 24 24" fill="none" width="48" height="48" stroke="currentColor" stroke-width="1"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.35-4.35"/></svg>
                            <p>No hay artículos en la base maestra. Pulsa "Sincronizar Maestro" para actualizar.</p>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($articles->hasPages())
        <div style="padding: 16px 24px; background: var(--bg-card); display: flex; justify-content: space-between; align-items: center; border-top: 1px solid var(--border); flex-wrap: wrap; gap: 12px;">
            <p style="font-size: 12px; color: var(--text-muted); margin:0;">
                Mostrando página <span style="color:var(--text-primary); font-weight:700;">{{ $articles->currentPage() }}</span> de {{ $articles->lastPage() }}
            </p>
            <div class="premium-pagination">
                {{ $articles->links('pagination::bootstrap-4') }}
            </div>
        </div>
    @endif
</div>


<div id="history-modal" style="display:none; position:fixed; inset:0; z-index:9999; background:rgba(2,6,23,0.85); backdrop-filter:blur(8px); align-items:center; justify-content:center;">
    <div class="glass-card shadow-premium" style="width:90%; max-width:600px; padding:0; overflow:hidden; border:1px solid rgba(16,185,129,0.3);">
        <div style="padding:20px 24px; background:rgba(16,185,129,0.1); border-bottom:1px solid rgba(16,185,129,0.2); display:flex; justify-content:space-between; align-items:center;">
            <div style="display:flex; align-items:center; gap:12px;">
                <div style="width:32px; height:32px; border-radius:8px; background:var(--emerald); color:white; display:flex; align-items:center; justify-content:center;">
                    <svg viewBox="0 0 24 24" fill="none" width="18" height="18" stroke="currentColor" stroke-width="2.5"><path d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                </div>
                <h3 style="margin:0; font-size:16px; font-weight:800; color:white;">Historial de Sincronización</h3>
            </div>
            <button onclick="closeHistoryModal()" style="background:transparent; border:none; color:var(--text-muted); cursor:pointer;"><svg viewBox="0 0 24 24" fill="none" width="20" height="20" stroke="currentColor" stroke-width="2.5"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg></button>
        </div>
        <div id="history-content" style="max-height:400px; overflow-y:auto; padding:12px;">
            <div style="padding:40px; text-align:center; color:var(--text-muted);">Cargando historial...</div>
        </div>
        <div style="padding:16px 24px; text-align:right; background:rgba(0,0,0,0.2); border-top:1px solid var(--border);">
            <button onclick="closeHistoryModal()" class="btn btn--ghost" style="font-size:12px; border:1px solid var(--border);">Cerrar</button>
        </div>
    </div>
</div>

<script>
function startSyncMaster() {
    Swal.fire({
        title: '¿Sincronizar DB Master?',
        text: 'El proceso correrá en segundo plano. Puedes cerrar la pestaña sin interrumpirlo.',
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#10b981',
        cancelButtonColor: '#64748b',
        confirmButtonText: 'Sí, sincronizar',
        cancelButtonText: 'Cancelar',
        background: '#0f172a',
        color: '#f8fafc',
        backdrop: 'rgba(0,0,0,0.6)',
    }).then((result) => {
        if (!result.isConfirmed) return;

        fetch('{{ route("db_master.sync") }}', {
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Accept': 'application/json' }
        })
        .then(r => r.json())
        .then(data => {
            if (data.status === 'already_running') {
                showSyncOverlay(); // ya corre, mostrar overlay de progreso
                return;
            }
            if (data.status === 'started') {
                showSyncOverlay();
            } else {
                Swal.fire({ icon: 'error', title: 'Error', text: data.message || 'No se pudo iniciar.', background: '#0f172a', color: '#f8fafc' });
            }
        })
        .catch(e => {
            Swal.fire({ icon: 'error', title: 'Error Fatal', text: 'Error en la petición: ' + e.message });
        });
    });
}

function showSyncOverlay() {
    const overlay = document.getElementById('dbmaster-sync-overlay');
    overlay.style.display = 'flex';
    const bar     = document.getElementById('dbmaster-sync-bar');
    const pct     = document.getElementById('dbmaster-sync-pct');
    const msg     = document.getElementById('dbmaster-sync-msg');
    const elapsed = document.getElementById('dbmaster-sync-elapsed');
    const startTs = Date.now();

    const elTimer = setInterval(() => {
        elapsed.textContent = 'Tiempo: ' + Math.round((Date.now() - startTs) / 1000) + 's';
    }, 1000);

    const poll = setInterval(() => {
        fetch('{{ route("db_master.sync.status") }}', { headers: { 'Accept': 'application/json' } })
        .then(r => r.json())
        .then(data => {
            const step  = parseInt(data.step  ?? 0);
            const total = parseInt(data.total ?? 1);
            const p     = total > 0 ? Math.min(Math.round((step / total) * 100), 99) : 5;

            bar.style.width = p + '%';
            pct.textContent = p + '%';
            msg.textContent = data.message ?? '...';

            if (data.status === 'done') {
                clearInterval(poll); clearInterval(elTimer);
                bar.style.width = '100%'; pct.textContent = '100%';
                msg.textContent = '✅ ' + data.message;
                setTimeout(() => { overlay.style.display='none'; location.reload(); }, 2000);
            } else if (data.status === 'error') {
                clearInterval(poll); clearInterval(elTimer);
                msg.textContent = '❌ ' + data.message;
                bar.style.background = '#ef4444';
            }
        });
    }, 2500);
}


function openHistoryModal() {
    const modal = document.getElementById('history-modal');
    modal.style.display = 'flex';
    const content = document.getElementById('history-content');
    
    fetch('{{ route('db_master.history') }}')
    .then(r => r.json())
    .then(data => {
        if (!data || data.length === 0) {
            content.innerHTML = '<div style="padding:40px; text-align:center; color:var(--text-muted); font-size:12px;">No hay registros de sincronización aún.</div>';
            return;
        }

        let html = '<table style="width:100%; font-size:12px; border-collapse:collapse;">';
        html += '<thead style="background:rgba(255,255,255,0.03);"><tr style="border-bottom:1px solid var(--border);">';
        html += '<th style="padding:10px;text-align:left;color:var(--text-muted);">FECHA / HORA</th>';
        html += '<th style="padding:10px;text-align:right;color:var(--text-muted);">ARTÍCULOS</th>';
        html += '</tr></thead><tbody>';

        data.forEach(h => {
            const date = new Date(h.created_at).toLocaleString();
            html += `<tr style="border-bottom:1px solid rgba(255,255,255,0.05);">
                <td style="padding:12px 10px; color:white; font-weight:600;">${date}</td>
                <td style="padding:12px 10px; text-align:right; color:var(--emerald); font-weight:800; font-family:\'JetBrains Mono\', monospace;">${h.total_articulos.toLocaleString()}</td>
            </tr>`;
        });
        html += '</tbody></table>';
        content.innerHTML = html;
    });
}

function closeHistoryModal() {
    document.getElementById('history-modal').style.display = 'none';
}

function switchModalTab(event, tabId) {
    // Hide all panels
    document.querySelectorAll('.modal-tab-panel').forEach(panel => panel.style.display = 'none');
    // Deactivate all tabs
    document.querySelectorAll('.modal-tab').forEach(tab => {
        tab.classList.remove('active');
        tab.style.color = 'var(--text-muted)';
        tab.style.borderBottomColor = 'transparent';
    });
    
    // Show selected panel
    document.getElementById(tabId).style.display = 'block';
    // Activate clicked tab
    event.currentTarget.classList.add('active');
    event.currentTarget.style.color = 'white';
    event.currentTarget.style.borderBottomColor = 'var(--violet)';
}

function calculateEditPrices() {
    const editPrecioLista = document.getElementById('edit-precio_lista');
    const editDescP4 = document.getElementById('edit-desc_precio4');
    const editPrecio4 = document.getElementById('edit-precio4');
    const editDescEsp = document.getElementById('edit-desc_precio_espec');
    const editPrecioEsp = document.getElementById('edit-precio_especial');
    const editPorcPV = document.getElementById('edit-porcentaje_pv');
    const editPrecioVenta = document.getElementById('edit-precio_venta');
    const editDesVentaFinal = document.getElementById('edit-des_precio_venta');
    const editDescProv = document.getElementById('edit-desc_proveedor');
    const editResDescProv = document.getElementById('edit-resultado_desc_proveedor');
    const editPorcDesc = document.getElementById('edit-porcetaje_descuento');
    const editPrecioTope = document.getElementById('edit-precio_tope');

    const lista = parseFloat(editPrecioLista ? editPrecioLista.value : 0) || 0;
    const d4 = parseFloat(editDescP4 ? editDescP4.value : 0) || 0;
    const dEsp = parseFloat(editDescEsp ? editDescEsp.value : 0) || 0;
    const pPV = parseFloat(editPorcPV ? editPorcPV.value : 0) || 0;
    const pDesc = parseFloat(editPorcDesc ? editPorcDesc.value : 0) || 0;
    const dProv = parseFloat(editDescProv ? editDescProv.value : 0) || 0;

    // 1. Precio 4 = Precio_Lista * (100 - Desc_Precio4) / 100
    const p4 = lista * (100 - d4) / 100;
    if (editPrecio4) editPrecio4.value = p4.toFixed(2);

    // 2. Precio Especial = Precio_Lista * (100 - Desc_Precio_Esp) / 100
    const pEsp = lista * (100 - dEsp) / 100;
    if (editPrecioEsp) editPrecioEsp.value = pEsp.toFixed(2);

    // 3. Precio Venta = Precio_Especial * (1 + Porcentaje_PV / 100)
    const pVenta = pEsp * (1 + pPV / 100);
    if (editPrecioVenta) editPrecioVenta.value = pVenta.toFixed(2);

    // 4. Desc Venta Final (%) = 100 - (Precio_Venta / Precio_Lista * 100)
    if (editDesVentaFinal) {
        if (lista > 0) {
            const dVentaFinal = 100 - (pVenta / lista * 100);
            editDesVentaFinal.value = dVentaFinal.toFixed(2);
        } else {
            editDesVentaFinal.value = "0.00";
        }
    }

    // 5. Precio Proveedor (Gerente) = Precio_Lista * (100 - Desc_Proveedor) / 100
    if (editResDescProv) {
        const pGerente = lista * (100 - dProv) / 100;
        editResDescProv.value = pGerente.toFixed(2);
    }

    // 6. Precio Tope = Precio_Lista * (100 - Porcentaje_Descuento) / 100
    if (editPrecioTope && document.activeElement !== editPrecioTope) {
        const pTope = lista * (100 - pDesc) / 100;
        editPrecioTope.value = pTope.toFixed(2);
    }
}

function openEditModalFromEl(btn) {
    try {
        let rawData = btn.getAttribute('data-row');
        if (!rawData) return;
        if (typeof rawData === 'string' && rawData.includes('&quot;')) {
            const txt = document.createElement('textarea');
            txt.innerHTML = rawData;
            rawData = txt.value;
        }
        const row = typeof rawData === 'string' ? JSON.parse(rawData) : rawData;
        openEditModal(row);
    } catch (e) {
        console.error("Error al procesar datos del artículo para el modal:", e);
        Swal.fire('Error', 'No se pudieron procesar los datos del artículo seleccionado.', 'error');
    }
}

function openEditModal(row) {
    console.log("Opening edit modal for:", row);

    // Guardamos los valores originales para luego enviar solo lo que el usuario realmente cambie
    window.editOriginalData = row;

    // Set Header
    document.getElementById('modal-clave-badge').textContent = row.clave;
    
    // General
    document.getElementById('edit-article-id').value = row.id;
    document.getElementById('edit-clave').value = row.clave;
    document.getElementById('edit-descripcion').value = row.descripcion || '';
    document.getElementById('edit-linea').value = row.linea || '';
    document.getElementById('edit-clasificacion').value = row.clasificacion || '';
    document.getElementById('edit-area').value = row.area || '';
    document.getElementById('edit-unidad_medida').value = row.unidad_medida || '';
    
    const colorSelect = document.getElementById('edit-color');
    if (colorSelect) colorSelect.value = row.color !== null && row.color !== undefined ? row.color : 0;

    @if(isset($branches))
        let bColorEl;
        @foreach($branches as $branch)
            @php
                $bCode = is_array($branch) ? ($branch['code'] ?? '') : ($branch->code ?? '');
            @endphp
            @if($bCode)
                bColorEl = document.getElementById('edit-color_branch-{{ $bCode }}');
                if (bColorEl) {
                    bColorEl.value = (row.color_branch && row.color_branch['{{ $bCode }}'] !== undefined) ? row.color_branch['{{ $bCode }}'] : '';
                }
            @endif
        @endforeach
    @endif

    document.getElementById('edit-protocolo').checked = !!row.protocolo;
    document.getElementById('edit-articulo_kit').checked = !!row.articulo_kit;
    document.getElementById('edit-articulo_serie').checked = !!row.articulo_serie;
    document.getElementById('edit-habilitado').checked = (row.habilitado == 1);
    
    // Precios
    const mnVal = (row.mn_usd === 'U' || row.mn_usd === 1 || row.mn_usd === '1' || row.mn_usd === 'USD') ? 'U' : 'M';
    document.getElementById('edit-mn_usd').value = mnVal;
    document.getElementById('edit-precio_lista').value = row.precio_lista || 0;
    document.getElementById('edit-desc_precio4').value = row.desc_precio4 || 0;
    document.getElementById('edit-desc_precio_espec').value = row.desc_precio_espec || 0;
    document.getElementById('edit-porcentaje_pv').value = row.porcentaje_pv || 5.27;
    document.getElementById('edit-desc_proveedor').value = row.desc_proveedor || 0;

    let porcDescVal = row.porcetaje_descuento || 0;
    if (!porcDescVal && (row.precio_lista > 0) && (row.precio_tope > 0)) {
        porcDescVal = (100 - (row.precio_tope / row.precio_lista * 100)).toFixed(2);
    }
    document.getElementById('edit-porcetaje_descuento').value = porcDescVal || 0;

    document.getElementById('edit-margen_minimo').value = row.margen_minimo || 0;
    document.getElementById('edit-costo_venta').value = row.costo_venta || 0;
    
    // Calcular automáticamente los precios derivados
    calculateEditPrices();

    // Si había un precio_tope específico asignado en la BD, respetarlo si no fue calculado de cero
    if (row.precio_tope > 0) {
        document.getElementById('edit-precio_tope').value = row.precio_tope;
    }
    
    // Inventario
    document.getElementById('edit-inventario_maximo').value = row.inventario_maximo || 0;
    document.getElementById('edit-inventario_minimo').value = row.inventario_minimo || 0;
    document.getElementById('edit-punto_reorden').value = row.punto_reorden || 0;
    document.getElementById('edit-existencia_teorica').value = row.existencia_teorica || 0;
    document.getElementById('edit-existencia_fisica').value = row.existencia_fisica || 0;
    document.getElementById('edit-ubicacion').value = row.ubicacion || '';
    document.getElementById('edit-peso').value = row.peso || 0;
    document.getElementById('edit-std_pack').value = row.std_pack || 1;
    document.getElementById('edit-costo_ult_compra').value = row.costo_ult_compra || 0;
    document.getElementById('edit-fecha_ult_compra').value = row.fecha_ult_compra || '';
    document.getElementById('edit-costo_compra_ant').value = row.costo_compra_ant || 0;
    
    // Extra
    document.getElementById('edit-idsat').value = row.idsat || '';
    document.getElementById('edit-id_impuesto_sat').value = row.id_impuesto_sat || '002';
    document.getElementById('edit-iva').value = row.iva || 16;
    document.getElementById('edit-id_tipo_factor').value = row.id_tipo_factor || 'Tasa';
    document.getElementById('edit-sustituto').value = row.sustituto || '';
    document.getElementById('edit-sustituto1').value = row.sustituto1 || '';
    document.getElementById('edit-sustituto2').value = row.sustituto2 || '';
    document.getElementById('edit-en_promocion').checked = !!row.en_promocion;
    document.getElementById('edit-critico').checked = !!row.critico;
    document.getElementById('edit-control_pedimentos').checked = !!row.control_pedimentos;

    // Reset tabs to first one
    document.querySelector('.modal-tab').click();
    
    document.getElementById('edit-modal').style.display = 'flex';
}

function closeEditModal() {
    document.getElementById('edit-modal').style.display = 'none';
}

// Form Submission handling
document.addEventListener('DOMContentLoaded', function() {
    const editPrecioLista = document.getElementById('edit-precio_lista');
    const editDescP4 = document.getElementById('edit-desc_precio4');
    const editDescEsp = document.getElementById('edit-desc_precio_espec');
    const editPorcPV = document.getElementById('edit-porcentaje_pv');
    const editDescProv = document.getElementById('edit-desc_proveedor');
    const editPorcDesc = document.getElementById('edit-porcetaje_descuento');
    const editPrecioTope = document.getElementById('edit-precio_tope');

    [editPrecioLista, editDescP4, editDescEsp, editPorcPV, editDescProv, editPorcDesc].forEach(el => {
        if (el) el.addEventListener('input', calculateEditPrices);
    });

    if (editPrecioTope) {
        editPrecioTope.addEventListener('input', function() {
            const lista = parseFloat(editPrecioLista ? editPrecioLista.value : 0) || 0;
            const pTope = parseFloat(this.value) || 0;
            if (lista > 0 && editPorcDesc) {
                const pDesc = (100 - (pTope / lista * 100)).toFixed(2);
                editPorcDesc.value = pDesc;
            }
        });
    }

    document.getElementById('edit-article-form').addEventListener('submit', function(e) {
        e.preventDefault();
        let formData = new FormData(this);

        // Convert checkboxes to values
        const checks = ['articulo_kit', 'articulo_serie', 'habilitado', 'protocolo', 'en_promocion', 'critico', 'control_pedimentos'];
        checks.forEach(c => {
            const el = document.getElementById('edit-' + c);
            if (el) formData.set(c, el.checked ? 1 : 0);
        });

        // Enviar solo los campos que realmente cambiaron respecto al valor original
        const original = window.editOriginalData || {};
        const diffData = new FormData();
        diffData.append('id', formData.get('id'));
        for (const [key, value] of formData.entries()) {
            if (key === 'id') continue;
            const originalValue = original[key] ?? '';
            if (String(value) !== String(originalValue)) {
                diffData.append(key, value);
            }
        }
        formData = diffData;

        closeEditModal();

        Swal.fire({
            title: 'Guardando cambios...',
            text: 'Se actualizará el Maestro y se replicará a todas las sucursales.',
            allowOutsideClick: false,
            didOpen: () => { Swal.showLoading(); }
        });

        const articleId = document.getElementById('edit-article-id').value;
        fetch('/db-master/item/' + articleId, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'Accept': 'application/json'
            },
            body: formData
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                Swal.fire('Éxito', data.message, 'success').then(() => location.reload());
            } else {
                Swal.fire('Error', data.message || 'Error desconocido', 'error');
            }
        })
        .catch(err => {
            console.error(err);
            Swal.fire('Error', 'Fallo en la comunicación con el servidor', 'error');
        });
    });
});
</script>

{{--EDIT MODAL HTML --}}
<div id="edit-modal" style="display:none; position:fixed; inset:0; z-index:9999; background:rgba(2,6,23,0.85); backdrop-filter:blur(8px); align-items:center; justify-content:center; padding: 20px;">
    <div class="glass-card shadow-premium" style="width:100%; max-width:1100px; padding:0; overflow:hidden; border:1px solid rgba(139,92,246,0.3); border-radius: 20px;">
        <div style="padding:20px 30px; background:var(--grad-surface); border-bottom:1px solid rgba(139,92,246,0.2); display:flex; justify-content:space-between; align-items:center;">
            <div style="display:flex; align-items:center; gap:16px;">
                <div style="width:40px; height:40px; border-radius:12px; background:rgba(139,92,246,0.15); border:1px solid rgba(139,92,246,0.3); color:var(--violet); display:flex; align-items:center; justify-content:center;">
                    <svg viewBox="0 0 24 24" fill="none" width="22" height="22" stroke="currentColor" stroke-width="2.5"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
                </div>
                <div>
                    <h3 style="margin:0; font-size:18px; font-weight:800; color:white; display:flex; align-items:center; gap:10px;">
                        Editar Artículo Maestro 
                        <span id="modal-clave-badge" style="background:rgba(139,92,246,0.2); border:1px solid rgba(139,92,246,0.4); padding:3px 10px; border-radius:6px; font-size:13px; color:var(--violet-light); font-weight:700;"></span>
                    </h3>
                    <p style="margin:2px 0 0 0; font-size:12px; color:var(--text-secondary);">Modificar catálogo maestro de productos y replicar cambios</p>
                </div>
            </div>
            <button onclick="closeEditModal()" style="background:rgba(255,255,255,0.05); border:1px solid var(--border); border-radius:8px; width:36px; height:36px; color:var(--text-muted); cursor:pointer; display:flex; align-items:center; justify-content:center;"><svg viewBox="0 0 24 24" fill="none" width="20" height="20" stroke="currentColor" stroke-width="2.5"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg></button>
        </div>

        <!-- TABS HEADER -->
        <div style="display:flex; background:rgba(0,0,0,0.25); border-bottom:1px solid var(--border); padding:0 30px; gap:8px;">
            <button class="modal-tab active" onclick="switchModalTab(event, 'tab-general')" style="padding:14px 22px; background:none; border:none; color:white; border-bottom:2px solid var(--violet); cursor:pointer; font-size:13px; font-weight:700; display:flex; align-items:center; gap:8px;">
                <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
                Identificación Base
            </button>
            <button class="modal-tab" onclick="switchModalTab(event, 'tab-precios')" style="padding:14px 22px; background:none; border:none; color:var(--text-muted); border-bottom:2px solid transparent; cursor:pointer; font-size:13px; font-weight:700; display:flex; align-items:center; gap:8px;">
                <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="12" y1="1" x2="12" y2="23"/><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg>
                Precios y Costos
            </button>
            <button class="modal-tab" onclick="switchModalTab(event, 'tab-inventario')" style="padding:14px 22px; background:none; border:none; color:var(--text-muted); border-bottom:2px solid transparent; cursor:pointer; font-size:13px; font-weight:700; display:flex; align-items:center; gap:8px;">
                <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"/></svg>
                Logística e Inventario
            </button>
            <button class="modal-tab" onclick="switchModalTab(event, 'tab-extra')" style="padding:14px 22px; background:none; border:none; color:var(--text-muted); border-bottom:2px solid transparent; cursor:pointer; font-size:13px; font-weight:700; display:flex; align-items:center; gap:8px;">
                <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2.5"><rect x="3" y="4" width="18" height="16" rx="2"/><path d="M16 2v4"/><path d="M8 2v4"/><path d="M3 10h18"/></svg>
                Impuestos y Sustitutos
            </button>
        </div>
        
        <form id="edit-article-form" style="padding: 30px;">
            <input type="hidden" name="id" id="edit-article-id">
            
            <div id="modal-tab-content" style="max-height: 540px; overflow-y: auto; padding-right: 8px;">
                <!-- TAB GENERAL -->
                <div id="tab-general" class="modal-tab-panel">
                    <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 20px;">
                        <div class="form-group">
                            <label class="modal-label">Clave del Artículo (No editable)</label>
                            <input type="text" id="edit-clave" readonly class="modal-input readonly" style="background: rgba(255,255,255,0.05); cursor: not-allowed; font-weight: bold;">
                        </div>
                        <div class="form-group" style="grid-column: span 2;">
                            <label class="modal-label">Descripción <span style="color:var(--rose)">*</span></label>
                            <input type="text" name="descripcion" id="edit-descripcion" required maxlength="200" class="modal-input">
                        </div>
                        <div class="form-group">
                            <label class="modal-label">Línea</label>
                            <input type="text" name="linea" id="edit-linea" required maxlength="4" class="modal-input">
                        </div>
                        <div class="form-group">
                            <label class="modal-label">Clasificación</label>
                            <input type="text" name="clasificacion" id="edit-clasificacion" required maxlength="6" class="modal-input">
                        </div>
                        <div class="form-group">
                            <label class="modal-label">Área</label>
                            <input type="number" name="area" id="edit-area" required class="modal-input">
                        </div>
                        <div class="form-group">
                            <label class="modal-label">Unidad de Medida</label>
                            <input type="text" name="unidad_medida" id="edit-unidad_medida" required maxlength="4" class="modal-input">
                        </div>
                        <div class="form-group">
                            <label class="modal-label">ID Color (Base)</label>
                            <select name="color" id="edit-color" class="modal-input">
                                @for($i = 0; $i <= 9; $i++)
                                    <option value="{{ $i }}">{{ $i }}</option>
                                @endfor
                            </select>
                        </div>
                        <div class="form-group" style="display: flex; align-items: center; padding-top: 24px;">
                            <label style="display:flex; align-items:center; gap:10px; cursor:pointer; background: rgba(255,255,255,0.05); padding: 10px 16px; border-radius: 8px; border: 1px solid var(--border); width: 100%;">
                                <input type="checkbox" name="protocolo" id="edit-protocolo" value="1" style="width:16px; height:16px; accent-color: var(--violet);">
                                <span style="font-size: 13px; font-weight: 700; color: white;">Protocolo Especial</span>
                            </label>
                        </div>

                        @if(isset($branches) && count($branches) > 0)
                        <div style="grid-column: span 3; margin-top: 10px; padding: 20px; background: rgba(139,92,246,0.05); border-radius: 12px; border: 1px solid rgba(139,92,246,0.2);">
                            <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 16px;">
                                <div style="display: flex; align-items: center; gap: 8px;">
                                    <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="var(--violet)" stroke-width="2.5"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/></svg>
                                    <span style="font-size: 13px; font-weight: 800; color: white; letter-spacing: 0.05em; text-transform: uppercase;">Color Específico por Sucursal</span>
                                </div>
                                <span style="font-size: 11px; color: var(--text-muted);">Seleccionar para anular el Color Base</span>
                            </div>
                            <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(200px, 1fr)); gap: 14px;">
                                @foreach($branches as $branch)
                                @php
                                    $bCode = is_array($branch) ? ($branch['code'] ?? '') : ($branch->code ?? '');
                                    $bName = is_array($branch) ? ($branch['name'] ?? '') : ($branch->name ?? '');
                                @endphp
                                @if($bCode)
                                <div style="background: rgba(0,0,0,0.25); padding: 12px; border-radius: 8px; border: 1px solid var(--border);">
                                    <label class="modal-label" style="margin-bottom: 6px; color: var(--violet-light);">{{ $bName }}</label>
                                    <select name="color_branch[{{ $bCode }}]" id="edit-color_branch-{{ $bCode }}" class="modal-input" style="padding: 8px 12px; font-size: 13px;">
                                        <option value="">Usar Color Base</option>
                                        @for($i = 0; $i <= 9; $i++)
                                            <option value="{{ $i }}">{{ $i }}</option>
                                        @endfor
                                    </select>
                                </div>
                                @endif
                                @endforeach
                            </div>
                        </div>
                        @endif
                    </div>

                    <div style="display: flex; flex-wrap: wrap; gap: 20px; margin-top: 28px; padding: 20px; background: rgba(0,0,0,0.2); border-radius: 12px; border: 1px solid var(--border);">
                        <label style="display:flex; align-items:center; gap:12px; cursor:pointer;">
                            <input type="checkbox" name="habilitado" id="edit-habilitado" value="1" style="width:18px; height:18px; accent-color: var(--emerald);">
                            <div style="display:flex; flex-direction:column;">
                                <span style="font-size: 13px; font-weight: 700; color: white;">Habilitado</span>
                                <span style="font-size: 11px; color: var(--text-muted);">Visible en el sistema</span>
                            </div>
                        </label>
                        <div style="width: 1px; background: var(--border); height: 30px; margin: 0 10px;"></div>
                        <label style="display:flex; align-items:center; gap:12px; cursor:pointer;">
                            <input type="checkbox" name="articulo_kit" id="edit-articulo_kit" value="1" style="width:18px; height:18px; accent-color: var(--violet);">
                            <div style="display:flex; flex-direction:column;">
                                <span style="font-size: 13px; font-weight: 700; color: white;">Es KIT</span>
                                <span style="font-size: 11px; color: var(--text-muted);">Compuesto por otros artículos</span>
                            </div>
                        </label>
                        <div style="width: 1px; background: var(--border); height: 30px; margin: 0 10px;"></div>
                        <label style="display:flex; align-items:center; gap:12px; cursor:pointer;">
                            <input type="checkbox" name="articulo_serie" id="edit-articulo_serie" value="1" style="width:18px; height:18px; accent-color: var(--amber);">
                            <div style="display:flex; flex-direction:column;">
                                <span style="font-size: 13px; font-weight: 700; color: white;">Series</span>
                                <span style="font-size: 11px; color: var(--text-muted);">Maneja números de serie</span>
                            </div>
                        </label>
                    </div>
                </div>

                <!-- TAB PRECIOS -->
                <div id="tab-precios" class="modal-tab-panel" style="display:none;">
                    <div style="display: grid; grid-template-columns: repeat(4, 1fr); gap: 20px;">
                        <!-- FILA 1: DATOS BASE -->
                        <div class="form-group">
                            <label class="modal-label">Moneda</label>
                            <select name="mn_usd" id="edit-mn_usd" class="modal-input">
                                <option value="M">MXN (Pesos)</option>
                                <option value="U">USD (Dólares)</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label class="modal-label">Precio Lista <span style="color:var(--rose)">*</span></label>
                            <input type="number" step="0.0001" id="edit-precio_lista" name="precio_lista" class="modal-input" required>
                        </div>
                        <div class="form-group">
                            <label class="modal-label">Costo Venta</label>
                            <input type="number" step="0.0001" id="edit-costo_venta" name="costo_venta" class="modal-input">
                        </div>
                        <div class="form-group">
                            <label class="modal-label">Margen Mín. (%)</label>
                            <input type="number" step="0.01" id="edit-margen_minimo" name="margen_minimo" class="modal-input">
                        </div>
                        
                        <!-- FILA 2: PRECIO 4 -->
                        <div class="form-group">
                            <label class="modal-label">Desc. Precio 4 (%)</label>
                            <input type="number" step="0.01" id="edit-desc_precio4" name="desc_precio4" class="modal-input">
                        </div>
                        <div class="form-group">
                            <label class="modal-label">Precio 4 (Resultado)</label>
                            <input type="number" step="0.01" id="edit-precio4" name="precio4" class="modal-input" readonly style="background: rgba(255,255,255,0.05); cursor: not-allowed; border-color: rgba(255,255,255,0.1);">
                        </div>
                        <div class="form-group"></div>
                        <div class="form-group"></div>

                        <!-- FILA 3: PRECIO ESPECIAL -->
                        <div class="form-group">
                            <label class="modal-label">Desc. Especial (%)</label>
                            <input type="number" step="0.01" id="edit-desc_precio_espec" name="desc_precio_espec" class="modal-input">
                        </div>
                        <div class="form-group">
                            <label class="modal-label">Precio Especial</label>
                            <input type="number" step="0.01" id="edit-precio_especial" name="precio_especial" class="modal-input" readonly style="background: rgba(255,255,255,0.05); cursor: not-allowed; border-color: rgba(255,255,255,0.1);">
                        </div>
                        <div class="form-group"></div>
                        <div class="form-group"></div>

                        <!-- FILA 4: PRECIO VENTA -->
                        <div class="form-group">
                            <label class="modal-label">Porcentaje PV (%)</label>
                            <input type="number" step="0.01" id="edit-porcentaje_pv" name="porcentaje_pv" class="modal-input" readonly style="background: rgba(255,255,255,0.05); cursor: not-allowed; border-color: rgba(255,255,255,0.1); font-weight: bold;">
                        </div>
                        <div class="form-group">
                            <label class="modal-label">Precio Venta</label>
                            <input type="number" step="0.01" id="edit-precio_venta" name="precio_venta" class="modal-input" readonly style="background: var(--grad-premium); border:none; font-weight: bold; cursor: not-allowed;">
                        </div>
                        <div class="form-group" style="grid-column: span 2;">
                            <label class="modal-label">Desc. Venta Final (%)</label>
                            <input type="number" step="0.01" id="edit-des_precio_venta" name="des_precio_venta" class="modal-input" readonly style="background: rgba(16,185,129,0.1); border-color: var(--emerald); color: var(--emerald); font-weight: bold; cursor: not-allowed;">
                        </div>

                        <!-- FILA 5: PRECIO TOPE Y DESCUENTOS (PROVEEDOR) -->
                        <div class="form-group">
                            <label class="modal-label">Desc. Proveedor (%)</label>
                            <input type="number" step="0.01" id="edit-desc_proveedor" name="desc_proveedor" class="modal-input">
                        </div>
                        <div class="form-group">
                            <label class="modal-label">Precio Proveedor (Gerente)</label>
                            <input type="number" step="0.01" id="edit-resultado_desc_proveedor" name="precio_gerente" readonly class="modal-input" style="background: rgba(255,255,255,0.05); cursor: not-allowed; border-color: rgba(255,255,255,0.1);">
                        </div>
                        <div class="form-group">
                            <label class="modal-label">Porcentaje Descuento (Pricing)</label>
                            <input type="number" step="0.01" id="edit-porcetaje_descuento" name="porcetaje_descuento" class="modal-input">
                        </div>
                        <div class="form-group">
                            <label class="modal-label">Precio Tope</label>
                            <input type="number" step="0.01" id="edit-precio_tope" name="precio_tope" class="modal-input" style="background: rgba(239,68,68,0.1); border-color: rgba(239,68,68,0.3); color: var(--rose); font-weight: bold;">
                        </div>
                    </div>
                </div>

                <!-- TAB INVENTARIO -->
                <div id="tab-inventario" class="modal-tab-panel" style="display:none;">
                    <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 20px;">
                        <div class="form-group">
                            <label class="modal-label">Ubicación (Almacén)</label>
                            <input type="text" name="ubicacion" id="edit-ubicacion" class="modal-input" placeholder="Ej: A-12-B">
                        </div>
                        <div class="form-group">
                            <label class="modal-label">Peso (kg)</label>
                            <input type="number" step="0.001" name="peso" id="edit-peso" class="modal-input">
                        </div>
                        <div class="form-group">
                            <label class="modal-label">Std Pack</label>
                            <input type="number" step="0.01" name="std_pack" id="edit-std_pack" class="modal-input">
                        </div>

                        <div class="form-group">
                            <label class="modal-label">Inventario Máximo</label>
                            <input type="number" step="0.01" name="inventario_maximo" id="edit-inventario_maximo" class="modal-input">
                        </div>
                        <div class="form-group">
                            <label class="modal-label">Inventario Mínimo</label>
                            <input type="number" step="0.01" name="inventario_minimo" id="edit-inventario_minimo" class="modal-input">
                        </div>
                        <div class="form-group">
                            <label class="modal-label">Punto de Reorden</label>
                            <input type="number" step="0.01" name="punto_reorden" id="edit-punto_reorden" class="modal-input">
                        </div>

                        <div class="form-group">
                            <label class="modal-label">Existencia Teórica</label>
                            <input type="number" step="0.01" name="existencia_teorica" id="edit-existencia_teorica" class="modal-input readonly" readonly style="background: rgba(255,255,255,0.05); cursor: not-allowed;">
                        </div>
                        <div class="form-group">
                            <label class="modal-label">Existencia Física</label>
                            <input type="number" step="0.01" name="existencia_fisica" id="edit-existencia_fisica" class="modal-input readonly" readonly style="background: rgba(255,255,255,0.05); cursor: not-allowed;">
                        </div>
                        <div class="form-group"></div>

                        <div class="form-group" style="grid-column: span 3; border-top: 1px solid var(--border); margin-top: 10px; padding-top: 15px;">
                            <label style="color:var(--violet-light); font-size:11px; font-weight:800; text-transform:uppercase; letter-spacing: 0.08em;">Historial de Últimas Compras</label>
                        </div>
                        <div class="form-group">
                            <label class="modal-label">Último Costo</label>
                            <input type="number" step="0.0001" name="costo_ult_compra" id="edit-costo_ult_compra" class="modal-input">
                        </div>
                        <div class="form-group">
                            <label class="modal-label">Fecha Últ. Compra</label>
                            <input type="date" name="fecha_ult_compra" id="edit-fecha_ult_compra" class="modal-input">
                        </div>
                        <div class="form-group">
                            <label class="modal-label">Costo Compra Ant.</label>
                            <input type="number" step="0.0001" name="costo_compra_ant" id="edit-costo_compra_ant" class="modal-input">
                        </div>
                    </div>
                </div>

                <!-- TAB EXTRA -->
                <div id="tab-extra" class="modal-tab-panel" style="display:none;">
                    <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 20px;">
                        <div class="form-group">
                            <label class="modal-label">Clave IDSAT</label>
                            <input type="text" name="idsat" id="edit-idsat" maxlength="25" class="modal-input" placeholder="Clave de producto SAT">
                        </div>
                        <div class="form-group">
                            <label class="modal-label">ID Impuesto SAT</label>
                            <input type="text" name="id_impuesto_sat" id="edit-id_impuesto_sat" maxlength="3" class="modal-input" readonly style="background: rgba(255,255,255,0.05); cursor: not-allowed; font-weight: bold; color: var(--emerald);">
                        </div>
                        <div class="form-group">
                            <label class="modal-label">IVA (%)</label>
                            <input type="number" step="0.01" name="iva" id="edit-iva" class="modal-input" readonly style="background: rgba(255,255,255,0.05); cursor: not-allowed; font-weight: bold; color: var(--emerald);">
                        </div>
                        <div class="form-group">
                            <label class="modal-label">Tipo Factor SAT</label>
                            <input type="text" name="id_tipo_factor" id="edit-id_tipo_factor" class="modal-input" readonly style="background: rgba(255,255,255,0.05); cursor: not-allowed; font-weight: bold; color: var(--emerald);">
                        </div>
                        <div class="form-group">
                            <label class="modal-label">Sustituto Principal</label>
                            <input type="text" name="sustituto" id="edit-sustituto" class="modal-input">
                        </div>
                        <div class="form-group">
                            <label class="modal-label">Sustituto Adicional 1</label>
                            <input type="text" name="sustituto1" id="edit-sustituto1" class="modal-input">
                        </div>
                        <div class="form-group">
                            <label class="modal-label">Sustituto Adicional 2</label>
                            <input type="text" name="sustituto2" id="edit-sustituto2" class="modal-input">
                        </div>
                    </div>

                    <div style="display: flex; flex-wrap: wrap; gap: 24px; margin-top: 28px; padding: 20px; background: rgba(244,63,94,0.05); border-radius: 12px; border: 1px solid rgba(244,63,94,0.15);">
                        <label style="display:flex; align-items:center; gap:10px; cursor:pointer;">
                            <input type="checkbox" name="critico" id="edit-critico" value="1" style="width:18px; height:18px; accent-color: var(--rose);">
                            <span style="font-size: 13px; font-weight: 700; color: white;">¿Artículo Crítico?</span>
                        </label>
                        <div style="width: 1px; background: var(--border); height: 24px; margin: 0 10px;"></div>
                        <label style="display:flex; align-items:center; gap:10px; cursor:pointer;">
                            <input type="checkbox" name="control_pedimentos" id="edit-control_pedimentos" value="1" style="width:18px; height:18px; accent-color: var(--rose);">
                            <span style="font-size: 13px; font-weight: 700; color: white;">Requiere Control Pedimentos</span>
                        </label>
                        <div style="width: 1px; background: var(--border); height: 24px; margin: 0 10px;"></div>
                        <label style="display:flex; align-items:center; gap:10px; cursor:pointer;">
                            <input type="checkbox" name="en_promocion" id="edit-en_promocion" value="1" style="width:18px; height:18px; accent-color: var(--amber);">
                            <span style="font-size: 13px; font-weight: 700; color: white;">En Promoción</span>
                        </label>
                    </div>
                </div>
            </div>

            <div style="margin-top: 30px; padding-top: 20px; border-top: 1px solid var(--border); display: flex; justify-content: flex-end; gap: 16px;">
                <button type="button" onclick="closeEditModal()" class="btn btn--ghost" style="padding: 12px 24px;">Cancelar</button>
                <button type="submit" class="btn btn--primary shadow-premium" style="background: var(--grad-premium); padding: 12px 36px; font-weight: 800; font-size: 14px;">
                    <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2.5" style="margin-right: 8px;"><path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"/><polyline points="17 21 17 13 7 13 7 21"/><polyline points="7 3 7 8 15 8"/></svg>
                    Guardar Cambios y Replicar
                </button>
            </div>
        </form>
    </div>
</div>

<style>
.modal-label { font-size:11px; font-weight:800; color:var(--text-secondary); display:block; margin-bottom:8px; text-transform: uppercase; letter-spacing: 0.08em; }
.modal-input { width:100%; background:var(--bg-root); border:1px solid var(--border); padding:12px 14px; border-radius:10px; color:white; font-size:13px; transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1); }
.modal-input.readonly { background:rgba(0,0,0,0.25); color:var(--text-muted); cursor:not-allowed; }
.modal-input:focus { border-color:var(--violet); outline:none; box-shadow:0 0 0 4px rgba(139,92,246,0.15); background: rgba(139,92,246,0.08); }
select.modal-input option { background-color: #1a1d27 !important; color: #ffffff !important; padding: 10px; }
</style>


{{-- ======== DB MASTER SYNC PROGRESS OVERLAY ======== --}}
<div id="dbmaster-sync-overlay" style="display:none; position:fixed; inset:0; z-index:9999; background:rgba(0,0,0,0.75); backdrop-filter:blur(6px); align-items:center; justify-content:center;">
    <div style="background:#0f172a; border:1px solid rgba(255,255,255,0.1); border-radius:16px; padding:36px 40px; width:440px; max-width:90vw; box-shadow:0 25px 60px rgba(0,0,0,0.6);">
        <div style="display:flex; align-items:center; gap:12px; margin-bottom:20px;">
            <div style="width:36px;height:36px;border-radius:50%;background:linear-gradient(135deg,#10b981,#059669);display:flex;align-items:center;justify-content:center;">
                <svg viewBox="0 0 24 24" fill="none" width="18" height="18" stroke="white" stroke-width="2.5"><path d="M21 2v6h-6"/><path d="M3 12a9 9 0 0 1 15-6.7L21 8"/><path d="M3 22v-6h6"/><path d="M21 12a9 9 0 0 1-15 6.7L3 16"/></svg>
            </div>
            <div>
                <h3 style="margin:0;font-size:15px;font-weight:800;color:#f8fafc;">Sincronizando DB Master</h3>
                <p style="margin:0;font-size:11px;color:#64748b;">Puedes cerrar esta pestaña — el proceso continúa</p>
            </div>
        </div>
        <div style="background:#1e293b;border-radius:8px;overflow:hidden;margin-bottom:12px;">
            <div id="dbmaster-sync-bar" style="height:8px;background:linear-gradient(90deg,#10b981,#059669);width:5%;transition:width 0.6s ease;border-radius:8px;"></div>
        </div>
        <div style="display:flex;justify-content:space-between;margin-bottom:12px;">
            <span id="dbmaster-sync-msg" style="font-size:12px;color:#94a3b8;">Iniciando...</span>
            <span id="dbmaster-sync-pct" style="font-size:12px;font-weight:700;color:#10b981;">0%</span>
        </div>
        <div style="text-align:center;">
            <span id="dbmaster-sync-elapsed" style="font-size:11px;color:#475569;">Tiempo: 0s</span>
        </div>
    </div>
</div>

@endsection
