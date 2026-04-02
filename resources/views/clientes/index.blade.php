@extends('layouts.app')

@section('title', 'Clientes')
@section('breadcrumb', 'Clientes')

@section('content')

{{-- PAGE HEADER --}}
<div class="page-header">
    <div class="page-header-content">
        <div class="page-header-icon page-header-icon--sky">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/>
                <circle cx="9" cy="7" r="4"/>
                <path d="M23 21v-2a4 4 0 0 0-3-3.87"/>
                <path d="M16 3.13a4 4 0 0 1 0 7.75"/>
            </svg>
        </div>
        <div>
            <h1 class="page-title">Clientes</h1>
            <p class="page-subtitle">Gestión del catálogo de clientes por sucursal</p>
        </div>
    </div>
    <div class="page-header-actions">
        <div class="stat-chip stat-chip--blue">
            <span class="stat-chip-dot"></span>
            Módulo en construcción
        </div>
    </div>
</div>

{{-- COMING SOON PLACEHOLDER --}}
<div class="empty-module">
    <div class="empty-module-icon empty-module-icon--sky">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
            <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/>
            <circle cx="9" cy="7" r="4"/>
            <path d="M23 21v-2a4 4 0 0 0-3-3.87"/>
            <path d="M16 3.13a4 4 0 0 1 0 7.75"/>
        </svg>
    </div>
    <h2 class="empty-module-title">Módulo de Clientes</h2>
    <p class="empty-module-desc">
        Aquí podrás consultar, homologar y sincronizar el catálogo de clientes entre las sucursales de la empresa.
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
            Trazabilidad de cambios
        </div>
    </div>
</div>

@endsection
