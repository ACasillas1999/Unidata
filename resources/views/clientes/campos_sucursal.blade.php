@extends('layouts.app')

@section('title', 'Configurar Campos — Sucursales')
@section('breadcrumb', 'Configuración de Campos por Sucursal')

@section('content')

<div style="max-width: 960px; margin: 0 auto 30px auto;">

    {{-- Header --}}
    <div class="page-header shadow-premium" style="margin-bottom: 24px; padding: 20px 30px; background: var(--grad-surface); border-radius: var(--radius-xl); border: 1px solid var(--glass-border); display: flex; justify-content: space-between; align-items: center;">
        <div style="display: flex; gap: 20px; align-items: center;">
            <div style="width: 48px; height: 48px; background: rgba(245,158,11,0.15); border: 1px solid rgba(245,158,11,0.3); color: #fbbf24; border-radius: 12px; display: flex; align-items: center; justify-content: center;">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" width="22" height="22"><rect x="2" y="3" width="7" height="7"/><rect x="15" y="3" width="7" height="7"/><rect x="15" y="14" width="7" height="7"/><path d="M2 17h10M2 21h5"/><path d="M9 7h3a3 3 0 0 1 3 3v1"/></svg>
            </div>
            <div>
                <h1 class="page-title" style="margin:0; font-size: 22px;">Campos por Sucursal</h1>
                <p style="color: var(--text-secondary); margin:4px 0 0; font-size: 13px;">Define qué campos de la BD de cada sucursal son visibles y editables en el formulario de edición por sucursal</p>
            </div>
        </div>
        <div style="display: flex; gap: 10px;">
            <a href="{{ route('clientes.campos') }}" class="btn btn--ghost" style="border: 1px solid var(--border); padding: 10px 18px; font-size: 13px;">
                <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2.5" style="margin-right:6px;"><path d="M12 20h9"/><path d="M16.5 3.5a2.121 2.121 0 0 1 3 3L7 19l-4 1 1-4L16.5 3.5z"/></svg>
                Campos Maestro
            </a>
            <a href="{{ route('clientes.index') }}" class="btn btn--ghost" style="border: 1px solid var(--border); padding: 10px 18px; font-size: 13px;">
                <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2.5" style="margin-right:6px;"><path d="M19 12H5M12 19l-7-7 7-7"/></svg>
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

    @if($errors->any())
        <div style="background: rgba(239,68,68,0.1); border: 1px solid rgba(239,68,68,0.3); border-radius: 10px; padding: 14px 18px; margin-bottom: 20px; color: #f87171; font-size: 14px;">
            <ul style="margin:0; padding-left:18px;">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
        </div>
    @endif

    <form action="{{ route('clientes.campos_sucursal.update') }}" method="POST" id="form-campos-suc">
        @csrf

        <div class="glass-card shadow-premium" style="border-radius: 20px; overflow: hidden;">

            {{-- Info --}}
            <div style="padding: 18px 24px; background: rgba(245,158,11,0.08); border-bottom: 1px solid rgba(245,158,11,0.2); display: flex; align-items: flex-start; gap: 12px;">
                <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="#fbbf24" stroke-width="2.5" style="flex-shrink:0; margin-top:2px;"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
                <div style="font-size: 13px; color: #fde68a; line-height: 1.6;">
                    Escribe el nombre exacto del campo tal como aparece en la tabla <code style="background:rgba(0,0,0,0.3); padding: 1px 6px; border-radius:4px;">clientes</code> de la sucursal (ej: <code style="background:rgba(0,0,0,0.3); padding: 1px 6px; border-radius:4px;">LimiteCredito</code>, <code style="background:rgba(0,0,0,0.3); padding: 1px 6px; border-radius:4px;">Vendedor</code>).
                    Si un campo no existe en alguna sucursal, se omite automáticamente al abrir el formulario de esa sucursal.
                </div>
            </div>

            {{-- Tabla encabezado --}}
            <div style="display: grid; grid-template-columns: 1fr 1fr 130px 100px 48px; gap: 0; padding: 10px 24px; border-bottom: 1px solid rgba(255,255,255,0.06); background: rgba(255,255,255,0.02);">
                <span style="font-size:11px; font-weight:800; color:var(--text-secondary); text-transform:uppercase; letter-spacing:.08em;">Campo (BD)</span>
                <span style="font-size:11px; font-weight:800; color:var(--text-secondary); text-transform:uppercase; letter-spacing:.08em;">Etiqueta visible</span>
                <span style="font-size:11px; font-weight:800; color:var(--text-secondary); text-transform:uppercase; letter-spacing:.08em;">Tipo</span>
                <span style="font-size:11px; font-weight:800; color:var(--text-secondary); text-transform:uppercase; letter-spacing:.08em; text-align:center;">Editable</span>
                <span></span>
            </div>

            {{-- Filas dinámicas --}}
            <div id="campos-list" style="padding: 8px 0;">
                @forelse($campos as $idx => $campo)
                <div class="campo-row" data-idx="{{ $idx }}" style="display: grid; grid-template-columns: 1fr 1fr 130px 100px 48px; gap: 10px; align-items: center; padding: 10px 24px; border-bottom: 1px solid rgba(255,255,255,0.04); transition: background 0.15s;"
                     onmouseover="this.style.background='rgba(255,255,255,0.02)'"
                     onmouseout="this.style.background='transparent'">

                    <div>
                        <input type="text" name="campos[{{ $idx }}][campo]" value="{{ $campo->campo }}"
                               placeholder="Ej: LimiteCredito"
                               class="cs-input" style="font-family: monospace; font-size: 13px;" required>
                        <input type="hidden" name="campos[{{ $idx }}][orden]" value="{{ $idx }}">
                    </div>

                    <div>
                        <input type="text" name="campos[{{ $idx }}][label]" value="{{ $campo->label }}"
                               placeholder="Ej: Límite de Crédito"
                               class="cs-input" required>
                    </div>

                    <div>
                        <select name="campos[{{ $idx }}][tipo]" class="cs-input">
                            <option value="text"    {{ $campo->tipo === 'text'    ? 'selected' : '' }}>Texto</option>
                            <option value="number"  {{ $campo->tipo === 'number'  ? 'selected' : '' }}>Entero</option>
                            <option value="decimal" {{ $campo->tipo === 'decimal' ? 'selected' : '' }}>Decimal</option>
                            <option value="date"    {{ $campo->tipo === 'date'    ? 'selected' : '' }}>Fecha</option>
                            <option value="boolean" {{ $campo->tipo === 'boolean' ? 'selected' : '' }}>Booleano</option>
                        </select>
                    </div>

                    <div style="display:flex; justify-content:center;">
                        <label class="toggle-switch">
                            <input type="checkbox" name="editables[]" value="{{ $campo->campo }}"
                                   {{ $campo->editable ? 'checked' : '' }}
                                   style="display:none;"
                                   onchange="syncToggle(this)">
                            <div class="toggle-track {{ $campo->editable ? 'active' : '' }}">
                                <div class="toggle-thumb"></div>
                            </div>
                        </label>
                    </div>

                    <div style="display:flex; justify-content:center;">
                        <button type="button" onclick="removeRow(this)" title="Eliminar campo"
                                style="width:32px; height:32px; background:rgba(239,68,68,0.1); border:1px solid rgba(239,68,68,0.25); border-radius:8px; color:#f87171; cursor:pointer; display:flex; align-items:center; justify-content:center; transition:all .2s;"
                                onmouseover="this.style.background='rgba(239,68,68,0.2)'"
                                onmouseout="this.style.background='rgba(239,68,68,0.1)'">
                            <svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="3 6 5 6 21 6"/><path d="M19 6l-1 14H6L5 6"/><path d="M10 11v6M14 11v6"/></svg>
                        </button>
                    </div>
                </div>
                @empty
                <div id="empty-state" style="padding: 48px 24px; text-align:center; color: var(--text-secondary);">
                    <svg viewBox="0 0 24 24" width="40" height="40" fill="none" stroke="currentColor" stroke-width="1.5" style="opacity:.3; margin-bottom:12px;"><rect x="2" y="3" width="7" height="7"/><rect x="15" y="3" width="7" height="7"/><rect x="15" y="14" width="7" height="7"/><path d="M2 17h10M2 21h5"/></svg>
                    <p style="margin:0; font-size:14px;">Aún no hay campos configurados.</p>
                    <p style="margin:6px 0 0; font-size:13px;">Haz clic en <strong>"+ Agregar Campo"</strong> para comenzar.</p>
                </div>
                @endforelse
            </div>

            {{-- Footer --}}
            <div style="padding: 20px 24px; border-top: 1px solid var(--border); display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 12px;">
                <div style="display: flex; gap: 10px; align-items:center;">
                    <button type="button" id="btn-add" onclick="addRow()"
                            style="padding: 9px 18px; background: rgba(245,158,11,0.1); border: 1px solid rgba(245,158,11,0.3); border-radius: 10px; color: #fbbf24; cursor: pointer; font-size: 13px; font-weight:600; display:flex; align-items:center; gap:6px; transition:all .2s;"
                            onmouseover="this.style.background='rgba(245,158,11,0.2)'"
                            onmouseout="this.style.background='rgba(245,158,11,0.1)'">
                        <svg viewBox="0 0 24 24" width="15" height="15" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                        Agregar Campo
                    </button>
                    <span id="campo-count" style="font-size:13px; color:var(--text-secondary);">
                        {{ $campos->count() }} campo(s) configurado(s)
                    </span>
                </div>
                <button type="submit" class="btn btn--primary shadow-premium" style="background: var(--grad-premium); padding: 10px 28px; font-weight: 700;">
                    <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2.5" style="margin-right:6px;"><path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"/><polyline points="17 21 17 13 7 13 7 21"/></svg>
                    Guardar Configuración
                </button>
            </div>
        </div>
    </form>
</div>

<style>
.cs-input {
    width: 100%;
    background: var(--bg-root);
    border: 1px solid var(--border);
    padding: 9px 12px;
    border-radius: 8px;
    color: white;
    font-size: 13px;
    transition: all 0.2s;
    box-sizing: border-box;
}
.cs-input:focus { border-color: #fbbf24; outline: none; box-shadow: 0 0 0 3px rgba(245,158,11,0.15); }
.cs-input option { background: #1a1d27; color: white; }

.toggle-track {
    width: 44px; height: 24px;
    background: rgba(255,255,255,0.1);
    border: 1px solid var(--border);
    border-radius: 12px;
    position: relative;
    transition: all 0.25s;
    cursor: pointer;
}
.toggle-track.active { background: rgba(16,185,129,0.4); border-color: rgba(16,185,129,0.6); }
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
let rowIdx = {{ $campos->count() }};

function syncToggle(cb) {
    const track = cb.closest('label').querySelector('.toggle-track');
    track.classList.toggle('active', cb.checked);
    // Sync value on change so it's submitted correctly
    cb.value = cb.closest('.campo-row').querySelector('input[name$="[campo]"]').value;
}

function updateCount() {
    const count = document.querySelectorAll('.campo-row').length;
    document.getElementById('campo-count').textContent = count + ' campo(s) configurado(s)';
    const empty = document.getElementById('empty-state');
    if (empty) empty.style.display = count > 0 ? 'none' : 'block';
}

function addRow() {
    const idx = rowIdx++;
    const row = document.createElement('div');
    row.className = 'campo-row';
    row.dataset.idx = idx;
    row.style.cssText = 'display:grid; grid-template-columns:1fr 1fr 130px 100px 48px; gap:10px; align-items:center; padding:10px 24px; border-bottom:1px solid rgba(255,255,255,0.04); transition:background 0.15s; background:rgba(245,158,11,0.04);';
    row.innerHTML = `
        <div>
            <input type="text" name="campos[${idx}][campo]" placeholder="Ej: LimiteCredito"
                   class="cs-input" style="font-family:monospace; font-size:13px;" required>
            <input type="hidden" name="campos[${idx}][orden]" value="${idx}">
        </div>
        <div>
            <input type="text" name="campos[${idx}][label]" placeholder="Ej: Límite de Crédito"
                   class="cs-input" required>
        </div>
        <div>
            <select name="campos[${idx}][tipo]" class="cs-input">
                <option value="text">Texto</option>
                <option value="number">Entero</option>
                <option value="decimal">Decimal</option>
                <option value="date">Fecha</option>
                <option value="boolean">Booleano</option>
            </select>
        </div>
        <div style="display:flex; justify-content:center;">
            <label class="toggle-switch">
                <input type="checkbox" name="editables[]" value="" style="display:none;" onchange="syncToggle(this)">
                <div class="toggle-track"><div class="toggle-thumb"></div></div>
            </label>
        </div>
        <div style="display:flex; justify-content:center;">
            <button type="button" onclick="removeRow(this)" title="Eliminar campo"
                    style="width:32px; height:32px; background:rgba(239,68,68,0.1); border:1px solid rgba(239,68,68,0.25); border-radius:8px; color:#f87171; cursor:pointer; display:flex; align-items:center; justify-content:center; transition:all .2s;"
                    onmouseover="this.style.background='rgba(239,68,68,0.2)'"
                    onmouseout="this.style.background='rgba(239,68,68,0.1)'">
                <svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="3 6 5 6 21 6"/><path d="M19 6l-1 14H6L5 6"/><path d="M10 11v6M14 11v6"/></svg>
            </button>
        </div>
    `;
    row.addEventListener('mouseover', () => row.style.background = 'rgba(255,255,255,0.02)');
    row.addEventListener('mouseout', () => row.style.background = 'transparent');
    document.getElementById('campos-list').appendChild(row);
    row.querySelector('input[name$="[campo]"]').focus();
    updateCount();
}

function removeRow(btn) {
    btn.closest('.campo-row').remove();
    updateCount();
}

// Sync checkbox values before submit (ensure value matches campo name)
document.getElementById('form-campos-suc').addEventListener('submit', function() {
    document.querySelectorAll('.campo-row').forEach((row, i) => {
        const campoInput = row.querySelector('input[name$="[campo]"]');
        const cb = row.querySelector('input[type=checkbox]');
        if (cb && campoInput) cb.value = campoInput.value;
        // Re-index orden
        const ordenInput = row.querySelector('input[name$="[orden]"]');
        if (ordenInput) ordenInput.value = i;
    });
});

// Init
updateCount();
</script>
@endpush

@endsection
