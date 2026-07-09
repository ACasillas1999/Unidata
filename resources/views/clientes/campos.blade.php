@extends('layouts.app')

@section('title', 'Configurar Campos — Clientes')
@section('breadcrumb', 'Configuración de Campos')

@section('content')

<div style="max-width: 900px; margin: 0 auto 30px auto;">

    {{-- Header --}}
    <div class="page-header shadow-premium" style="margin-bottom: 24px; padding: 20px 30px; background: var(--grad-surface); border-radius: var(--radius-xl); border: 1px solid var(--glass-border); display: flex; justify-content: space-between; align-items: center;">
        <div style="display: flex; gap: 20px; align-items: center;">
            <div style="width: 48px; height: 48px; background: rgba(99,102,241,0.15); border: 1px solid rgba(99,102,241,0.3); color: #818cf8; border-radius: 12px; display: flex; align-items: center; justify-content: center;">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" width="22" height="22"><path d="M12 20h9"/><path d="M16.5 3.5a2.121 2.121 0 0 1 3 3L7 19l-4 1 1-4L16.5 3.5z"/></svg>
            </div>
            <div>
                <h1 class="page-title" style="margin:0; font-size: 22px;">Configurar Campos — Clientes</h1>
                <p style="color: var(--text-secondary); margin:4px 0 0; font-size: 13px;">Elige qué campos se muestran en el formulario de alta y edición</p>
            </div>
        </div>
        <div style="display: flex; gap: 10px;">
            <a href="{{ route('clientes.campos_sucursal') }}" class="btn btn--ghost" style="border: 1px solid rgba(245,158,11,0.4); color:#fbbf24; padding: 10px 18px; font-size: 13px;">
                <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2.5" style="margin-right:6px;"><rect x="2" y="3" width="7" height="7"/><rect x="15" y="3" width="7" height="7"/><rect x="15" y="14" width="7" height="7"/></svg>
                Campos por Sucursal
            </a>
            <a href="{{ route('clientes.index') }}" class="btn btn--ghost" style="border: 1px solid var(--border); padding: 10px 20px;">
                <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2.5" style="margin-right: 8px;"><path d="M19 12H5M12 19l-7-7 7-7"/></svg>
                Volver
            </a>
        </div>
    </div>

    @if(session('success'))
        <div style="background: rgba(16,185,129,0.1); border: 1px solid rgba(16,185,129,0.3); border-radius: 10px; padding: 14px 18px; margin-bottom: 20px; color: #34d399; font-size: 14px; display: flex; gap: 10px; align-items: center;">
            <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
            {{ session('success') }}
        </div>
    @endif

    <form action="{{ route('clientes.campos.update') }}" method="POST">
        @csrf

        <div class="glass-card shadow-premium" style="border-radius: 20px; overflow: hidden;">

            {{-- Info --}}
            <div style="padding: 18px 24px; background: rgba(99,102,241,0.08); border-bottom: 1px solid rgba(99,102,241,0.2); display: flex; align-items: center; gap: 10px;">
                <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="#818cf8" stroke-width="2.5"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
                <span style="font-size: 13px; color: #a5b4fc;">Los campos marcados con <strong>🔒</strong> son requeridos y siempre están activos. Los demás pueden habilitarse o deshabilitarse libremente.</span>
            </div>

            {{-- Tabla de campos --}}
            <div style="padding: 8px 0;">
                @foreach($campos as $campo)
                <div style="display: flex; align-items: center; justify-content: space-between; padding: 14px 24px; border-bottom: 1px solid rgba(255,255,255,0.04); transition: background 0.15s;"
                     onmouseover="this.style.background='rgba(255,255,255,0.02)'"
                     onmouseout="this.style.background='transparent'">

                    <div style="display: flex; align-items: center; gap: 14px;">
                        {{-- Ícono de requerido --}}
                        @if($campo->is_required)
                            <span title="Campo requerido" style="font-size: 16px;">🔒</span>
                        @else
                            <span style="width: 20px; display:inline-block;"></span>
                        @endif

                        <div>
                            <div style="font-weight: 600; font-size: 14px;">{{ $campo->label }}</div>
                            <div style="font-size: 11px; color: var(--text-secondary); font-family: monospace; margin-top: 2px;">{{ $campo->campo }}</div>
                        </div>
                    </div>

                    {{-- Toggles --}}
                    <div style="display: flex; gap: 30px; align-items: center;">
                        
                        {{-- Toggle Crear --}}
                        <div style="display: flex; flex-direction: column; align-items: center; gap: 6px;">
                            <span style="font-size: 11px; color: var(--text-secondary); text-transform: uppercase; font-weight: 700;">En Alta</span>
                            <label class="toggle-switch" style="cursor: {{ $campo->is_required ? 'not-allowed' : 'pointer' }};">
                                <input
                                    type="checkbox"
                                    name="campos_create[]"
                                    value="{{ $campo->campo }}"
                                    {{ $campo->show_in_create ? 'checked' : '' }}
                                    {{ $campo->is_required ? 'disabled' : '' }}
                                    style="display: none;"
                                    onchange="this.closest('.toggle-switch').classList.toggle('active', this.checked)"
                                >
                                <div class="toggle-track {{ $campo->show_in_create ? 'active' : '' }}">
                                    <div class="toggle-thumb"></div>
                                </div>
                            </label>
                        </div>

                        {{-- Toggle Editar --}}
                        <div style="display: flex; flex-direction: column; align-items: center; gap: 6px;">
                            <span style="font-size: 11px; color: var(--text-secondary); text-transform: uppercase; font-weight: 700;">En Edición</span>
                            <label class="toggle-switch" style="cursor: {{ $campo->is_required ? 'not-allowed' : 'pointer' }};">
                                <input
                                    type="checkbox"
                                    name="campos_edit[]"
                                    value="{{ $campo->campo }}"
                                    {{ $campo->show_in_edit ? 'checked' : '' }}
                                    {{ $campo->is_required ? 'disabled' : '' }}
                                    style="display: none;"
                                    onchange="this.closest('.toggle-switch').classList.toggle('active', this.checked)"
                                >
                                <div class="toggle-track {{ $campo->show_in_edit ? 'active' : '' }}">
                                    <div class="toggle-thumb"></div>
                                </div>
                            </label>
                        </div>

                    </div>
                </div>
                @endforeach
            </div>

            {{-- Footer --}}
            <div style="padding: 20px 24px; border-top: 1px solid var(--border); display: flex; justify-content: space-between; align-items: center;">
                <span style="font-size: 13px; color: var(--text-secondary);">
                    {{ $campos->where('show_in_create', true)->count() }} en Alta | {{ $campos->where('show_in_edit', true)->count() }} en Edición
                </span>
                <div style="display: flex; gap: 12px;">
                    <button type="button" onclick="selectAll()" style="padding: 9px 18px; background: rgba(255,255,255,0.05); border: 1px solid var(--border); border-radius: 10px; color: var(--text-secondary); cursor: pointer; font-size: 13px;">Activar todos</button>
                    <button type="submit" class="btn btn--primary shadow-premium" style="background: var(--grad-premium); padding: 10px 28px; font-weight: 700;">
                        <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2.5" style="margin-right:6px;"><path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"/><polyline points="17 21 17 13 7 13 7 21"/></svg>
                        Guardar Configuración
                    </button>
                </div>
            </div>
        </div>
    </form>
</div>

<style>
.toggle-track {
    width: 44px; height: 24px;
    background: rgba(255,255,255,0.1);
    border: 1px solid var(--border);
    border-radius: 12px;
    position: relative;
    transition: all 0.25s;
}
.toggle-track.active {
    background: rgba(99,102,241,0.5);
    border-color: rgba(99,102,241,0.7);
}
.toggle-thumb {
    width: 18px; height: 18px;
    background: white;
    border-radius: 50%;
    position: absolute;
    top: 2px; left: 2px;
    transition: all 0.25s;
    box-shadow: 0 1px 4px rgba(0,0,0,0.3);
}
.toggle-track.active .toggle-thumb { left: 22px; }
</style>

@push('scripts')
<script>
// Sincronizar visualmente los toggles con el estado de los checkboxes
document.querySelectorAll('input[type=checkbox]').forEach(cb => {
    cb.addEventListener('change', function() {
        const track = this.closest('label').querySelector('.toggle-track');
        track.classList.toggle('active', this.checked);
    });
    // Forzar requeridos como checked aunque estén disabled
    if (cb.disabled) cb.checked = true;
});

function selectAll() {
    document.querySelectorAll('input[type=checkbox]:not(:disabled)').forEach(cb => {
        cb.checked = true;
        cb.closest('label').querySelector('.toggle-track').classList.add('active');
    });
}
</script>
@endpush

@endsection
