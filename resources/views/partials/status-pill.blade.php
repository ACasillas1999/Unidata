@php
    $isActive = $value === 'ACTIVO';
@endphp

<span class="status-pill {{ $isActive ? 'status-pill--activo' : 'status-pill--inactivo' }}">
    {{ $value }}
</span>
