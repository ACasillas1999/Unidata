@extends('layouts.app')

@section('title', 'Configurar Campos de Sincronización')
@section('breadcrumb', 'Configurar Campos de Sincronización')

@section('content')
<style>
    .config-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(240px, 1fr));
        gap: 16px;
        padding: 20px;
    }
    
    .config-item {
        background: rgba(255, 255, 255, 0.03);
        border: 1px solid var(--border);
        border-radius: var(--radius-lg);
        padding: 16px;
        display: flex;
        justify-content: space-between;
        align-items: center;
        transition: all 0.2s;
    }

    .config-item:hover {
        background: rgba(255, 255, 255, 0.05);
        border-color: rgba(139, 92, 246, 0.3);
    }
    
    .config-item.active-item {
        border-color: rgba(16, 185, 129, 0.3);
        background: rgba(16, 185, 129, 0.05);
    }

    /* Toggle Switch */
    .toggle-switch {
        position: relative;
        display: inline-block;
        width: 44px;
        height: 24px;
    }

    .toggle-switch input {
        opacity: 0;
        width: 0;
        height: 0;
    }

    .slider {
        position: absolute;
        cursor: pointer;
        top: 0; left: 0; right: 0; bottom: 0;
        background-color: rgba(255, 255, 255, 0.1);
        transition: .3s;
        border-radius: 34px;
        border: 1px solid var(--border);
    }

    .slider:before {
        position: absolute;
        content: "";
        height: 18px;
        width: 18px;
        left: 2px;
        bottom: 2px;
        background-color: #94a3b8;
        transition: .3s;
        border-radius: 50%;
    }

    input:checked + .slider {
        background-color: var(--emerald-bg);
        border-color: var(--emerald);
    }

    input:checked + .slider:before {
        transform: translateX(20px);
        background-color: var(--emerald);
    }

    input:disabled + .slider {
        opacity: 0.5;
        cursor: not-allowed;
    }
</style>

<div style="flex-shrink: 0; overflow-x: auto;">
    {{-- ── PREMIUM HEADER ────────────────────────────────────────── --}}
    <div class="page-header shadow-premium" style="margin-bottom: 20px; padding: 14px 20px; background: var(--grad-surface); border-radius: var(--radius-xl); border: 1px solid var(--glass-border); position: relative; overflow: hidden; display: flex; justify-content: space-between; align-items: center;">
        <div style="position:absolute; top:-50px; right:-50px; width:150px; height:150px; background:var(--violet); filter:blur(100px); opacity:0.1; pointer-events:none;"></div>
        
        <div class="page-header-content" style="display: flex; gap: 16px; align-items: center; z-index: 1;">
            <div class="page-header-icon shadow-premium" style="background: var(--grad-premium); border: none; color: white;">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"></path>
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                </svg>
            </div>
            <div>
                <h1 class="page-title" style="letter-spacing: -0.01em; margin:0;">Configuración de Campos</h1>
                <p class="page-subtitle" style="color: var(--text-secondary); margin:4px 0 0;">Selecciona qué información de los artículos se copiará a la Matriz</p>
            </div>
        </div>
        <div class="page-header-actions" style="display:flex;gap:12px;align-items:center; z-index: 1;">
            <a href="{{ route('homologacion.index') }}" class="btn btn--ghost btn--sm" style="font-size:11px; border:1px solid var(--border); display:flex; align-items:center; gap:6px; padding:9px 14px;">
                &larr; Volver a la Matriz
            </a>
        </div>
    </div>

    @if(session('success'))
    <div class="alert alert--success shadow-premium" style="margin-bottom:16px; border-left: 4px solid #10b981; padding: 12px 16px; background: rgba(16, 185, 129, 0.1); color: #10b981; border-radius: 6px;">
        <strong>¡Éxito!</strong> {{ session('success') }}
    </div>
    @endif

    <div class="glass-card shadow-premium" style="padding-bottom: 20px;">
        <div style="padding: 16px 20px; border-bottom: 1px solid var(--border); background: rgba(255,255,255,0.02); display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 16px;">
            <div>
                <h2 style="font-size: 14px; font-weight: 800; color: var(--text-primary); margin:0;">Campos a Sincronizar</h2>
                <p style="font-size: 12px; color: var(--text-muted); margin-top: 6px;">
                    Los campos desactivados se limpiarán (NULL) durante la siguiente sincronización.
                </p>
            </div>
            
            <div style="display: flex; gap: 10px; align-items: center;">
                <div style="position: relative;">
                    <input type="text" id="fieldSearch" placeholder="Buscar campo..." 
                        style="background: rgba(255,255,255,0.05); border: 1px solid var(--border); border-radius: 6px; padding: 6px 12px 6px 32px; color: white; font-size: 12px; width: 180px;">
                    <svg style="position: absolute; left: 10px; top: 50%; transform: translateY(-50%); color: var(--text-muted);" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line></svg>
                </div>
                
                <div class="btn-group" style="display: flex; border: 1px solid var(--border); border-radius: 6px; overflow: hidden;">
                    <button type="button" onclick="selectAll(true)" class="btn btn--sm btn--ghost" style="border: none; font-size: 10px; padding: 6px 12px; background: rgba(255,255,255,0.03);">Todos</button>
                    <button type="button" onclick="selectAll(false)" class="btn btn--sm btn--ghost" style="border: none; border-left: 1px solid var(--border); font-size: 10px; padding: 6px 12px; background: rgba(255,255,255,0.03);">Ninguno</button>
                </div>
            </div>
        </div>

        <form action="{{ route('homologacion.campos.update') }}" method="POST" id="camposForm">
            @csrf
            <div class="config-grid" id="fieldsContainer">
                @foreach($campos as $campo)
                <label class="config-item {{ $campo->is_active ? 'active-item' : '' }}" 
                    data-field="{{ strtolower($campo->campo) }}"
                    style="cursor: pointer;" 
                    onclick="handleToggle(this, event)">
                    <div style="display: flex; flex-direction: column;">
                        <span class="field-name" style="font-size: 13px; font-weight: 600; font-family: 'JetBrains Mono', monospace; color: {{ $campo->is_active ? 'var(--emerald)' : 'var(--text-primary)' }};">
                            {{ $campo->campo }}
                        </span>
                        @if($campo->is_required)
                            <span style="font-size: 10px; color: var(--rose); font-weight: 700; margin-top: 4px;">Requerido por el sistema</span>
                        @endif
                    </div>
                    <div class="toggle-switch">
                        <input type="checkbox" name="campos[]" value="{{ $campo->campo }}" 
                            class="campo-checkbox"
                            {{ $campo->is_active ? 'checked' : '' }} 
                            {{ $campo->is_required ? 'data-required="true" onclick="return false;" tabindex="-1"' : '' }}>
                        <span class="slider"></span>
                    </div>
                </label>
                @endforeach
            </div>

            <div style="padding: 20px; border-top: 1px solid var(--border); display: flex; justify-content: flex-end; gap: 12px;">
                <a href="{{ route('homologacion.index') }}" class="btn btn--ghost shadow-premium" style="font-size: 13px; padding: 10px 20px; border: 1px solid var(--border);">
                    Cancelar
                </a>
                <button type="submit" class="btn btn--primary shadow-premium" style="font-size: 13px; padding: 10px 24px; background: var(--violet); border: none; color: white; display: flex; align-items: center; gap: 8px;">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="16" height="16"><path stroke-linecap="round" stroke-linejoin="round" d="M8 7H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-3m-1 4l-3 3m0 0l-3-3m3 3V4"></path></svg>
                    Guardar Configuración
                </button>
            </div>
        </form>
    </div>
</div>

<script>
    function handleToggle(label, event) {
        const input = label.querySelector('input');
        
        // Si el campo es requerido, no hacer nada
        if (input.dataset.required === 'true') {
            event.preventDefault();
            return;
        }

        // Si el clic fue directamente en el input, el navegador ya cambió su estado.
        // Solo necesitamos sincronizar la clase visual si el clic fue en el label (fuera del input).
        if (event.target !== input) {
            input.checked = !input.checked;
        }
        
        updateLabelStyle(label, input.checked);
    }

    function updateLabelStyle(label, checked) {
        const nameSpan = label.querySelector('.field-name');
        if (checked) {
            label.classList.add('active-item');
            nameSpan.style.color = 'var(--emerald)';
        } else {
            label.classList.remove('active-item');
            nameSpan.style.color = 'var(--text-primary)';
        }
    }

    function selectAll(state) {
        document.querySelectorAll('.campo-checkbox').forEach(cb => {
            if (cb.dataset.required !== 'true') {
                cb.checked = state;
                updateLabelStyle(cb.closest('.config-item'), state);
            }
        });
    }

    // Buscador en tiempo real
    document.getElementById('fieldSearch').addEventListener('input', function(e) {
        const term = e.target.value.toLowerCase();
        document.querySelectorAll('.config-item').forEach(item => {
            const field = item.dataset.field;
            if (field.includes(term)) {
                item.style.display = 'flex';
            } else {
                item.style.display = 'none';
            }
        });
    });
</script>
@endsection
