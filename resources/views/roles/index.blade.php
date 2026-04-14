@extends('layouts.app')

@section('title', 'Roles y Permisos')
@section('breadcrumb', 'Roles y Permisos')

@push('page-content-style')
    .page-content { overflow-y: hidden !important; }
@endpush

@section('content')

{{-- ═══════════════════════════════════════════════════════════
     PAGE HEADER
════════════════════════════════════════════════════════════ --}}
<div style="display:flex; align-items:center; justify-content:space-between; flex-shrink:0; margin-bottom:20px;">
    <div>
        <h1 style="font-size:22px; font-weight:800; color:var(--text-primary); margin:0; letter-spacing:-0.03em; display:flex; align-items:center; gap:10px;">
            <span style="width:36px; height:36px; background:linear-gradient(135deg,#8b5cf6,#6366f1); border-radius:10px; display:flex; align-items:center; justify-content:center; box-shadow:0 0 16px rgba(139,92,246,.35);">
                <svg viewBox="0 0 24 24" fill="none" width="18" height="18" stroke="white" stroke-width="2"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/><path d="M9 12l2 2 4-4"/></svg>
            </span>
            Roles y Permisos
        </h1>
        <p style="font-size:13px; color:var(--text-secondary); margin:4px 0 0 46px;">Gestiona los roles del sistema y configura sus permisos de acceso.</p>
    </div>
    <button onclick="openNewRoleModal()" id="btn-new-role"
        style="display:flex; align-items:center; gap:8px; background:linear-gradient(135deg,#8b5cf6,#6366f1); color:white; border:none; border-radius:10px; padding:10px 18px; font-size:13px; font-weight:700; cursor:pointer; box-shadow:0 4px 14px rgba(139,92,246,.35); transition:all .2s;">
        <svg viewBox="0 0 24 24" fill="none" width="15" height="15" stroke="currentColor" stroke-width="2.5"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
        Nuevo Rol
    </button>
</div>

{{-- ═══════════════════════════════════════════════════════════
     LAYOUT DUAL: lista de roles (izq) + editor de permisos (der)
════════════════════════════════════════════════════════════ --}}
<div style="display:grid; grid-template-columns:300px 1fr; gap:16px; flex:1; min-height:0; overflow:hidden;">

    {{-- ── Panel izquierdo: lista de roles ── --}}
    <div style="overflow-y:auto; display:flex; flex-direction:column; gap:10px; padding-right:4px;">
        <div style="font-size:10px; font-weight:600; color:var(--text-muted); text-transform:uppercase; letter-spacing:.1em; padding:0 4px 4px;">
            {{ $roles->count() }} roles configurados
        </div>

        @foreach ($roles as $role)
        <div class="role-card {{ $loop->first ? 'selected' : '' }}"
             data-role-id="{{ $role->id }}"
             onclick="selectRole({{ $role->id }})"
             style="background:var(--bg-card); border:1px solid var(--border); border-radius:12px; padding:14px; cursor:pointer; transition:all .2s; position:relative;">
            {{-- Badge sistema --}}
            @if($role->is_system)
            <span style="position:absolute; top:10px; right:10px; font-size:9px; font-weight:700; background:rgba(255,255,255,0.06); color:var(--text-muted); border-radius:4px; padding:2px 6px; letter-spacing:.05em; text-transform:uppercase;">SISTEMA</span>
            @endif
            <div style="display:flex; align-items:center; gap:10px; margin-bottom:8px;">
                <span style="width:10px; height:10px; border-radius:50%; background:{{ $role->color }}; box-shadow:0 0 8px {{ $role->color }}88; flex-shrink:0;"></span>
                <span style="font-size:14px; font-weight:700; color:var(--text-primary);">{{ $role->name }}</span>
            </div>
            @if($role->description)
            <p style="font-size:12px; color:var(--text-secondary); margin:0 0 10px; line-height:1.5;">{{ $role->description }}</p>
            @endif
            <div style="display:flex; align-items:center; justify-content:space-between;">
                <span style="font-size:11px; color:var(--text-muted);">
                    <strong style="color:var(--text-secondary);">{{ $role->users_count }}</strong> usuario{{ $role->users_count !== 1 ? 's' : '' }}
                </span>
                <div style="display:flex; gap:6px;">
                    <button onclick="event.stopPropagation(); duplicateRole({{ $role->id }}, '{{ addslashes($role->name) }}')"
                        title="Duplicar" style="background:rgba(255,255,255,.04); border:1px solid var(--border); border-radius:6px; color:var(--text-secondary); cursor:pointer; padding:4px 7px; font-size:11px; transition:all .2s;">
                        <svg viewBox="0 0 24 24" fill="none" width="12" height="12" stroke="currentColor" stroke-width="2"><rect x="9" y="9" width="13" height="13" rx="2"/><path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"/></svg>
                    </button>
                    @if(!$role->is_system)
                    <button onclick="event.stopPropagation(); deleteRole({{ $role->id }}, '{{ addslashes($role->name) }}')"
                        title="Eliminar" style="background:rgba(244,63,94,.08); border:1px solid rgba(244,63,94,.2); border-radius:6px; color:var(--rose); cursor:pointer; padding:4px 7px; font-size:11px; transition:all .2s;">
                        <svg viewBox="0 0 24 24" fill="none" width="12" height="12" stroke="currentColor" stroke-width="2"><polyline points="3 6 5 6 21 6"/><path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6"/><path d="M10 11v6M14 11v6"/><path d="M9 6V4a1 1 0 0 1 1-1h4a1 1 0 0 1 1 1v2"/></svg>
                    </button>
                    @endif
                </div>
            </div>
        </div>
        @endforeach
    </div>

    {{-- ── Panel derecho: editor de permisos ── --}}
    <div id="permissions-panel" style="background:var(--bg-card); border:1px solid var(--border); border-radius:16px; overflow-y:auto; padding:24px;">
        <div id="permissions-loading" style="display:none; text-align:center; padding:60px 0; color:var(--text-muted); font-size:13px;">
            Cargando permisos...
        </div>

        <div id="permissions-content">
            {{-- Se rellena por JS al seleccionar un rol --}}
            <div style="text-align:center; padding:60px 0; color:var(--text-muted);">
                <svg viewBox="0 0 24 24" fill="none" width="40" height="40" stroke="currentColor" stroke-width="1.5" style="margin:0 auto 12px; display:block; opacity:.2;"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
                <p style="font-size:13px; margin:0;">Selecciona un rol para editar sus permisos</p>
            </div>
        </div>
    </div>
</div>

{{-- ═══════════════════════════════════════════════════════════
     MODAL: NUEVO ROL
════════════════════════════════════════════════════════════ --}}
<div id="modal-new-role" style="display:none; position:fixed; inset:0; z-index:9999; background:rgba(0,0,0,.7); backdrop-filter:blur(4px); align-items:center; justify-content:center;">
    <div style="background:var(--bg-card); border:1px solid var(--border); border-radius:20px; padding:28px; width:440px; max-width:95vw; box-shadow:0 30px 60px rgba(0,0,0,.5);">
        <h2 style="font-size:17px; font-weight:800; color:var(--text-primary); margin:0 0 6px;">Nuevo Rol</h2>
        <p style="font-size:13px; color:var(--text-secondary); margin:0 0 22px;">El nuevo rol se creará sin permisos. Luego podrás configurarlos en el editor.</p>

        <div style="display:flex; flex-direction:column; gap:14px;">
            <div>
                <label style="font-size:12px; font-weight:600; color:var(--text-secondary); display:block; margin-bottom:6px;">Nombre del rol *</label>
                <input id="new-role-name" type="text" placeholder="Ej: Supervisor de Ventas" maxlength="64"
                    style="width:100%; background:rgba(255,255,255,.04); border:1px solid var(--border); border-radius:8px; padding:10px 14px; color:var(--text-primary); font-size:13px; outline:none; transition:border .2s; box-sizing:border-box;"
                    onfocus="this.style.borderColor='#8b5cf6'" onblur="this.style.borderColor='var(--border)'">
            </div>
            <div>
                <label style="font-size:12px; font-weight:600; color:var(--text-secondary); display:block; margin-bottom:6px;">Descripción</label>
                <textarea id="new-role-desc" placeholder="Descripción breve del rol..." maxlength="255" rows="2"
                    style="width:100%; background:rgba(255,255,255,.04); border:1px solid var(--border); border-radius:8px; padding:10px 14px; color:var(--text-primary); font-size:13px; outline:none; resize:none; box-sizing:border-box; transition:border .2s; font-family:inherit;"
                    onfocus="this.style.borderColor='#8b5cf6'" onblur="this.style.borderColor='var(--border)'"></textarea>
            </div>
            <div>
                <label style="font-size:12px; font-weight:600; color:var(--text-secondary); display:block; margin-bottom:8px;">Color de identificación</label>
                <div style="display:flex; gap:8px; flex-wrap:wrap;">
                    @foreach(['#8b5cf6','#6366f1','#0ea5e9','#10b981','#f59e0b','#f43f5e','#64748b','#06b6d4'] as $c)
                    <button type="button" onclick="selectColor('{{ $c }}')" data-color="{{ $c }}"
                        style="width:28px; height:28px; border-radius:50%; background:{{ $c }}; border:2px solid transparent; cursor:pointer; transition:all .15s;"
                        title="{{ $c }}"></button>
                    @endforeach
                </div>
            </div>
        </div>

        <div style="display:flex; gap:10px; margin-top:24px;">
            <button onclick="closeNewRoleModal()" style="flex:1; background:rgba(255,255,255,.04); border:1px solid var(--border); border-radius:10px; color:var(--text-secondary); padding:11px; font-size:13px; font-weight:600; cursor:pointer;">Cancelar</button>
            <button onclick="createRole()" style="flex:2; background:linear-gradient(135deg,#8b5cf6,#6366f1); border:none; border-radius:10px; color:white; padding:11px; font-size:13px; font-weight:700; cursor:pointer; box-shadow:0 4px 14px rgba(139,92,246,.35);">Crear Rol</button>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<style>
/* ── Role card states ── */
.role-card { user-select: none; }
.role-card:hover { border-color: rgba(255,255,255,0.12) !important; transform: translateY(-1px); }
.role-card.selected { border-color: rgba(139,92,246,0.4) !important; background: rgba(139,92,246,0.06) !important; }

/* ── Permission toggle ── */
.perm-toggle {
    position: relative; display: inline-flex; align-items: center;
    width: 40px; height: 22px; border-radius: 999px; cursor: pointer;
    transition: background .2s; border: none; flex-shrink: 0;
}
.perm-toggle.on  { background: #8b5cf6; box-shadow: 0 0 10px rgba(139,92,246,.4); }
.perm-toggle.off { background: rgba(255,255,255,0.1); }
.perm-toggle .knob {
    position: absolute; width: 16px; height: 16px; border-radius: 50%;
    background: white; box-shadow: 0 2px 4px rgba(0,0,0,.3);
    transition: left .2s cubic-bezier(.4,0,.2,1);
}
.perm-toggle.on  .knob { left: 21px; }
.perm-toggle.off .knob { left: 3px; }

/* ── Section header ── */
.perm-section-title {
    font-size: 10px; font-weight: 700; text-transform: uppercase;
    letter-spacing: .12em; color: var(--text-muted); margin: 0 0 12px;
    padding-bottom: 8px; border-bottom: 1px solid var(--border);
}

/* ── Permission row ── */
.perm-row {
    display: flex; align-items: center; justify-content: space-between;
    padding: 10px 0; border-bottom: 1px solid rgba(255,255,255,.04);
}
.perm-row:last-child { border-bottom: none; }
</style>

<script>
// ── State ──────────────────────────────────────────────────────────────────────
const CSRF = document.querySelector('meta[name="csrf-token"]').content;
let selectedRoleId  = null;
let selectedColor   = '#8b5cf6';

// Datos de roles embebidos desde PHP para evitar peticiones extra
const rolesData = @json($roles->keyBy('id'));

// Definición de permisos por sección
const PERM_DEFS = {
    modules: {
        label: '🗂️  Acceso a Módulos',
        items: {
            articulos:    { label: 'Artículos',             desc: 'Módulo de catálogo y gestión de artículos'     },
            homologacion: { label: 'Homologación',          desc: 'Comparación de artículos entre sucursales'     },
            estadisticas: { label: 'Estadísticas',          desc: 'Panel de métricas y análisis'                  },
            db_master:    { label: 'DB Master',             desc: 'Lista maestra de artículos'                    },
            descargas:    { label: 'Centro de Descargas',   desc: 'Historial y descarga de exportaciones'         },
            clientes:     { label: 'Clientes',              desc: 'Módulo de gestión de clientes'                 },
            proveedores:  { label: 'Proveedores',           desc: 'Módulo de gestión de proveedores'              },
            configuracion:{ label: 'Configuración',         desc: 'Acceso a usuarios, conexiones y roles'         },
        }
    },
    actions: {
        label: '⚡  Acciones Permitidas',
        items: {
            articulos_crear:      { label: 'Crear artículo',             desc: 'Registrar artículos manualmente'            },
            articulos_subir:      { label: 'Actualizar artículos (CSV)', desc: 'Cargar archivo de actualización masiva'      },
            articulos_export:     { label: 'Exportar artículos (Excel)', desc: 'Descargar catálogo completo en Excel'        },
            articulos_revertir:   { label: 'Revertir carga',             desc: 'Deshacer una actualización previa'           },
            homologacion_sync:    { label: 'Sincronizar Homologación',   desc: 'Iniciar proceso de sincronización de datos'  },
            homologacion_export:  { label: 'Exportar Homologación',      desc: 'Exportar resultado de homologación a Excel'  },
            db_master_sync:       { label: 'Sincronizar DB Master',      desc: 'Actualizar la lista maestra desde sucursales'},
            db_master_export:     { label: 'Exportar DB Master',         desc: 'Exportar lista maestra en Excel'             },
            usuarios_gestionar:   { label: 'Gestionar Usuarios',         desc: 'Crear, editar y eliminar usuarios'           },
            conexiones_gestionar: { label: 'Gestionar Conexiones',       desc: 'Administrar conexiones a bases de datos'     },
            roles_gestionar:      { label: 'Gestionar Roles',            desc: 'Crear y modificar roles y permisos'          },
        }
    }
};

// ── Role card selection ───────────────────────────────────────────────────────
function selectRole(id) {
    selectedRoleId = id;
    document.querySelectorAll('.role-card').forEach(c => c.classList.remove('selected'));
    document.querySelector(`.role-card[data-role-id="${id}"]`)?.classList.add('selected');
    renderPermissions(id);
}

// ── Render permissions editor ─────────────────────────────────────────────────
function renderPermissions(id) {
    const role = rolesData[id];
    if (!role) return;

    const perms = role.permissions || { modules: {}, actions: {} };
    let html = '';

    // Header del rol
    html += `
    <div style="display:flex; align-items:center; justify-content:space-between; margin-bottom:24px; flex-wrap:wrap; gap:12px;">
        <div style="display:flex; align-items:center; gap:12px;">
            <span style="width:12px; height:12px; border-radius:50%; background:${role.color}; box-shadow:0 0 10px ${role.color}88; display:inline-block;"></span>
            <div>
                <div style="font-size:18px; font-weight:800; color:var(--text-primary);">${role.name}</div>
                <div style="font-size:12px; color:var(--text-secondary); margin-top:2px;">${role.description || 'Sin descripción'}</div>
            </div>
            ${role.is_system ? '<span style="font-size:10px; font-weight:700; background:rgba(255,255,255,.06); color:var(--text-muted); border-radius:4px; padding:3px 8px; letter-spacing:.05em; text-transform:uppercase;">SISTEMA</span>' : ''}
        </div>
        <div style="display:flex; gap:8px;">
            <button onclick="openEditMetaModal(${role.id})"
                style="display:flex; align-items:center; gap:6px; background:rgba(255,255,255,.04); border:1px solid var(--border); border-radius:8px; color:var(--text-secondary); padding:8px 14px; font-size:12px; font-weight:600; cursor:pointer;">
                <svg viewBox="0 0 24 24" fill="none" width="13" height="13" stroke="currentColor" stroke-width="2"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4z"/></svg>
                Editar metadata
            </button>
            <button onclick="resetPermissions(${role.id})"
                style="display:flex; align-items:center; gap:6px; background:rgba(244,63,94,.07); border:1px solid rgba(244,63,94,.2); border-radius:8px; color:var(--rose); padding:8px 14px; font-size:12px; font-weight:600; cursor:pointer;">
                <svg viewBox="0 0 24 24" fill="none" width="13" height="13" stroke="currentColor" stroke-width="2"><path d="M3 12a9 9 0 0 1 15-6.7L21 8"/><path d="M21 2v6h-6"/></svg>
                Restablecer
            </button>
        </div>
    </div>`;

    // Secciones de permisos
    for (const [section, sectionDef] of Object.entries(PERM_DEFS)) {
        html += `
        <div style="margin-bottom:28px;">
            <p class="perm-section-title">${sectionDef.label}</p>
            <div>`;

        for (const [key, itemDef] of Object.entries(sectionDef.items)) {
            const isOn = !!(perms[section]?.[key]);
            html += `
            <div class="perm-row">
                <div>
                    <div style="font-size:13px; font-weight:600; color:var(--text-primary);">${itemDef.label}</div>
                    <div style="font-size:11px; color:var(--text-muted); margin-top:2px;">${itemDef.desc}</div>
                </div>
                <button class="perm-toggle ${isOn ? 'on' : 'off'}"
                        id="toggle-${section}-${key}"
                        onclick="togglePermission(${id}, '${section}', '${key}', this)"
                        title="${isOn ? 'Habilitado — clic para deshabilitar' : 'Deshabilitado — clic para habilitar'}">
                    <span class="knob"></span>
                </button>
            </div>`;
        }

        html += `</div></div>`;
    }

    document.getElementById('permissions-content').innerHTML = html;
}

// ── Toggle a permission ───────────────────────────────────────────────────────
function togglePermission(roleId, section, key, btn) {
    const isCurrentlyOn = btn.classList.contains('on');
    const newValue      = !isCurrentlyOn;

    // Optimistic update
    btn.classList.toggle('on',  newValue);
    btn.classList.toggle('off', !newValue);
    rolesData[roleId].permissions[section] = rolesData[roleId].permissions[section] || {};
    rolesData[roleId].permissions[section][key] = newValue;

    fetch(`/roles/${roleId}`, {
        method: 'PUT',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json' },
        body: JSON.stringify({ permissions: { [section]: { [key]: newValue } } })
    })
    .then(r => r.json())
    .then(data => {
        if (!data.ok) throw new Error(data.message);
        // Update in-memory data with server response
        rolesData[roleId].permissions = data.role.permissions;
        showToast(newValue ? '✓ Permiso habilitado' : '✗ Permiso deshabilitado', newValue ? 'emerald' : 'muted');
    })
    .catch(err => {
        // Revert on error
        btn.classList.toggle('on',  isCurrentlyOn);
        btn.classList.toggle('off', !isCurrentlyOn);
        rolesData[roleId].permissions[section][key] = isCurrentlyOn;
        showToast('Error: ' + err.message, 'rose');
    });
}

// ── Reset all permissions to false ───────────────────────────────────────────
function resetPermissions(roleId) {
    Swal.fire({
        title: '¿Restablecer permisos?',
        text: 'Todos los permisos de este rol se desactivarán. ¿Continuar?',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#f43f5e',
        cancelButtonColor: '#64748b',
        confirmButtonText: 'Sí, restablecer',
        cancelButtonText: 'Cancelar',
        background: '#1a1d27', color: '#f0f2f9',
        backdrop: 'rgba(0,0,0,.7)',
    }).then(result => {
        if (!result.isConfirmed) return;
        const empty = { modules: {}, actions: {} };
        Object.keys(PERM_DEFS.modules.items).forEach(k => empty.modules[k] = false);
        Object.keys(PERM_DEFS.actions.items).forEach(k => empty.actions[k] = false);

        fetch(`/roles/${roleId}`, {
            method: 'PUT',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json' },
            body: JSON.stringify({ permissions: empty })
        })
        .then(r => r.json())
        .then(data => {
            if (!data.ok) throw new Error(data.message);
            rolesData[roleId].permissions = data.role.permissions;
            renderPermissions(roleId);
            showToast('Permisos restablecidos', 'muted');
        })
        .catch(err => showToast('Error: ' + err.message, 'rose'));
    });
}

// ── New role modal ────────────────────────────────────────────────────────────
function openNewRoleModal() {
    selectedColor = '#8b5cf6';
    document.getElementById('new-role-name').value = '';
    document.getElementById('new-role-desc').value = '';
    // Highlight first color
    document.querySelectorAll('[data-color]').forEach(b => b.style.outline = '');
    document.querySelector('[data-color="#8b5cf6"]').style.outline = '2px solid white';
    document.getElementById('modal-new-role').style.display = 'flex';
    setTimeout(() => document.getElementById('new-role-name').focus(), 100);
}
function closeNewRoleModal() {
    document.getElementById('modal-new-role').style.display = 'none';
}
function selectColor(hex) {
    selectedColor = hex;
    document.querySelectorAll('[data-color]').forEach(b => {
        b.style.outline = b.dataset.color === hex ? '2px solid white' : '';
    });
}

function createRole() {
    const name = document.getElementById('new-role-name').value.trim();
    const desc = document.getElementById('new-role-desc').value.trim();
    if (!name) {
        document.getElementById('new-role-name').style.borderColor = '#f43f5e';
        return;
    }
    fetch('/roles', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json' },
        body: JSON.stringify({ name, description: desc, color: selectedColor })
    })
    .then(r => r.json())
    .then(data => {
        if (!data.ok) { showToast(data.message, 'rose'); return; }
        closeNewRoleModal();
        showToast(data.message, 'emerald');
        setTimeout(() => location.reload(), 800);
    })
    .catch(() => showToast('Error de red.', 'rose'));
}

// ── Edit metadata modal (inline Swal) ─────────────────────────────────────────
function openEditMetaModal(roleId) {
    const role = rolesData[roleId];
    Swal.fire({
        title: 'Editar metadata del rol',
        html: `
            <div style="text-align:left; display:flex; flex-direction:column; gap:12px; margin-top:8px;">
                <div>
                    <label style="font-size:12px; font-weight:600; color:#8a91a8; display:block; margin-bottom:6px;">Nombre</label>
                    <input id="swal-role-name" type="text" value="${role.name}" ${role.is_system ? 'disabled' : ''}
                        style="width:100%; background:rgba(255,255,255,.04); border:1px solid rgba(255,255,255,.1); border-radius:8px; padding:9px 12px; color:#f0f2f9; font-size:13px; box-sizing:border-box; outline:none;">
                </div>
                <div>
                    <label style="font-size:12px; font-weight:600; color:#8a91a8; display:block; margin-bottom:6px;">Descripción</label>
                    <textarea id="swal-role-desc" rows="2" style="width:100%; background:rgba(255,255,255,.04); border:1px solid rgba(255,255,255,.1); border-radius:8px; padding:9px 12px; color:#f0f2f9; font-size:13px; box-sizing:border-box; outline:none; resize:none; font-family:inherit;">${role.description || ''}</textarea>
                </div>
                <div>
                    <label style="font-size:12px; font-weight:600; color:#8a91a8; display:block; margin-bottom:8px;">Color</label>
                    <div style="display:flex; gap:8px;">
                        ${['#8b5cf6','#6366f1','#0ea5e9','#10b981','#f59e0b','#f43f5e','#64748b','#06b6d4'].map(c =>
                            `<button type="button" onclick="this.parentElement.querySelectorAll('button').forEach(b=>b.style.outline=''); this.style.outline='2px solid white'; window._swalColor='${c}';"
                                style="width:26px;height:26px;border-radius:50%;background:${c};border:none;cursor:pointer;outline:${c===role.color?'2px solid white':'none'};"
                                title="${c}"></button>`
                        ).join('')}
                    </div>
                </div>
            </div>`,
        showCancelButton: true,
        confirmButtonText: 'Guardar',
        cancelButtonText: 'Cancelar',
        confirmButtonColor: '#8b5cf6',
        cancelButtonColor: '#64748b',
        background: '#1a1d27', color: '#f0f2f9',
        backdrop: 'rgba(0,0,0,.7)',
        didOpen: () => { window._swalColor = role.color; },
        preConfirm: () => ({
            name: document.getElementById('swal-role-name').value.trim(),
            description: document.getElementById('swal-role-desc').value.trim(),
            color: window._swalColor || role.color,
        })
    }).then(result => {
        if (!result.isConfirmed) return;
        const payload = result.value;
        if (!payload.name) { showToast('El nombre es obligatorio.', 'rose'); return; }

        fetch(`/roles/${roleId}`, {
            method: 'PUT',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json' },
            body: JSON.stringify(payload)
        })
        .then(r => r.json())
        .then(data => {
            if (!data.ok) { showToast(data.message, 'rose'); return; }
            // Patch local data
            Object.assign(rolesData[roleId], payload);
            rolesData[roleId].users_count = data.role.users_count;
            renderPermissions(roleId);
            // Update card in sidebar
            const card = document.querySelector(`.role-card[data-role-id="${roleId}"]`);
            if (card) {
                card.querySelector('span[style*="border-radius:50%"]').style.background = payload.color;
                card.querySelector('span[style*="border-radius:50%"]').style.boxShadow = `0 0 8px ${payload.color}88`;
                card.querySelector('span[style*="font-weight:700"]').textContent = payload.name;
            }
            showToast(data.message, 'emerald');
        })
        .catch(() => showToast('Error de red.', 'rose'));
    });
}

// ── Duplicate ─────────────────────────────────────────────────────────────────
function duplicateRole(id, name) {
    Swal.fire({
        title: `Duplicar "${name}"`,
        text: 'Se creará una copia con los mismos permisos. Podrás editarla después.',
        icon: 'question',
        showCancelButton: true,
        confirmButtonText: 'Duplicar',
        cancelButtonText: 'Cancelar',
        confirmButtonColor: '#8b5cf6',
        cancelButtonColor: '#64748b',
        background: '#1a1d27', color: '#f0f2f9',
        backdrop: 'rgba(0,0,0,.7)',
    }).then(result => {
        if (!result.isConfirmed) return;
        fetch(`/roles/${id}/duplicate`, {
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json' }
        })
        .then(r => r.json())
        .then(data => {
            if (!data.ok) { showToast(data.message, 'rose'); return; }
            showToast(data.message, 'emerald');
            setTimeout(() => location.reload(), 800);
        })
        .catch(() => showToast('Error de red.', 'rose'));
    });
}

// ── Delete ────────────────────────────────────────────────────────────────────
function deleteRole(id, name) {
    Swal.fire({
        title: `¿Eliminar rol "${name}"?`,
        text: 'Esta acción es irreversible. Los usuarios con este rol quedarán sin rol asignado.',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Sí, eliminar',
        cancelButtonText: 'Cancelar',
        confirmButtonColor: '#f43f5e',
        cancelButtonColor: '#64748b',
        background: '#1a1d27', color: '#f0f2f9',
        backdrop: 'rgba(0,0,0,.7)',
    }).then(result => {
        if (!result.isConfirmed) return;
        fetch(`/roles/${id}`, {
            method: 'DELETE',
            headers: { 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json' }
        })
        .then(r => r.json())
        .then(data => {
            if (!data.ok) { showToast(data.message, 'rose'); return; }
            showToast(data.message, 'emerald');
            setTimeout(() => location.reload(), 800);
        })
        .catch(() => showToast('Error de red.', 'rose'));
    });
}

// ── Toast mini ────────────────────────────────────────────────────────────────
function showToast(msg, type = 'muted') {
    const colors = { emerald: '#10b981', rose: '#f43f5e', muted: '#8a91a8' };
    const t = document.createElement('div');
    t.textContent = msg;
    t.style.cssText = `position:fixed;bottom:24px;right:24px;z-index:99999;background:#1a1d27;border:1px solid ${colors[type]||colors.muted}44;color:${colors[type]||colors.muted};border-radius:10px;padding:11px 18px;font-size:13px;font-weight:600;box-shadow:0 8px 24px rgba(0,0,0,.4);animation:toastSlideIn .3s both;`;
    document.body.appendChild(t);
    setTimeout(() => { t.style.animation = 'toastSlideOut .3s both'; setTimeout(() => t.remove(), 300); }, 2800);
}

// ── Init: select first role ───────────────────────────────────────────────────
document.addEventListener('DOMContentLoaded', () => {
    const firstCard = document.querySelector('.role-card');
    if (firstCard) {
        const id = parseInt(firstCard.dataset.roleId);
        selectRole(id);
    }
    // Enter key on new role modal
    document.getElementById('new-role-name').addEventListener('keydown', e => {
        if (e.key === 'Enter') createRole();
    });
    // Click outside modal closes it
    document.getElementById('modal-new-role').addEventListener('click', e => {
        if (e.target === e.currentTarget) closeNewRoleModal();
    });
});
</script>
@endpush
