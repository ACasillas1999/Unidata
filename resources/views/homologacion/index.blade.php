@extends('layouts.app')

@section('title', 'Homologación')
@section('breadcrumb', 'Homologación')

@section('content')

<style>
/* Homologacion: desactivar scroll de page-content para que la tabla maneje su propio scroll */
.page-content {
    overflow: hidden !important;
    padding-bottom: 0 !important;
    display: flex;
    flex-direction: column;
}
#homo-table-card {
    flex: 1;
    min-height: 0;
    display: flex;
    flex-direction: column;
    overflow: hidden;
}
#table-inner-wrap {
    overflow-y: auto !important;
    overflow-x: auto !important;
    /* Altura: viewport menos barra lateral de cabecera del sistema (~52px) + padding + header + filtros + encabezado del card */
    height: calc(100vh - 270px);
    min-height: 200px;
}
</style>

{{-- Wrapper for all sections ABOVE the table. flex-shrink:0 prevents competing with the table card. --}}
<div style="flex-shrink: 0; overflow-x: auto;">

{{-- ── PREMIUM HEADER ────────────────────────────────────────── --}}
<div class="page-header shadow-premium" style="margin-bottom: 12px; padding: 14px 20px; background: var(--grad-surface); border-radius: var(--radius-xl); border: 1px solid var(--glass-border); position: relative; overflow: hidden; display: flex; justify-content: space-between; align-items: center;">
    {{-- Efecto decorativo de fondo --}}
    <div style="position:absolute; top:-50px; right:-50px; width:150px; height:150px; background:var(--violet); filter:blur(100px); opacity:0.1; pointer-events:none;"></div>
    
    <div class="page-header-content" style="display: flex; gap: 16px; align-items: center; z-index: 1;">
        <div class="page-header-icon shadow-premium" style="background: var(--grad-premium); border: none; color: white;">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                <polyline points="22 12 18 12 15 21 9 3 6 12 2 12"/>
            </svg>
        </div>
        <div>
            <h1 class="page-title" style="letter-spacing: -0.01em; margin:0;">Homologación Inteligente</h1>
            <p class="page-subtitle" style="color: var(--text-secondary); margin:4px 0 0;">Universo Maestro y Control Transversal</p>
        </div>
    </div>
    <div class="page-header-actions" style="display:flex;gap:12px;align-items:center; z-index: 1;">
        <div class="stat-chip" style="background:var(--violet-bg); color:var(--violet-light); border-color:rgba(139, 92, 246, 0.2);">
            <span class="stat-chip-dot" style="background:var(--violet);"></span>
            Centralizado
        </div>
        <form method="POST" action="{{ route('homologacion.sync') }}" id="sync-form" style="margin:0;">
            @csrf
            <button type="button" id="sync-btn" class="btn btn--primary shadow-premium" style="background:var(--grad-premium); border:none; color:white; padding: 10px 20px;" onclick="startSync()">
                <svg id="sync-icon" style="margin-right:6px;" viewBox="0 0 24 24" fill="none" width="16" height="16" stroke="currentColor" stroke-width="2.5"><path d="M21 2v6h-6"/><path d="M3 12a9 9 0 0 1 15-6.7L21 8"/><path d="M3 22v-6h6"/><path d="M21 12a9 9 0 0 1-15 6.7L3 16"/></svg>
                Sincronizar
            </button>
        </form>
    </div>
</div>

{{-- ── ADVANCED FILTERS (collapsible panel) ── --}}
@php
    $hasActiveFilters = $cobertura || count($tienEn) || count($faltaEn);
    $presets = [
        ''                => ['label'=>'Sin preset',        'icon'=>'⬜', 'color'=>'#64748b'],
        'todas'           => ['label'=>'En todas',          'icon'=>'✨', 'color'=>'#10b981'],
        'todas_menos_una' => ['label'=>'Casi todas (-1)',   'icon'=>'⚡', 'color'=>'#f59e0b'],
        'incompleta'      => ['label'=>'Cobertura parcial', 'icon'=>'🔶', 'color'=>'#ec4899'],
        'solo_una'        => ['label'=>'Solo en una',       'icon'=>'📍', 'color'=>'#ef4444'],
        'ninguna'         => ['label'=>'En ninguna',        'icon'=>'🌑', 'color'=>'#94a3b8'],
    ];
@endphp

<form method="GET" action="{{ route('homologacion.index') }}" id="cobertura-form" style="margin-bottom: 10px; flex-shrink: 0;">
    @if($filterCol)<input type="hidden" name="filtro" value="{{ $filterCol }}"> @endif
    @if($filterVal)<input type="hidden" name="estado" value="{{ $filterVal }}"> @endif
    <input type="hidden" name="per_page" id="per_page_input" value="{{ request('per_page', 50) }}">

    <div class="glass-card shadow-premium" id="panel-combinado" style="overflow: visible;">
        
        {{-- Visible Header with Search Bar --}}
        <div style="padding: 10px 16px; background: rgba(255,255,255,0.02); display: flex; align-items: center; flex-wrap: wrap; gap: 12px;" id="panel-combinado-header">
            
            {{-- SEARCH BAR --}}
            <div class="search-input-wrap" style="flex:1; min-width:250px; margin: 0; display:flex; align-items:center; background:var(--bg-root); border-radius:8px; border:1px solid var(--border); overflow:hidden;">
                <span class="search-icon" style="padding: 0 12px; color:var(--text-muted); display:flex; align-items:center;">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="16" height="16"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.35-4.35"/></svg>
                </span>
                <input type="text" name="q" value="{{ $search }}" placeholder="Buscar por código maestro o descripción universal..." class="search-input" autocomplete="off" style="width: 100%; border:none; background:transparent; padding:9px 12px 9px 0; color:white; outline:none; font-size:12px; font-weight:600;">
            </div>

            <div style="display:flex; align-items:center; gap:8px;">
                <button type="submit" class="btn btn--primary btn--sm shadow-premium" style="background:var(--grad-premium); border-color:transparent; color:white; padding:7px 16px; font-size:12px;">Buscar</button>
                <a href="{{ route('homologacion.index') }}" class="btn btn--ghost btn--sm" style="font-size:12px;">Limpiar</a>
                <button type="button" onclick="startExportExcelBg()" class="btn btn--primary btn--sm shadow-premium" style="background:var(--emerald); border-color:var(--emerald); color:white; font-size:12px; display:flex; align-items:center;">
                    <svg style="margin-right:4px;" viewBox="0 0 24 24" fill="none" width="14" height="14" stroke="currentColor" stroke-width="2.5"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
                    Excel
                </button>
                <button type="button" class="btn btn--ghost btn--sm shadow-premium" onclick="togglePanelCombinado()" style="border: 1px solid var(--border); display:flex; align-items:center; gap:6px; background:rgba(255,255,255,0.05); padding:7px 12px; font-size:12px;">
                    <svg viewBox="0 0 24 24" fill="none" width="12" height="12" stroke="currentColor" stroke-width="2"><polygon points="22 3 2 3 10 12.46 10 19 14 21 14 12.46 22 3"/></svg>
                    Filtros
                    @if($hasActiveFilters)
                        <span style="font-size:9px; font-weight:800; padding: 2px 4px; background: var(--violet); color:white; border-radius:4px; margin-left:2px; line-height:1;">1</span>
                    @endif
                    <svg id="panel-chevron" viewBox="0 0 24 24" fill="none" width="13" height="13" stroke="currentColor" stroke-width="3" style="transition: transform 0.3s; transform: {{ $hasActiveFilters ? 'rotate(0deg)' : 'rotate(-90deg)' }}; margin-left: 2px;"><polyline points="6 9 12 15 18 9"/></svg>
                </button>
            </div>
        </div>

        {{-- Collapsible body --}}
        <div id="panel-combinado-body" style="overflow: hidden; transition: max-height 0.4s ease; max-height: {{ $hasActiveFilters ? '800px' : '0px' }}; border-top: 1px solid rgba(255,255,255,0.05);">
            <div style="padding: 14px 16px 16px;">
                <div style="display: flex; flex-direction: column; gap: 14px;">

                {{-- Preset buttons --}}
                <div>
                    <p style="font-size: 9px; font-weight: 800; color: var(--text-muted); text-transform: uppercase; letter-spacing: 0.12em; margin-bottom: 8px;">Ajustes rápidos de cobertura</p>
                    <div style="display: flex; flex-wrap: wrap; gap: 8px;" id="preset-btns">
                        @foreach($presets as $val => $meta)
                            <button type="button" class="preset-btn glass" data-val="{{ $val }}"
                                style="padding: 7px 14px; font-size: 11px; font-weight: 700; border-radius: 10px; cursor: pointer; border: 1px solid {{ $cobertura===$val ? $meta['color'] : 'var(--border)' }}; background: {{ $cobertura===$val ? $meta['color'].'15' : 'transparent' }}; color: {{ $cobertura===$val ? $meta['color'] : 'var(--text-secondary)' }}; transition: all 0.2s; display: flex; align-items: center; gap: 6px;">
                                <span style="font-size: 12px;">{{ $meta['icon'] }}</span>{{ $meta['label'] }}
                            </button>
                        @endforeach
                    </div>
                    <input type="hidden" name="cobertura" id="cobertura-hidden" value="{{ $cobertura }}">
                </div>

                {{-- Manual inclusion/exclusion --}}
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px;">
                    {{-- Inclusion --}}
                    <div class="glass" style="padding: 12px 16px; border-radius: var(--radius-lg); border-top: 2px solid var(--emerald);">
                        <p style="font-size: 10px; font-weight: 800; color: var(--emerald); text-transform: uppercase; letter-spacing: 0.1em; margin-bottom: 10px; display: flex; align-items: center; gap: 6px;">
                            <svg viewBox="0 0 24 24" fill="none" width="12" height="12" stroke="currentColor" stroke-width="3"><polyline points="20 6 9 17 4 12"/></svg>
                            Activo en...
                        </p>
                        <div style="display: flex; flex-wrap: wrap; gap: 6px;">
                            @foreach($branches as $name => $info)
                                @php $checked = in_array($info['col'], $tienEn); @endphp
                                <label class="coverage-checkbox" data-type="yes"
                                       style="display: flex; align-items: center; gap: 6px; padding: 5px 10px; border-radius: 8px; cursor: pointer; font-size: 10px; font-weight: 700; border: 1px solid {{ $checked ? 'rgba(16,185,129,0.3)' : 'var(--border)' }}; background: {{ $checked ? 'rgba(16,185,129,0.1)' : 'transparent' }}; color: {{ $checked ? 'var(--emerald)' : 'var(--text-muted)' }}; transition: all 0.2s;">
                                    <input type="checkbox" name="tiene_en[]" value="{{ $info['col'] }}" {{ $checked ? 'checked' : '' }} style="display:none">
                                    <div style="width: 5px; height: 5px; border-radius: 50%; background: {{ $checked ? 'var(--emerald)' : 'rgba(255,255,255,0.1)' }};"></div>
                                    {{ $name }}
                                </label>
                            @endforeach
                        </div>
                    </div>

                    {{-- Exclusion --}}
                    <div class="glass" style="padding: 12px 16px; border-radius: var(--radius-lg); border-top: 2px solid var(--rose);">
                        <p style="font-size: 10px; font-weight: 800; color: var(--rose); text-transform: uppercase; letter-spacing: 0.1em; margin-bottom: 10px; display: flex; align-items: center; gap: 6px;">
                            <svg viewBox="0 0 24 24" fill="none" width="12" height="12" stroke="currentColor" stroke-width="3"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                            No existe en...
                        </p>
                        <div style="display: flex; flex-wrap: wrap; gap: 6px;">
                            @foreach($branches as $name => $info)
                                @php $checked = in_array($info['col'], $faltaEn); @endphp
                                <label class="coverage-checkbox" data-type="no"
                                       style="display: flex; align-items: center; gap: 6px; padding: 5px 10px; border-radius: 8px; cursor: pointer; font-size: 10px; font-weight: 700; border: 1px solid {{ $checked ? 'rgba(244,63,94,0.3)' : 'var(--border)' }}; background: {{ $checked ? 'rgba(244,63,94,0.1)' : 'transparent' }}; color: {{ $checked ? 'var(--rose)' : 'var(--text-muted)' }}; transition: all 0.2s;">
                                    <input type="checkbox" name="falta_en[]" value="{{ $info['col'] }}" {{ $checked ? 'checked' : '' }} style="display:none">
                                    <div style="width: 5px; height: 5px; border-radius: 50%; background: {{ $checked ? 'var(--rose)' : 'rgba(255,255,255,0.1)' }};"></div>
                                    {{ $name }}
                                </label>
                            @endforeach
                        </div>
                    </div>
                </div>

                {{-- Actions --}}
                <div style="display: flex; align-items: center; justify-content: space-between; gap: 12px; flex-wrap: wrap;">
                    <div style="display: flex; gap: 8px;">
                        <button type="submit" class="btn btn--primary shadow-premium" style="background: var(--violet); border: none; color: white; padding: 8px 20px; font-size: 12px;">
                            Aplicar Filtros
                        </button>
                        <a href="{{ route('homologacion.index') }}" class="btn btn--ghost" style="font-size: 12px; border: 1px solid var(--border); padding: 8px 16px;">Limpiar cobertura</a>
                    </div>
                    @if($cobertura && isset($presets[$cobertura]))
                    <span style="font-size: 10px; font-weight: 700; color: var(--violet-light);">{{ $presets[$cobertura]['label'] }} · {{ count($tienEn) + count($faltaEn) }} sel. manuales</span>
                    @endif
                </div>

            </div>
        </div>{{-- /collapsible body --}}
    </div>{{-- /glass-card --}}
</form>




{{-- ── ALERTS (Success/Errores) ────────────────────────────── --}}
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

</div>{{-- /end above-table wrapper --}}

{{-- ── RESULTS TABLE (Intelligent Grid) ──────────────────────── --}}
<div class="glass-card shadow-premium" id="homo-table-card" style="display: flex; flex-direction: column; margin-bottom: 0;">
    <div style="padding: 10px 14px; border-bottom: 1px solid var(--border); display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 8px;">
        <div style="display:flex; align-items:center; gap:10px;">
            <h2 style="font-size: 13px; font-weight: 800; color: var(--text-primary); margin:0;">Matriz de Homologación Completa</h2>
            <span style="font-size: 10px; color: var(--text-muted);">
                @if($cobertura || count($tienEn) || count($faltaEn))
                    · <span style="color: var(--violet-light); font-weight: 700;">Filtrada</span> · {{ number_format($articles->total()) }}
                @elseif($search)
                    · "<span style="color: var(--violet-light);">{{ $search }}</span>" · {{ number_format($articles->total()) }}
                @else
                    · {{ number_format($stats['universo'] ?? 0) }} artículos
                @endif
            </span>
        </div>
        <div style="background: rgba(255,255,255,0.03); padding: 4px 10px; border-radius: 20px; border: 1px solid var(--border); display: flex; align-items: center; gap: 8px;">
            <label for="page-selector" style="font-size: 10px; font-weight: 700; color: var(--text-muted); text-transform: uppercase;">Mostrar:</label>
            <select id="page-selector" 
                    onchange="document.getElementById('per_page_input').value=this.value; document.getElementById('cobertura-form').submit();" 
                    style="background: transparent; border: none; color: var(--violet-light); font-size: 11px; font-weight: 800; cursor: pointer; outline: none; -webkit-appearance: none; padding-right: 12px; background-image: url('data:image/svg+xml;utf8,<svg fill=%22%238b5cf6%22 height=%2214%22 viewBox=%220 0 24 24%22 width=%2214%22 xmlns=%22http://www.w3.org/2000/svg%22><path d=%22M7 10l5 5 5-5z%22/></svg>'); background-repeat: no-repeat; background-position-x: 100%; background-position-y: center;">
                <option value="50" style="background:var(--bg-root);color:white;" @if($per_page == 50) selected @endif>50</option>
                <option value="100" style="background:var(--bg-root);color:white;" @if($per_page == 100) selected @endif>100</option>
                <option value="250" style="background:var(--bg-root);color:white;" @if($per_page == 250) selected @endif>250</option>
                <option value="500" style="background:var(--bg-root);color:white;" @if($per_page == 500) selected @endif>500</option>
            </select>
        </div>
    </div>

    <div class="table-wrap" id="table-inner-wrap" style="background: rgba(0,0,0,0.1);">
        <table class="data-table" id="homo-table" style="width: 100%; border-collapse: separate; border-spacing: 0;">
            <thead style="position: sticky; top: 0; z-index: 10;">
                <tr style="background: var(--bg-card-2);">
                    <th style="padding: 14px 20px; text-align: left; font-size: 11px; font-weight: 800; color: var(--text-secondary); text-transform: uppercase; letter-spacing: 0.05em; border-bottom: 2px solid var(--border);">Código Maestro</th>
                    <th style="padding: 14px 20px; text-align: left; font-size: 11px; font-weight: 800; color: var(--text-secondary); text-transform: uppercase; letter-spacing: 0.05em; border-bottom: 2px solid var(--border); min-width: 250px;">Descripción</th>
                    @foreach($branches as $name => $info)
                        <th style="padding: 14px 20px; text-align: center; font-size: 11px; font-weight: 800; color: var(--text-secondary); text-transform: uppercase; letter-spacing: 0.05em; border-bottom: 2px solid var(--border);">{{ $name }}</th>
                    @endforeach
                </tr>
            </thead>
            <tbody>
                @forelse($articles as $row)
                    <tr style="transition: background 0.1s;" onmouseover="this.style.background='rgba(255,255,255,0.02)'" onmouseout="this.style.background='transparent'">
                        <td style="padding: 12px 20px; font-family: 'JetBrains Mono', monospace; font-size: 12px; color: var(--violet-light); font-weight: 600; border-bottom: 1px solid var(--border-light);">{{ $row->Codigo_Deasa }}</td>
                        <td style="padding: 12px 20px; font-size: 13px; color: var(--text-primary); border-bottom: 1px solid var(--border-light);">{{ $row->Descripcion_Deasa }}</td>
                        @foreach($branches as $name => $info)
                            @php $val = $row->{$info['col']}; @endphp
                            <td style="padding: 12px 20px; text-align: center; border-bottom: 1px solid var(--border-light);">
                                @php
                                    $color = 'var(--text-muted)';
                                    $bg = 'rgba(255,255,255,0.03)';
                                    $icon = '';
                                    if ($val === 'ACTIVO') {
                                        $color = 'var(--emerald)';
                                        $bg = 'var(--emerald-bg)';
                                        $icon = '<svg style="margin-right:4px" viewBox="0 0 24 24" fill="none" width="12" height="12" stroke="currentColor" stroke-width="3"><polyline points="20 6 9 17 4 12"/></svg>';
                                    } elseif ($val === 'INACTIVO') {
                                        $color = 'var(--amber)';
                                        $bg = 'var(--amber-bg)';
                                        $icon = '<svg style="margin-right:4px" viewBox="0 0 24 24" fill="none" width="12" height="12" stroke="currentColor" stroke-width="3"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>';
                                    } else {
                                        $color = 'var(--rose)';
                                        $bg = 'var(--rose-bg)';
                                        $icon = '<svg style="margin-right:4px" viewBox="0 0 24 24" fill="none" width="12" height="12" stroke="currentColor" stroke-width="3"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>';
                                    }
                                @endphp
                                <span style="display: inline-flex; align-items: center; padding: 4px 10px; border-radius: 8px; font-size: 10px; font-weight: 800; text-transform: uppercase; background: {{ $bg }}; color: {{ $color }}; border: 1px solid transparent;">
                                    {!! $icon !!}
                                    {{ $val ?: 'FALTA' }}
                                </span>
                            </td>
                        @endforeach
                    </tr>
                @empty
                    <tr>
                        <td colspan="{{ 2 + count($branches) }}" style="padding: 60px; text-align: center; color: var(--text-muted); font-size: 14px;">
                            <svg style="opacity: 0.2; margin-bottom: 12px;" viewBox="0 0 24 24" fill="none" width="48" height="48" stroke="currentColor" stroke-width="1"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.35-4.35"/></svg>
                            <p>No se encontraron artículos con estos criterios.</p>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{-- Paginación Premium --}}
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

{{-- ── INTERACTIVE LOGIC (Clean & Optimized) ───────────────────── --}}
<script>
// ── Toggle: Panel Combinado (Estadísticas & Filtros) ──
function togglePanelCombinado() {
    const body    = document.getElementById('panel-combinado-body');
    const chevron = document.getElementById('panel-chevron');
    const label   = document.getElementById('panel-tog-label');
    const isOpen  = body.style.maxHeight !== '0px' && body.style.maxHeight !== '';
    
    if (isOpen) {
        body.style.maxHeight = '0px';
        chevron.style.transform = 'rotate(-90deg)';
        label.textContent = 'Expandir';
    } else {
        body.style.maxHeight = '800px';
        chevron.style.transform = 'rotate(0deg)';
        label.textContent = 'Ocultar';
    }
}

document.addEventListener('DOMContentLoaded', function() {

    // ── Advanced Coverage Presets ──
    const presetBtns = document.querySelectorAll('.preset-btn');
    const coberturaHidden = document.getElementById('cobertura-hidden');
    const presetsMeta = {
        '': { color: '#64748b' },
        'todas': { color: '#10b981' },
        'todas_menos_una': { color: '#f59e0b' },
        'incompleta': { color: '#ec4899' },
        'solo_una': { color: '#ef4444' },
        'ninguna': { color: '#94a3b8' }
    };

    presetBtns.forEach(btn => {
        btn.addEventListener('click', (e) => {
            const val = btn.getAttribute('data-val');
            coberturaHidden.value = val;
            
            presetBtns.forEach(b => {
                const bVal = b.getAttribute('data-val');
                const meta = presetsMeta[bVal] || { color: '#64748b' };
                const isActive = bVal === val;
                
                b.style.borderColor = isActive ? meta.color : 'var(--border)';
                b.style.background = isActive ? meta.color + '15' : 'transparent';
                b.style.color = isActive ? meta.color : 'var(--text-secondary)';
            });
        });
    });

    // ── Coverage Checkbox Visuals ──
    const updateCbStyle = (label) => {
        const input = label.querySelector('input');
        const type = label.getAttribute('data-type'); // 'yes' or 'no'
        const activeColor = type === 'yes' ? 'var(--emerald)' : 'var(--rose)';
        const activeBg = type === 'yes' ? 'rgba(16,185,129,0.1)' : 'rgba(244,63,94,0.1)';
        const activeBorder = type === 'yes' ? 'rgba(16,185,129,0.3)' : 'rgba(244,63,94,0.3)';
        const dot = label.querySelector('div');

        if (input.checked) {
            label.style.borderColor = activeBorder;
            label.style.background = activeBg;
            label.style.color = activeColor;
            if (dot) dot.style.background = activeColor;
        } else {
            label.style.borderColor = 'var(--border)';
            label.style.background = 'transparent';
            label.style.color = 'var(--text-muted)';
            if (dot) dot.style.background = 'rgba(255,255,255,0.1)';
        }
    };

    document.querySelectorAll('.coverage-checkbox').forEach(label => {
        label.addEventListener('click', () => {
            const input = label.querySelector('input');
            input.checked = !input.checked;
            updateCbStyle(label);
        });
    });
});
</script>

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
                background: radial-gradient(circle, rgba(139,92,246,0.15) 0%, transparent 70%);
                pointer-events:none;"></div>

    <div style="
        background: rgba(15,23,42,0.9);
        border: 1px solid rgba(139,92,246,0.25);
        border-radius: 20px;
        padding: 40px 48px;
        text-align: center;
        max-width: 480px;
        width: 90%;
        box-shadow: 0 25px 60px rgba(0,0,0,0.5), 0 0 0 1px rgba(139,92,246,0.1);
        position: relative;
    ">
        {{-- Spinner animado --}}
        <div style="position:relative; width:72px; height:72px; margin:0 auto 24px;">
            <svg viewBox="0 0 72 72" style="width:72px; height:72px; animation: spin 1.2s linear infinite;">
                <circle cx="36" cy="36" r="30" fill="none" stroke="rgba(139,92,246,0.15)" stroke-width="5"/>
                <circle cx="36" cy="36" r="30" fill="none" stroke="url(#syncGrad)" stroke-width="5"
                        stroke-linecap="round" stroke-dasharray="50 140"/>
                <defs>
                    <linearGradient id="syncGrad" x1="0%" y1="0%" x2="100%" y2="0%">
                        <stop offset="0%" stop-color="#8b5cf6"/>
                        <stop offset="100%" stop-color="#6366f1"/>
                    </linearGradient>
                </defs>
            </svg>
            <div style="position:absolute; inset:0; display:flex; align-items:center; justify-content:center;">
                <svg viewBox="0 0 24 24" fill="none" width="24" height="24" stroke="#8b5cf6" stroke-width="2">
                    <path d="M21 2v6h-6"/><path d="M3 12a9 9 0 0 1 15-6.7L21 8"/>
                    <path d="M3 22v-6h6"/><path d="M21 12a9 9 0 0 1-15 6.7L3 16"/>
                </svg>
            </div>
        </div>

        <h2 style="font-size:20px; font-weight:800; color:white; margin:0 0 6px; letter-spacing:-0.01em;">Sincronizando Datos</h2>
        <p id="sync-step-label" style="font-size:13px; color:#94a3b8; margin:0 0 28px;">Conectando con las 12 sucursales...</p>

        {{-- Pasos del proceso --}}
        <div style="display:flex; flex-direction:column; gap:10px; text-align:left; margin-bottom:28px;">
            <div class="sync-step" id="step-1" style="display:flex; align-items:center; gap:10px;">
                <div class="step-dot" style="width:8px;height:8px;border-radius:50%;background:#8b5cf6;flex-shrink:0;"></div>
                <span style="font-size:12px; color:#cbd5e1;">Verificando conexiones a sucursales</span>
            </div>
            <div class="sync-step" id="step-2" style="display:flex; align-items:center; gap:10px; opacity:0.4;">
                <div class="step-dot" style="width:8px;height:8px;border-radius:50%;background:#475569;flex-shrink:0;"></div>
                <span style="font-size:12px; color:#64748b;">Extrayendo catálogos de artículos</span>
            </div>
            <div class="sync-step" id="step-3" style="display:flex; align-items:center; gap:10px; opacity:0.4;">
                <div class="step-dot" style="width:8px;height:8px;border-radius:50%;background:#475569;flex-shrink:0;"></div>
                <span style="font-size:12px; color:#64748b;">Construyendo matriz de cobertura</span>
            </div>
            <div class="sync-step" id="step-4" style="display:flex; align-items:center; gap:10px; opacity:0.4;">
                <div class="step-dot" style="width:8px;height:8px;border-radius:50%;background:#475569;flex-shrink:0;"></div>
                <span style="font-size:12px; color:#64748b;">Guardando resultados en BD local</span>
            </div>
        </div>

        {{-- Barra de progreso animada --}}
        <div style="height:4px; background:rgba(255,255,255,0.06); border-radius:4px; overflow:hidden; margin-bottom:16px;">
            <div id="sync-progress-bar" style="height:100%; width:0%; background:linear-gradient(90deg,#8b5cf6,#6366f1); border-radius:4px; transition:width 0.6s ease;"></div>
        </div>

        <div style="display:flex; justify-content:space-between; align-items:center;">
            <p id="sync-elapsed" style="font-size:11px; color:#475569; margin:0;">Tiempo transcurrido: 0s</p>
            <button type="button" id="sync-cancel-btn" onclick="cancelSync()" class="btn btn--sm" style="background: rgba(244,63,94,0.1); color: var(--rose); border: 1px solid rgba(244,63,94,0.3); font-size: 11px; padding: 4px 12px; border-radius: 6px;">
                Interrumpir
            </button>
        </div>
    </div>
</div>

<style>
@keyframes spin { to { transform: rotate(360deg); } }
.sync-step.active span { color: #e2e8f0 !important; }
.sync-step.active .step-dot { background: #8b5cf6 !important; box-shadow: 0 0 8px rgba(139,92,246,0.6); }
.sync-step.done .step-dot { background: #10b981 !important; }
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

    // Contador de tiempo
    elapsedT = setInterval(() => {
        elapsed.textContent = 'Tiempo transcurrido: ' + Math.round((Date.now() - startTs) / 1000) + 's';
    }, 1000);

    // Paso 1 activo de inmediato
    activateStep('step-1');
    bar.style.width = '5%';

    // ── 1. Disparar sincronización (retorna JSON inmediatamente) ──
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content
                   || '{{ csrf_token() }}';

    fetch('{{ route("homologacion.sync") }}', {
        method: 'POST',
        headers: { 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json' }
    })
    .then(r => r.json())
    .then(data => {
        if (data.status === 'already_running') {
            stepLbl.textContent = '⚠️ Ya hay una sincronización en progreso...';
        }
        // ── 2. Iniciar polling cada 2 segundos ──
        pollTimer = setInterval(pollStatus, 2000);
    })
    .catch(() => {
        stepLbl.textContent = 'Error al iniciar. Recargando...';
        setTimeout(() => location.reload(), 3000);
    });

    // ── Polling: consulta el estado real del proceso ──
    function pollStatus() {
        fetch('{{ route("homologacion.sync.status") }}', { headers: { 'Accept': 'application/json' } })
        .then(r => r.json())
        .then(data => {
            const step  = parseInt(data.step  ?? 0);
            const total = parseInt(data.total ?? 12);
            const pct   = total > 0 ? Math.round((step / total) * 95) : 5;

            bar.style.width  = pct + '%';
            stepLbl.textContent = data.message ?? '...';

            // Actualizar visualmente el paso activo
            const activeSId = 'step-' + Math.min(Math.ceil((step / total) * 4) + 1, 4);
            ['step-1','step-2','step-3','step-4'].forEach(id => {
                const el = document.getElementById(id);
                if (!el) return;
                const idx   = parseInt(id.split('-')[1]);
                const aIdx  = parseInt(activeSId.split('-')[1]);
                if (idx < aIdx) {
                    el.classList.remove('active'); el.classList.add('done'); el.style.opacity = '1';
                } else if (idx === aIdx) {
                    activateStep(id);
                } else {
                    el.classList.remove('active','done'); el.style.opacity = '0.4';
                }
            });

            if (data.status === 'done') {
                clearInterval(pollTimer);
                clearInterval(elapsedT);
                bar.style.width = '100%';
                stepLbl.textContent = '✅ ¡Sincronización completada! Recargando...';
                ['step-1','step-2','step-3','step-4'].forEach(id => {
                    const el = document.getElementById(id);
                    if (el) { el.classList.remove('active'); el.classList.add('done'); el.style.opacity = '1'; }
                });
                setTimeout(() => location.reload(), 1500);
            } else if (data.status === 'error') {
                clearInterval(pollTimer);
                clearInterval(elapsedT);
                stepLbl.textContent = '❌ Error: ' + (data.message ?? 'Revisa los logs.');
            } else if (data.status === 'cancelled') {
                clearInterval(pollTimer);
                clearInterval(elapsedT);
                stepLbl.textContent = '⚠️ ' + (data.message ?? 'Sincronización interrumpida.');
                document.getElementById('sync-progress-bar').style.background = 'var(--amber)';
                setTimeout(() => location.reload(), 2500);
            }
        })
        .catch(() => {}); // ignorar errores de red temporales
    }
}

function activateStep(id) {
    const el = document.getElementById(id);
    if (!el) return;
    el.classList.add('active');
    el.style.opacity = '1';
}

function cancelSync() {
    Swal.fire({
        title: '¿Interrumpir sincronización?',
        text: 'Las sucursales pendientes quedarán marcadas temporalmente con estado "FALTA".',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#f43f5e',
        cancelButtonColor: '#64748b',
        confirmButtonText: 'Sí, detener',
        cancelButtonText: 'Cancelar',
        background: '#0f172a',
        color: '#f8fafc',
        customClass: {
            popup: 'shadow-premium'
        }
    }).then((result) => {
        if (result.isConfirmed) {
            const btn = document.getElementById('sync-cancel-btn');
            if(btn) { btn.disabled = true; btn.textContent = 'Cancelando...'; btn.style.opacity = '0.5'; }
            
            document.getElementById('sync-progress-bar').style.background = 'var(--rose)';
            document.getElementById('sync-step-label').textContent = 'Enviando señal de interrupción...';

            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content || '{{ csrf_token() }}';
            fetch('{{ route("homologacion.sync.cancel") }}', {
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json' }
            }).catch(() => {});
        }
    });
}
</script>



<script>
// ── Exportación Asíncrona (Excel) ──
function startExportExcelBg() {
    const form = document.getElementById('cobertura-form');
    const formData = new FormData(form);
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content || '{{ csrf_token() }}';

    // Disparar toast informativo
    Swal.mixin({
      toast: true, position: 'bottom-end', showConfirmButton: false, timer: 3000, background: '#1e293b', color: '#f8fafc'
    }).fire({
        icon: 'success',
        title: 'Exportación iniciada',
        text: 'Puedes revisar el progreso en el Centro de Descargas (arriba a la derecha)'
    });

    fetch('{{ route("homologacion.export.bg") }}', {
        method: 'POST',
        headers: { 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json' },
        body: formData
    })
    .then(r => r.json())
    .then(data => {
        if (data.status === 'started') {
            // Check if GDC exists and trigger open optionally, or just let badge do it
            const gdcBtn = document.getElementById('gdc-toggle-btn');
            if(gdcBtn && !document.getElementById('gdc-panel').style.display.includes('block')) {
                 gdcBtn.click();
            }
        }
    })
    .catch(() => {});
}


</script>

<script>
// ── Ajuste dinámico: recalcula la altura del scroll de la tabla ──────────────
// Se ejecuta al cargar y cada vez que se expande/colapsa el panel de filtros.
function adjustTableHeight() {
    const wrap = document.getElementById('table-inner-wrap');
    const card = document.getElementById('homo-table-card');
    if (!wrap || !card) return;

    const cardTop = card.getBoundingClientRect().top;          // px desde arriba del viewport
    const cardHeaderH = card.querySelector('div')?.offsetHeight ?? 48; // header del card
    const bottomPad = 8;
    const available = window.innerHeight - cardTop - cardHeaderH - bottomPad;

    wrap.style.height = Math.max(180, available) + 'px';
}

// Ejecutar al cargar
document.addEventListener('DOMContentLoaded', adjustTableHeight);
window.addEventListener('resize', adjustTableHeight);

// Volver a ajustar cuando el panel de filtros cambia de tamaño
const panelBody = document.getElementById('panel-combinado-body');
if (panelBody && typeof ResizeObserver !== 'undefined') {
    new ResizeObserver(adjustTableHeight).observe(panelBody);
}
</script>

@endsection
