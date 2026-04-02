<tr id="row-{{ $branch->id }}">
    <td class="td--muted">{{ $branch->id }}</td>
    <td class="td--bold">{{ $branch->name }}</td>
    <td><span class="conn-code-badge">{{ $branch->code }}</span></td>
    <td class="td--mono">{{ $branch->db_host }}</td>
    <td class="td--mono td--muted">{{ $branch->db_port ?? 3306 }}</td>
    <td class="td--mono">{{ $branch->db_database }}</td>
    <td class="td--mono td--muted">{{ $branch->db_user }}</td>
    <td>
        <span id="status-{{ $branch->id }}" class="conn-status-pill {{ match($branch->connection_status) { 'connected' => 'conn-status--ok', 'error' => 'conn-status--error', default => 'conn-status--pending' } }}">
            <span class="conn-status-dot"></span>
            {{ match($branch->connection_status) { 'connected' => 'Conectada', 'error' => 'Error', default => 'Pendiente' } }}
        </span>
    </td>
    <td id="checked-{{ $branch->id }}" class="td--muted td--small">
        {{ $branch->last_connection_check ? $branch->last_connection_check->format('d/m H:i') : '—' }}
    </td>
    <td id="latency-{{ $branch->id }}" class="td--mono td--muted">—</td>
    <td>
        <div style="display:flex;gap:6px;align-items:center">
            <button
                class="btn-test"
                id="btn-{{ $branch->id }}"
                onclick="testConnection({{ $branch->id }})"
                @if($branch->status === 'inactive') disabled title="Sucursal inactiva" @endif
            >
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="23 4 23 10 17 10"/><path d="M20.49 15a9 9 0 1 1-2.12-9.36L23 10"/></svg>
                Probar
            </button>
            <button class="btn-delete" onclick="deleteBranch({{ $branch->id }}, '{{ addslashes($branch->name) }}')" title="Eliminar conexión">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="3 6 5 6 21 6"/><path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6"/><path d="M10 11v6M14 11v6"/><path d="M9 6V4h6v2"/></svg>
            </button>
        </div>
    </td>
</tr>
