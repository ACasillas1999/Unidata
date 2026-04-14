<tr id="row-{{ $user->id }}">
    <td class="td--muted">{{ $user->id }}</td>
    <td class="td--bold">{{ $user->name }}</td>
    <td class="td--mono">{{ $user->username }}</td>
    <td class="td--mono td--muted">{{ $user->email }}</td>
    <td>
        @php
            $roleClass = match($user->role) {
                'Administrador' => 'badge--violet',
                'Coordinador'  => 'badge--amber',
                'Auxiliar'     => 'badge--slate',
                default        => 'badge--slate'
            };
        @endphp
        <span class="badge {{ $roleClass }}">{{ $user->role }}</span>
    </td>
    <td>
        <div style="display:flex;gap:6px;align-items:center">
            <button class="btn-edit" onclick="editUser({{ $user->id }}, '{{ addslashes($user->name) }}', '{{ addslashes($user->username) }}', '{{ addslashes($user->email) }}', '{{ $user->role }}')" title="Editar usuario">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
            </button>
            <button class="btn-delete" onclick="deleteUser({{ $user->id }}, '{{ addslashes($user->name) }}')" title="Eliminar usuario">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="3 6 5 6 21 6"/><path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6"/><path d="M10 11v6M14 11v6"/><path d="M9 6V4h6v2"/></svg>
            </button>
        </div>
    </td>
</tr>
