@extends('layouts.app')

@section('title', 'Proveedores')
@section('breadcrumb', 'Proveedores')

@section('content')

{{-- PAGE HEADER --}}
<div class="page-header">
    <div class="page-header-content">
        <div class="page-header-icon page-header-icon--emerald">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/>
                <polyline points="9 22 9 12 15 12 15 22"/>
            </svg>
        </div>
        <div>
            <h1 class="page-title">Proveedores</h1>
            <p class="page-subtitle">Gestión del catálogo de proveedores por sucursal</p>
        </div>
    </div>
    <div class="page-header-actions">
        <div class="stat-chip stat-chip--green">
            <span class="stat-chip-dot"></span>
            Módulo en construcción
        </div>
    </div>
</div>

{{-- COMING SOON PLACEHOLDER --}}
<div class="empty-module">
    <div class="empty-module-icon empty-module-icon--emerald">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
            <path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/>
            <polyline points="9 22 9 12 15 12 15 22"/>
        </svg>
    </div>
    <h2 class="empty-module-title">Módulo de Proveedores</h2>
    <p class="empty-module-desc">
        Aquí podrás consultar, homologar y sincronizar el catálogo de proveedores entre las sucursales de la empresa.
        Este módulo está en desarrollo.
    </p>
    <div class="empty-module-features">
        <div class="feature-pill">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="20 6 9 17 4 12"/></svg>
            Consulta por sucursal
        </div>
        <div class="feature-pill">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="20 6 9 17 4 12"/></svg>
            Homologación de registros
        </div>
        <div class="feature-pill">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="20 6 9 17 4 12"/></svg>
            Sincronización controlada
        </div>
        <div class="feature-pill">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="20 6 9 17 4 12"/></svg>
            Auditoría de cambios
        </div>
    </div>
</div>

@endsection
