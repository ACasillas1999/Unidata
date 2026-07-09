@extends('layouts.app')

@section('title', 'Editar Sucursal — ' . $branch->name . ' | ' . $cliente->rfc)
@section('breadcrumb', 'Editar en Sucursal: ' . $branch->name)

@section('content')

<div style="max-width: 900px; margin: 0 auto 30px auto;">

    {{-- Header --}}
    <div class="page-header shadow-premium" style="margin-bottom: 24px; padding: 20px 30px; background: var(--grad-surface); border-radius: var(--radius-xl); border: 1px solid var(--glass-border); display: flex; justify-content: space-between; align-items: center;">
        <div style="display: flex; gap: 20px; align-items: center;">
            <div style="width: 48px; height: 48px; background: rgba(245,158,11,0.15); border: 1px solid rgba(245,158,11,0.3); color: #fbbf24; border-radius: 12px; display: flex; align-items: center; justify-content: center;">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" width="22" height="22"><rect x="2" y="3" width="7" height="7"/><rect x="15" y="3" width="7" height="7"/><rect x="15" y="14" width="7" height="7"/><path d="M2 17h10M2 21h5"/><path d="M9 7h3a3 3 0 0 1 3 3v1"/></svg>
            </div>
            <div>
                <h1 class="page-title" style="margin:0; font-size: 22px;">
                    Sucursal: <span style="color:#fbbf24;">{{ $branch->name }}</span>
                </h1>
                <p style="color: var(--text-secondary); margin:4px 0 0; font-size: 13px;">
                    Cliente: <span style="font-family:monospace; color:#a78bfa;">{{ $cliente->rfc }}</span>
                    &nbsp;|&nbsp; <span style="color: var(--text-secondary);">{{ $cliente->razon_social }}</span>
                    &nbsp;|&nbsp; ID Global: <span style="font-family:monospace; color:#34d399;">#{{ $cliente->id_global }}</span>
                </p>
            </div>
        </div>
        <a href="{{ route('clientes.edit', $cliente->rfc) }}" class="btn btn--ghost" style="border: 1px solid var(--border); padding: 10px 20px;">
            <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2.5" style="margin-right: 8px;"><path d="M19 12H5M12 19l-7-7 7-7"/></svg>
            Volver al Cliente
        </a>
    </div>

    @if(session('success'))
        <div style="background: rgba(16,185,129,0.1); border: 1px solid rgba(16,185,129,0.3); border-radius: 10px; padding: 14px 18px; margin-bottom: 20px; color: #34d399; font-size: 14px; display: flex; gap: 10px; align-items: center;">
            <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
            {{ session('success') }}
        </div>
    @endif

    @if($errors->any())
        <div style="background: rgba(239,68,68,0.1); border: 1px solid rgba(239,68,68,0.3); border-radius: 10px; padding: 14px 18px; margin-bottom: 20px; color: #f87171; font-size: 14px;">
            <ul style="margin:0; padding-left:18px;">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
        </div>
    @endif

    @if($campos->isEmpty())
        {{-- Sin campos configurados --}}
        <div class="glass-card shadow-premium" style="padding: 60px 40px; border-radius: 20px; text-align: center;">
            <svg viewBox="0 0 24 24" width="48" height="48" fill="none" stroke="currentColor" stroke-width="1.5" style="opacity:.3; margin-bottom:16px; color:#fbbf24;"><rect x="2" y="3" width="7" height="7"/><rect x="15" y="3" width="7" height="7"/><rect x="15" y="14" width="7" height="7"/></svg>
            <h3 style="margin:0 0 10px; font-size:18px; color:var(--text-secondary);">Sin campos configurados</h3>
            <p style="color:var(--text-secondary); font-size:14px; margin:0 0 20px;">
                Primero debes configurar qué campos de la sucursal quieres ver y editar.
            </p>
            <a href="{{ route('clientes.campos_sucursal') }}" class="btn btn--primary shadow-premium" style="background: rgba(245,158,11,0.8); padding: 11px 28px; font-weight:700; display:inline-flex; align-items:center; gap:8px;">
                <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M12 20h9"/><path d="M16.5 3.5a2.121 2.121 0 0 1 3 3L7 19l-4 1 1-4L16.5 3.5z"/></svg>
                Configurar Campos de Sucursal
            </a>
        </div>
    @else
        <form action="{{ route('clientes.update_sucursal', ['rfc' => $cliente->rfc, 'branchId' => $branch->id]) }}" method="POST" id="form-sucursal">
            @csrf
            @method('PUT')

            <div class="glass-card shadow-premium" style="padding: 36px; border-radius: 20px;">

                {{-- Badge de solo lectura --}}
                @php $hayReadonly = $campos->where('editable', false)->count() > 0; @endphp
                @if($hayReadonly)
                <div style="margin-bottom:24px; padding:12px 16px; background:rgba(99,102,241,0.08); border:1px solid rgba(99,102,241,0.2); border-radius:10px; font-size:13px; color:#a5b4fc; display:flex; gap:10px; align-items:center;">
                    <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2.5" style="flex-shrink:0;"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
                    Los campos con fondo oscuro son de <strong>solo lectura</strong> — se muestran para referencia pero no se pueden modificar.
                </div>
                @endif

                {{-- Grid de campos --}}
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 20px;">
                    @foreach($campos as $campo)
                        @php
                            $key    = $campo->campo;
                            $valor  = $registro->{$key} ?? '';
                            $editar = $campo->editable;
                        @endphp

                        <div class="es-field {{ !$editar ? 'es-readonly' : '' }}">
                            <label class="es-label">
                                {{ $campo->label }}
                                @if($editar)
                                    <span style="color:#fbbf24; font-size:10px; margin-left:4px;">✏️</span>
                                @else
                                    <span style="color:var(--text-secondary); font-size:10px; margin-left:4px;">🔒</span>
                                @endif
                            </label>
                            <div style="font-size:10px; color:var(--text-secondary); font-family:monospace; margin-bottom:6px;">{{ $key }}</div>

                            @if(!$editar)
                                {{-- Solo lectura: mostrar valor como texto --}}
                                <div class="es-value-readonly">{{ $valor !== '' && $valor !== null ? $valor : '—' }}</div>
                            @elseif($campo->tipo === 'date')
                                <input type="date" name="{{ $key }}" value="{{ $valor }}" class="es-input">
                            @elseif($campo->tipo === 'number')
                                <input type="number" name="{{ $key }}" value="{{ $valor }}" class="es-input">
                            @elseif($campo->tipo === 'decimal')
                                <input type="number" step="0.01" name="{{ $key }}" value="{{ $valor }}" class="es-input">
                            @elseif($campo->tipo === 'boolean')
                                <select name="{{ $key }}" class="es-input">
                                    <option value="0" {{ (int)$valor === 0 ? 'selected' : '' }}>No</option>
                                    <option value="1" {{ (int)$valor === 1 ? 'selected' : '' }}>Sí</option>
                                </select>
                            @else
                                <input type="text" name="{{ $key }}" value="{{ $valor }}" class="es-input">
                            @endif
                        </div>
                    @endforeach
                </div>

                {{-- Info de identificación del registro --}}
                <div style="margin-top: 24px; padding: 14px 16px; background: rgba(255,255,255,0.02); border: 1px solid var(--border); border-radius: 10px; display:flex; gap:20px; flex-wrap:wrap; font-size:12px; color:var(--text-secondary);">
                    <span>ID local (Cliente): <code style="color:#a78bfa;">#{{ $registro->Cliente ?? '—' }}</code></span>
                    <span>ID Global: <code style="color:#34d399;">#{{ $registro->IdGlobal ?? '—' }}</code></span>
                    <span>RFC: <code style="color:#a78bfa;">{{ $registro->RFC ?? $cliente->rfc }}</code></span>
                    <span>Sucursal: <strong style="color:#fbbf24;">{{ $branch->name }}</strong></span>
                </div>

                {{-- Botones --}}
                <div style="margin-top: 28px; padding-top: 24px; border-top: 1px solid var(--border); display: flex; justify-content: flex-end; gap: 14px; align-items:center;">
                    <span style="font-size:12px; color:var(--text-secondary); margin-right:auto;">
                        Los cambios se aplican <strong>únicamente</strong> en la sucursal <strong style="color:#fbbf24;">{{ $branch->name }}</strong>
                    </span>
                    <a href="{{ route('clientes.edit', $cliente->rfc) }}" class="btn btn--ghost" style="padding: 12px 24px;">Cancelar</a>
                    <button type="submit" class="btn btn--primary shadow-premium" style="background: linear-gradient(135deg, #f59e0b, #d97706); padding: 13px 40px; font-weight: 800;">
                        <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2.5" style="margin-right: 8px;"><path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"/><polyline points="17 21 17 13 7 13 7 21"/></svg>
                        Guardar en {{ $branch->name }}
                    </button>
                </div>
            </div>
        </form>
    @endif

</div>

<style>
.es-label {
    font-size: 11px;
    font-weight: 800;
    color: var(--text-secondary);
    display: block;
    text-transform: uppercase;
    letter-spacing: 0.08em;
    margin-bottom: 3px;
}
.es-input {
    width: 100%;
    background: var(--bg-root);
    border: 1px solid var(--border);
    padding: 11px 14px;
    border-radius: 10px;
    color: white;
    font-size: 14px;
    transition: all 0.2s;
    box-sizing: border-box;
}
.es-input:focus { border-color: #fbbf24; outline: none; box-shadow: 0 0 0 4px rgba(245,158,11,0.15); }
.es-input option { background: #1a1d27; color: white; }
.es-field {
    padding: 14px;
    border-radius: 12px;
    background: rgba(255,255,255,0.02);
    border: 1px solid var(--border);
    transition: border-color .2s;
}
.es-field:has(.es-input:focus) { border-color: rgba(245,158,11,0.4); }
.es-readonly {
    background: rgba(255,255,255,0.01);
    opacity: 0.75;
}
.es-value-readonly {
    background: rgba(0,0,0,0.2);
    border: 1px solid var(--border);
    padding: 11px 14px;
    border-radius: 10px;
    color: var(--text-secondary);
    font-size: 14px;
    font-family: monospace;
    min-height: 42px;
    word-break: break-all;
}
</style>

@push('scripts')
<script>
document.getElementById('form-sucursal')?.addEventListener('submit', function() {
    Swal.fire({
        title: 'Guardando...',
        text: 'Actualizando campos en la sucursal {{ $branch->name }}.',
        allowOutsideClick: false,
        didOpen: () => Swal.showLoading()
    });
});
</script>
@endpush

@endsection
