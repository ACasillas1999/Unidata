@extends('layouts.app')

@section('title', 'Mapeo de Campos PowerSales')
@section('breadcrumb', 'PowerSales / Mapeo')

@section('content')
<div style="padding-bottom: 32px;">

<div class="page-header shadow-premium" style="margin-bottom: 24px; padding: 20px 24px; background: var(--grad-surface); border-radius: var(--radius-xl); border: 1px solid var(--glass-border); display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 16px;">
    <div style="display: flex; gap: 20px; align-items: center;">
        <div class="page-header-icon shadow-premium" style="background: rgba(59, 130, 246, 0.1); border: 1px solid rgba(59, 130, 246, 0.3); color: #60a5fa;">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" width="24" height="24">
                <path d="M9 3H5a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h4m0-18h10a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H9m0-18v18"/>
            </svg>
        </div>
        <div>
            <h1 class="page-title" style="margin:0;">Mapeo de Campos PowerSales</h1>
            <p class="page-subtitle" style="margin:4px 0 0; color: var(--text-secondary);">Solo lectura — reflejo en vivo de <code>proteo_db.field_mapping</code>. Editar en Proteo.</p>
        </div>
    </div>
    <a href="{{ route('powersales.auditoria') }}" class="btn btn--ghost" style="display: flex; align-items: center; gap: 8px; white-space: nowrap;">
        <svg viewBox="0 0 24 24" fill="none" width="16" height="16" stroke="currentColor" stroke-width="2.5"><path d="M19 12H5M12 19l-7-7 7-7"/></svg>
        Ver Auditoría
    </a>
</div>

<div class="glass-card shadow-premium" style="padding: 14px 20px; margin-bottom: 20px; border: 1px solid rgba(245,158,11,0.25); background: rgba(245,158,11,0.05);">
    <p style="margin: 0; font-size: 12.5px; color: #fbbf24;">
        ⚠ Campos con tipo especial (Prefijo SKU, Sistema/concatenado, ID fijo por lógica) no viven en esta tabla — son lógica del backend de Proteo y no se muestran aquí como "columna ERP".
    </p>
</div>

{{-- TABS --}}
<div style="display: flex; gap: 10px; flex-wrap: wrap; margin-bottom: 16px;">
    @foreach($groups as $key => $group)
    <button
        type="button"
        class="ps-tab-btn {{ $loop->first ? 'active' : '' }}"
        data-tab="tab-{{ $key }}"
        style="display:flex; align-items:center; gap:8px; padding:10px 16px; border-radius:10px; border:1px solid var(--border); background:rgba(255,255,255,0.03); color:var(--text-secondary); font-size:13px; font-weight:700; cursor:pointer; transition:all 0.2s;">
        {{ $group['label'] }}
        <span style="font-size:10.5px; font-weight:800; background:rgba(255,255,255,0.08); padding:2px 7px; border-radius:999px;">{{ $group['rows']->count() }}</span>
    </button>
    @endforeach
</div>

<div style="margin-bottom: 16px;">
    <input type="text" id="ps-filter-input" placeholder="Filtrar campos..." style="width: 100%; max-width: 360px; background: rgba(255,255,255,0.03); border: 1px solid var(--border); color: white; border-radius: 10px; padding: 10px 14px; font-size: 13px;">
</div>

{{-- PANELES --}}
@foreach($groups as $key => $group)
<div class="ps-tab-panel" id="tab-{{ $key }}" style="{{ $loop->first ? '' : 'display:none;' }}">
    <div class="glass-card shadow-premium" style="padding: 0; overflow: hidden;">
        <div style="padding: 16px 20px; border-bottom: 1px solid var(--border); background: rgba(255,255,255,0.02); display: flex; align-items: center; justify-content: space-between;">
            <h3 style="margin: 0; font-size: 14px; letter-spacing: 0.02em; color: white;">{{ $group['label'] }}</h3>
            <span style="font-size: 11px; color: var(--text-muted);">{{ $group['rows']->count() }} campos</span>
        </div>
        <div style="overflow-x: auto;">
        <table style="width: 100%; border-collapse: collapse; min-width: 560px;">
            <thead>
                <tr style="background: rgba(255,255,255,0.02); border-bottom: 1px solid var(--border);">
                    <th style="padding: 10px 20px; text-align: left; font-size: 10.5px; font-weight: 800; color: var(--text-secondary); text-transform: uppercase; white-space: nowrap;">Campo PowerSales</th>
                    <th style="padding: 10px 20px; text-align: left; font-size: 10.5px; font-weight: 800; color: var(--text-secondary); text-transform: uppercase; white-space: nowrap;">Columna ERP</th>
                    <th style="padding: 10px 20px; text-align: left; font-size: 10.5px; font-weight: 800; color: var(--text-secondary); text-transform: uppercase; white-space: nowrap;">Valor fijo</th>
                    <th style="padding: 10px 20px; text-align: center; font-size: 10.5px; font-weight: 800; color: var(--text-secondary); text-transform: uppercase; white-space: nowrap;">Estado</th>
                </tr>
            </thead>
            <tbody>
                @forelse($group['rows'] as $f)
                @php($isAuto = isset($f->auto_note))
                <tr class="ps-field-row" data-field="{{ strtolower($f->ps_field . ' ' . $f->erp_column . ' ' . $f->fixed_value) }}" style="border-bottom: 1px solid rgba(255,255,255,0.04); {{ $isAuto ? 'background: rgba(245,158,11,0.04);' : '' }}">
                    <td style="padding: 10px 20px; font-family: monospace; font-size: 12.5px; font-weight: 700; color: white; white-space: nowrap;">{{ $f->ps_field }}</td>
                    @if($isAuto)
                        <td colspan="2" style="padding: 10px 20px; font-size: 12px; color: #fbbf24;">🔒 {{ $f->auto_note }}</td>
                    @else
                        <td style="padding: 10px 20px; font-family: monospace; font-size: 12px; color: #60a5fa; white-space: nowrap;">{{ $f->erp_column ?? '—' }}</td>
                        <td style="padding: 10px 20px; font-family: monospace; font-size: 12px; color: #a78bfa; white-space: nowrap;">{{ $f->fixed_value ?? '—' }}</td>
                    @endif
                    <td style="padding: 10px 20px; text-align: center; white-space: nowrap;">
                        @if($isAuto)
                            <span style="color: var(--amber, #fbbf24); font-size: 11px; font-weight: 700;">Automático</span>
                        @elseif($f->erp_column || $f->fixed_value)
                            <span style="color: var(--emerald); font-size: 11px; font-weight: 700;">Mapeado</span>
                        @else
                            <span style="color: var(--text-muted); font-size: 11px;">Sin mapear</span>
                        @endif
                    </td>
                </tr>
                @empty
                <tr><td colspan="4" style="padding: 30px 20px; text-align: center; color: var(--text-muted); font-size: 12.5px;">Sin campos en esta categoría.</td></tr>
                @endforelse
            </tbody>
        </table>
        </div>
    </div>
</div>
@endforeach

</div>

<style>
.ps-tab-btn.active {
    background: rgba(59,130,246,0.15) !important;
    color: #60a5fa !important;
    border-color: rgba(59,130,246,0.35) !important;
}
</style>

<script>
(function () {
    const tabBtns = document.querySelectorAll('.ps-tab-btn');
    const panels  = document.querySelectorAll('.ps-tab-panel');

    tabBtns.forEach(btn => {
        btn.addEventListener('click', () => {
            tabBtns.forEach(b => b.classList.remove('active'));
            panels.forEach(p => p.style.display = 'none');
            btn.classList.add('active');
            document.getElementById(btn.dataset.tab).style.display = 'block';
        });
    });

    const filterInput = document.getElementById('ps-filter-input');
    filterInput?.addEventListener('input', () => {
        const term = filterInput.value.trim().toLowerCase();
        document.querySelectorAll('.ps-field-row').forEach(row => {
            row.style.display = row.dataset.field.includes(term) ? '' : 'none';
        });
    });
})();
</script>
@endsection
