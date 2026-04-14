<!-- TAB 1: GENERAL -->
<div id="tab-general" class="create-tab-panel">
    <h3 style="font-size: 14px; font-weight: 800; color: var(--emerald); text-transform: uppercase; letter-spacing: 0.1em; margin-bottom: 24px; display: flex; align-items: center; gap: 8px;">
        <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
        Identificación Base
    </h3>
    <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 20px;">
        <div class="form-group">
            <label class="modal-label">Clave del Artículo <span style="color:var(--rose)">*</span></label>
            <input type="text" name="clave" value="{{ old('clave') }}" required maxlength="40" class="modal-input" placeholder="Ej: ART-001">
        </div>
        <div class="form-group" style="grid-column: span 2;">
            <label class="modal-label">Descripción <span style="color:var(--rose)">*</span></label>
            <input type="text" name="descripcion" value="{{ old('descripcion') }}" required maxlength="200" class="modal-input" placeholder="Nombre completo del producto...">
        </div>
        <div class="form-group">
            <label class="modal-label">Línea</label>
            <input type="text" name="linea" value="{{ old('linea') }}" required maxlength="4" class="modal-input">
        </div>
        <div class="form-group">
            <label class="modal-label">Clasificación</label>
            <input type="text" name="clasificacion" value="{{ old('clasificacion') }}" required maxlength="6" class="modal-input">
        </div>
        <div class="form-group">
            <label class="modal-label">Área</label>
            <input type="number" name="area" value="{{ old('area') }}" required class="modal-input">
        </div>
        <div class="form-group">
            <label class="modal-label">Unidad de Medida</label>
            <input type="text" name="unidad_medida" value="{{ old('unidad_medida', 'PZ') }}" required maxlength="4" class="modal-input">
        </div>
        <div class="form-group" style="display: flex; align-items: center; gap: 20px; padding-top: 10px;">
            <label style="display:flex; align-items:center; gap:8px; cursor:pointer;">
                <input type="checkbox" name="color" value="1" {{ old('color') == '1' ? 'checked' : '' }} style="width:16px; height:16px;">
                <span style="font-size: 13px; font-weight: 700; color: white;">Color</span>
            </label>
            <label style="display:flex; align-items:center; gap:8px; cursor:pointer;">
                <input type="checkbox" name="protocolo" value="1" {{ old('protocolo') == '1' ? 'checked' : '' }} style="width:16px; height:16px;">
                <span style="font-size: 13px; font-weight: 700; color: white;">Protocolo</span>
            </label>
        </div>
    </div>

    <div style="display: flex; flex-wrap: wrap; gap: 24px; margin-top: 30px; padding-top: 20px; border-top: 1px solid var(--border);">
        <label style="display:flex; align-items:center; gap:10px; cursor:pointer;">
            <input type="checkbox" name="habilitado" value="1" {{ old('habilitado', '1') == '1' ? 'checked' : '' }} style="width:18px; height:18px;">
            <span style="font-size: 13px; font-weight: 700; color: white;">Habilitado</span>
        </label>
        <label style="display:flex; align-items:center; gap:10px; cursor:pointer;">
            <input type="checkbox" name="articulo_kit" value="1" {{ old('articulo_kit') == '1' ? 'checked' : '' }} style="width:18px; height:18px;">
            <span style="font-size: 13px; font-weight: 700; color: white;">Es KIT</span>
        </label>
        <label style="display:flex; align-items:center; gap:10px; cursor:pointer;">
            <input type="checkbox" name="articulo_serie" value="1" {{ old('articulo_serie') == '1' ? 'checked' : '' }} style="width:18px; height:18px;">
            <span style="font-size: 13px; font-weight: 700; color: white;">Maneja Series</span>
        </label>
    </div>
</div>

<!-- TAB 2: PRECIOS -->
<div id="tab-precios" class="create-tab-panel" style="display:none;">
    <h3 style="font-size: 14px; font-weight: 800; color: var(--amber); text-transform: uppercase; letter-spacing: 0.1em; margin-bottom: 24px; display: flex; align-items: center; gap: 8px;">
        <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="12" y1="1" x2="12" y2="23"/><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg>
        Precios y Estructura de Costos
    </h3>
    <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 20px;">
        <div class="form-group">
            <label class="modal-label">Moneda</label>
            <select name="mn_usd" class="modal-input">
                <option value="0" {{ old('mn_usd') == '0' ? 'selected' : '' }}>MXN (Pesos)</option>
                <option value="1" {{ old('mn_usd') == '1' ? 'selected' : '' }}>USD (Dólares)</option>
            </select>
        </div>
        <div class="form-group">
            <label class="modal-label">Precio Lista</label>
            <input type="number" step="0.0001" name="precio_lista" value="{{ old('precio_lista', 0) }}" class="modal-input">
        </div>
        <div class="form-group">
            <label class="modal-label">Precio Venta</label>
            <input type="number" step="0.0001" name="precio_venta" value="{{ old('precio_venta', 0) }}" class="modal-input">
        </div>
        <div class="form-group">
            <label class="modal-label">Desc. Venta (%)</label>
            <input type="number" step="0.01" name="des_precio_venta" value="{{ old('des_precio_venta', 0) }}" class="modal-input">
        </div>
        <div class="form-group">
            <label class="modal-label">Precio Especial</label>
            <input type="number" step="0.0001" name="precio_especial" value="{{ old('precio_especial', 0) }}" class="modal-input">
        </div>
        <div class="form-group">
            <label class="modal-label">Desc. Especial (%)</label>
            <input type="number" step="0.01" name="desc_precio_espec" value="{{ old('desc_precio_espec', 0) }}" class="modal-input">
        </div>
        <div class="form-group">
            <label class="modal-label">Precio 4</label>
            <input type="number" step="0.0001" name="precio4" value="{{ old('precio4', 0) }}" class="modal-input">
        </div>
        <div class="form-group">
            <label class="modal-label">Desc. Precio 4 (%)</label>
            <input type="number" step="0.01" name="desc_precio4" value="{{ old('desc_precio4', 0) }}" class="modal-input">
        </div>
        <div class="form-group">
            <label class="modal-label">Precio Mínimo</label>
            <input type="number" step="0.0001" name="precio_minimo" value="{{ old('precio_minimo', 0) }}" class="modal-input">
        </div>
        <div class="form-group">
            <label class="modal-label">Costo Venta</label>
            <input type="number" step="0.0001" name="costo_venta" value="{{ old('costo_venta', 0) }}" class="modal-input">
        </div>
        <div class="form-group">
            <label class="modal-label">Margen Mínimo (%)</label>
            <input type="number" step="0.01" name="margen_minimo" value="{{ old('margen_minimo', 0) }}" class="modal-input">
        </div>
    </div>
</div>

<!-- TAB 3: INVENTARIO -->
<div id="tab-inventario" class="create-tab-panel" style="display:none;">
    <h3 style="font-size: 14px; font-weight: 800; color: var(--violet-light); text-transform: uppercase; letter-spacing: 0.1em; margin-bottom: 24px; display: flex; align-items: center; gap: 8px;">
        <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"/></svg>
        Logística e Inventario
    </h3>
    <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 20px;">
        <div class="form-group">
            <label class="modal-label">Inventario Máximo</label>
            <input type="number" step="0.01" name="inventario_maximo" value="{{ old('inventario_maximo', 0) }}" class="modal-input">
        </div>
        <div class="form-group">
            <label class="modal-label">Inventario Mínimo</label>
            <input type="number" step="0.01" name="inventario_minimo" value="{{ old('inventario_minimo', 0) }}" class="modal-input">
        </div>
        <div class="form-group">
            <label class="modal-label">Punto de Reorden</label>
            <input type="number" step="0.01" name="punto_reorden" value="{{ old('punto_reorden', 0) }}" class="modal-input">
        </div>
        <div class="form-group">
            <label class="modal-label">Ubicación</label>
            <input type="text" name="ubicacion" value="{{ old('ubicacion') }}" class="modal-input">
        </div>
        <div class="form-group">
            <label class="modal-label">Peso (kg)</label>
            <input type="number" step="0.001" name="peso" value="{{ old('peso', 0) }}" class="modal-input">
        </div>
        <div class="form-group">
            <label class="modal-label">Std Pack</label>
            <input type="number" step="0.01" name="std_pack" value="{{ old('std_pack', 1) }}" class="modal-input">
        </div>
    </div>
</div>

<!-- TAB 4: SAT / OTROS -->
<div id="tab-extra" class="create-tab-panel" style="display:none;">
    <h3 style="font-size: 14px; font-weight: 800; color: var(--rose); text-transform: uppercase; letter-spacing: 0.1em; margin-bottom: 24px; display: flex; align-items: center; gap: 8px;">
        <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2.5"><rect x="3" y="4" width="18" height="16" rx="2"/><path d="M16 2v4"/><path d="M8 2v4"/><path d="M3 10h18"/></svg>
        Impuestos y Sustitutos
    </h3>
    <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 20px;">
        <div class="form-group">
            <label class="modal-label">Clave SAT (IDSAT)</label>
            <input type="text" name="idsat" value="{{ old('idsat') }}" maxlength="25" class="modal-input">
        </div>
        <div class="form-group">
            <label class="modal-label">ID Impuesto SAT</label>
            <input type="text" name="id_impuesto_sat" value="{{ old('id_impuesto_sat') }}" maxlength="3" class="modal-input">
        </div>
        <div class="form-group">
            <label class="modal-label">IVA (%)</label>
            <input type="number" step="0.01" name="iva" value="{{ old('iva', 16) }}" class="modal-input">
        </div>
        <div class="form-group">
            <label class="modal-label">Sustituto Principal</label>
            <input type="text" name="sustituto" value="{{ old('sustituto') }}" class="modal-input">
        </div>
        <div class="form-group">
            <label class="modal-label">Sustituto 1</label>
            <input type="text" name="sustituto1" value="{{ old('sustituto1') }}" class="modal-input">
        </div>
        <div class="form-group">
            <label class="modal-label">Sustituto 2</label>
            <input type="text" name="sustituto2" value="{{ old('sustituto2') }}" class="modal-input">
        </div>
    </div>

    <div style="display: flex; flex-wrap: wrap; gap: 24px; margin-top: 30px; padding-top: 20px; border-top: 1px solid var(--border);">
        <label style="display:flex; align-items:center; gap:10px; cursor:pointer;">
            <input type="checkbox" name="en_promocion" value="1" {{ old('en_promocion') == '1' ? 'checked' : '' }} style="width:18px; height:18px;">
            <span style="font-size: 13px; font-weight: 700; color: white;">En Promoción</span>
        </label>
        <label style="display:flex; align-items:center; gap:10px; cursor:pointer;">
            <input type="checkbox" name="critico" value="1" {{ old('critico') == '1' ? 'checked' : '' }} style="width:18px; height:18px;">
            <span style="font-size: 13px; font-weight: 700; color: white;">Artículo Crítico</span>
        </label>
        <label style="display:flex; align-items:center; gap:10px; cursor:pointer;">
            <input type="checkbox" name="control_pedimentos" value="1" {{ old('control_pedimentos') == '1' ? 'checked' : '' }} style="width:18px; height:18px;">
            <span style="font-size: 13px; font-weight: 700; color: white;">Pedimentos</span>
        </label>
    </div>
</div>
