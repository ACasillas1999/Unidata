@extends('layouts.app')

@section('title', 'Usuarios')
@section('breadcrumb', 'Configuración / Usuarios')

@section('content')

{{-- PAGE HEADER --}}
<div class="page-header">
    <div class="page-header-content">
        <div class="page-header-icon page-header-icon--indigo">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/>
                <circle cx="9" cy="7" r="4"/>
                <path d="M23 21v-2a4 4 0 0 0-3-3.87"/>
                <path d="M16 3.13a4 4 0 0 1 0 7.75"/>
            </svg>
        </div>
        <div>
            <h1 class="page-title">Gestión de Usuarios</h1>
            <p class="page-subtitle">Administra los accesos y roles del sistema central</p>
        </div>
    </div>
    <div class="page-header-actions">
        <button class="btn btn--primary btn--sm" id="btn-add-user" onclick="openUserModal()">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" style="width:14px;height:14px">
                <line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/>
            </svg>
            Nuevo usuario
        </button>
    </div>
</div>

{{-- STATS STRIP --}}
<div class="conn-stats">
    <div class="conn-stat-card">
        <div class="conn-stat-icon conn-stat-icon--slate">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/></svg>
        </div>
        <div>
            <p class="conn-stat-label">Total usuarios</p>
            <p class="conn-stat-value" id="stat-total">{{ $total }}</p>
        </div>
    </div>
    <div class="conn-stat-card">
        <div class="conn-stat-icon conn-stat-icon--indigo">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
        </div>
        <div>
            <p class="conn-stat-label">Administradores</p>
            <p class="conn-stat-value" id="stat-admin">{{ $administrador }}</p>
        </div>
    </div>
    <div class="conn-stat-card">
        <div class="conn-stat-icon conn-stat-icon--amber">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M16 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
        </div>
        <div>
            <p class="conn-stat-label">Coordinadores</p>
            <p class="conn-stat-value" id="stat-coord">{{ $coordinador }}</p>
        </div>
    </div>
    <div class="conn-stat-card">
        <div class="conn-stat-icon conn-stat-icon--slate">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="16" x2="12" y2="12"/><line x1="12" y1="8" x2="12.01" y2="8"/></svg>
        </div>
        <div>
            <p class="conn-stat-label">Auxiliares</p>
            <p class="conn-stat-value" id="stat-aux">{{ $auxiliar }}</p>
        </div>
    </div>
</div>

{{-- USERS TABLE --}}
<div class="card">
    <div class="card-header">
        <h2 class="card-title">Listado de usuarios</h2>
        <p class="card-subtitle">Gestión de credenciales y niveles de acceso</p>
    </div>

    <div class="table-wrap" style="overflow-x: auto; overflow-y: auto; max-height: 500px;">
        @if($users->isEmpty())
            <div class="conn-empty-state" id="empty-state">
                <div class="empty-module-icon empty-module-icon--indigo" style="width:56px;height:56px">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/></svg>
                </div>
                <p class="conn-empty-title">Sin usuarios registrados</p>
                <p class="conn-empty-desc">Haz clic en <strong>Nuevo usuario</strong> para registrar el primer acceso.</p>
            </div>
        @endif
        <table class="data-table" id="users-table" @if($users->isEmpty()) style="display:none" @endif>
            <thead style="position: sticky; top: 0; z-index: 5;">
                <tr>
                    <th>#</th>
                    <th>Nombre completo</th>
                    <th>Username</th>
                    <th>Correo electrónico</th>
                    <th>Rol / Nivel</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody id="users-tbody">
                @foreach ($users as $user)
                    @include('usuarios._row', ['user' => $user])
                @endforeach
            </tbody>
        </table>
    </div>
</div>

{{-- MODAL — CRUD USUARIOS --}}
<div class="modal-backdrop" id="user-modal-backdrop" onclick="closeUserModal(event)">
    <div class="modal" id="user-modal" role="dialog" aria-modal="true">
        <div class="modal-header">
            <div style="display:flex;align-items:center;gap:10px">
                <div class="page-header-icon page-header-icon--indigo" style="width:36px;height:36px" id="user-modal-icon">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:16px;height:16px"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                </div>
                <div>
                    <h2 class="modal-title" id="user-modal-title">Nuevo usuario</h2>
                    <p class="modal-subtitle" id="user-modal-subtitle">Asigna credenciales y rol al nuevo integrante</p>
                </div>
            </div>
            <button class="modal-close" onclick="closeUserModal()" aria-label="Cerrar">&times;</button>
        </div>

        <div class="modal-body">
            <div id="user-modal-error" class="alert alert--error" style="display:none;margin-bottom:16px">
                <span class="alert-icon">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
                </span>
                <div id="user-modal-error-text"></div>
            </div>

            <form id="user-form" onsubmit="submitUserForm(event)">
                @csrf
                <div class="form-group">
                    <label class="form-label" for="u-name">Nombre completo <span class="form-required">*</span></label>
                    <input type="text" id="u-name" name="name" class="form-input" placeholder="Ej. Alejandro Garcia" required autocomplete="off">
                </div>

                <div class="form-grid form-grid--2">
                    <div class="form-group">
                        <label class="form-label" for="u-username">Username <span class="form-required">*</span></label>
                        <input type="text" id="u-username" name="username" class="form-input form-input--mono" placeholder="agarcia" required autocomplete="off">
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="u-email">Correo electrónico <span class="form-required">*</span></label>
                        <input type="email" id="u-email" name="email" class="form-input form-input--mono" placeholder="ejemplo@mail.com" required autocomplete="off">
                    </div>
                </div>

                <div class="form-grid form-grid--2">
                    <div class="form-group">
                        <label class="form-label" for="u-role">Rol / Nivel <span class="form-required">*</span></label>
                        <select id="u-role" name="role" class="form-input" required style="background-color: var(--bg-input);">
                            <option value="Auxiliar">Auxiliar</option>
                            <option value="Coordinador">Coordinador</option>
                            <option value="Administrador">Administrador</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="u-password">Contraseña <span class="form-required" id="pwd-required">*</span></label>
                        <input type="password" id="u-password" name="password" class="form-input form-input--mono" placeholder="••••••••" autocomplete="new-password">
                        <span class="form-hint" id="pwd-hint">Para nuevas cuentas, mínimo 8 caracteres</span>
                    </div>
                </div>
            </form>
        </div>

        <div class="modal-footer">
            <button type="button" class="btn btn--ghost" onclick="closeUserModal()">Cancelar</button>
            <button type="submit" form="user-form" class="btn btn--primary" id="user-modal-submit">
                Guardar usuario
            </button>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script>
const USER_CSRF   = '{{ csrf_token() }}';
const userStore   = '{{ route("usuarios.store") }}';
const userUpdate  = (id) => `/usuarios/${id}`;
const userDelete  = (id) => `/usuarios/${id}`;
let editingUserId = null;

function openUserModal() {
    editingUserId = null;
    document.getElementById('user-modal-title').textContent = 'Nuevo usuario';
    document.getElementById('user-modal-subtitle').textContent = 'Asigna credenciales y rol al nuevo integrante';
    document.getElementById('user-modal-submit').textContent = 'Guardar usuario';
    document.getElementById('pwd-required').style.display = 'inline';
    document.getElementById('pwd-hint').textContent = 'Mínimo 8 caracteres';
    document.getElementById('u-password').required = true;
    document.getElementById('user-modal-backdrop').classList.add('active');
    document.getElementById('user-form').reset();
    document.getElementById('user-modal-error').style.display = 'none';
    setTimeout(() => document.getElementById('u-name').focus(), 100);
}

function editUser(id, name, username, email, role) {
    editingUserId = id;
    document.getElementById('user-modal-title').textContent = 'Editar usuario';
    document.getElementById('user-modal-subtitle').textContent = `Modificando datos de ${name}`;
    document.getElementById('user-modal-submit').textContent = 'Actualizar usuario';
    document.getElementById('pwd-required').style.display = 'none';
    document.getElementById('u-password').required = false;
    document.getElementById('pwd-hint').textContent = 'Dejar vacío para no cambiar';
    
    document.getElementById('u-name').value     = name;
    document.getElementById('u-username').value = username;
    document.getElementById('u-email').value    = email;
    document.getElementById('u-role').value     = role;
    document.getElementById('u-password').value = '';
    
    document.getElementById('user-modal-backdrop').classList.add('active');
    document.getElementById('user-modal-error').style.display = 'none';
}

function closeUserModal(e) {
    if (e && e.target !== document.getElementById('user-modal-backdrop')) return;
    document.getElementById('user-modal-backdrop').classList.remove('active');
}

async function submitUserForm(e) {
    e.preventDefault();
    const btn    = document.getElementById('user-modal-submit');
    const errBox = document.getElementById('user-modal-error');
    const errTxt = document.getElementById('user-modal-error-text');
    const isEdit = editingUserId !== null;

    btn.disabled = true;
    btn.innerHTML = `<span class="spinner"></span> ${isEdit ? 'Actualizando...' : 'Guardando...'}`;
    errBox.style.display = 'none';

    const formData = new FormData(document.getElementById('user-form'));
    const body = {};
    formData.forEach((v, k) => { body[k] = v; });

    try {
        const url    = isEdit ? userUpdate(editingUserId) : userStore;
        const method = isEdit ? 'PUT' : 'POST';

        const res = await fetch(url, {
            method,
            headers: { 'X-CSRF-TOKEN': USER_CSRF, 'Accept': 'application/json', 'Content-Type': 'application/json' },
            body: JSON.stringify(body),
        });
        const data = await res.json();

        if (!res.ok) {
            if (data.errors) {
                errTxt.innerHTML = Object.values(data.errors).flat().join('<br>');
            } else {
                errTxt.textContent = data.message || 'Error en el servidor';
            }
            errBox.style.display = 'flex';
            return;
        }

        location.reload(); // Recarga simple para actualizar la tabla y stats
    } catch (err) {
        errTxt.textContent = err.message;
        errBox.style.display = 'flex';
    } finally {
        btn.disabled = false;
        btn.textContent = isEdit ? 'Actualizar usuario' : 'Guardar usuario';
    }
}

async function deleteUser(id, name) {
    if (!confirm(`¿Estás seguro de eliminar al usuario "${name}"?`)) return;
    
    try {
        const res = await fetch(userDelete(id), {
            method: 'DELETE',
            headers: { 'X-CSRF-TOKEN': USER_CSRF, 'Accept': 'application/json' }
        });
        const data = await res.json();
        
        if (data.ok) {
            document.getElementById(`row-${id}`).remove();
            // Actualizar el contador total (opcional, location.reload es mas facil)
            const statTotal = document.getElementById('stat-total');
            statTotal.textContent = parseInt(statTotal.textContent) - 1;
        } else {
            alert(data.message);
        }
    } catch (err) {
        alert("Error al eliminar: " + err.message);
    }
}
</script>
@endpush
