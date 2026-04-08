@extends('layouts.app')

@section('title', 'Conexiones')
@section('breadcrumb', 'Conexiones')

@section('content')

{{-- PAGE HEADER --}}
<div class="page-header">
    <div class="page-header-content">
        <div class="page-header-icon page-header-icon--violet">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M5 12.55a11 11 0 0 1 14.08 0"/>
                <path d="M1.42 9a16 16 0 0 1 21.16 0"/>
                <path d="M8.53 16.11a6 6 0 0 1 6.95 0"/>
                <circle cx="12" cy="20" r="1" fill="currentColor"/>
            </svg>
        </div>
        <div>
            <h1 class="page-title">Conexiones</h1>
            <p class="page-subtitle">Estado de conectividad a las bases de datos de cada sucursal</p>
        </div>
    </div>
    <div class="page-header-actions">
        <button class="btn btn--ghost btn--sm" id="btn-test-all" onclick="testAllConnections()">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:14px;height:14px">
                <polyline points="23 4 23 10 17 10"/>
                <path d="M20.49 15a9 9 0 1 1-2.12-9.36L23 10"/>
            </svg>
            Probar todas
        </button>
        <button class="btn btn--primary btn--sm" id="btn-add" onclick="openModal()">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" style="width:14px;height:14px">
                <line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/>
            </svg>
            Nueva conexión
        </button>
    </div>
</div>

{{-- STATS STRIP --}}
<div class="conn-stats">
    <div class="conn-stat-card">
        <div class="conn-stat-icon conn-stat-icon--slate">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="3" width="20" height="14" rx="2"/><path d="M8 21h8M12 17v4"/></svg>
        </div>
        <div>
            <p class="conn-stat-label">Total sucursales</p>
            <p class="conn-stat-value" id="stat-total">{{ $total }}</p>
        </div>
    </div>
    <div class="conn-stat-card">
        <div class="conn-stat-icon conn-stat-icon--amber">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
        </div>
        <div>
            <p class="conn-stat-label">Activas</p>
            <p class="conn-stat-value" id="stat-active">{{ $active }}</p>
        </div>
    </div>
    <div class="conn-stat-card">
        <div class="conn-stat-icon conn-stat-icon--emerald">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
        </div>
        <div>
            <p class="conn-stat-label">Conectadas</p>
            <p class="conn-stat-value" id="stat-connected">{{ $connected }}</p>
        </div>
    </div>
    <div class="conn-stat-card">
        <div class="conn-stat-icon conn-stat-icon--rose">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
        </div>
        <div>
            <p class="conn-stat-label">Con error</p>
            <p class="conn-stat-value" id="stat-errors">{{ $errors }}</p>
        </div>
    </div>
</div>

{{-- NO TABLE WARNING --}}
@if (!$tableExists)
    <div class="alert alert--warning">
        <span class="alert-icon">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>
        </span>
        <div>
            <p class="alert-title">Tabla de sucursales no encontrada</p>
            <p class="alert-body">Ejecuta <code>php artisan migrate</code> para crear la tabla <code>branches</code>.</p>
        </div>
    </div>
@else

{{-- CONNECTIONS TABLE --}}
<div class="card" id="conn-card">
    <div class="card-header card-header--row">
        <div>
            <h2 class="card-title">Sucursales configuradas</h2>
            <p class="card-subtitle">Haz clic en "Probar" para verificar la conectividad en tiempo real</p>
        </div>
        <div style="display:flex;align-items:center;gap:8px">
            <div id="batch-progress" style="display:none" class="badge badge--slate">Probando…</div>
        </div>
    </div>

    <div class="table-wrap" id="table-wrap" style="overflow-x: auto; overflow-y: auto; max-height: 420px;">
        @if($branches->isEmpty())
            <div class="conn-empty-state" id="empty-state">
                <div class="empty-module-icon empty-module-icon--violet" style="width:56px;height:56px">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M5 12.55a11 11 0 0 1 14.08 0"/><path d="M1.42 9a16 16 0 0 1 21.16 0"/><path d="M8.53 16.11a6 6 0 0 1 6.95 0"/><circle cx="12" cy="20" r="1" fill="currentColor"/></svg>
                </div>
                <p class="conn-empty-title">Sin sucursales registradas</p>
                <p class="conn-empty-desc">Haz clic en <strong>Nueva conexión</strong> para agregar la primera sucursal.</p>
                <button class="btn btn--primary btn--sm" onclick="openModal()">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" style="width:13px;height:13px"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                    Nueva conexión
                </button>
            </div>
        @endif
        <table class="data-table" id="conn-table" @if($branches->isEmpty()) style="display:none" @endif>
            <thead style="position: sticky; top: 0; z-index: 5;">
                <tr>
                    <th>#</th>
                    <th>Nombre</th>
                    <th>Código</th>
                    <th>Host</th>
                    <th>Puerto</th>
                    <th>Base de datos</th>
                    <th>Usuario</th>
                    <th>Estado</th>
                    <th>Último check</th>
                    <th>Latencia</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody id="conn-tbody">
                @foreach ($branches as $branch)
                    @include('conexiones._row', ['branch' => $branch])
                @endforeach
            </tbody>
        </table>
    </div>
</div>

{{-- LOG PANEL --}}
<div class="card card--dark">
    <div class="card-header card-header--row card-header--dark">
        <h2 class="card-title card-title--light">
            <span style="display:inline-flex;align-items:center;gap:8px">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:15px;height:15px;color:#f59e0b"><polyline points="22 12 18 12 15 21 9 3 6 12 2 12"/></svg>
                Registro de actividad
            </span>
        </h2>
        <button class="btn btn--ghost btn--sm" onclick="clearLog()">Limpiar</button>
    </div>
    <div class="card-body" style="padding:0">
        <div id="activity-log" class="activity-log">
            <p class="activity-log-empty">Los resultados de las pruebas aparecerán aquí…</p>
        </div>
    </div>
</div>

@endif

{{-- ============================================================
     MODAL — Nueva conexión
     ============================================================ --}}
<div class="modal-backdrop" id="modal-backdrop" onclick="closeModal(event)">
    <div class="modal" id="modal" role="dialog" aria-modal="true" aria-labelledby="modal-title">

        <div class="modal-header">
            <div style="display:flex;align-items:center;gap:10px">
                <div class="page-header-icon page-header-icon--violet" style="width:36px;height:36px" id="modal-icon">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:16px;height:16px"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                </div>
                <div>
                    <h2 class="modal-title" id="modal-title">Nueva conexión</h2>
                    <p class="modal-subtitle" id="modal-subtitle">Configura la base de datos de la sucursal</p>
                </div>
            </div>
            <button class="modal-close" onclick="closeModal()" aria-label="Cerrar">&times;</button>
        </div>

        <div class="modal-body">
            <div id="modal-error" class="alert alert--error" style="display:none;margin-bottom:16px">
                <span class="alert-icon">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
                </span>
                <div id="modal-error-text"></div>
            </div>

            <form id="conn-form" onsubmit="submitForm(event)">
                @csrf

                {{-- Fila 1: Nombre + Código --}}
                <div class="form-grid form-grid--2">
                    <div class="form-group">
                        <label class="form-label" for="f-name">Nombre de la sucursal <span class="form-required">*</span></label>
                        <input type="text" id="f-name" name="name" class="form-input" placeholder="Ej. Sucursal Centro" required autocomplete="off">
                        <span class="form-hint">Nombre descriptivo para identificar la sucursal</span>
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="f-code">Código <span class="form-required">*</span></label>
                        <input type="text" id="f-code" name="code" class="form-input form-input--mono" placeholder="Ej. CENTRO" required autocomplete="off" style="text-transform:uppercase" oninput="this.value=this.value.toUpperCase()">
                        <span class="form-hint">Identificador único (máx. 20 caracteres)</span>
                    </div>
                </div>

                {{-- Separador --}}
                <div class="form-separator">
                    <span>Configuración de base de datos</span>
                </div>

                {{-- Fila 2: Host + Puerto --}}
                <div class="form-grid form-grid--3">
                    <div class="form-group form-group--grow">
                        <label class="form-label" for="f-host">Host / IP <span class="form-required">*</span></label>
                        <input type="text" id="f-host" name="db_host" class="form-input form-input--mono" placeholder="127.0.0.1" required autocomplete="off">
                    </div>
                    <div class="form-group" style="min-width:110px;max-width:130px">
                        <label class="form-label" for="f-port">Puerto</label>
                        <input type="number" id="f-port" name="db_port" class="form-input form-input--mono" placeholder="3306" min="1" max="65535" value="3306">
                    </div>
                    <div class="form-group form-group--grow">
                        <label class="form-label" for="f-database">Base de datos <span class="form-required">*</span></label>
                        <input type="text" id="f-database" name="db_database" class="form-input form-input--mono" placeholder="nombre_db" required autocomplete="off">
                    </div>
                </div>

                {{-- Fila 3: Usuario + Contraseña --}}
                <div class="form-grid form-grid--2">
                    <div class="form-group">
                        <label class="form-label" for="f-user">Usuario <span class="form-required">*</span></label>
                        <input type="text" id="f-user" name="db_user" class="form-input form-input--mono" placeholder="root" required autocomplete="off">
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="f-password">Contraseña <span class="form-required">*</span></label>
                        <div class="form-pwd-wrap">
                            <input type="password" id="f-password" name="db_password" class="form-input form-input--mono" placeholder="••••••••" required autocomplete="new-password">
                            <button type="button" class="form-pwd-toggle" onclick="togglePwd()" id="pwd-toggle" aria-label="Ver contraseña">
                                <svg id="pwd-eye" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                            </button>
                        </div>
                    </div>
                </div>

                {{-- Estado --}}
                <div class="form-group">
                    <label class="form-label">Estado inicial</label>
                    <div class="form-radio-group">
                        <label class="form-radio">
                            <input type="radio" name="status" value="active" checked>
                            <span class="form-radio-mark"></span>
                            <span class="form-radio-label">
                                <span class="form-radio-title">Activa</span>
                                <span class="form-radio-desc">Se incluirá en las pruebas de conexión automáticas</span>
                            </span>
                        </label>
                        <label class="form-radio">
                            <input type="radio" name="status" value="inactive">
                            <span class="form-radio-mark"></span>
                            <span class="form-radio-label">
                                <span class="form-radio-title">Inactiva</span>
                                <span class="form-radio-desc">Guardada pero excluida de las pruebas automáticas</span>
                            </span>
                        </label>
                    </div>
                </div>

            </form>
        </div>

        <div class="modal-footer">
            <button type="button" class="btn btn--ghost" onclick="closeModal()">Cancelar</button>
            <button type="submit" form="conn-form" class="btn btn--primary" id="modal-submit">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" style="width:14px;height:14px"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
                Guardar conexión
            </button>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script>
const CSRF       = '{{ csrf_token() }}';
const storeUrl   = '{{ route("conexiones.store") }}';
const updateUrl  = (id) => `/conexiones/${id}`;
const testUrl    = (id) => `/conexiones/${id}/test`;
const deleteUrl  = (id) => `/conexiones/${id}`;
const testAllUrl = '{{ route("conexiones.test-all") }}';
let   editingId  = null; // null = crear, number = editar

/* ── Modal ─────────────────────────────────────────────────── */
function openModal() {
    editingId = null;
    document.getElementById('modal-title').textContent    = 'Nueva conexión';
    document.getElementById('modal-subtitle').textContent = 'Configura la base de datos de la sucursal';
    document.getElementById('modal-submit').innerHTML     = `<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" style="width:14px;height:14px"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg> Guardar conexión`;
    document.getElementById('modal-icon').innerHTML       = `<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:16px;height:16px"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>`;
    document.getElementById('modal-backdrop').classList.add('active');
    document.getElementById('modal-error').style.display = 'none';
    document.getElementById('conn-form').reset();
    document.getElementById('f-port').value = '3306';
    document.getElementById('f-password').placeholder = '••••••••';
    document.getElementById('f-password').required = true;
    setTimeout(() => document.getElementById('f-name').focus(), 80);
}

function editBranch(id, name, code, host, port, database, user, status) {
    editingId = id;
    document.getElementById('modal-title').textContent    = 'Editar conexión';
    document.getElementById('modal-subtitle').textContent = `Modificando los datos de "${name}"`;
    document.getElementById('modal-submit').innerHTML     = `<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" style="width:14px;height:14px"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg> Actualizar conexión`;
    document.getElementById('modal-icon').innerHTML       = `<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:16px;height:16px"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>`;
    document.getElementById('modal-error').style.display = 'none';
    document.getElementById('f-name').value     = name;
    document.getElementById('f-code').value     = code;
    document.getElementById('f-host').value     = host;
    document.getElementById('f-port').value     = port;
    document.getElementById('f-database').value = database;
    document.getElementById('f-user').value     = user;
    document.getElementById('f-password').value = '';
    document.getElementById('f-password').placeholder = 'Dejar vacío para no cambiar';
    document.getElementById('f-password').required = false;
    document.querySelector(`input[name="status"][value="${status}"]`).checked = true;
    document.getElementById('modal-backdrop').classList.add('active');
    setTimeout(() => document.getElementById('f-name').focus(), 80);
}

function closeModal(e) {
    if (e && e.target !== document.getElementById('modal-backdrop')) return;
    document.getElementById('modal-backdrop').classList.remove('active');
    editingId = null;
}

document.addEventListener('keydown', e => {
    if (e.key === 'Escape') {
        document.getElementById('modal-backdrop').classList.remove('active');
        editingId = null;
    }
});

function togglePwd() {
    const inp = document.getElementById('f-password');
    inp.type = inp.type === 'password' ? 'text' : 'password';
}

/* ── Submit form ────────────────────────────────────────────── */
async function submitForm(e) {
    e.preventDefault();
    const btn    = document.getElementById('modal-submit');
    const errBox = document.getElementById('modal-error');
    const errTxt = document.getElementById('modal-error-text');
    const isEdit = editingId !== null;

    btn.disabled = true;
    btn.innerHTML = `<span class="spinner"></span> ${isEdit ? 'Actualizando…' : 'Guardando…'}`;
    errBox.style.display = 'none';

    const formData = new FormData(document.getElementById('conn-form'));

    // Para PUT convertimos a JSON ya que FormData no funciona bien con PUT en Laravel
    const body = {};
    formData.forEach((v, k) => { body[k] = v; });

    try {
        const url    = isEdit ? updateUrl(editingId) : storeUrl;
        const method = isEdit ? 'PUT' : 'POST';

        const res  = await fetch(url, {
            method,
            headers: { 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json', 'Content-Type': 'application/json' },
            body: JSON.stringify(body),
        });
        const data = await res.json();

        if (!res.ok || !data.ok) {
            if (data.errors) {
                const msgs = Object.values(data.errors).flat().join('<br>');
                errTxt.innerHTML = msgs;
            } else {
                errTxt.innerHTML = data.message ?? 'Error al guardar.';
            }
            errBox.style.display = 'flex';
            return;
        }

        closeModal();

        if (isEdit) {
            updateRow(data.branch);
            logEntry(data.branch.name, { ok: true, message: data.message });
        } else {
            appendRow(data.branch);
            logEntry(data.branch.name, { ok: true, message: data.message });
            updateStats(1, data.branch.status === 'active' ? 1 : 0, 0, 0);
        }

    } catch (err) {
        errTxt.innerHTML = err.message;
        errBox.style.display = 'flex';
    } finally {
        btn.disabled = false;
        const svgSave = `<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" style="width:14px;height:14px"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>`;
        btn.innerHTML = svgSave + (isEdit ? ' Actualizar conexión' : ' Guardar conexión');
    }
}

/* ── Build row actions HTML ─────────────────────────────────── */
function rowActionsHtml(b) {
    const disabled = b.status === 'inactive' ? 'disabled title="Sucursal inactiva"' : '';
    return `
        <div style="display:flex;gap:6px;align-items:center">
            <button class="btn-test" id="btn-${b.id}" onclick="testConnection(${b.id})" ${disabled}>
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="23 4 23 10 17 10"/><path d="M20.49 15a9 9 0 1 1-2.12-9.36L23 10"/></svg>
                Probar
            </button>
            <button class="btn-edit" onclick="editBranch(${b.id},'${esc(b.name)}','${esc(b.code)}','${esc(b.db_host)}',${b.db_port ?? 3306},'${esc(b.db_database)}','${esc(b.db_user)}','${b.status}')" title="Editar conexión">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
            </button>
            <button class="btn-delete" onclick="deleteBranch(${b.id}, '${esc(b.name)}')" title="Eliminar conexión">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="3 6 5 6 21 6"/><path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6"/><path d="M10 11v6M14 11v6"/><path d="M9 6V4h6v2"/></svg>
            </button>
        </div>
    `;
}

/* ── Append new row ─────────────────────────────────────────── */
function appendRow(b) {
    const tbody   = document.getElementById('conn-tbody');
    const table   = document.getElementById('conn-table');
    const empty   = document.getElementById('empty-state');

    if (empty)  empty.style.display  = 'none';
    if (table)  table.style.display  = '';

    const tr = document.createElement('tr');
    tr.id    = `row-${b.id}`;
    
    let statusPillHtml = '';
    if (b.status === 'inactive') {
        statusPillHtml = `<span id="status-${b.id}" class="conn-status-pill conn-status--inactive"><span class="conn-status-dot"></span>Inactiva</span>`;
    } else {
        statusPillHtml = `<span id="status-${b.id}" class="conn-status-pill conn-status--pending"><span class="conn-status-dot"></span>Pendiente</span>`;
    }

    tr.innerHTML = `
        <td class="td--muted">${b.id}</td>
        <td class="td--bold">${esc(b.name)}</td>
        <td><span class="conn-code-badge">${esc(b.code)}</span></td>
        <td class="td--mono">${esc(b.db_host)}</td>
        <td class="td--mono td--muted">${b.db_port ?? 3306}</td>
        <td class="td--mono">${esc(b.db_database)}</td>
        <td class="td--mono td--muted">${esc(b.db_user)}</td>
        <td>${statusPillHtml}</td>
        <td id="checked-${b.id}" class="td--muted td--small">—</td>
        <td id="latency-${b.id}" class="td--mono td--muted">—</td>
        <td>${rowActionsHtml(b)}</td>
    `;
    tbody.appendChild(tr);
}

/* ── Update existing row in place ───────────────────────────── */
function updateRow(b) {
    const tr = document.getElementById(`row-${b.id}`);
    if (!tr) return;
    const cells = tr.querySelectorAll('td');
    // 0:id 1:name 2:code 3:host 4:port 5:db 6:user 7:status 8:checked 9:latency 10:actions
    cells[1].textContent = b.name;
    cells[2].innerHTML   = `<span class="conn-code-badge">${esc(b.code)}</span>`;
    cells[3].textContent = b.db_host;
    cells[4].textContent = b.db_port ?? 3306;
    cells[5].textContent = b.db_database;
    cells[6].textContent = b.db_user;
    cells[10].innerHTML  = rowActionsHtml(b);
    
    const statusEl = document.getElementById(`status-${b.id}`);
    if (statusEl) {
        if (b.status === 'inactive') {
            statusEl.className = 'conn-status-pill conn-status--inactive';
            statusEl.innerHTML = '<span class="conn-status-dot"></span>Inactiva';
        } else {
            statusEl.className = 'conn-status-pill conn-status--pending';
            statusEl.innerHTML = '<span class="conn-status-dot"></span>Pendiente';
        }
    }
}

/* ── Test connection ────────────────────────────────────────── */
function setRowLoading(id, loading) {
    const btn = document.getElementById(`btn-${id}`);
    if (!btn) return;
    if (loading) {
        btn.disabled = true;
        btn.innerHTML = `<span class="spinner"></span> Probando…`;
    } else {
        btn.disabled = false;
        btn.innerHTML = `<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="23 4 23 10 17 10"/><path d="M20.49 15a9 9 0 1 1-2.12-9.36L23 10"/></svg> Probar`;
    }
}

function applyResult(id, data) {
    const statusEl  = document.getElementById(`status-${id}`);
    const checkedEl = document.getElementById(`checked-${id}`);
    const latencyEl = document.getElementById(`latency-${id}`);
    if (statusEl) {
        statusEl.className = `conn-status-pill ${data.ok ? 'conn-status--ok' : 'conn-status--error'}`;
        statusEl.innerHTML = `<span class="conn-status-dot"></span>${data.ok ? 'Conectada' : 'Error'}`;
    }
    if (checkedEl) checkedEl.textContent = data.checked ?? '—';
    if (latencyEl) latencyEl.textContent = data.ms != null ? `${data.ms} ms` : '—';
}

async function testConnection(id) {
    setRowLoading(id, true);
    try {
        const res  = await fetch(testUrl(id), {
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json' },
        });
        const data = await res.json();
        applyResult(id, data);
        const name = document.getElementById(`row-${id}`)?.querySelector('.td--bold')?.textContent?.trim() ?? `#${id}`;
        logEntry(name, data);
    } catch (e) {
        logEntry(`#${id}`, { ok: false, message: e.message });
    } finally {
        setRowLoading(id, false);
    }
}

async function testAllConnections() {
    const btn      = document.getElementById('btn-test-all');
    const progress = document.getElementById('batch-progress');
    if (btn) { btn.disabled = true; btn.textContent = 'Probando…'; }
    if (progress) progress.style.display = '';

    try {
        const res  = await fetch(testAllUrl, {
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json' },
        });
        const data = await res.json();
        let connected = 0, errors = 0;
        for (const [id, result] of Object.entries(data.results ?? {})) {
            applyResult(id, result);
            const name = document.getElementById(`row-${id}`)?.querySelector('.td--bold')?.textContent?.trim() ?? `#${id}`;
            logEntry(name, result);
            if (result.ok) connected++; else errors++;
        }
        document.getElementById('stat-connected').textContent = connected;
        document.getElementById('stat-errors').textContent    = errors;
    } catch (e) {
        logEntry('Batch', { ok: false, message: e.message });
    } finally {
        if (btn) { btn.disabled = false; btn.innerHTML = `<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:14px;height:14px"><polyline points="23 4 23 10 17 10"/><path d="M20.49 15a9 9 0 1 1-2.12-9.36L23 10"/></svg> Probar todas`; }
        if (progress) progress.style.display = 'none';
    }
}

/* ── Delete ─────────────────────────────────────────────────── */
async function deleteBranch(id, name) {
    if (!confirm(`¿Eliminar la conexión "${name}"? Esta acción no se puede deshacer.`)) return;
    try {
        const res  = await fetch(deleteUrl(id), {
            method: 'DELETE',
            headers: { 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json' },
        });
        const data = await res.json();
        if (data.ok) {
            document.getElementById(`row-${id}`)?.remove();
            logEntry(name, { ok: true, message: data.message });
            updateStats(-1, -1, 0, 0);

            // Si no quedan filas, mostrar empty state
            const tbody = document.getElementById('conn-tbody');
            if (tbody && tbody.children.length === 0) {
                document.getElementById('conn-table').style.display = 'none';
                const empty = document.getElementById('empty-state');
                if (empty) empty.style.display = '';
            }
        } else {
            logEntry(name, { ok: false, message: data.message });
        }
    } catch (e) {
        logEntry(name, { ok: false, message: e.message });
    }
}

/* ── Stats helpers ──────────────────────────────────────────── */
function updateStats(dTotal, dActive, dConnected, dErrors) {
    const add = (id, d) => {
        const el = document.getElementById(id);
        if (el) el.textContent = Math.max(0, (parseInt(el.textContent) || 0) + d);
    };
    add('stat-total',     dTotal);
    add('stat-active',    dActive);
    add('stat-connected', dConnected);
    add('stat-errors',    dErrors);
}

/* ── Log ────────────────────────────────────────────────────── */
function logEntry(name, data) {
    const log   = document.getElementById('activity-log');
    if (!log) return;
    const empty = log.querySelector('.activity-log-empty');
    if (empty) empty.remove();

    const time = new Date().toLocaleTimeString('es-MX', { hour: '2-digit', minute: '2-digit', second: '2-digit' });
    const line = document.createElement('div');
    line.className = `log-line ${data.ok ? 'log-line--ok' : 'log-line--error'}`;
    line.innerHTML = `
        <span class="log-time">${time}</span>
        <span class="log-badge ${data.ok ? 'log-badge--ok' : 'log-badge--error'}">${data.ok ? 'OK' : 'FAIL'}</span>
        <span class="log-name">${esc(name)}</span>
        <span class="log-msg">${esc(data.message ?? '')}</span>
        ${data.ms != null ? `<span class="log-ms">${data.ms} ms</span>` : ''}
    `;
    log.prepend(line);
}

function clearLog() {
    const log = document.getElementById('activity-log');
    if (log) log.innerHTML = '<p class="activity-log-empty">Los resultados de las pruebas aparecerán aquí…</p>';
}

/* ── Util ───────────────────────────────────────────────────── */
function esc(str) {
    return String(str ?? '').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
}
</script>
@endpush
