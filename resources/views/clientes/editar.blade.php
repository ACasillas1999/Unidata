@extends('layouts.app')

@section('title', 'Editar Cliente — ' . $cliente->rfc)
@section('breadcrumb', 'Editar Cliente')

@section('content')

<div style="width: 100%; margin: 0 auto 30px auto;">

    {{-- Header --}}
    <div class="page-header shadow-premium" style="margin-bottom: 24px; padding: 20px 30px; background: var(--grad-surface); border-radius: var(--radius-xl); border: 1px solid var(--glass-border); display: flex; justify-content: space-between; align-items: center;">
        <div style="display: flex; gap: 20px; align-items: center;">
            <div style="width: 48px; height: 48px; background: rgba(99,102,241,0.15); border: 1px solid rgba(99,102,241,0.3); color: #818cf8; border-radius: 12px; display: flex; align-items: center; justify-content: center;">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" width="24" height="24"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
            </div>
            <div>
                <h1 class="page-title" style="margin:0; font-size: 22px;">{{ $cliente->razon_social }}</h1>
                <p style="color: var(--text-secondary); margin:4px 0 0; font-size: 13px;">
                    RFC: <span style="font-family:monospace; color:#a78bfa">{{ $cliente->rfc }}</span>
                    &nbsp;|&nbsp; ID Global: <span style="font-family:monospace; color:#34d399">#{{ $cliente->id_global }}</span>
                </p>
            </div>
        </div>
        <a href="{{ route('clientes.index') }}" class="btn btn--ghost" style="border: 1px solid var(--border); padding: 10px 20px;">
            <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2.5" style="margin-right: 8px;"><path d="M19 12H5M12 19l-7-7 7-7"/></svg>
            Volver
        </a>
    </div>

    @if(session('success'))
        <div style="background: rgba(16,185,129,0.1); border: 1px solid rgba(16,185,129,0.3); border-radius: 10px; padding: 14px 18px; margin-bottom: 20px; color: #34d399; font-size: 14px;">
            ✅ {{ session('success') }}
        </div>
    @endif

    @if(session('warning'))
        <div style="background: rgba(251,191,36,0.1); border: 1px solid rgba(251,191,36,0.3); border-radius: 10px; padding: 14px 18px; margin-bottom: 20px; color: #fbbf24; font-size: 14px; display: flex; align-items: center; gap: 10px;">
            <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2.5" style="flex-shrink:0"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>
            <span>{!! session('warning') !!}</span>
        </div>
    @endif

    @if($errors->any())
        <div style="background: rgba(239,68,68,0.1); border: 1px solid rgba(239,68,68,0.3); border-radius: 10px; padding: 14px 18px; margin-bottom: 20px;">
            <ul style="margin: 0; padding-left: 20px; color: var(--rose); font-size: 13px;">
                @foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach
            </ul>
        </div>
    @endif

    <div style="display: grid; grid-template-columns: 1fr 320px; gap: 20px; align-items: start;">

        {{-- FORMULARIO --}}
        <div>
            <form action="{{ route('clientes.update', $cliente->rfc) }}" method="POST" id="form-editar-cliente">
                @csrf
                @method('PUT')

                <div class="glass-card shadow-premium" style="padding: 36px; border-radius: 20px;">

                    {{-- Identificación --}}
                    <div class="section-card">
                        <h3 class="section-title">
                            <svg viewBox="0 0 24 24" width="15" height="15" fill="none" stroke="currentColor" stroke-width="2.5"><rect x="2" y="5" width="20" height="14" rx="2"/><line x1="2" y1="10" x2="22" y2="10"/></svg>
                            Identificación
                        </h3>
                        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 18px;">
                            <div>
                                <label class="modal-label">RFC (no editable)</label>
                                <input type="text" value="{{ $cliente->rfc }}" class="modal-input" readonly style="opacity: 0.5; cursor: not-allowed;">
                            </div>
                            @if($campos->where('campo','razon_social')->first()!== null)
                            <div style="grid-column: span 2;">
                                <label class="modal-label">Razón Social *</label>
                                <input type="text" name="razon_social" value="{{ old('razon_social', $cliente->razon_social) }}" {{ !($campos->where('campo','razon_social')->first()?->show_in_edit ?? false) ? 'readonly style="opacity:0.6; cursor:not-allowed;" tabindex="-1"' : '' }} maxlength="255" class="modal-input" required>
                            </div>
                            @endif
                            @if($campos->where('campo','status')->first()!== null)
                            <div>
                                <label class="modal-label">Estatus *</label>
                                <select name="status" class="modal-input" {{ !($campos->where('campo','status')->first()?->show_in_edit ?? false) ? 'style="pointer-events:none; opacity:0.6;" tabindex="-1"' : '' }} {{ !($campos->where('campo','status')->first()?->show_in_edit ?? false) ? 'style="pointer-events:none; opacity:0.6;" tabindex="-1"' : '' }} >
                                    <option value="A" {{ old('status', $cliente->status) === 'A' ? 'selected' : '' }}>Activo</option>
                                    <option value="I" {{ old('status', $cliente->status) === 'I' ? 'selected' : '' }}>Inactivo</option>
                                </select>
                            </div>
                            @endif
                            @if($campos->where('campo','regimen_fiscal')->first()!== null)
                            <div><label class="modal-label">Régimen Fiscal</label><input type="text" name="regimen_fiscal" value="{{ old('regimen_fiscal', $cliente->regimen_fiscal) }}" {{ !($campos->where('campo','regimen_fiscal')->first()?->show_in_edit ?? false) ? 'readonly style="opacity:0.6; cursor:not-allowed;" tabindex="-1"' : '' }} maxlength="3" class="modal-input"></div>
                            @endif
                            @if($campos->where('campo','fecha_alta')->first()!== null)
                            <div><label class="modal-label">Fecha de Alta</label><input type="date" name="fecha_alta" value="{{ old('fecha_alta', $cliente->fecha_alta?->format('Y-m-d')) }}" class="modal-input"></div>
                            @endif
                        </div>
                    </div>

                    {{-- Dirección --}}
                    @if(collect(['calle','exterior','interior','colonia','cod_postal','ciudad','municipio'])->contains(fn($f) => $campos->where('campo',$f)->first()!== null))
                    <div class="section-card">
                        <h3 class="section-title">
                            <svg viewBox="0 0 24 24" width="15" height="15" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/></svg>
                            Dirección
                        </h3>
                        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 18px;">
                            @if($campos->where('campo','calle')->first()!== null)
                            <div style="grid-column: span 2;"><label class="modal-label">Calle</label><input type="text" name="calle" value="{{ old('calle', $cliente->calle) }}" {{ !($campos->where('campo','calle')->first()?->show_in_edit ?? false) ? 'readonly style="opacity:0.6; cursor:not-allowed;" tabindex="-1"' : '' }} maxlength="70" class="modal-input"></div>
                            @endif
                            @if($campos->where('campo','exterior')->first()!== null)
                            <div><label class="modal-label">No. Exterior</label><input type="text" name="exterior" value="{{ old('exterior', $cliente->exterior) }}" {{ !($campos->where('campo','exterior')->first()?->show_in_edit ?? false) ? 'readonly style="opacity:0.6; cursor:not-allowed;" tabindex="-1"' : '' }} maxlength="10" class="modal-input"></div>
                            @endif
                            @if($campos->where('campo','interior')->first()!== null)
                            <div><label class="modal-label">No. Interior</label><input type="text" name="interior" value="{{ old('interior', $cliente->interior) }}" {{ !($campos->where('campo','interior')->first()?->show_in_edit ?? false) ? 'readonly style="opacity:0.6; cursor:not-allowed;" tabindex="-1"' : '' }} maxlength="10" class="modal-input"></div>
                            @endif
                            @if($campos->where('campo','colonia')->first()!== null)
                            <div><label class="modal-label">Colonia</label><input type="text" name="colonia" value="{{ old('colonia', $cliente->colonia) }}" {{ !($campos->where('campo','colonia')->first()?->show_in_edit ?? false) ? 'readonly style="opacity:0.6; cursor:not-allowed;" tabindex="-1"' : '' }} maxlength="60" class="modal-input"></div>
                            @endif
                            @if($campos->where('campo','cod_postal')->first()!== null)
                            <div><label class="modal-label">Código Postal</label><input type="number" name="cod_postal" value="{{ old('cod_postal', $cliente->cod_postal) }}" {{ !($campos->where('campo','cod_postal')->first()?->show_in_edit ?? false) ? 'readonly style="opacity:0.6; cursor:not-allowed;" tabindex="-1"' : '' }} class="modal-input"></div>
                            @endif
                            @if($campos->where('campo','ciudad')->first()!== null)
                            <div><label class="modal-label">Ciudad</label><input type="text" name="ciudad" value="{{ old('ciudad', $cliente->ciudad) }}" {{ !($campos->where('campo','ciudad')->first()?->show_in_edit ?? false) ? 'readonly style="opacity:0.6; cursor:not-allowed;" tabindex="-1"' : '' }} maxlength="4" class="modal-input"></div>
                            @endif
                            @if($campos->where('campo','municipio')->first()!== null)
                            <div><label class="modal-label">Municipio</label><input type="text" name="municipio" value="{{ old('municipio', $cliente->municipio) }}" {{ !($campos->where('campo','municipio')->first()?->show_in_edit ?? false) ? 'readonly style="opacity:0.6; cursor:not-allowed;" tabindex="-1"' : '' }} maxlength="30" class="modal-input"></div>
                            @endif
                        </div>
                    </div>
                    @endif

                    {{-- Contacto --}}
                    @if(collect(['telefono1','telefono2','telefono3','fax','representante'])->contains(fn($f) => $campos->where('campo',$f)->first()!== null))
                    <div class="section-card">
                        <h3 class="section-title">
                            <svg viewBox="0 0 24 24" width="15" height="15" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07A19.5 19.5 0 0 1 4.69 12 19.79 19.79 0 0 1 1.61 3.29 2 2 0 0 1 3.58 1h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9a16 16 0 0 0 6.91 6.91l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"/></svg>
                            Contacto
                        </h3>
                        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 18px;">
                            @if($campos->where('campo','telefono1')->first()!== null)<div><label class="modal-label">Teléfono 1</label><input type="text" name="telefono1" value="{{ old('telefono1', $cliente->telefono1) }}" {{ !($campos->where('campo','telefono1')->first()?->show_in_edit ?? false) ? 'readonly style="opacity:0.6; cursor:not-allowed;" tabindex="-1"' : '' }} maxlength="15" class="modal-input"></div>@endif
                            @if($campos->where('campo','telefono2')->first()!== null)<div><label class="modal-label">Teléfono 2</label><input type="text" name="telefono2" value="{{ old('telefono2', $cliente->telefono2) }}" {{ !($campos->where('campo','telefono2')->first()?->show_in_edit ?? false) ? 'readonly style="opacity:0.6; cursor:not-allowed;" tabindex="-1"' : '' }} maxlength="15" class="modal-input"></div>@endif
                            @if($campos->where('campo','fax')->first()!== null)<div><label class="modal-label">Fax</label><input type="text" name="fax" value="{{ old('fax', $cliente->fax) }}" {{ !($campos->where('campo','fax')->first()?->show_in_edit ?? false) ? 'readonly style="opacity:0.6; cursor:not-allowed;" tabindex="-1"' : '' }} maxlength="15" class="modal-input"></div>@endif
                            @if($campos->where('campo','representante')->first()!== null)<div><label class="modal-label">Representante</label><input type="text" name="representante" value="{{ old('representante', $cliente->representante) }}" {{ !($campos->where('campo','representante')->first()?->show_in_edit ?? false) ? 'readonly style="opacity:0.6; cursor:not-allowed;" tabindex="-1"' : '' }} maxlength="60" class="modal-input"></div>@endif
                        </div>
                    </div>
                    @endif

                    {{-- Crédito --}}
                    @if(collect(['vendedor','dias_pago','condicion_pago','dias_credito','limite_credito','cta_contable','saldo_actual'])->contains(fn($f) => $campos->where('campo',$f)->first() !== null))
                    <div class="section-card">
                        <h3 class="section-title">
                            <svg viewBox="0 0 24 24" width="15" height="15" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="12" y1="1" x2="12" y2="23"/><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg>
                            Crédito y Condiciones
                        </h3>
                        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 18px;">
                            @if($campos->where('campo','vendedor')->first() !== null)<div><label class="modal-label">Vendedor</label><input type="text" name="vendedor" value="{{ old('vendedor', $cliente->vendedor) }}" {{ !($campos->where('campo','vendedor')->first()?->show_in_edit ?? false) ? 'readonly style="opacity:0.6; cursor:not-allowed;" tabindex="-1"' : '' }} maxlength="6" class="modal-input"></div>@endif
                            @if($campos->where('campo','dias_pago')->first() !== null)<div><label class="modal-label">Días de Pago</label><input type="text" name="dias_pago" value="{{ old('dias_pago', $cliente->dias_pago) }}" {{ !($campos->where('campo','dias_pago')->first()?->show_in_edit ?? false) ? 'readonly style="opacity:0.6; cursor:not-allowed;" tabindex="-1"' : '' }} maxlength="6" class="modal-input"></div>@endif
                            @if($campos->where('campo','condicion_pago')->first() !== null)<div><label class="modal-label">Condición de Pago</label><input type="text" name="condicion_pago" value="{{ old('condicion_pago', $cliente->condicion_pago) }}" {{ !($campos->where('campo','condicion_pago')->first()?->show_in_edit ?? false) ? 'readonly style="opacity:0.6; cursor:not-allowed;" tabindex="-1"' : '' }} maxlength="4" class="modal-input"></div>@endif
                            @if($campos->where('campo','dias_credito')->first() !== null)<div><label class="modal-label">Días de Crédito</label><input type="number" name="dias_credito" value="{{ old('dias_credito', $cliente->dias_credito) }}" {{ !($campos->where('campo','dias_credito')->first()?->show_in_edit ?? false) ? 'readonly style="opacity:0.6; cursor:not-allowed;" tabindex="-1"' : '' }} class="modal-input"></div>@endif
                            @if($campos->where('campo','limite_credito')->first() !== null)<div><label class="modal-label">Límite de Crédito</label><input type="number" step="0.01" name="limite_credito" value="{{ old('limite_credito', $cliente->limite_credito) }}" {{ !($campos->where('campo','limite_credito')->first()?->show_in_edit ?? false) ? 'readonly style="opacity:0.6; cursor:not-allowed;" tabindex="-1"' : '' }} class="modal-input"></div>@endif
                            @if($campos->where('campo','saldo_actual')->first() !== null)<div><label class="modal-label">Saldo Actual</label><input type="number" step="0.01" name="saldo_actual" value="{{ old('saldo_actual', $cliente->saldo_actual) }}" {{ !($campos->where('campo','saldo_actual')->first()?->show_in_edit ?? false) ? 'readonly style="opacity:0.6; cursor:not-allowed;" tabindex="-1"' : '' }} class="modal-input"></div>@endif
                            @if($campos->where('campo','cta_contable')->first() !== null)<div><label class="modal-label">Cuenta Contable</label><input type="text" name="cta_contable" value="{{ old('cta_contable', $cliente->cta_contable) }}" {{ !($campos->where('campo','cta_contable')->first()?->show_in_edit ?? false) ? 'readonly style="opacity:0.6; cursor:not-allowed;" tabindex="-1"' : '' }} maxlength="14" class="modal-input"></div>@endif
                        </div>
                    </div>
                    @endif

                    {{-- Control / Opciones Adicionales --}}
                    @if(collect(['documentos','codigo_contpaq','modificar_fpmpfac','id_opcion_bloqueo','sync','prefijo_descripcion','id_sugar'])->contains(fn($f) => $campos->where('campo',$f)->first() !== null))
                    <div class="section-card">
                        <h3 class="section-title">
                            <svg viewBox="0 0 24 24" width="15" height="15" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
                            Control y Opciones Adicionales
                        </h3>
                        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 18px;">
                            @if($campos->where('campo','documentos')->first() !== null)<div style="grid-column: span 2;"><label class="modal-label">Documentos</label><input type="text" name="documentos" value="{{ old('documentos', $cliente->documentos) }}" {{ !($campos->where('campo','documentos')->first()?->show_in_edit ?? false) ? 'readonly style="opacity:0.6; cursor:not-allowed;" tabindex="-1"' : '' }} maxlength="255" class="modal-input"></div>@endif
                            @if($campos->where('campo','codigo_contpaq')->first() !== null)<div><label class="modal-label">Código Contpaq</label><input type="number" name="codigo_contpaq" value="{{ old('codigo_contpaq', $cliente->codigo_contpaq) }}" {{ !($campos->where('campo','codigo_contpaq')->first()?->show_in_edit ?? false) ? 'readonly style="opacity:0.6; cursor:not-allowed;" tabindex="-1"' : '' }} class="modal-input"></div>@endif
                            @if($campos->where('campo','modificar_fpmpfac')->first() !== null)<div><label class="modal-label">Modificar FPMPFac</label><input type="number" name="modificar_fpmpfac" value="{{ old('modificar_fpmpfac', $cliente->modificar_fpmpfac) }}" {{ !($campos->where('campo','modificar_fpmpfac')->first()?->show_in_edit ?? false) ? 'readonly style="opacity:0.6; cursor:not-allowed;" tabindex="-1"' : '' }} class="modal-input"></div>@endif
                            @if($campos->where('campo','id_opcion_bloqueo')->first() !== null)<div><label class="modal-label">Opción Bloqueo</label><input type="number" name="id_opcion_bloqueo" value="{{ old('id_opcion_bloqueo', $cliente->id_opcion_bloqueo) }}" {{ !($campos->where('campo','id_opcion_bloqueo')->first()?->show_in_edit ?? false) ? 'readonly style="opacity:0.6; cursor:not-allowed;" tabindex="-1"' : '' }} class="modal-input"></div>@endif
                            @if($campos->where('campo','sync')->first() !== null)<div><label class="modal-label">Sync</label><input type="number" name="sync" value="{{ old('sync', $cliente->sync) }}" {{ !($campos->where('campo','sync')->first()?->show_in_edit ?? false) ? 'readonly style="opacity:0.6; cursor:not-allowed;" tabindex="-1"' : '' }} class="modal-input"></div>@endif
                            @if($campos->where('campo','prefijo_descripcion')->first() !== null)<div><label class="modal-label">Prefijo Descripción</label><input type="number" name="prefijo_descripcion" value="{{ old('prefijo_descripcion', $cliente->prefijo_descripcion) }}" {{ !($campos->where('campo','prefijo_descripcion')->first()?->show_in_edit ?? false) ? 'readonly style="opacity:0.6; cursor:not-allowed;" tabindex="-1"' : '' }} class="modal-input"></div>@endif
                            @if($campos->where('campo','id_sugar')->first() !== null)<div><label class="modal-label">ID Sugar</label><input type="text" name="id_sugar" value="{{ old('id_sugar', $cliente->id_sugar) }}" {{ !($campos->where('campo','id_sugar')->first()?->show_in_edit ?? false) ? 'readonly style="opacity:0.6; cursor:not-allowed;" tabindex="-1"' : '' }} maxlength="36" class="modal-input"></div>@endif
                        </div>
                    </div>
                    @endif

                    {{-- Botones --}}
                    <div style="margin-top: 30px; padding-top: 24px; border-top: 1px solid var(--border); display: flex; justify-content: flex-end; gap: 14px;">
                        <a href="{{ route('clientes.index') }}" class="btn btn--ghost" style="padding: 12px 24px;">Cancelar</a>
                        <button type="submit" class="btn btn--primary shadow-premium" style="background: var(--grad-premium); padding: 13px 40px; font-weight: 800;">
                            <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2.5" style="margin-right: 8px;"><path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"/><polyline points="17 21 17 13 7 13 7 21"/></svg>
                            Guardar en Todas las Sucursales
                        </button>
                    </div>
                </div>
            </form>
        </div>

        {{-- PANEL LATERAL: Estado en sucursales --}}
        <div>
            <div class="glass-card shadow-premium" style="padding: 24px; border-radius: 16px; position: sticky; top: 20px;">
                <h3 style="margin: 0 0 18px; font-size: 13px; font-weight: 800; text-transform: uppercase; letter-spacing: 0.08em; color: var(--text-secondary);">Estado en Sucursales</h3>

                <div id="estado-sucursales-list">
                    @foreach($estadoSucursales as $s)
                    <div style="padding: 12px; border-radius: 10px; margin-bottom: 8px; background: rgba(255,255,255,0.03); border: 1px solid
                        @if($s['found']) rgba(16,185,129,0.2) @else rgba(239,68,68,0.2) @endif;">
                        <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 4px;">
                            <span style="font-weight: 700; font-size: 13px;">{{ $s['name'] }}</span>
                            @if($s['error'])
                                <span style="font-size: 10px; background: rgba(251,191,36,0.15); color: #fbbf24; padding: 2px 8px; border-radius: 10px;">SIN CONEXIÓN</span>
                            @elseif($s['found'])
                                <span style="font-size: 10px; background: rgba(16,185,129,0.15); color: #34d399; padding: 2px 8px; border-radius: 10px;">✓ ENCONTRADO</span>
                            @else
                                <span style="font-size: 10px; background: rgba(239,68,68,0.15); color: #f87171; padding: 2px 8px; border-radius: 10px;">✗ NO EXISTE</span>
                            @endif
                        </div>
                        @if($s['found'])
                        <div style="font-size: 11px; color: var(--text-secondary);">
                            Localizado por: <strong style="color: {{ $s['metodo'] === 'id_global' ? '#34d399' : '#fbbf24' }}">{{ $s['metodo'] === 'id_global' ? 'ID Global' : 'RFC' }}</strong>
                            &nbsp;| ID local: <code style="color: #a78bfa;">#{{ $s['local_id'] }}</code>
                        </div>
                        @elseif($s['error'])
                        <div style="font-size: 11px; color: #fbbf24;">{{ Str::limit($s['error'], 60) }}</div>
                        @endif
                    </div>
                    @endforeach
                </div>

                <button onclick="recargarEstado()" style="width: 100%; margin-top: 12px; padding: 10px; background: rgba(99,102,241,0.1); border: 1px solid rgba(99,102,241,0.3); border-radius: 10px; color: #818cf8; cursor: pointer; font-size: 13px; transition: all 0.2s;" onmouseover="this.style.background='rgba(99,102,241,0.2)'" onmouseout="this.style.background='rgba(99,102,241,0.1)'">
                    <svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2.5" style="margin-right:6px; vertical-align:middle;"><polyline points="23 4 23 10 17 10"/><path d="M20.49 15a9 9 0 1 1-2.12-9.36L23 10"/></svg>
                    Actualizar Estado
                </button>
            </div>
        </div>
    </div>
</div>

<style>
.modal-label { font-size:11px; font-weight:800; color:var(--text-secondary); display:block; margin-bottom:8px; text-transform: uppercase; letter-spacing: 0.08em; }
.modal-input { width:100%; background:var(--bg-root); border:1px solid var(--border); padding:11px 14px; border-radius:10px; color:white; font-size:14px; transition: all 0.2s; box-sizing: border-box; }
.modal-input:focus { border-color:#818cf8; outline:none; box-shadow:0 0 0 4px rgba(99,102,241,0.15); }
.section-card { background: rgba(255,255,255,0.02); border: 1px solid var(--border); border-radius: 12px; padding: 24px; margin-bottom: 20px; }
.section-title { margin: 0 0 20px; font-size: 12px; font-weight: 800; text-transform: uppercase; letter-spacing: 0.08em; color: #818cf8; display: flex; align-items: center; gap: 7px; }
</style>

@push('scripts')
<script>
document.getElementById('form-editar-cliente').addEventListener('submit', function() {
    Swal.fire({ title: 'Actualizando...', text: 'Propagando cambios a todas las sucursales.', allowOutsideClick: false, didOpen: () => Swal.showLoading() });
});

function recargarEstado() {
    fetch('{{ route("clientes.estado", $cliente->rfc) }}')
        .then(r => r.json())
        .then(data => {
            const list = document.getElementById('estado-sucursales-list');
            list.innerHTML = data.map(s => {
                const badge = s.error
                    ? `<span style="font-size:10px;background:rgba(251,191,36,0.15);color:#fbbf24;padding:2px 8px;border-radius:10px;">SIN CONEXIÓN</span>`
                    : s.found
                        ? `<span style="font-size:10px;background:rgba(16,185,129,0.15);color:#34d399;padding:2px 8px;border-radius:10px;">✓ ENCONTRADO</span>`
                        : `<span style="font-size:10px;background:rgba(239,68,68,0.15);color:#f87171;padding:2px 8px;border-radius:10px;">✗ NO EXISTE</span>`;
                const borderColor = s.found ? 'rgba(16,185,129,0.2)' : 'rgba(239,68,68,0.2)';
                const detail = s.found
                    ? `<div style="font-size:11px;color:var(--text-secondary);">Por: <strong style="color:${s.metodo==='id_global'?'#34d399':'#fbbf24'}">${s.metodo==='id_global'?'ID Global':'RFC'}</strong> | ID: <code style="color:#a78bfa">#${s.local_id}</code></div>`
                    : s.error ? `<div style="font-size:11px;color:#fbbf24;">${s.error.substring(0,60)}</div>` : '';
                return `<div style="padding:12px;border-radius:10px;margin-bottom:8px;background:rgba(255,255,255,0.03);border:1px solid ${borderColor}">
                    <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:4px;">
                        <span style="font-weight:700;font-size:13px;">${s.name}</span>${badge}</div>${detail}</div>`;
            }).join('');
        });
}
</script>
@endpush

@endsection
