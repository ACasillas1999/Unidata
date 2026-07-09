@extends('layouts.app')

@section('title', 'Nuevo Cliente')
@section('breadcrumb', 'Nuevo Cliente')

@section('content')

<div style="width: 100%; margin: 0 auto 30px auto;">

    {{-- Header --}}
    <div class="page-header shadow-premium" style="margin-bottom: 24px; padding: 20px 30px; background: var(--grad-surface); border-radius: var(--radius-xl); border: 1px solid var(--glass-border); display: flex; justify-content: space-between; align-items: center;">
        <div style="display: flex; gap: 20px; align-items: center;">
            <div style="width: 48px; height: 48px; background: rgba(99,102,241,0.15); border: 1px solid rgba(99,102,241,0.3); color: #818cf8; border-radius: 12px; display: flex; align-items: center; justify-content: center;">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" width="24" height="24"><path d="M12 5v14M5 12h14"/></svg>
            </div>
            <div>
                <h1 class="page-title" style="margin:0; font-size: 24px;">Nuevo Cliente</h1>
                <p style="color: var(--text-secondary); margin:4px 0 0; font-size: 14px;">Se creará en <strong style="color: #34d399;">todas las sucursales activas</strong> automáticamente</p>
            </div>
        </div>
        <a href="{{ route('clientes.index') }}" class="btn btn--ghost" style="border: 1px solid var(--border); padding: 10px 20px;">
            <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2.5" style="margin-right: 8px;"><path d="M19 12H5M12 19l-7-7 7-7"/></svg>
            Volver
        </a>
    </div>

    @if($errors->any())
        <div style="background: rgba(239,68,68,0.1); border: 1px solid rgba(239,68,68,0.3); border-radius: 10px; padding: 14px 18px; margin-bottom: 20px;">
            <ul style="margin: 0; padding-left: 20px; color: var(--rose); font-size: 13px;">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form action="{{ route('clientes.store') }}" method="POST" id="form-crear-cliente">
        @csrf

        <div class="glass-card shadow-premium" style="padding: 40px; border-radius: 20px;">

            {{-- SECCIÓN: Identificación --}}
            <div class="section-card">
                <h3 style="margin: 0 0 24px; font-size: 14px; font-weight: 800; text-transform: uppercase; letter-spacing: 0.08em; color: #818cf8; display: flex; align-items: center; gap: 8px;">
                    <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2.5"><rect x="2" y="5" width="20" height="14" rx="2"/><line x1="2" y1="10" x2="22" y2="10"/></svg>
                    Identificación
                </h3>
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); gap: 20px;">
                    @if($campos->where('campo','rfc')->first()!== null)
                    <div>
                        <label class="modal-label">RFC *</label>
                        <input type="text" name="rfc" id="rfc" value="{{ old('rfc') }}" maxlength="14" class="modal-input" style="text-transform:uppercase;" required>
                    </div>
                    @endif
                    @if($campos->where('campo','razon_social')->first()!== null)
                    <div style="grid-column: span 2;">
                        <label class="modal-label">Razón Social *</label>
                        <input type="text" name="razon_social" value="{{ old('razon_social') }}" maxlength="255" class="modal-input" required>
                    </div>
                    @endif
                    @if($campos->where('campo','status')->first()!== null)
                    <div>
                        <label class="modal-label">Estatus *</label>
                        <select name="status" class="modal-input">
                            <option value="A" {{ old('status','A') === 'A' ? 'selected' : '' }}>Activo</option>
                            <option value="I" {{ old('status') === 'I' ? 'selected' : '' }}>Inactivo</option>
                        </select>
                    </div>
                    @endif
                    @if($campos->where('campo','regimen_fiscal')->first()!== null)
                    <div>
                        <label class="modal-label">Régimen Fiscal</label>
                        <select name="regimen_fiscal" class="modal-input">
                            <option value="">Seleccione Régimen Fiscal...</option>
                            @foreach($catalogs['regimenes'] as $key => $desc)
                                <option value="{{ $key }}" {{ old('regimen_fiscal') == $key ? 'selected' : '' }}>
                                    {{ $key }} - {{ $desc }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    @endif
                    @if($campos->where('campo','fecha_alta')->first()!== null)
                    <div>
                        <label class="modal-label">Fecha de Alta</label>
                        <input type="date" name="fecha_alta" value="{{ old('fecha_alta', now()->toDateString()) }}" class="modal-input">
                    </div>
                    @endif
                    @if($campos->where('campo','giro_principal')->first()!== null)
                    <div>
                        <label class="modal-label">Giro Principal</label>
                        <select name="giro_principal" class="modal-input">
                            <option value="">Seleccione Giro Principal...</option>
                            @foreach($catalogs['giros'] as $key => $desc)
                                <option value="{{ $key }}" {{ old('giro_principal') == $key ? 'selected' : '' }}>
                                    {{ $desc }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    @endif
                </div>
            </div>

            {{-- SECCIÓN: Dirección --}}
            @if(collect(['calle','exterior','interior','colonia','cod_postal','ciudad','municipio'])->contains(fn($f) => $campos->where('campo',$f)->first()!== null))
            <div class="section-card">
                <h3 style="margin: 0 0 24px; font-size: 14px; font-weight: 800; text-transform: uppercase; letter-spacing: 0.08em; color: #818cf8; display: flex; align-items: center; gap: 8px;">
                    <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/></svg>
                    Dirección
                </h3>
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px;">
                    @if($campos->where('campo','calle')->first()!== null)
                    <div style="grid-column: span 2;">
                        <label class="modal-label">Calle</label>
                        <input type="text" name="calle" value="{{ old('calle') }}" maxlength="70" class="modal-input">
                    </div>
                    @endif
                    @if($campos->where('campo','exterior')->first()!== null)
                    <div><label class="modal-label">No. Exterior</label><input type="text" name="exterior" value="{{ old('exterior') }}" maxlength="10" class="modal-input"></div>
                    @endif
                    @if($campos->where('campo','interior')->first()!== null)
                    <div><label class="modal-label">No. Interior</label><input type="text" name="interior" value="{{ old('interior') }}" maxlength="10" class="modal-input"></div>
                    @endif
                    @if($campos->where('campo','colonia')->first()!== null)
                    <div><label class="modal-label">Colonia</label><input type="text" name="colonia" value="{{ old('colonia') }}" maxlength="60" class="modal-input"></div>
                    @endif
                    @if($campos->where('campo','cod_postal')->first()!== null)
                    <div><label class="modal-label">Código Postal</label><input type="number" name="cod_postal" value="{{ old('cod_postal') }}" class="modal-input"></div>
                    @endif
                    @if($campos->where('campo','ciudad')->first()!== null)
                    <div>
                        <label class="modal-label">País (Ubicación)</label>
                        <select name="ubicacion" id="pais-select" class="modal-input">
                            <option value="">Seleccione País...</option>
                            @foreach($catalogs['paises'] as $key => $name)
                                <option value="{{ $key }}" {{ old('ubicacion') == $key ? 'selected' : '' }}>
                                    {{ $name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="modal-label">Estado</label>
                        <select id="estado-select" class="modal-input" disabled>
                            <option value="">Seleccione Estado...</option>
                            @foreach($catalogs['estados'] as $key => $name)
                                <option value="{{ $key }}">{{ $name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="modal-label">Ciudad</label>
                        <select name="ciudad" id="ciudad-select" class="modal-input" disabled>
                            <option value="">Seleccione Ciudad...</option>
                            @foreach($catalogs['ciudades'] as $key => $c)
                                <option value="{{ $key }}" {{ old('ciudad') == $key ? 'selected' : '' }}>
                                    {{ $c['nombre'] }} ({{ $key }})
                                </option>
                            @endforeach
                        </select>
                    </div>
                    @endif
                    @if($campos->where('campo','municipio')->first()!== null)
                    <div>
                        <label class="modal-label">Municipio</label>
                        <input type="text" name="municipio" id="municipio-input" value="{{ old('municipio') }}" maxlength="30" class="modal-input">
                    </div>
                    @endif
                </div>
            </div>
            @endif

            {{-- SECCIÓN: Contacto --}}
            @if(collect(['telefono1','telefono2','telefono3','fax','representante'])->contains(fn($f) => $campos->where('campo',$f)->first()!== null))
            <div class="section-card">
                <h3 style="margin: 0 0 24px; font-size: 14px; font-weight: 800; text-transform: uppercase; letter-spacing: 0.08em; color: #818cf8; display: flex; align-items: center; gap: 8px;">
                    <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07A19.5 19.5 0 0 1 4.69 12 19.79 19.79 0 0 1 1.61 3.29 2 2 0 0 1 3.58 1h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9a16 16 0 0 0 6.91 6.91l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"/></svg>
                    Contacto
                </h3>
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px;">
                    @if($campos->where('campo','telefono1')->first()!== null)
                    <div><label class="modal-label">Teléfono 1</label><input type="text" name="telefono1" value="{{ old('telefono1') }}" maxlength="15" class="modal-input"></div>
                    @endif
                    @if($campos->where('campo','telefono2')->first()!== null)
                    <div><label class="modal-label">Teléfono 2</label><input type="text" name="telefono2" value="{{ old('telefono2') }}" maxlength="15" class="modal-input"></div>
                    @endif
                    @if($campos->where('campo','telefono3')->first()!== null)
                    <div><label class="modal-label">Teléfono 3</label><input type="text" name="telefono3" value="{{ old('telefono3') }}" maxlength="15" class="modal-input"></div>
                    @endif
                    @if($campos->where('campo','fax')->first()!== null)
                    <div><label class="modal-label">Fax</label><input type="text" name="fax" value="{{ old('fax') }}" maxlength="15" class="modal-input"></div>
                    @endif
                    @if($campos->where('campo','representante')->first()!== null)
                    <div><label class="modal-label">Representante</label><input type="text" name="representante" value="{{ old('representante') }}" maxlength="60" class="modal-input"></div>
                    @endif
                </div>
            </div>
            @endif

            {{-- SECCIÓN: Crédito --}}
            @if(collect(['vendedor','dias_pago','condicion_pago','dias_credito','limite_credito','cta_contable','saldo_actual'])->contains(fn($f) => $campos->where('campo',$f)->first() !== null))
            <div class="section-card">
                <h3 style="margin: 0 0 24px; font-size: 14px; font-weight: 800; text-transform: uppercase; letter-spacing: 0.08em; color: #818cf8; display: flex; align-items: center; gap: 8px;">
                    <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="12" y1="1" x2="12" y2="23"/><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg>
                    Crédito y Condiciones
                </h3>
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px;">
                    @if($campos->where('campo','vendedor')->first() !== null)
                    <div><label class="modal-label">Vendedor</label><input type="text" name="vendedor" value="{{ old('vendedor') }}" maxlength="6" class="modal-input"></div>
                    @endif
                    @if($campos->where('campo','dias_pago')->first() !== null)
                    <div><label class="modal-label">Días de Pago</label><input type="text" name="dias_pago" value="{{ old('dias_pago') }}" maxlength="6" class="modal-input"></div>
                    @endif
                    @if($campos->where('campo','condicion_pago')->first() !== null)
                    <div><label class="modal-label">Condición de Pago</label><input type="text" name="condicion_pago" value="{{ old('condicion_pago') }}" maxlength="4" class="modal-input"></div>
                    @endif
                    @if($campos->where('campo','dias_credito')->first() !== null)
                    <div><label class="modal-label">Días de Crédito</label><input type="number" name="dias_credito" value="{{ old('dias_credito', 0) }}" class="modal-input"></div>
                    @endif
                    @if($campos->where('campo','limite_credito')->first() !== null)
                    <div><label class="modal-label">Límite de Crédito</label><input type="number" step="0.01" name="limite_credito" value="{{ old('limite_credito', 0) }}" class="modal-input"></div>
                    @endif
                    @if($campos->where('campo','saldo_actual')->first() !== null)
                    <div><label class="modal-label">Saldo Actual</label><input type="number" step="0.01" name="saldo_actual" value="{{ old('saldo_actual', 0) }}" class="modal-input"></div>
                    @endif
                    @if($campos->where('campo','cta_contable')->first() !== null)
                    <div><label class="modal-label">Cuenta Contable</label><input type="text" name="cta_contable" value="{{ old('cta_contable') }}" maxlength="14" class="modal-input"></div>
                    @endif
                </div>
            </div>
            @endif

            {{-- SECCIÓN: Control / Opciones Adicionales --}}
            @if(collect(['documentos','codigo_contpaq','modificar_fpmpfac','id_opcion_bloqueo','sync','prefijo_descripcion','id_sugar'])->contains(fn($f) => $campos->where('campo',$f)->first() !== null))
            <div class="section-card">
                <h3 style="margin: 0 0 24px; font-size: 14px; font-weight: 800; text-transform: uppercase; letter-spacing: 0.08em; color: #818cf8; display: flex; align-items: center; gap: 8px;">
                    <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
                    Control y Opciones Adicionales
                </h3>
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px;">
                    @if($campos->where('campo','documentos')->first() !== null)
                    <div style="grid-column: span 2;"><label class="modal-label">Documentos</label><input type="text" name="documentos" value="{{ old('documentos') }}" maxlength="255" class="modal-input"></div>
                    @endif
                    @if($campos->where('campo','codigo_contpaq')->first() !== null)
                    <div><label class="modal-label">Código Contpaq</label><input type="number" name="codigo_contpaq" value="{{ old('codigo_contpaq', 0) }}" class="modal-input"></div>
                    @endif
                    @if($campos->where('campo','modificar_fpmpfac')->first() !== null)
                    <div><label class="modal-label">Modificar FPMPFac</label><input type="number" name="modificar_fpmpfac" value="{{ old('modificar_fpmpfac', 0) }}" class="modal-input"></div>
                    @endif
                    @if($campos->where('campo','id_opcion_bloqueo')->first() !== null)
                    <div>
                        <label class="modal-label">Opción Bloqueo</label>
                        <select name="id_opcion_bloqueo" class="modal-input">
                            <option value="0" {{ old('id_opcion_bloqueo', 0) == 0 ? 'selected' : '' }}>Ninguno</option>
                            @foreach($catalogs['bloqueos'] as $key => $desc)
                                <option value="{{ $key }}" {{ old('id_opcion_bloqueo') == $key ? 'selected' : '' }}>
                                    {{ $desc }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    @endif
                    @if($campos->where('campo','clasificacion')->first() !== null)
                    <div>
                        <label class="modal-label">Clasificación</label>
                        <select name="clasificacion" class="modal-input">
                            <option value="">Seleccione Clasificación...</option>
                            @foreach($catalogs['clasificaciones'] as $key => $desc)
                                <option value="{{ $key }}" {{ old('clasificacion') == $key ? 'selected' : '' }}>
                                    {{ $desc }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    @endif
                    @if($campos->where('campo','sync')->first() !== null)
                    <div><label class="modal-label">Sync</label><input type="number" name="sync" value="{{ old('sync', 0) }}" class="modal-input"></div>
                    @endif
                    @if($campos->where('campo','prefijo_descripcion')->first() !== null)
                    <div><label class="modal-label">Prefijo Descripción</label><input type="number" name="prefijo_descripcion" value="{{ old('prefijo_descripcion', 0) }}" class="modal-input"></div>
                    @endif
                    @if($campos->where('campo','id_sugar')->first() !== null)
                    <div><label class="modal-label">ID Sugar</label><input type="text" name="id_sugar" value="{{ old('id_sugar') }}" maxlength="36" class="modal-input"></div>
                    @endif
                </div>
            </div>
            @endif

            {{-- Botones --}}
            <div style="margin-top: 40px; padding-top: 30px; border-top: 1px solid var(--border); display: flex; justify-content: flex-end; gap: 16px;">
                <a href="{{ route('clientes.index') }}" class="btn btn--ghost" style="padding: 12px 24px;">Cancelar</a>
                <button type="submit" class="btn btn--primary shadow-premium" style="background: var(--grad-premium); padding: 14px 48px; font-weight: 800; font-size: 15px;">
                    <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2.5" style="margin-right: 10px;"><path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"/><polyline points="17 21 17 13 7 13 7 21"/><polyline points="7 3 7 8 15 8"/></svg>
                    Crear en Todas las Sucursales
                </button>
            </div>
        </div>
    </form>
</div>

<style>
.modal-label { font-size:11px; font-weight:800; color:var(--text-secondary); display:block; margin-bottom:8px; text-transform: uppercase; letter-spacing: 0.08em; }
.modal-input { width:100%; background:var(--bg-root); border:1px solid var(--border); padding:12px 16px; border-radius:10px; color:white; font-size:14px; transition: all 0.2s; box-sizing: border-box; }
.modal-input:focus { border-color:#818cf8; outline:none; box-shadow:0 0 0 4px rgba(99,102,241,0.15); }
.modal-input option { background: #1a1d27; color: white; }
.section-card { background: rgba(255,255,255,0.02); border: 1px solid var(--border); border-radius: 14px; padding: 28px; margin-bottom: 28px; }
.section-card:last-of-type { margin-bottom: 0; }
</style>

@push('scripts')
<script>
document.getElementById('form-crear-cliente').addEventListener('submit', function() {
    Swal.fire({
        title: 'Creando cliente...',
        text: 'Replicando en todas las sucursales activas.',
        allowOutsideClick: false,
        didOpen: () => Swal.showLoading()
    });
});

const ciudadesMap = @json($catalogs['ciudades']);
const paisSelect = document.getElementById('pais-select');
const estadoSelect = document.getElementById('estado-select');
const ciudadSelect = document.getElementById('ciudad-select');
const municipioInput = document.getElementById('municipio-input');

// Guardar opciones originales para filtrado
const originalStates = Array.from(estadoSelect?.options || []).slice(1);
const originalCities = Array.from(ciudadSelect?.options || []).slice(1);

function updateStates() {
    if (!paisSelect || !estadoSelect) return;
    const selectedPais = paisSelect.value;
    
    estadoSelect.value = '';
    estadoSelect.disabled = !selectedPais;
    if (ciudadSelect) {
        ciudadSelect.value = '';
        ciudadSelect.disabled = true;
    }
    
    if (!selectedPais) return;
    
    const allowedStates = new Set();
    for (const code in ciudadesMap) {
        if (ciudadesMap[code].pais === selectedPais) {
            allowedStates.add(ciudadesMap[code].estado);
        }
    }
    
    estadoSelect.innerHTML = '<option value="">Seleccione Estado...</option>';
    originalStates.forEach(opt => {
        if (allowedStates.has(opt.value)) {
            estadoSelect.appendChild(opt.cloneNode(true));
        }
    });
}

function updateCities() {
    if (!estadoSelect || !ciudadSelect) return;
    const selectedEstado = estadoSelect.value;
    
    ciudadSelect.value = '';
    ciudadSelect.disabled = !selectedEstado;
    
    if (!selectedEstado) return;
    
    const allowedCities = new Set();
    for (const code in ciudadesMap) {
        if (ciudadesMap[code].estado === selectedEstado && ciudadesMap[code].pais === paisSelect.value) {
            allowedCities.add(code);
        }
    }
    
    ciudadSelect.innerHTML = '<option value="">Seleccione Ciudad...</option>';
    originalCities.forEach(opt => {
        if (allowedCities.has(opt.value)) {
            ciudadSelect.appendChild(opt.cloneNode(true));
        }
    });
}

paisSelect?.addEventListener('change', updateStates);
estadoSelect?.addEventListener('change', updateCities);

ciudadSelect?.addEventListener('change', function() {
    const val = this.value;
    if (val && ciudadesMap[val] && municipioInput) {
        municipioInput.value = ciudadesMap[val].nombre.substring(0, 30);
    }
});

// Trigger inicial por si hay old values
if (paisSelect && paisSelect.value) {
    const savedEstado = "{{ old('ciudad') ? (isset($catalogs['ciudades'][old('ciudad')]) ? $catalogs['ciudades'][old('ciudad')]['estado'] : '') : '' }}";
    const savedCiudad = "{{ old('ciudad') }}";
    
    paisSelect.removeEventListener('change', updateStates);
    estadoSelect.removeEventListener('change', updateCities);
    
    const selectedPais = paisSelect.value;
    const allowedStates = new Set();
    for (const code in ciudadesMap) {
        if (ciudadesMap[code].pais === selectedPais) {
            allowedStates.add(ciudadesMap[code].estado);
        }
    }
    estadoSelect.innerHTML = '<option value="">Seleccione Estado...</option>';
    originalStates.forEach(opt => {
        if (allowedStates.has(opt.value)) {
            estadoSelect.appendChild(opt.cloneNode(true));
        }
    });
    estadoSelect.disabled = false;
    estadoSelect.value = savedEstado;
    
    if (savedEstado) {
        const allowedCities = new Set();
        for (const code in ciudadesMap) {
            if (ciudadesMap[code].estado === savedEstado && ciudadesMap[code].pais === selectedPais) {
                allowedCities.add(code);
            }
        }
        ciudadSelect.innerHTML = '<option value="">Seleccione Ciudad...</option>';
        originalCities.forEach(opt => {
            if (allowedCities.has(opt.value)) {
                ciudadSelect.appendChild(opt.cloneNode(true));
            }
        });
        ciudadSelect.disabled = false;
        ciudadSelect.value = savedCiudad;
    }
    
    paisSelect.addEventListener('change', updateStates);
    estadoSelect.addEventListener('change', updateCities);
}
</script>
@endpush

@endsection
