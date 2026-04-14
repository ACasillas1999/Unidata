@extends('layouts.app')

@section('title', 'Crear Artículo')
@section('breadcrumb', 'Crear Artículo')

@section('content')

<div class="page-header shadow-premium" style="margin-bottom: 0; padding: 14px 20px; background: var(--grad-surface); border-radius: var(--radius-xl) var(--radius-xl) 0 0; border: 1px solid var(--glass-border); position: relative; overflow: hidden; display: flex; justify-content: space-between; align-items: center;">
    <div class="page-header-content" style="display: flex; gap: 16px; align-items: center; z-index: 1;">
        <div class="page-header-icon shadow-premium" style="background: rgba(139,92,246,0.15); border: 1px solid rgba(139,92,246,0.3); color: var(--violet);">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                <path d="M12 5v14M5 12h14"/>
            </svg>
        </div>
        <div>
            <h1 class="page-title" style="letter-spacing: -0.01em; margin:0;">Crear Artículo</h1>
            <p class="page-subtitle" style="color: var(--text-secondary); margin:4px 0 0;">Agregar un nuevo artículo al catálogo maestro</p>
        </div>
    </div>
    <a href="{{ route('articulos.index') }}" class="btn btn--ghost" style="border: 1px solid var(--border);">
        <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2"><path d="M19 12H5M12 19l-7-7 7-7"/></svg>
        Volver
    </a>
</div>

<div style="max-width: 1200px;">
    @if($errors->any())
        <div style="background: rgba(239,68,68,0.1); border: 1px solid rgba(239,68,68,0.3); border-radius: 8px; padding: 12px 16px; margin-bottom: 16px;">
            <ul style="margin: 0; padding-left: 20px; color: var(--rose); font-size: 13px;">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

        @include('articulos.partials.create_tabs_header')

        <form action="{{ route('articulos.storeManual') }}" method="POST" id="create-article-form">
            @csrf

            <div class="glass-card shadow-premium" style="padding: 32px; border-top-left-radius: 0; border-top-right-radius: 0; border-radius: 0 0 16px 16px;">
                
                <div id="create-tabs-content">
                    @include('articulos.partials.create_tabs_content')
                </div>

                <div style="margin-top: 40px; padding-top: 24px; border-top: 1px solid var(--border); display: flex; justify-content: flex-end; gap: 16px;">
                    <button type="button" onclick="window.history.back()" class="btn btn--ghost">Cancelar</button>
                    <button type="submit" class="btn btn--primary shadow-premium" style="background: var(--grad-premium); padding: 12px 32px; font-weight: 800;">
                        Crear y Replicar ArtÃ­culo
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>

<style>
.modal-label { font-size:11px; font-weight:700; color:var(--text-secondary); display:block; margin-bottom:4px; text-transform: uppercase; }
.modal-input { width:100%; background:var(--bg-root); border:1px solid var(--border); padding:10px 14px; border-radius:8px; color:white; font-size:13px; transition: 0.2s; }
.modal-input:focus { border-color:var(--violet); outline:none; box-shadow:0 0 0 2px rgba(139,92,246,0.2); }
.modal-tab.active { color: white !important; border-bottom-color: var(--violet) !important; }
</style>

@push('scripts')
<script>
function switchCreateTab(event, tabId) {
    // Hide all panels
    document.querySelectorAll('.create-tab-panel').forEach(panel => panel.style.display = 'none');
    // Deactivate all tabs
    document.querySelectorAll('.modal-tab').forEach(tab => {
        tab.classList.remove('active');
        tab.style.color = 'var(--text-muted)';
        tab.style.borderBottomColor = 'transparent';
    });
    
    // Show selected panel
    document.getElementById(tabId).style.display = 'block';
    // Activate clicked tab
    event.currentTarget.classList.add('active');
}

document.getElementById('create-article-form').addEventListener('submit', function(e) {
    Swal.fire({
        title: 'Creando ArtÃ­culo...',
        text: 'Se estÃ¡ guardando en el Maestro y replicando en todas las sucursales conectadas.',
        allowOutsideClick: false,
        didOpen: () => {
            Swal.showLoading();
        }
    });
});
</script>
@endpush
@endsection
