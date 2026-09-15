@extends('layouts.app')

@section('title', 'Subir Artículos')
@section('breadcrumb', 'Artículos / Subir')

@section('content')
<div class="page-header" style="margin-bottom: 16px;">
    <div class="page-header-content" style="display: flex; justify-content: space-between; align-items: center; width: 100%; flex-wrap: wrap; gap: 12px;">
        <div style="display: flex; align-items: center; gap: 14px;">
            <div class="page-header-icon page-header-icon--amber" style="width: 40px; height: 40px;">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width: 20px; height: 20px;">
                    <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/>
                    <polyline points="17 8 12 3 7 8"/>
                    <line x1="12" y1="3" x2="12" y2="15"/>
                </svg>
            </div>
            <div>
                <h1 class="page-title" style="font-size: 20px;">Subir Artículos via CSV</h1>
                <p class="page-subtitle" style="font-size: 12px;">Actualiza masivamente el catálogo en múltiples sucursales</p>
            </div>
        </div>
        <div style="display: flex; gap: 10px; position: relative;">
            <div class="dropdown-machotes" style="position: relative;">
                <button type="button" id="btn-machotes" class="btn btn--secondary shadow-premium" style="background: rgba(16, 185, 129, 0.1); color: var(--emerald); border: 1px solid rgba(16, 185, 129, 0.2); font-size: 11px; padding: 6px 14px; display: flex; align-items: center; gap: 8px; cursor: pointer; border-radius: var(--radius-md);">
                    <svg viewBox="0 0 24 24" fill="none" width="14" height="14" stroke="currentColor" stroke-width="2.5"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
                    Descargar Machote
                    <svg viewBox="0 0 24 24" fill="none" width="12" height="12" stroke="currentColor" stroke-width="2.5"><path d="M6 9l6 6 6-6"/></svg>
                </button>
                <div id="menu-machotes" style="display: none; position: absolute; right: 0; top: 100%; margin-top: 6px; background: #181824; border: 1px solid rgba(255,255,255,0.12); border-radius: 10px; z-index: 999; min-width: 270px; box-shadow: 0 12px 30px rgba(0,0,0,0.6); padding: 8px; backdrop-filter: blur(16px);">
                    <a href="{{ route('articulos.subir.machote', ['tipo' => 'con_datos']) }}" class="machote-item" style="padding: 10px 12px; display: flex; align-items: center; gap: 10px; color: var(--text-main); font-size: 12px; font-weight: 500; border-radius: 6px; text-decoration: none; transition: background 0.2s;">
                        <span style="font-size: 15px;">📊</span>
                        <div>
                            <div style="font-weight: 700;">Con Datos de Ejemplo</div>
                            <div style="font-size: 10px; color: var(--text-muted);">Valores numéricos limpios (el sistema calcula automáticamente)</div>
                        </div>
                    </a>
                    <a href="{{ route('articulos.subir.machote', ['tipo' => 'vacio']) }}" class="machote-item" style="padding: 10px 12px; display: flex; align-items: center; gap: 10px; color: var(--text-main); font-size: 12px; font-weight: 500; border-radius: 6px; text-decoration: none; transition: background 0.2s;">
                        <span style="font-size: 15px;">📄</span>
                        <div>
                            <div style="font-weight: 700;">Sin Datos (Solo Encabezados)</div>
                            <div style="font-size: 10px; color: var(--text-muted);">Plantilla limpia lista para llenar</div>
                        </div>
                    </a>
                    <a href="{{ route('articulos.subir.machote', ['tipo' => 'catalogo']) }}" class="machote-item" style="padding: 10px 12px; display: flex; align-items: center; gap: 10px; color: var(--text-main); font-size: 12px; font-weight: 500; border-radius: 6px; text-decoration: none; border-top: 1px solid rgba(255,255,255,0.06); margin-top: 4px; transition: background 0.2s;">
                        <span style="font-size: 15px;">📦</span>
                        <div>
                            <div style="font-weight: 700;">Catálogo Maestro Actual</div>
                            <div style="font-size: 10px; color: var(--text-muted);">Exporta artículos existentes formateados</div>
                        </div>
                    </a>
                </div>
            </div>
            <a href="{{ route('articulos.historial') }}" class="btn btn--secondary shadow-premium" style="background: rgba(245, 158, 11, 0.1); color: var(--amber); border: 1px solid rgba(245, 158, 11, 0.2); font-size: 11px; padding: 6px 14px; display: flex; align-items: center; gap: 8px; border-radius: var(--radius-md);">
                <svg viewBox="0 0 24 24" fill="none" width="14" height="14" stroke="currentColor" stroke-width="2.5"><path d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                Ver Historial de Subidas
            </a>
        </div>
    </div>
</div>

{{-- Layout Principal Grid: Sidebar de Configuración + Panel Central de Previsualización --}}
<div class="upload-workspace-grid">
    
    {{-- SIDEBAR IZQUIERDO: CONFIGURACIÓN Y CONTROLES --}}
    <div class="upload-sidebar">
        
        {{-- Card 1: Sucursales de Destino --}}
        <div class="card card--dark" style="margin-bottom: 0;">
            <div class="card-header card-header--row" style="padding: 12px 14px; background: rgba(56, 189, 248, 0.03); border-bottom: 1px solid rgba(56, 189, 248, 0.1);">
                <div style="display: flex; align-items: center; gap: 8px;">
                    <div style="width: 26px; height: 26px; background: var(--sky-bg); color: var(--sky); border-radius: 6px; display: flex; align-items: center; justify-content: center;">
                        <svg viewBox="0 0 24 24" fill="none" width="14" height="14" stroke="currentColor" stroke-width="2.5"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/></svg>
                    </div>
                    <h4 style="font-size: 13px; font-weight: 800; color: white; margin: 0;">Sucursales</h4>
                </div>
                <label style="display: flex; align-items: center; gap: 6px; font-size: 11px; font-weight: 700; color: var(--sky); cursor: pointer; background: var(--sky-bg); padding: 2px 8px; border-radius: 5px; border: 1px solid var(--sky-border);">
                    <input type="checkbox" id="select-all-branches" class="form-checkbox" style="width:13px; height:13px;">
                    Todas
                </label>
            </div>
            <div class="card-body" style="padding: 12px 14px;">
                <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 6px;">
                    @foreach($branches as $id => $name)
                        <label class="checkbox-wrapper checkbox-wrapper--sky" style="padding: 6px 8px;">
                            <input type="checkbox" name="branches[]" value="{{ $id }}" class="branch-checkbox form-checkbox" style="width:13px; height:13px;">
                            <span class="checkbox-label" style="font-size: 11px;">{{ $name }}</span>
                        </label>
                    @endforeach
                </div>
            </div>
        </div>

        {{-- Card 2: Columnas a Actualizar --}}
        <div class="card card--dark" style="margin-bottom: 0;">
            <div class="card-header card-header--row" style="padding: 12px 14px; background: rgba(167, 139, 250, 0.03); border-bottom: 1px solid rgba(167, 139, 250, 0.1); justify-content: space-between;">
                <div style="display: flex; align-items: center; gap: 8px;">
                    <div style="width: 26px; height: 26px; background: var(--violet-bg); color: var(--violet-light); border-radius: 6px; display: flex; align-items: center; justify-content: center;">
                        <svg viewBox="0 0 24 24" fill="none" width="14" height="14" stroke="currentColor" stroke-width="2.5"><path d="M12 3h7a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-7m0-18H5a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h7m0-18v18"/></svg>
                    </div>
                    <h4 style="font-size: 13px; font-weight: 800; color: white; margin: 0;">Columnas</h4>
                </div>
                <div style="display: flex; gap: 6px;">
                    <button type="button" id="btn-toggle-all-cols" class="btn-xs-ghost" title="Marcar/Desmarcar todas" style="font-size: 10px; padding: 2px 7px; background: rgba(255,255,255,0.05); border: 1px solid rgba(255,255,255,0.1); color: var(--text-muted); border-radius: 4px; cursor: pointer;">Toggle</button>
                </div>
            </div>

            <div class="card-body" style="padding: 12px 14px;">
                {{-- Leyenda informativa de fórmulas --}}
                <div style="display: flex; flex-direction: column; gap: 3px; padding: 6px 8px; background: rgba(255,255,255,0.02); border: 1px solid rgba(255,255,255,0.06); border-radius: 6px; margin-bottom: 10px; font-size: 9.5px;">
                    <span style="color: #a78bfa; font-weight: 700; display: inline-flex; align-items: center; gap: 4px;">⚡ FÓRMULA = Cálculo auto</span>
                    <span style="color: #fbbf24; font-weight: 700; display: inline-flex; align-items: center; gap: 4px;">🔄 DETONA = Dispara cálculos</span>
                </div>

                <div class="columns-scroll-container">
                    @php
                        $colsMap = [
                            'Descripción'          => ['type' => 'normal'],
                            'U.M.'                 => ['type' => 'normal'],
                            'Línea'                => ['type' => 'normal'],
                            'Clasificación'        => ['type' => 'normal'],
                            'Area'                 => ['type' => 'normal'],
                            'IVA'                  => ['type' => 'normal'],
                            'Ubicación'            => ['type' => 'normal'],
                            'Sustituto'            => ['type' => 'normal'],
                            'Sustituto 1'          => ['type' => 'normal'],
                            'Sustituto 2'          => ['type' => 'normal'],
                            'MN/USD'               => ['type' => 'normal'],
                            'P. Lista'             => ['type' => 'trigger', 'title' => 'Dispara el cálculo de P4, P.Especial, P.Gerente, P.Tope'],
                            'P. Venta'             => ['type' => 'normal'],
                            'Desc. P. Venta'       => ['type' => 'formula', 'title' => 'Fórmula: 100 - (P.Venta / P.Lista * 100)'],
                            'P. Especial'          => ['type' => 'formula', 'title' => 'Fórmula: P.Lista * (100 - Desc.Espec) / 100'],
                            'Desc. P. Espec'       => ['type' => 'trigger', 'title' => 'Dispara el cálculo de P.Especial'],
                            'Precio 4'             => ['type' => 'formula', 'title' => 'Fórmula: P.Lista * (100 - Desc.P4) / 100'],
                            'Desc. Precio 4'       => ['type' => 'trigger', 'title' => 'Dispara el cálculo de Precio 4'],
                            'Desc. Proveedor'      => ['type' => 'trigger', 'title' => 'Dispara el cálculo de Precio Gerente'],
                            'Precio Gerente'       => ['type' => 'formula', 'title' => 'Fórmula: P.Lista * (100 - Desc.Prov) / 100'],
                            '% Descuento'          => ['type' => 'trigger', 'title' => 'Dispara el cálculo de Precio Tope'],
                            'Precio Tope'          => ['type' => 'formula', 'title' => 'Fórmula: P.Lista * (100 - %Desc) / 100'],
                            'Costo Venta'          => ['type' => 'normal'],
                            'Art. Kit'             => ['type' => 'normal'],
                            'Art. Serie'           => ['type' => 'normal'],
                            'Mg Mín'               => ['type' => 'normal'],
                            'Color'                => ['type' => 'normal'],
                            'Protocolo'            => ['type' => 'normal'],
                            'IDSAT'                => ['type' => 'normal'],
                            'ID Impuesto SAT'      => ['type' => 'normal'],
                            'Peso'                 => ['type' => 'normal'],
                            'Std Pack'             => ['type' => 'normal'],
                            'Crítico'              => ['type' => 'normal'],
                            'Control Pedimentos'   => ['type' => 'normal'],
                            'Estatus'              => ['type' => 'normal']
                        ];
                    @endphp
                    @foreach($colsMap as $colName => $meta)
                        <label class="checkbox-wrapper" title="{{ $meta['title'] ?? '' }}" style="padding: 6px 8px; display: flex; align-items: center; justify-content: space-between;">
                            <div style="display: flex; align-items: center; gap: 8px;">
                                <input type="checkbox" name="columns[]" value="{{ $colName }}" class="form-checkbox" style="width:13px; height:13px;">
                                <span class="checkbox-label" style="font-size: 11px;">{{ $colName }}</span>
                            </div>
                            @if(($meta['type'] ?? '') === 'formula')
                                <span style="font-size: 8px; padding: 1px 4px; border-radius: 3px; background: rgba(139, 92, 246, 0.2); color: #a78bfa; border: 1px solid rgba(139, 92, 246, 0.35); font-weight: 700; white-space: nowrap;">⚡ FÓRMULA</span>
                            @elseif(($meta['type'] ?? '') === 'trigger')
                                <span style="font-size: 8px; padding: 1px 4px; border-radius: 3px; background: rgba(245, 158, 11, 0.2); color: #fbbf24; border: 1px solid rgba(245, 158, 11, 0.35); font-weight: 700; white-space: nowrap;">🔄 DETONA</span>
                            @endif
                        </label>
                    @endforeach
                </div>
            </div>
        </div>

        {{-- Card 3: Panel Fijo de Acciones en Sidebar --}}
        <div class="card card--dark" style="margin-bottom: 0; padding: 12px 14px; background: rgba(245,158,11,0.02); border-color: rgba(245,158,11,0.2);">
            <div style="display: flex; flex-direction: column; gap: 8px;">
                <button id="preview-btn" class="btn btn--ghost" style="width: 100%; padding: 8px 14px; justify-content: center; border-radius: var(--radius-md); font-size: 12px; font-weight: 700;">
                    <svg viewBox="0 0 24 24" fill="none" width="14" height="14" stroke="currentColor" stroke-width="2.5"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                    Vista Previa
                </button>
                <button id="process-btn" class="btn btn--primary" style="width: 100%; padding: 9px 14px; justify-content: center; border-radius: var(--radius-md); font-size: 12px; font-weight: 800; box-shadow: 0 6px 14px -4px rgba(245,158,11,0.3);">
                    <svg viewBox="0 0 24 24" fill="none" width="14" height="14" stroke="currentColor" stroke-width="2.5"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
                    Ejecutar Actualización
                </button>
            </div>
        </div>
    </div>

    {{-- PANEL CENTRAL PRINCIPAL: ARCHIVO, MAPEO Y PREVISUALIZACIÓN --}}
    <div class="upload-main-content">
        
        {{-- Zona de Carga de Archivo (COMPACTA) --}}
        <div class="card card--dark" style="margin-bottom: 12px;">
            <div class="card-body" style="padding: 10px 14px;">
                <div id="upload-container" class="glass" style="padding: 12px 16px; border: 1.5px dashed var(--border); border-radius: var(--radius-lg); transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1); background: rgba(255,255,255,0.01); position: relative; overflow: hidden;">
                    <div class="glow-effect" style="position: absolute; top: 0; left: 0; right: 0; bottom: 0; background: radial-gradient(circle at center, rgba(245,158,11,0.05) 0%, transparent 70%); opacity: 0; transition: opacity 0.4s;"></div>
                    <input type="file" id="csv_file_input" accept=".csv" style="display: none;">
                    
                    {{-- Estado Drop Zone Inicial --}}
                    <div id="drop-zone" style="cursor: pointer; position: relative; z-index: 1; display: flex; align-items: center; justify-content: space-between; gap: 12px; flex-wrap: wrap;">
                        <div style="display: flex; align-items: center; gap: 12px;">
                            <div class="upload-icon-wrapper" style="width: 36px; height: 36px; background: var(--amber-bg); border-radius: 50%; display: flex; align-items: center; justify-content: center; border: 1px solid var(--amber-border); transition: transform 0.3s; flex-shrink: 0;">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" style="width: 18px; height: 18px; color: var(--amber);">
                                    <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/>
                                    <polyline points="17 8 12 3 7 8"/>
                                    <line x1="12" y1="3" x2="12" y2="15"/>
                                </svg>
                            </div>
                            <div style="text-align: left;">
                                <h3 style="font-size: 13px; font-weight: 700; color: white; margin: 0;">Arrastra tu archivo CSV o haz clic para seleccionar</h3>
                                <p style="font-size: 11px; color: var(--text-muted); margin: 2px 0 0;">Debe incluir la columna <strong>Clave</strong>.</p>
                            </div>
                        </div>
                        <button class="btn btn--primary" style="padding: 6px 14px; font-size: 11px;">
                            <svg viewBox="0 0 24 24" fill="none" width="13" height="13" stroke="currentColor" stroke-width="2.5"><path d="M12 5v14M5 12h14"/></svg>
                            Seleccionar CSV
                        </button>
                    </div>

                    {{-- Estado Archivo Cargado (Ultra Compacto) --}}
                    <div id="file-info" style="display: none; position: relative; z-index: 1; animation: slideIn 0.3s ease-out;">
                        <div style="background: rgba(16,185,129,0.06); border: 1px solid rgba(16,185,129,0.2); padding: 6px 12px; border-radius: var(--radius-md); display: flex; align-items: center; justify-content: space-between; gap: 12px;">
                            <div style="display: flex; align-items: center; gap: 10px;">
                                <svg viewBox="0 0 24 24" fill="none" width="18" height="18" stroke="var(--emerald)" stroke-width="2.5"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><path d="m9 15 2 2 4-4"/></svg>
                                <div style="display: flex; align-items: center; gap: 8px;">
                                    <span id="filename-badge" style="font-size: 12px; font-weight: 700; color: white;">archivo.csv</span>
                                    <span id="filesize-badge" style="font-size: 10px; color: var(--emerald); font-weight: 600; background: rgba(16,185,129,0.15); padding: 2px 6px; border-radius: 4px;">0.0 KB</span>
                                </div>
                            </div>
                            <button class="btn btn--ghost" style="padding: 4px 10px; font-size: 11px; border-radius: 5px; display: flex; align-items: center; gap: 4px;" id="change-file-btn" title="Cambiar archivo">
                                <svg viewBox="0 0 24 24" fill="none" width="12" height="12" stroke="currentColor" stroke-width="2.5"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                                Cambiar
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Mapeo de columnas CSV → BD (COMPACTO) --}}
        <div id="colmap-container" class="card card--dark" style="display: none; margin-bottom: 12px; border-color: rgba(99,102,241,0.3);">
            <div class="card-header card-header--row" style="padding: 8px 14px; background: rgba(99,102,241,0.03); justify-content: space-between; align-items: center;">
                <div style="display: flex; align-items: center; gap: 8px;">
                    <div style="width: 24px; height: 24px; background: var(--violet-bg); color: var(--violet-light); border-radius: 6px; display: flex; align-items: center; justify-content: center;">
                        <svg viewBox="0 0 24 24" fill="none" width="13" height="13" stroke="currentColor" stroke-width="2.5"><path d="M8 6h13M8 12h13M8 18h13M3 6h.01M3 12h.01M3 18h.01"/></svg>
                    </div>
                    <h4 class="card-title" style="color: var(--violet-light); font-size: 12px; margin: 0; font-weight: 700;">Mapeo de Columnas Detectadas</h4>
                </div>
                <button type="button" onclick="const p = document.getElementById('colmap-pills-wrapper'); p.style.display = (p.style.display === 'none') ? 'block' : 'none';" class="btn-xs-ghost" style="font-size: 10px; padding: 2px 8px; color: var(--text-muted); border: 1px solid rgba(255,255,255,0.08); border-radius: 4px; cursor: pointer;">Minimizar / Mostrar</button>
            </div>
            <div id="colmap-pills-wrapper" class="card-body" style="padding: 10px 14px;">
                <div id="colmap-pills" style="display: flex; flex-wrap: wrap; gap: 5px; max-height: 110px; overflow-y: auto; padding-right: 4px;"></div>
                <div id="colmap-unrecognized" style="display: none; margin-top: 8px; padding: 6px 10px; background: rgba(244,63,94,0.08); border: 1px solid rgba(244,63,94,0.2); border-radius: 6px;">
                    <span style="font-size: 10px; font-weight: 700; color: var(--rose); margin-right: 6px;">NO RECONOCIDAS:</span>
                    <span id="colmap-unrecognized-list" style="font-size: 10px; color: var(--text-muted);"></span>
                </div>
            </div>
        </div>

        {{-- Previsualización de Cambios --}}
        <div id="preview-container" class="card card--dark" style="display: none; flex-direction: column; border-color: var(--amber-border); box-shadow: 0 16px 36px -10px rgba(0,0,0,0.5);">
            <div class="card-header card-header--row" style="padding: 10px 16px; background: rgba(245,158,11,0.03); justify-content: space-between; flex-wrap: wrap; gap: 10px;">
                <div style="display: flex; align-items: center; gap: 10px;">
                    <div style="width: 28px; height: 28px; background: var(--amber-bg); color: var(--amber); border-radius: 6px; display: flex; align-items: center; justify-content: center;">
                        <svg viewBox="0 0 24 24" fill="none" width="15" height="15" stroke="currentColor" stroke-width="2.5"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/><polyline points="10 9 9 9 8 9"/></svg>
                    </div>
                    <div>
                        <h3 class="card-title" style="color: var(--amber-light); font-size: 14px; margin: 0;">Previsualización de Comparativa</h3>
                        <p class="card-subtitle" style="font-size: 10.5px; margin: 0;">Verifica los cambios calculados antes de ejecutar la actualización final</p>
                    </div>
                </div>
                <div style="display: flex; gap: 8px; align-items: center;">
                    <button id="select-changed-btn" class="btn btn--primary" style="padding: 5px 10px; font-size: 10.5px; background: var(--amber); border: none; display:none; border-radius: 5px;">
                        <svg viewBox="0 0 24 24" fill="none" width="12" height="12" stroke="currentColor" stroke-width="2.5" style="margin-right:4px;"><path d="m9 11 3 3L22 4"/><path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"/></svg>
                        Seleccionar solo campos con cambios
                    </button>
                    <button onclick="document.getElementById('preview-container').style.display = 'none'; document.getElementById('preview-placeholder').style.display = 'block';" class="btn btn--ghost" style="padding: 4px 10px; font-size: 10.5px; border-radius: 5px;">Ocultar</button>
                </div>
            </div>
            <div class="card-body" style="flex: 1; overflow-x: auto; overflow-y: hidden !important; max-height: none !important; height: auto !important; padding: 0;">
                <div class="table-wrap">
                    <table class="data-table" id="preview-table">
                        <thead>
                            <tr id="preview-header-row">
                                {{-- Se llena con JS --}}
                            </tr>
                        </thead>
                        <tbody id="preview-body" style="font-size: 11.5px;">
                            {{-- Se llena con JS --}}
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        {{-- Estado inicial informativo (Placeholder antes de Previsualizar) --}}
        <div id="preview-placeholder" class="card card--dark" style="padding: 28px 20px; text-align: center; border: 1px dashed rgba(255,255,255,0.08); background: rgba(255,255,255,0.005);">
            <div style="width: 40px; height: 40px; background: rgba(255,255,255,0.03); border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 12px; color: var(--text-muted);">
                <svg viewBox="0 0 24 24" fill="none" width="18" height="18" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/></svg>
            </div>
            <h4 style="font-size: 14px; font-weight: 700; color: white; margin: 0 0 4px;">Panel de Previsualización</h4>
            <p style="font-size: 11.5px; color: var(--text-muted); max-width: 400px; margin: 0 auto; line-height: 1.4;">Selecciona tu archivo CSV, ajusta las sucursales y columnas a modificar en el sidebar izquierdo, y presiona <strong>"Vista Previa"</strong> para comparar los datos.</p>
        </div>

    </div>
</div>

<style>
    @keyframes slideIn {
        from { opacity: 0; transform: translateY(8px); }
        to { opacity: 1; transform: translateY(0); }
    }
    
    .upload-workspace-grid {
        display: grid;
        grid-template-columns: 340px minmax(0, 1fr);
        gap: 16px;
        align-items: start;
    }

    @media (max-width: 1024px) {
        .upload-workspace-grid {
            grid-template-columns: 1fr;
        }
    }

    .upload-sidebar {
        display: flex;
        flex-direction: column;
        gap: 12px;
        position: sticky;
        top: 20px;
    }

    .columns-scroll-container {
        max-height: 360px;
        overflow-y: auto;
        padding-right: 4px;
        display: flex;
        flex-direction: column;
        gap: 5px;
    }

    .columns-scroll-container::-webkit-scrollbar,
    #colmap-pills::-webkit-scrollbar {
        width: 4px;
        height: 4px;
    }
    .columns-scroll-container::-webkit-scrollbar-track,
    #colmap-pills::-webkit-scrollbar-track {
        background: rgba(255,255,255,0.02);
        border-radius: 4px;
    }
    .columns-scroll-container::-webkit-scrollbar-thumb,
    #colmap-pills::-webkit-scrollbar-thumb {
        background: rgba(255,255,255,0.15);
        border-radius: 4px;
    }

    .checkbox-wrapper {
        display: flex;
        align-items: center;
        gap: 8px;
        padding: 6px 10px;
        background: rgba(255,255,255,0.02);
        border: 1px solid var(--border);
        border-radius: var(--radius-md);
        cursor: pointer;
        transition: all 0.2s;
        user-select: none;
    }
    .checkbox-wrapper:hover {
        background: rgba(255,255,255,0.05);
        border-color: var(--violet-light);
    }
    .checkbox-wrapper--sky:hover {
        border-color: var(--sky);
    }
    
    .checkbox-label {
        font-size: 11px;
        color: var(--text-secondary);
        font-weight: 500;
        transition: color 0.2s;
    }
    .checkbox-wrapper:has(.form-checkbox:checked) {
        background: var(--violet-bg);
        border-color: var(--violet);
    }
    .checkbox-wrapper--sky:has(.form-checkbox:checked) {
        background: var(--sky-bg);
        border-color: var(--sky);
    }
    .checkbox-wrapper:has(.form-checkbox:checked) .checkbox-label {
        color: white;
        font-weight: 700;
    }

    .form-checkbox {
        width: 14px; height: 14px; 
        accent-color: var(--violet);
        border: 1.5px solid var(--border);
        border-radius: 3px;
        background: var(--bg-page);
        cursor: pointer;
    }
    .checkbox-wrapper--sky .form-checkbox {
        accent-color: var(--sky);
    }

    #upload-container:hover {
        border-color: var(--amber);
        background: rgba(245,158,11,0.02);
    }
    #upload-container:hover .glow-effect {
        opacity: 1;
    }
    #upload-container:hover .upload-icon-wrapper {
        transform: scale(1.06);
        background: var(--amber);
        color: white;
    }
    #upload-container:hover .upload-icon-wrapper svg {
        color: white;
    }
</style>
@endsection

@push('scripts')
{{-- PapaParse para facilidad de parsing --}}
<script src="https://cdnjs.cloudflare.com/ajax/libs/PapaParse/5.3.2/papaparse.min.js"></script>

<script>
document.addEventListener('DOMContentLoaded', () => {
    const dropZone = document.getElementById('drop-zone');
    const fileInput = document.getElementById('csv_file_input');
    const fileInfo = document.getElementById('file-info');
    const filenameBadge = document.getElementById('filename-badge');
    const changeFileBtn = document.getElementById('change-file-btn');
    const previewBtn = document.getElementById('preview-btn');
    const processBtn = document.getElementById('process-btn');
    const selectAllBranches = document.getElementById('select-all-branches');
    const branchCheckboxes = document.querySelectorAll('.branch-checkbox');
    const selectChangedBtn = document.getElementById('select-changed-btn');
    const btnToggleAllCols = document.getElementById('btn-toggle-all-cols');
    const previewPlaceholder = document.getElementById('preview-placeholder');

    let currentData = null;
    let selectedFile = null;
    let lastChangedCols = [];

    // Toggle All Columns
    if (btnToggleAllCols) {
        btnToggleAllCols.addEventListener('click', () => {
            const columnCheckboxes = document.querySelectorAll('input[name="columns[]"]');
            const anyUnchecked = Array.from(columnCheckboxes).some(cb => !cb.checked);
            columnCheckboxes.forEach(cb => cb.checked = anyUnchecked);
        });
    }

    // Handle File Selection
    dropZone.addEventListener('click', () => fileInput.click());

    fileInput.addEventListener('change', (e) => {
        if (e.target.files.length) {
            handleFile(e.target.files[0]);
        }
    });

    changeFileBtn.addEventListener('click', (e) => {
        e.stopPropagation();
        fileInput.click();
    });

    // Drag and Drop
    dropZone.addEventListener('dragover', (e) => {
        e.preventDefault();
        dropZone.parentElement.style.borderColor = 'var(--amber)';
    });

    dropZone.addEventListener('dragleave', () => {
        dropZone.parentElement.style.borderColor = 'var(--border)';
    });

    dropZone.addEventListener('drop', (e) => {
        e.preventDefault();
        dropZone.parentElement.style.borderColor = 'var(--border)';
        if (e.dataTransfer.files.length) {
            handleFile(e.dataTransfer.files[0]);
        }
    });

    function handleFile(file) {
        if (!file.name.endsWith('.csv')) {
            Swal.fire('Error', 'Por favor sube solo archivos .csv', 'error');
            return;
        }
        selectedFile = file;
        filenameBadge.textContent = file.name;
        
        // Formatear tamaño de archivo
        const size = file.size / 1024;
        document.getElementById('filesize-badge').textContent = size > 1024 
            ? (size / 1024).toFixed(2) + ' MB' 
            : size.toFixed(2) + ' KB';

        dropZone.style.display = 'none';
        fileInfo.style.display = 'flex';

        Papa.parse(file, {
            complete: (results) => {
                currentData = results.data;
                if (results.meta && results.meta.fields) {
                    syncCheckboxesWithCsvHeaders(results.meta.fields);
                }
            },
            header: true,
            skipEmptyLines: true
        });
    }

    // Select All Branches
    selectAllBranches.addEventListener('change', () => {
        branchCheckboxes.forEach(cb => cb.checked = selectAllBranches.checked);
    });

    // ── Lógica de restricción y autocontrol de dependencias de fórmulas ──
    const columnCheckboxes = document.querySelectorAll('input[name="columns[]"]');
    const formulaDependencies = {
        'Precio Gerente': ['P. Lista', 'Desc. Proveedor'],
        'Precio 4': ['P. Lista', 'Desc. Precio 4'],
        'P. Especial': ['P. Lista', 'Desc. P. Espec'],
        'Precio Tope': ['P. Lista', '% Descuento'],
        'Desc. P. Venta': ['P. Lista', 'P. Venta']
    };

    function normalizeStr(str) {
        return str ? str.toString().toLowerCase().normalize("NFD").replace(/[\u0300-\u036f]/g, "").replace(/[^a-z0-9]/g, "") : "";
    }

    function syncCheckboxesWithCsvHeaders(csvHeaders) {
        if (!csvHeaders || !csvHeaders.length) return;

        const normCsvHeaders = csvHeaders.map(h => normalizeStr(h));
        let matchedCount = 0;

        columnCheckboxes.forEach(cb => {
            const cbValue = cb.value;
            const normCbValue = normalizeStr(cbValue);

            let isMatch = normCsvHeaders.some(h => {
                if (h === normCbValue) return true;
                if (normCbValue === 'plista' && (h === 'preciolista' || h === 'plista')) return true;
                if (normCbValue === 'pventa' && (h === 'precioventa' || h === 'pventa')) return true;
                if (normCbValue === 'descpventa' && (h === 'desprecioventa' || h === 'descprecioventa')) return true;
                if (normCbValue === 'pespecial' && (h === 'precioespecial' || h === 'pespecial')) return true;
                if (normCbValue === 'descpespec' && (h === 'descprecioespec' || h === 'descpespec')) return true;
                if (normCbValue === 'precio4' && (h === 'p4' || h === 'precio4')) return true;
                if (normCbValue === 'descprecio4' && (h === 'descp4' || h === 'descprecio4')) return true;
                if (normCbValue === 'descproveedor' && (h === 'descproveedor' || h === 'descprov')) return true;
                if (normCbValue === 'preciogerente' && (h === 'preciogerente' || h === 'pgerente')) return true;
                if (normCbValue === 'descuento' && (h === 'porcetajedescuento' || h === 'porcentajedescuento' || h === 'descuento')) return true;
                if (normCbValue === 'preciotope' && (h === 'preciotope' || h === 'ptope')) return true;
                if (normCbValue === 'um' && (h === 'unidadmedida' || h === 'um')) return true;
                if (normCbValue === 'estatus' && (h === 'habilitado' || h === 'estatus' || h === 'estado')) return true;
                if (normCbValue === 'idsat' && (h === 'idsat' || h === 'clavesat')) return true;
                if (normCbValue === 'idimpuestosat' && (h === 'idimpuestosat' || h === 'impuestosat')) return true;
                return false;
            });

            cb.checked = isMatch;
            if (isMatch) matchedCount++;
        });

        // Activar automáticamente fórmulas si sus detonadores están seleccionados
        Object.keys(formulaDependencies).forEach(formulaName => {
            const deps = formulaDependencies[formulaName];
            const allDepsChecked = deps.every(depName => {
                const depCb = Array.from(columnCheckboxes).find(c => c.value === depName);
                return depCb && depCb.checked;
            });
            if (allDepsChecked) {
                const formulaCb = Array.from(columnCheckboxes).find(c => c.value === formulaName);
                if (formulaCb) formulaCb.checked = true;
            }
        });

        if (matchedCount > 0) {
            Swal.fire({
                icon: 'info',
                title: 'Columnas Detectadas del CSV',
                text: `Se seleccionaron automáticamente las ${matchedCount} columnas presentes en tu archivo CSV.`,
                toast: true,
                position: 'top-end',
                showConfirmButton: false,
                timer: 3500,
                background: '#1a1d27',
                color: '#fff'
            });
        }
    }

    columnCheckboxes.forEach(cb => {
        cb.addEventListener('change', (e) => {
            const name = e.target.value;
            const isChecked = e.target.checked;

            if (isChecked && formulaDependencies[name]) {
                const deps = formulaDependencies[name];
                let autoSelected = [];

                deps.forEach(depName => {
                    const depCb = Array.from(columnCheckboxes).find(c => c.value === depName);
                    if (depCb && !depCb.checked) {
                        depCb.checked = true;
                        autoSelected.push(depName);
                    }
                });

                if (autoSelected.length > 0) {
                    Swal.fire({
                        icon: 'info',
                        title: 'Campos Requeridos Activados',
                        text: `Se activó automáticamente: ${autoSelected.join(', ')} para poder calcular "${name}".`,
                        toast: true,
                        position: 'top-end',
                        showConfirmButton: false,
                        timer: 3500,
                        background: '#1a1d27',
                        color: '#fff'
                    });
                }
            } else if (!isChecked) {
                Object.keys(formulaDependencies).forEach(formulaName => {
                    const deps = formulaDependencies[formulaName];
                    if (deps.includes(name)) {
                        const formulaCb = Array.from(columnCheckboxes).find(c => c.value === formulaName);
                        if (formulaCb && formulaCb.checked) {
                            formulaCb.checked = false;
                            Swal.fire({
                                icon: 'warning',
                                title: 'Fórmula Desactivada',
                                text: `Se desmarcó "${formulaName}" porque desactivaste "${name}", necesario para su cálculo.`,
                                toast: true,
                                position: 'top-end',
                                showConfirmButton: false,
                                timer: 3500,
                                background: '#1a1d27',
                                color: '#fff'
                            });
                        }
                    }
                });
            }
        });
    });

    // Preview Logic (Backend-powered)
    previewBtn.addEventListener('click', async () => {
        const columns = Array.from(document.querySelectorAll('input[name="columns[]"]:checked')).map(cb => cb.value);
        if (!selectedFile) {
            Swal.fire('Atención', 'Sube un archivo primero.', 'warning');
            return;
        }

        Swal.fire({
            title: 'Generando comparativa...',
            text: 'Estamos comparando tu CSV con la Base Maestra',
            allowOutsideClick: false,
            didOpen: () => Swal.showLoading()
        });

        const formData = new FormData();
        formData.append('csv_file', selectedFile);
        columns.forEach(c => formData.append('columns[]', c));

        try {
            const response = await fetch('{{ route("articulos.subir.preview") }}', {
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
                body: formData
            });

            const data = await response.json();
            Swal.close();

            if (!data.success) {
                Swal.fire('Error', data.message, 'error');
                return;
            }

            lastChangedCols = data.changed_cols || [];
            selectChangedBtn.style.display = (lastChangedCols.length > 0) ? 'flex' : 'none';

            // ── Renderizar mapeo de columnas ──
            const colmapContainer = document.getElementById('colmap-container');
            const colmapPills = document.getElementById('colmap-pills');
            const colmapUnrecognized = document.getElementById('colmap-unrecognized');
            const colmapUnrecognizedList = document.getElementById('colmap-unrecognized-list');

            colmapPills.innerHTML = '';
            if (data.column_map && data.column_map.length > 0) {
                data.column_map.forEach(col => {
                    const isSelected = col.selected;
                    const pill = document.createElement('div');
                    pill.style.cssText = `
                        display: inline-flex; align-items: center; gap: 4px;
                        padding: 3px 8px; border-radius: 12px; font-size: 10px; font-weight: 600;
                        border: 1px solid ${isSelected ? 'rgba(16,185,129,0.3)' : 'rgba(255,255,255,0.08)'};
                        background: ${isSelected ? 'rgba(16,185,129,0.08)' : 'rgba(255,255,255,0.03)'};
                        color: ${isSelected ? 'var(--emerald)' : 'var(--text-muted)'};
                    `;
                    pill.innerHTML = `
                        <span style="color:var(--text-muted); font-weight:400;">${col.csv}</span>
                        <svg viewBox="0 0 24 24" fill="none" width="9" height="9" stroke="currentColor" stroke-width="2.5"><path d="M5 12h14M12 5l7 7-7 7"/></svg>
                        <span style="font-family:monospace; font-size:9.5px;">${col.field}</span>
                        ${isSelected ? '<svg viewBox="0 0 24 24" fill="none" width="9" height="9" stroke="currentColor" stroke-width="3"><polyline points="20 6 9 17 4 12"/></svg>' : ''}
                    `;
                    pill.title = isSelected ? 'Seleccionada para actualizar' : 'En CSV pero NO seleccionada (no se actualizará)';
                    colmapPills.appendChild(pill);
                });
            }

            if (data.unrecognized && data.unrecognized.length > 0) {
                colmapUnrecognizedList.textContent = data.unrecognized.join(', ');
                colmapUnrecognized.style.display = 'block';
            } else {
                colmapUnrecognized.style.display = 'none';
            }

            colmapContainer.style.display = 'block';

            const headerRow = document.getElementById('preview-header-row');
            const body = document.getElementById('preview-body');
            const previewContainer = document.getElementById('preview-container');

            // Determinar columnas a mostrar (CLAVE fija al inicio + todas las demás columnas del CSV)
            const activeCols = (data.column_map && data.column_map.length > 0)
                ? data.column_map.filter(c => c.field !== 'clave')
                : [];

            let headerHtml = '<th style="padding:10px 12px; font-size:11px; white-space:nowrap; background:#181b26; position:sticky; left:0; z-index:2; border-right:1px solid var(--border);">CLAVE</th>';
            activeCols.forEach(col => {
                const isSelected = col.selected;
                headerHtml += `<th style="padding:10px 12px; font-size:11px; white-space:nowrap; ${isSelected ? 'color:var(--text-main);' : 'color:var(--text-muted); opacity:0.6;'}">
                    ${col.csv.toUpperCase()}
                </th>`;
            });
            headerRow.innerHTML = headerHtml;
            body.innerHTML = '';

            if (!data.diffs || data.diffs.length === 0) {
                body.innerHTML = `<tr><td colspan="${activeCols.length + 1}" style="padding:20px; text-align:center; color:var(--emerald);">✓ Todos los artículos coinciden con la Base Maestra. No hay cambios necesarios.</td></tr>`;
            } else {
                data.diffs.forEach(item => {
                    const tr = document.createElement('tr');
                    tr.style.borderBottom = '1px solid var(--border-light)';

                    // Columna 1: CLAVE (Sticky fija a la izquierda)
                    let rowHtml = `<td style="padding:8px 12px; font-family:monospace; vertical-align:middle; position:sticky; left:0; background:#141721; z-index:1; border-right:1px solid var(--border);">
                        <div style="font-weight:bold; color:var(--amber); font-size:12px;">${item.clave}</div>
                        ${item.description ? `<div style="font-size:9.5px; color:var(--text-muted); white-space:nowrap; max-width:140px; overflow:hidden; text-overflow:ellipsis;">${item.description}</div>` : ''}
                    </td>`;

                    if (item.status === 'new') {
                        activeCols.forEach(col => {
                            const val = item.data ? (item.data[col.field] ?? item.data[col.csv] ?? '') : '';
                            rowHtml += `<td style="padding:8px 12px; vertical-align:middle; background:rgba(56, 189, 248, 0.05); color:var(--sky); font-size:11px; white-space:nowrap;">
                                <span style="font-size:9px; font-weight:bold; background:rgba(56,189,248,0.2); padding:1px 4px; border-radius:3px; margin-right:4px;">NUEVO</span> ${val}
                            </td>`;
                        });
                    } else {
                        // Artículo existente con cambios
                        activeCols.forEach(col => {
                            const field = col.field;
                            const diff = item.diff ? item.diff[field] : null;

                            if (diff) {
                                // ¡CAMBIO DETECTADO EN ESTA COLUMNA! Destacar celda
                                const isFormulaTag = (diff.is_formula || field === 'precio_gerente');
                                rowHtml += `<td style="padding:6px 10px; vertical-align:middle; background:rgba(245, 158, 11, 0.14); border:1px solid rgba(245, 158, 11, 0.35); white-space:nowrap;">
                                    <div style="display:flex; flex-direction:column; gap:2px;">
                                        <span style="text-decoration:line-through; color:var(--rose); opacity:0.8; font-size:10px;">${diff.old ?? 'NULL'}</span>
                                        <span style="color:var(--emerald); font-weight:800; font-size:11.5px; display:inline-flex; align-items:center; gap:4px;">
                                            ➜ ${diff.new} ${isFormulaTag ? '<span style="color:#a78bfa; font-size:9px;" title="Recálculo por fórmula">⚡</span>' : ''}
                                        </span>
                                    </div>
                                </td>`;
                            } else {
                                // Sin cambios en esta columna
                                let currentVal = '';
                                if (item.master_data && item.master_data[field] !== undefined) {
                                    currentVal = item.master_data[field];
                                } else if (item.full_new && item.full_new[field] !== undefined) {
                                    currentVal = item.full_new[field];
                                }

                                if (currentVal === null || currentVal === undefined || currentVal === '') currentVal = '-';

                                rowHtml += `<td style="padding:8px 12px; vertical-align:middle; color:var(--text-muted); font-size:11px; white-space:nowrap; opacity:0.85;">
                                    ${currentVal}
                                </td>`;
                            }
                        });
                    }

                    tr.innerHTML = rowHtml;
                    body.appendChild(tr);
                });
            }

            if (previewPlaceholder) previewPlaceholder.style.display = 'none';
            previewContainer.style.display = 'flex';
            previewContainer.scrollIntoView({ behavior: 'smooth' });

        } catch (error) {
            Swal.fire('Error', 'Fallo al conectar con el servidor para la vista previa.', 'error');
        }
    });

    // Lógica para el botón mágico de selección
    selectChangedBtn.addEventListener('click', () => {
        if (!lastChangedCols.length) return;

        const columnCheckboxes = document.querySelectorAll('input[name="columns[]"]');
        columnCheckboxes.forEach(cb => {
            cb.checked = lastChangedCols.includes(cb.value);
        });

        Swal.fire({
            icon: 'info',
            title: 'Columnas Seleccionadas',
            text: `Se han marcado automáticamente las ${lastChangedCols.length} columnas que presentan cambios en el CSV.`,
            toast: true,
            position: 'top-end',
            showConfirmButton: false,
            timer: 3000,
            background: '#1a1d27',
            color: '#fff'
        });
    });

    // Process Update
    processBtn.addEventListener('click', async () => {
        const branches = Array.from(document.querySelectorAll('input[name="branches[]"]:checked')).map(cb => cb.value);
        const columns = Array.from(document.querySelectorAll('input[name="columns[]"]:checked')).map(cb => cb.value);

        if (branches.length === 0) {
            Swal.fire('Atención', 'Selecciona al menos una sucursal.', 'warning');
            return;
        }

        if (columns.length === 0) {
            Swal.fire('Atención', 'Selecciona al menos una columna para actualizar.', 'warning');
            return;
        }

        const result = await Swal.fire({
            title: '¿Confirmar actualización masiva?',
            text: `Se actualizará el DB MASTER y las ${branches.length} sucursales seleccionadas para todos los artículos en el CSV. Esta acción no se puede deshacer.`,
            icon: 'warning',
            showCancelButton: true,
            background: '#1a1d27',
            color: '#fff',
            confirmButtonText: 'Sí, procesar',
            cancelButtonText: 'Cancelar'
        });

        if (!result.isConfirmed) return;

        const formData = new FormData();
        formData.append('csv_file', selectedFile);
        branches.forEach(b => formData.append('branches[]', b));
        columns.forEach(c => formData.append('columns[]', c));

        Swal.fire({
            title: 'Procesando...',
            text: 'Por favor no cierres la ventana.',
            allowOutsideClick: false,
            didOpen: () => Swal.showLoading()
        });

        try {
            const response = await fetch('{{ route("articulos.subir.proceso") }}', {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                body: formData
            });

            const data = await response.json();

            if (data.success) {
                Swal.fire('¡Éxito!', data.message, 'success').then(() => {
                    location.reload();
                });
            } else {
                Swal.fire('Error', data.message, 'error');
            }
        } catch (error) {
            Swal.fire('Error', 'Ocurrió un error en la conexión.', 'error');
        }
    });

    // Menú desplegable Machotes
    const btnMachotes = document.getElementById('btn-machotes');
    const menuMachotes = document.getElementById('menu-machotes');
    if (btnMachotes && menuMachotes) {
        btnMachotes.addEventListener('click', (e) => {
            e.stopPropagation();
            menuMachotes.style.display = (menuMachotes.style.display === 'block') ? 'none' : 'block';
        });

        document.addEventListener('click', (e) => {
            if (!menuMachotes.contains(e.target) && !btnMachotes.contains(e.target)) {
                menuMachotes.style.display = 'none';
            }
        });
    }
});
</script>
@endpush
