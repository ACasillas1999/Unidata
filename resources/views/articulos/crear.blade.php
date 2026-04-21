@extends('layouts.app')

@section('title', 'Crear Artículo')
@section('breadcrumb', 'Crear Artículo')

@section('content')

<div style="max-width: 1400px; margin: 0 auto 30px auto;">
    <div class="page-header shadow-premium" style="margin-bottom: 0; padding: 20px 30px; background: var(--grad-surface); border-radius: var(--radius-xl); border: 1px solid var(--glass-border); position: relative; overflow: hidden; display: flex; justify-content: space-between; align-items: center;">
        <div class="page-header-content" style="display: flex; gap: 20px; align-items: center; z-index: 1;">
            <div class="page-header-icon shadow-premium" style="width: 48px; height: 48px; background: rgba(139,92,246,0.15); border: 1px solid rgba(139,92,246,0.3); color: var(--violet); border-radius: 12px; display: flex; align-items: center; justify-content: center;">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" width="24" height="24">
                    <path d="M12 5v14M5 12h14"/>
                </svg>
            </div>
            <div>
                <h1 class="page-title" style="letter-spacing: -0.01em; margin:0; font-size: 24px;">Crear Artículo</h1>
                <p class="page-subtitle" style="color: var(--text-secondary); margin:4px 0 0; font-size: 14px;">Agregar un nuevo artículo al catálogo maestro del sistema</p>
            </div>
        </div>
        <a href="{{ route('articulos.index') }}" class="btn btn--ghost" style="border: 1px solid var(--border); padding: 10px 20px;">
            <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2.5" style="margin-right: 8px;"><path d="M19 12H5M12 19l-7-7 7-7"/></svg>
            Volver al Catálogo
        </a>
    </div>
</div>

<div style="max-width: 1400px; margin: 0 auto;">
    @if($errors->any())
        <div style="background: rgba(239,68,68,0.1); border: 1px solid rgba(239,68,68,0.3); border-radius: 8px; padding: 12px 16px; margin-bottom: 24px;">
            <ul style="margin: 0; padding-left: 20px; color: var(--rose); font-size: 13px;">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form action="{{ route('articulos.storeManual') }}" method="POST" id="create-article-form">
        @csrf

        <div class="glass-card shadow-premium" style="padding: 50px; border-radius: 20px;">
            @include('articulos.partials.create_tabs_content')

            <div style="margin-top: 60px; padding-top: 40px; border-top: 1px solid var(--border); display: flex; justify-content: flex-end; gap: 20px;">
                <a href="{{ route('articulos.index') }}" class="btn btn--ghost" style="padding: 12px 24px;">Cancelar</a>
                <button type="submit" class="btn btn--primary shadow-premium" style="background: var(--grad-premium); padding: 14px 48px; font-weight: 800; font-size: 15px;">
                    <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2.5" style="margin-right: 10px;"><path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"/><polyline points="17 21 17 13 7 13 7 21"/><polyline points="7 3 7 8 15 8"/></svg>
                    Finalizar y Crear Artículo
                </button>
            </div>
        </div>
    </form>
</div>

<style>
.modal-label { font-size:11px; font-weight:800; color:var(--text-secondary); display:block; margin-bottom:8px; text-transform: uppercase; letter-spacing: 0.08em; }
.modal-input { width:100%; background:var(--bg-root); border:1px solid var(--border); padding:14px 16px; border-radius:12px; color:white; font-size:14px; transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1); }
.modal-input:focus { border-color:var(--violet); outline:none; box-shadow:0 0 0 5px rgba(139,92,246,0.15); background: rgba(139,92,246,0.08); }
.section-card { background: rgba(255,255,255,0.03); border: 1px solid var(--border); border-radius: 16px; padding: 35px; margin-bottom: 40px; transition: all 0.3s ease; }
.section-card:hover { border-color: rgba(139,92,246,0.4); background: rgba(255,255,255,0.045); transform: translateY(-2px); }
</style>

@push('scripts')
<script>
document.getElementById('create-article-form').addEventListener('submit', function(e) {
    Swal.fire({
        title: 'Creando Artículo...',
        text: 'Se está procesando el alta en el sistema maestro.',
        allowOutsideClick: false,
        didOpen: () => {
            Swal.showLoading();
        }
    });
});
</script>
@endpush
@endsection
