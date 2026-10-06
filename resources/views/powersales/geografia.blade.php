@extends('layouts.app')

@section('title', 'Mapeo Geográfico PowerSales')
@section('breadcrumb', 'PowerSales / Mapeo Geográfico')

@section('content')
<div style="padding-bottom: 32px;">

    {{-- HEADER --}}
    <div class="page-header shadow-premium" style="margin-bottom: 24px; padding: 20px 24px; background: var(--grad-surface); border-radius: var(--radius-xl); border: 1px solid var(--glass-border); display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 16px;">
        <div style="display: flex; gap: 20px; align-items: center;">
            <div class="page-header-icon shadow-premium" style="background: rgba(16, 185, 129, 0.1); border: 1px solid rgba(16, 185, 129, 0.3); color: #34d399;">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" width="24" height="24">
                    <circle cx="12" cy="10" r="3"/>
                    <path d="M12 21.7C17.3 17 20 13 20 10a8 8 0 1 0-16 0c0 3 2.7 7 8 11.7z"/>
                </svg>
            </div>
            <div>
                <h1 class="page-title" style="margin:0;">Mapeo Geográfico PowerSales</h1>
                <p class="page-subtitle" style="margin:4px 0 0; color: var(--text-secondary);">
                    Homologación de Estados (<code>tabgen</code>) y Ciudades (<code>ciudades</code>) de Magic con los IDs de PowerSales.
                </p>
            </div>
        </div>
        <div style="display: flex; gap: 10px; align-items: center; flex-wrap: wrap;">
            <button type="button" id="btn-refresh-catalogs" class="btn btn--ghost" style="display: flex; align-items: center; gap: 8px;">
                <svg viewBox="0 0 24 24" fill="none" width="16" height="16" stroke="currentColor" stroke-width="2.5"><path d="M21.5 2v6h-6M2.5 22v-6h6M2 11.5a10 10 0 0 1 18.8-4.3M22 12.5a10 10 0 0 1-18.8 4.2"/></svg>
                Actualizar Catálogos
            </button>
            <a href="{{ route('powersales.mapeo') }}" class="btn btn--ghost" style="display: flex; align-items: center; gap: 8px;">
                <svg viewBox="0 0 24 24" fill="none" width="16" height="16" stroke="currentColor" stroke-width="2.5"><path d="M9 3H5a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h4m0-18h10a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H9m0-18v18"/></svg>
                Mapeo de Campos
            </a>
            <a href="{{ route('powersales.auditoria') }}" class="btn btn--ghost" style="display: flex; align-items: center; gap: 8px;">
                <svg viewBox="0 0 24 24" fill="none" width="16" height="16" stroke="currentColor" stroke-width="2.5"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                Auditoría
            </a>
        </div>
    </div>

    {{-- ALERTAS DE ACCION / MENSAJES --}}
    @if(session('success'))
        <div class="glass-card shadow-premium" style="padding: 12px 18px; margin-bottom: 20px; border: 1px solid rgba(16,185,129,0.3); background: rgba(16,185,129,0.1); color: #34d399; font-size: 13px; display: flex; align-items: center; gap: 10px;">
            <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>
            <span>{{ session('success') }}</span>
        </div>
    @endif

    {{-- KPI STATS CARDS --}}
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(260px, 1fr)); gap: 16px; margin-bottom: 24px;">
        {{-- Card Estados --}}
        @php
            $pctEstados = $stats['estados_total'] > 0 ? round(($stats['estados_mapped'] / $stats['estados_total']) * 100) : 0;
            $pctCiudades = $stats['ciudades_total'] > 0 ? round(($stats['ciudades_mapped'] / $stats['ciudades_total']) * 100) : 0;
        @endphp
        <div class="glass-card shadow-premium" style="padding: 18px 20px; border: 1px solid var(--border); display: flex; flex-direction: column; justify-content: space-between;">
            <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 12px;">
                <div>
                    <span style="font-size: 11.5px; font-weight: 700; color: var(--text-secondary); text-transform: uppercase; letter-spacing: 0.05em;">Estados (tabgen)</span>
                    <div style="font-size: 26px; font-weight: 800; color: white; margin-top: 4px;">{{ $stats['estados_mapped'] }} <span style="font-size: 15px; font-weight: 500; color: var(--text-muted);">/ {{ $stats['estados_total'] }}</span></div>
                </div>
                <span style="padding: 4px 10px; border-radius: 999px; font-size: 12px; font-weight: 800; background: {{ $pctEstados >= 80 ? 'rgba(16,185,129,0.15)' : 'rgba(245,158,11,0.15)' }}; color: {{ $pctEstados >= 80 ? '#34d399' : '#fbbf24' }}; border: 1px solid {{ $pctEstados >= 80 ? 'rgba(16,185,129,0.3)' : 'rgba(245,158,11,0.3)' }};">
                    {{ $pctEstados }}%
                </span>
            </div>
            <div style="display: flex; justify-content: space-between; font-size: 12px; color: var(--text-muted); border-top: 1px solid rgba(255,255,255,0.05); padding-top: 8px;">
                <span>Pendientes: <strong style="color: #fbbf24;">{{ $stats['estados_unmapped'] }}</strong></span>
                <span>PowerSales API: <strong>{{ count($psStates) }} estados</strong></span>
            </div>
        </div>

        {{-- Card Ciudades --}}
        <div class="glass-card shadow-premium" style="padding: 18px 20px; border: 1px solid var(--border); display: flex; flex-direction: column; justify-content: space-between;">
            <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 12px;">
                <div>
                    <span style="font-size: 11.5px; font-weight: 700; color: var(--text-secondary); text-transform: uppercase; letter-spacing: 0.05em;">Ciudades (ciudades)</span>
                    <div style="font-size: 26px; font-weight: 800; color: white; margin-top: 4px;">{{ $stats['ciudades_mapped'] }} <span style="font-size: 15px; font-weight: 500; color: var(--text-muted);">/ {{ $stats['ciudades_total'] }}</span></div>
                </div>
                <span style="padding: 4px 10px; border-radius: 999px; font-size: 12px; font-weight: 800; background: {{ $pctCiudades >= 80 ? 'rgba(16,185,129,0.15)' : 'rgba(59,130,246,0.15)' }}; color: {{ $pctCiudades >= 80 ? '#34d399' : '#60a5fa' }}; border: 1px solid {{ $pctCiudades >= 80 ? 'rgba(16,185,129,0.3)' : 'rgba(59,130,246,0.3)' }};">
                    {{ $pctCiudades }}%
                </span>
            </div>
            <div style="display: flex; justify-content: space-between; font-size: 12px; color: var(--text-muted); border-top: 1px solid rgba(255,255,255,0.05); padding-top: 8px;">
                <span>Pendientes: <strong style="color: #fbbf24;">{{ $stats['ciudades_unmapped'] }}</strong></span>
                <span>PowerSales API: <strong>{{ count($psCities) }} ciudades</strong></span>
            </div>
        </div>
    </div>

    {{-- TABS SWITCHER --}}
    <div style="display: flex; gap: 10px; align-items: center; justify-content: space-between; flex-wrap: wrap; margin-bottom: 16px;">
        <div style="display: flex; gap: 8px;">
            <a href="{{ route('powersales.geografia', ['tab' => 'estados', 'status' => $statusFilter]) }}"
               class="btn {{ $tab === 'estados' ? 'btn--primary' : 'btn--ghost' }}"
               style="display: flex; align-items: center; gap: 8px; font-weight: 700; border-radius: 10px;">
                <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="18" height="18" rx="2"/><line x1="3" y1="9" x2="21" y2="9"/><line x1="9" y1="21" x2="9" y2="9"/></svg>
                Estados (Magic)
                <span style="padding: 2px 7px; border-radius: 999px; font-size: 11px; background: rgba(255,255,255,0.15);">{{ $stats['estados_total'] }}</span>
            </a>
            <a href="{{ route('powersales.geografia', ['tab' => 'ciudades', 'status' => $statusFilter]) }}"
               class="btn {{ $tab === 'ciudades' ? 'btn--primary' : 'btn--ghost' }}"
               style="display: flex; align-items: center; gap: 8px; font-weight: 700; border-radius: 10px;">
                <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><polygon points="16.24 7.76 14.12 14.12 7.76 16.24 9.88 9.88 16.24 7.76"/></svg>
                Ciudades (Magic)
                <span style="padding: 2px 7px; border-radius: 999px; font-size: 11px; background: rgba(255,255,255,0.15);">{{ $stats['ciudades_total'] }}</span>
            </a>
        </div>

        {{-- BOTON AUTO-MATCH --}}
        <div>
            @if($tab === 'estados')
                <button type="button" class="btn btn--outline" id="btn-auto-match-estados" style="display: flex; align-items: center; gap: 8px; color: #60a5fa; border-color: rgba(59,130,246,0.4);">
                    <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M12 2v4M12 18v4M4.93 4.93l2.83 2.83M16.24 16.24l2.83 2.83M2 12h4M18 12h4M4.93 19.07l2.83-2.83M16.24 7.76l2.83-2.83"/></svg>
                    Auto-Mapear Estados Faltantes
                </button>
            @else
                <button type="button" class="btn btn--outline" id="btn-auto-match-ciudades" style="display: flex; align-items: center; gap: 8px; color: #60a5fa; border-color: rgba(59,130,246,0.4);">
                    <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M12 2v4M12 18v4M4.93 4.93l2.83 2.83M16.24 16.24l2.83 2.83M2 12h4M18 12h4M4.93 19.07l2.83-2.83M16.24 7.76l2.83-2.83"/></svg>
                    Auto-Mapear Ciudades Faltantes
                </button>
            @endif
        </div>
    </div>

    {{-- FILTROS DE TABLA --}}
    <div class="glass-card shadow-premium" style="padding: 14px 20px; margin-bottom: 20px; border: 1px solid var(--border);">
        <form method="GET" action="{{ route('powersales.geografia') }}" style="display: flex; gap: 12px; align-items: center; flex-wrap: wrap;">
            <input type="hidden" name="tab" value="{{ $tab }}">

            {{-- Buscador por texto --}}
            <div style="flex: 1; min-width: 240px; position: relative;">
                <input type="text" name="q" value="{{ $q }}" placeholder="{{ $tab === 'estados' ? 'Buscar estado por clave, nombre o PowerSales...' : 'Buscar ciudad por código, nombre o PowerSales...' }}"
                       style="width: 100%; background: rgba(255,255,255,0.04); border: 1px solid var(--border); color: white; border-radius: 10px; padding: 9px 14px 9px 36px; font-size: 13px;">
                <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" style="position: absolute; left: 12px; top: 50%; transform: translateY(-50%); color: var(--text-muted); pointer-events: none;"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
            </div>

            {{-- Filtro por Estado (solo para tab ciudades) --}}
            @if($tab === 'ciudades')
            <div style="min-width: 200px;">
                <select name="estado" style="width: 100%; background: #1e293b; border: 1px solid var(--border); color: white; border-radius: 10px; padding: 9px 14px; font-size: 13px;">
                    <option value="">Todos los Estados (Magic)</option>
                    @foreach($magicEstadosList as $me)
                        <option value="{{ $me->magic_clave }}" {{ $estadoFilter === $me->magic_clave ? 'selected' : '' }}>
                            {{ $me->magic_descripcion }} ({{ $me->magic_clave }})
                        </option>
                    @endforeach
                </select>
            </div>
            @endif

            {{-- Filtro de Estatus --}}
            <div>
                <select name="status" style="background: #1e293b; border: 1px solid var(--border); color: white; border-radius: 10px; padding: 9px 14px; font-size: 13px;">
                    <option value="all" {{ $statusFilter === 'all' ? 'selected' : '' }}>Todos los estatus</option>
                    <option value="mapped" {{ $statusFilter === 'mapped' ? 'selected' : '' }}>Solo Mapeados</option>
                    <option value="unmapped" {{ $statusFilter === 'unmapped' ? 'selected' : '' }}>Solo Pendientes</option>
                </select>
            </div>

            <button type="submit" class="btn btn--primary" style="padding: 9px 16px; border-radius: 10px; font-size: 13px;">Filtrar</button>
            @if($q !== '' || $estadoFilter !== '' || $statusFilter !== 'all')
                <a href="{{ route('powersales.geografia', ['tab' => $tab]) }}" class="btn btn--ghost" style="padding: 9px 14px; border-radius: 10px; font-size: 13px;">Limpiar</a>
            @endif
        </form>
    </div>

    {{-- TAB 1: ESTADOS --}}
    @if($tab === 'estados')
    <div class="glass-card shadow-premium" style="padding: 0; overflow: hidden; border: 1px solid var(--border);">
        <div style="padding: 14px 20px; border-bottom: 1px solid var(--border); background: rgba(255,255,255,0.02); display: flex; align-items: center; justify-content: space-between;">
            <h3 style="margin: 0; font-size: 14px; color: white;">Catálogo de Estados Magic vs PowerSales</h3>
            <span style="font-size: 11.5px; color: var(--text-muted);">Mostrando {{ $estados->count() }} de {{ $estados->total() }} estados</span>
        </div>
        <div style="overflow-x: auto;">
            <table style="width: 100%; border-collapse: collapse; min-width: 720px;">
                <thead>
                    <tr style="background: rgba(255,255,255,0.02); border-bottom: 1px solid var(--border);">
                        <th style="padding: 11px 18px; text-align: left; font-size: 11px; font-weight: 800; color: var(--text-secondary); text-transform: uppercase;">Clave Magic</th>
                        <th style="padding: 11px 18px; text-align: left; font-size: 11px; font-weight: 800; color: var(--text-secondary); text-transform: uppercase;">Descripción (tabgen)</th>
                        <th style="padding: 11px 18px; text-align: left; font-size: 11px; font-weight: 800; color: var(--text-secondary); text-transform: uppercase; width: 40%;">Estado PowerSales</th>
                        <th style="padding: 11px 18px; text-align: center; font-size: 11px; font-weight: 800; color: var(--text-secondary); text-transform: uppercase;">Estatus</th>
                        <th style="padding: 11px 18px; text-align: right; font-size: 11px; font-weight: 800; color: var(--text-secondary); text-transform: uppercase;">Acción</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($estados as $e)
                    <tr id="row-estado-{{ $e->id }}" style="border-bottom: 1px solid rgba(255,255,255,0.04); transition: background 0.2s;">
                        <td style="padding: 12px 18px; font-family: monospace; font-size: 13px; font-weight: 700; color: #60a5fa;">
                            {{ $e->magic_clave }}
                        </td>
                        <td style="padding: 12px 18px; font-size: 13px; font-weight: 600; color: white;">
                            {{ $e->magic_descripcion }}
                        </td>
                        <td style="padding: 12px 18px;">
                            <select class="form-select-ps ps-state-select" data-id="{{ $e->id }}" style="width: 100%; background: #1e293b; border: 1px solid var(--border); color: white; border-radius: 8px; padding: 7px 12px; font-size: 12.5px;">
                                <option value="">-- Sin Mapear --</option>
                                @foreach($psStates as $ps)
                                    <option value="{{ $ps['Id'] }}" {{ (int)$e->ps_state_id === (int)$ps['Id'] ? 'selected' : '' }}>
                                        [ID: {{ $ps['Id'] }}] {{ $ps['Name'] }} ({{ $ps['StatesCol'] ?? '—' }})
                                    </option>
                                @endforeach
                            </select>
                        </td>
                        <td style="padding: 12px 18px; text-align: center;">
                            <span class="status-badge" id="badge-estado-{{ $e->id }}">
                                @if($e->ps_state_id)
                                    <span style="background: rgba(16,185,129,0.15); color: #34d399; border: 1px solid rgba(16,185,129,0.3); padding: 4px 10px; border-radius: 999px; font-size: 11px; font-weight: 700;">
                                        Mapeado (ID {{ $e->ps_state_id }})
                                    </span>
                                @else
                                    <span style="background: rgba(245,158,11,0.15); color: #fbbf24; border: 1px solid rgba(245,158,11,0.3); padding: 4px 10px; border-radius: 999px; font-size: 11px; font-weight: 700;">
                                        Sin Mapear
                                    </span>
                                @endif
                            </span>
                        </td>
                        <td style="padding: 12px 18px; text-align: right;">
                            <button type="button" class="btn btn--outline btn-save-estado" data-id="{{ $e->id }}" style="padding: 6px 12px; font-size: 11.5px; border-radius: 6px;">
                                Guardar
                            </button>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="5" style="padding: 40px; text-align: center; color: var(--text-muted); font-size: 13px;">
                            No se encontraron estados con los filtros seleccionados.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($estados->hasPages())
        <div style="padding: 14px 20px; border-top: 1px solid var(--border); display: flex; justify-content: center;">
            {{ $estados->links() }}
        </div>
        @endif
    </div>
    @endif

    {{-- TAB 2: CIUDADES --}}
    @if($tab === 'ciudades')
    <div class="glass-card shadow-premium" style="padding: 0; overflow: hidden; border: 1px solid var(--border);">
        <div style="padding: 14px 20px; border-bottom: 1px solid var(--border); background: rgba(255,255,255,0.02); display: flex; align-items: center; justify-content: space-between;">
            <h3 style="margin: 0; font-size: 14px; color: white;">Catálogo de Ciudades Magic vs PowerSales</h3>
            <span style="font-size: 11.5px; color: var(--text-muted);">Mostrando {{ $ciudades->count() }} de {{ $ciudades->total() }} ciudades</span>
        </div>
        <div style="overflow-x: auto;">
            <table style="width: 100%; border-collapse: collapse; min-width: 800px;">
                <thead>
                    <tr style="background: rgba(255,255,255,0.02); border-bottom: 1px solid var(--border);">
                        <th style="padding: 11px 18px; text-align: left; font-size: 11px; font-weight: 800; color: var(--text-secondary); text-transform: uppercase;">Cve Ciudad</th>
                        <th style="padding: 11px 18px; text-align: left; font-size: 11px; font-weight: 800; color: var(--text-secondary); text-transform: uppercase;">Nombre (ciudades)</th>
                        <th style="padding: 11px 18px; text-align: left; font-size: 11px; font-weight: 800; color: var(--text-secondary); text-transform: uppercase;">Estado Magic</th>
                        <th style="padding: 11px 18px; text-align: left; font-size: 11px; font-weight: 800; color: var(--text-secondary); text-transform: uppercase; width: 35%;">Ciudad PowerSales</th>
                        <th style="padding: 11px 18px; text-align: center; font-size: 11px; font-weight: 800; color: var(--text-secondary); text-transform: uppercase;">Estatus</th>
                        <th style="padding: 11px 18px; text-align: right; font-size: 11px; font-weight: 800; color: var(--text-secondary); text-transform: uppercase;">Acción</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($ciudades as $c)
                    <tr id="row-ciudad-{{ $c->id }}" style="border-bottom: 1px solid rgba(255,255,255,0.04); transition: background 0.2s;">
                        <td style="padding: 12px 18px; font-family: monospace; font-size: 13px; font-weight: 700; color: #a78bfa;">
                            {{ $c->magic_cve_ciudad }}
                        </td>
                        <td style="padding: 12px 18px; font-size: 13px; font-weight: 600; color: white;">
                            {{ $c->magic_dsc_ciudad }}
                        </td>
                        <td style="padding: 12px 18px; font-size: 12px; color: var(--text-secondary);">
                            <span style="background: rgba(255,255,255,0.06); padding: 3px 8px; border-radius: 6px; font-family: monospace; color: #60a5fa; font-weight: 700;">
                                {{ $c->magic_cve_estado }}
                            </span>
                            {{ $c->estado ? $c->estado->magic_descripcion : '' }}
                        </td>
                        <td style="padding: 12px 18px;">
                            @php
                                $mappedPsStateId = $c->estado ? $c->estado->ps_state_id : null;
                                $stateCities = $mappedPsStateId ? ($psCitiesByState[$mappedPsStateId] ?? []) : [];
                            @endphp
                            <select class="form-select-ps ps-city-select" data-id="{{ $c->id }}" style="width: 100%; background: #1e293b; border: 1px solid var(--border); color: white; border-radius: 8px; padding: 7px 12px; font-size: 12.5px;">
                                @if(!$mappedPsStateId)
                                    <option value="">-- Mapea primero el estado ({{ $c->magic_cve_estado }}) en la pestaña Estados --</option>
                                @else
                                    <option value="">-- Sin Mapear --</option>
                                @endif

                                @if($c->ps_city_id && !empty($c->ps_city_name))
                                    <option value="{{ $c->ps_city_id }}" selected>
                                        [ID: {{ $c->ps_city_id }}] {{ $c->ps_city_name }} (Actual)
                                    </option>
                                @endif

                                @foreach($stateCities as $psc)
                                    @if((int)$c->ps_city_id !== (int)$psc['Id'])
                                        <option value="{{ $psc['Id'] }}">
                                            [ID: {{ $psc['Id'] }}] {{ $psc['Name'] }} ({{ $psc['CityNumber'] ?? '' }})
                                        </option>
                                    @endif
                                @endforeach
                            </select>
                        </td>
                        <td style="padding: 12px 18px; text-align: center;">
                            <span class="status-badge" id="badge-ciudad-{{ $c->id }}">
                                @if($c->ps_city_id)
                                    <span style="background: rgba(16,185,129,0.15); color: #34d399; border: 1px solid rgba(16,185,129,0.3); padding: 4px 10px; border-radius: 999px; font-size: 11px; font-weight: 700;">
                                        Mapeado (ID {{ $c->ps_city_id }})
                                    </span>
                                @else
                                    <span style="background: rgba(245,158,11,0.15); color: #fbbf24; border: 1px solid rgba(245,158,11,0.3); padding: 4px 10px; border-radius: 999px; font-size: 11px; font-weight: 700;">
                                        Sin Mapear
                                    </span>
                                @endif
                            </span>
                        </td>
                        <td style="padding: 12px 18px; text-align: right;">
                            <button type="button" class="btn btn--outline btn-save-ciudad" data-id="{{ $c->id }}" style="padding: 6px 12px; font-size: 11.5px; border-radius: 6px;">
                                Guardar
                            </button>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" style="padding: 40px; text-align: center; color: var(--text-muted); font-size: 13px;">
                            No se encontraron ciudades con los filtros seleccionados.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($ciudades->hasPages())
        <div style="padding: 14px 20px; border-top: 1px solid var(--border); display: flex; justify-content: center;">
            {{ $ciudades->links() }}
        </div>
        @endif
    </div>
    @endif

</div>

{{-- NOTIFICACION TOAST --}}
<div id="ps-toast" style="position: fixed; bottom: 24px; right: 24px; z-index: 9999; padding: 14px 20px; border-radius: 12px; font-size: 13px; font-weight: 600; display: none; align-items: center; gap: 10px; box-shadow: 0 10px 25px rgba(0,0,0,0.5); transition: opacity 0.3s;">
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '{{ csrf_token() }}';

    function showToast(message, isError = false) {
        const toast = document.getElementById('ps-toast');
        if (!toast) return;
        toast.style.background = isError ? 'rgba(239, 68, 68, 0.95)' : 'rgba(16, 185, 129, 0.95)';
        toast.style.color = '#ffffff';
        toast.style.border = isError ? '1px solid #f87171' : '1px solid #34d399';
        toast.innerHTML = (isError ? '❌ ' : '✔ ') + message;
        toast.style.display = 'flex';
        toast.style.opacity = '1';
        setTimeout(() => {
            toast.style.opacity = '0';
            setTimeout(() => { toast.style.display = 'none'; }, 300);
        }, 3500);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Guardar Estado individual
    // ─────────────────────────────────────────────────────────────────────────
    document.querySelectorAll('.btn-save-estado').forEach(btn => {
        btn.addEventListener('click', async function () {
            const id = this.dataset.id;
            const select = document.querySelector(`.ps-state-select[data-id="${id}"]`);
            const psStateId = select ? select.value : '';

            btn.disabled = true;
            btn.textContent = 'Guardando...';

            try {
                const res = await fetch('{{ route("powersales.geografia.estado") }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': csrfToken
                    },
                    body: JSON.stringify({ id: id, ps_state_id: psStateId || null })
                });
                const data = await res.json();
                if (data.success) {
                    showToast(data.message);
                    const badge = document.getElementById(`badge-estado-${id}`);
                    if (badge) {
                        badge.innerHTML = psStateId
                            ? `<span style="background: rgba(16,185,129,0.15); color: #34d399; border: 1px solid rgba(16,185,129,0.3); padding: 4px 10px; border-radius: 999px; font-size: 11px; font-weight: 700;">Mapeado (ID ${psStateId})</span>`
                            : `<span style="background: rgba(245,158,11,0.15); color: #fbbf24; border: 1px solid rgba(245,158,11,0.3); padding: 4px 10px; border-radius: 999px; font-size: 11px; font-weight: 700;">Sin Mapear</span>`;
                    }
                } else {
                    showToast(data.message || 'Error al guardar.', true);
                }
            } catch (err) {
                showToast('Error de conexión al servidor.', true);
            } finally {
                btn.disabled = false;
                btn.textContent = 'Guardar';
            }
        });
    });

    // ─────────────────────────────────────────────────────────────────────────
    // Guardar Ciudad individual
    // ─────────────────────────────────────────────────────────────────────────
    document.querySelectorAll('.btn-save-ciudad').forEach(btn => {
        btn.addEventListener('click', async function () {
            const id = this.dataset.id;
            const select = document.querySelector(`.ps-city-select[data-id="${id}"]`);
            const psCityId = select ? select.value : '';

            btn.disabled = true;
            btn.textContent = 'Guardando...';

            try {
                const res = await fetch('{{ route("powersales.geografia.ciudad") }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': csrfToken
                    },
                    body: JSON.stringify({ id: id, ps_city_id: psCityId || null })
                });
                const data = await res.json();
                if (data.success) {
                    showToast(data.message);
                    const badge = document.getElementById(`badge-ciudad-${id}`);
                    if (badge) {
                        badge.innerHTML = psCityId
                            ? `<span style="background: rgba(16,185,129,0.15); color: #34d399; border: 1px solid rgba(16,185,129,0.3); padding: 4px 10px; border-radius: 999px; font-size: 11px; font-weight: 700;">Mapeado (ID ${psCityId})</span>`
                            : `<span style="background: rgba(245,158,11,0.15); color: #fbbf24; border: 1px solid rgba(245,158,11,0.3); padding: 4px 10px; border-radius: 999px; font-size: 11px; font-weight: 700;">Sin Mapear</span>`;
                    }
                } else {
                    showToast(data.message || 'Error al guardar.', true);
                }
            } catch (err) {
                showToast('Error de conexión al servidor.', true);
            } finally {
                btn.disabled = false;
                btn.textContent = 'Guardar';
            }
        });
    });

    // ─────────────────────────────────────────────────────────────────────────
    // Auto-Match Estados
    // ─────────────────────────────────────────────────────────────────────────
    const btnAutoEstados = document.getElementById('btn-auto-match-estados');
    if (btnAutoEstados) {
        btnAutoEstados.addEventListener('click', async function () {
            if (!confirm('¿Deseas ejecutar el auto-mapeo heurístico de estados por similitud de nombres y claves?')) {
                return;
            }
            btnAutoEstados.disabled = true;
            btnAutoEstados.textContent = 'Mapeando...';
            try {
                const res = await fetch('{{ route("powersales.geografia.auto_match") }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': csrfToken
                    },
                    body: JSON.stringify({ tipo: 'estados' })
                });
                const data = await res.json();
                showToast(data.message);
                setTimeout(() => window.location.reload(), 1200);
            } catch (err) {
                showToast('Error ejecutando auto-mapeo.', true);
                btnAutoEstados.disabled = false;
                btnAutoEstados.textContent = 'Auto-Mapear Estados Faltantes';
            }
        });
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Auto-Match Ciudades
    // ─────────────────────────────────────────────────────────────────────────
    const btnAutoCiudades = document.getElementById('btn-auto-match-ciudades');
    if (btnAutoCiudades) {
        btnAutoCiudades.addEventListener('click', async function () {
            const estadoActual = '{{ $estadoFilter }}';
            const promptMsg = estadoActual 
                ? `¿Deseas auto-mapear las ciudades del estado ${estadoActual}?` 
                : '¿Deseas auto-mapear todas las ciudades faltantes coincidentes con los estados mapeados?';
            if (!confirm(promptMsg)) return;

            btnAutoCiudades.disabled = true;
            btnAutoCiudades.textContent = 'Mapeando...';
            try {
                const res = await fetch('{{ route("powersales.geografia.auto_match") }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': csrfToken
                    },
                    body: JSON.stringify({ tipo: 'ciudades', magic_cve_estado: estadoActual || null })
                });
                const data = await res.json();
                showToast(data.message);
                setTimeout(() => window.location.reload(), 1200);
            } catch (err) {
                showToast('Error ejecutando auto-mapeo.', true);
                btnAutoCiudades.disabled = false;
                btnAutoCiudades.textContent = 'Auto-Mapear Ciudades Faltantes';
            }
        });
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Actualizar Catálogos (PowerSales API + Magic)
    // ─────────────────────────────────────────────────────────────────────────
    const btnRefresh = document.getElementById('btn-refresh-catalogs');
    if (btnRefresh) {
        btnRefresh.addEventListener('click', async function () {
            btnRefresh.disabled = true;
            btnRefresh.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Actualizando...';
            try {
                const res = await fetch('{{ route("powersales.geografia.refresh") }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': csrfToken
                    }
                });
                const data = await res.json();
                showToast(data.message);
                setTimeout(() => window.location.reload(), 1200);
            } catch (err) {
                showToast('Error actualizando catálogos.', true);
                btnRefresh.disabled = false;
                btnRefresh.textContent = 'Actualizar Catálogos';
            }
        });
    }
});
</script>
@endsection
