@extends('layouts.app')

@section('title', 'Subir Artículos')
@section('breadcrumb', 'Artículos / Subir')

@section('content')
<div class="page-header">
    <div class="page-header-content">
        <div class="page-header-icon page-header-icon--amber">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/>
                <polyline points="17 8 12 3 7 8"/>
                <line x1="12" y1="3" x2="12" y2="15"/>
            </svg>
        </div>
        <div>
            <h1 class="page-title">Subir Artículos via CSV</h1>
            <p class="page-subtitle">Actualiza masivamente el catálogo en múltiples sucursales</p>
        </div>
    </div>
</div>

<div class="content-grid">
    <div class="card">
        <div class="card-header card-header--row" style="display: flex; justify-content: space-between; align-items: center;">
            <div>
                <h3 class="card-title">1. Configuración de Carga</h3>
                <p class="card-subtitle">Sube tu archivo y selecciona las preferencias</p>
            </div>
            <a href="{{ route('articulos.historial') }}" class="btn btn--secondary shadow-premium" style="background: rgba(245, 158, 11, 0.1); color: var(--amber); border: 1px solid rgba(245, 158, 11, 0.2); font-size: 11px; padding: 6px 14px; display: flex; align-items: center; gap: 8px;">
                <svg viewBox="0 0 24 24" fill="none" width="14" height="14" stroke="currentColor" stroke-width="2.5"><path d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                Ver Historial de Subidas
            </a>
        </div>
        <div class="card-body">
            <div id="upload-container" class="glass" style="padding: 60px 40px; text-align: center; border: 2px dashed var(--border); border-radius: var(--radius-xl); transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1); background: rgba(255,255,255,0.01); position: relative; overflow: hidden;">
                <div class="glow-effect" style="position: absolute; top: 0; left: 0; right: 0; bottom: 0; background: radial-gradient(circle at center, rgba(245,158,11,0.05) 0%, transparent 70%); opacity: 0; transition: opacity 0.4s;"></div>
                <input type="file" id="csv_file_input" accept=".csv" style="display: none;">
                <div id="drop-zone" style="cursor: pointer; position: relative; z-index: 1;">
                    <div class="upload-icon-wrapper" style="width: 80px; height: 80px; background: var(--amber-bg); border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 24px; border: 1px solid var(--amber-border); transition: transform 0.3s;">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" style="width: 32px; height: 32px; color: var(--amber);">
                            <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/>
                            <polyline points="17 8 12 3 7 8"/>
                            <line x1="12" y1="3" x2="12" y2="15"/>
                        </svg>
                    </div>
                    <h2 style="font-size: 18px; font-weight: 800; color: white; letter-spacing: -0.02em;">Arrastra tu archivo CSV aquí</h2>
                    <p style="font-size: 14px; color: var(--text-secondary); margin-top: 8px; max-width: 400px; margin-left: auto; margin-right: auto;">Actualiza tu catálogo de forma centralizada. Asegúrate de que el archivo tenga la columna <strong>Clave</strong>.</p>
                    <div style="margin-top: 24px;">
                        <button class="btn btn--primary">
                            <svg viewBox="0 0 24 24" fill="none" width="16" height="16" stroke="currentColor" stroke-width="2.5"><path d="M12 5v14M5 12h14"/></svg>
                            Seleccionar Archivo
                        </button>
                    </div>
                </div>
                <div id="file-info" style="display: none; margin-top: 20px; position: relative; z-index: 1; animation: slideIn 0.3s ease-out;">
                    <div style="background: rgba(16,185,129,0.1); border: 1px solid rgba(16,185,129,0.2); padding: 16px; border-radius: var(--radius-lg); display: inline-flex; align-items: center; gap: 12px;">
                        <svg viewBox="0 0 24 24" fill="none" width="24" height="24" stroke="var(--emerald)" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><path d="m9 15 2 2 4-4"/></svg>
                        <div style="text-align: left;">
                            <p id="filename-badge" style="font-size: 14px; font-weight: 700; color: white; margin: 0;">archivo.csv</p>
                            <p id="filesize-badge" style="font-size: 11px; color: var(--text-muted); margin: 0;">0.0 KB</p>
                        </div>
                        <button class="btn btn--ghost" style="padding: 6px; border-radius: 50%; width: 32px; height: 32px; display: flex; align-items: center; justify-content: center;" id="change-file-btn" title="Cambiar archivo">
                            <svg viewBox="0 0 24 24" fill="none" width="14" height="14" stroke="currentColor" stroke-width="2.5"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                        </button>
                    </div>
                </div>
            </div>

            <div id="config-options" style="display: none; margin-top: 32px; animation: slideIn 0.5s cubic-bezier(0.16, 1, 0.3, 1);">
                <div class="content-grid content-grid--two">
                    {{-- Columnas a Actualizar --}}
                    <div class="glass-card" style="padding: 24px; border-radius: var(--radius-xl);">
                        <div style="display: flex; align-items: center; gap: 10px; margin-bottom: 20px;">
                            <div style="width: 32px; height: 32px; background: var(--violet-bg); color: var(--violet-light); border-radius: 8px; display: flex; align-items: center; justify-content: center;">
                                <svg viewBox="0 0 24 24" fill="none" width="16" height="16" stroke="currentColor" stroke-width="2.5"><path d="M12 3h7a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-7m0-18H5a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h7m0-18v18"/></svg>
                            </div>
                            <h4 style="font-size: 15px; font-weight: 800; color: white;">Columnas a Actualizar</h4>
                        </div>
                        <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 10px;">
                            @php
                                $cols = ['Descripción', 'U.M.', 'Línea', 'Clasificación', 'Area', 'IVA', 'Ubicación', 'Sustituto', 'MN/USD', 'P. Lista', 'P. Venta', 'Desc. P. Venta', 'P. Especial', 'Desc. P. Espec', 'Precio 4', 'Desc. Precio 4', 'Costo Venta', '% Descuento', 'Art. Kit', 'Art. Serie', 'Mg Mín', 'Color', 'Protocolo', 'IDSAT', 'ID Impuesto SAT', 'Estatus'];
                            @endphp
                            @foreach($cols as $col)
                                <label class="checkbox-wrapper">
                                    <input type="checkbox" name="columns[]" value="{{ $col }}" class="form-checkbox" checked>
                                    <span class="checkbox-label">{{ $col }}</span>
                                </label>
                            @endforeach
                        </div>
                    </div>

                    {{-- Sucursales a Afectar --}}
                    <div class="glass-card" style="padding: 24px; border-radius: var(--radius-xl);">
                        <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 20px;">
                            <div style="display: flex; align-items: center; gap: 10px;">
                                <div style="width: 32px; height: 32px; background: var(--sky-bg); color: var(--sky); border-radius: 8px; display: flex; align-items: center; justify-content: center;">
                                    <svg viewBox="0 0 24 24" fill="none" width="16" height="16" stroke="currentColor" stroke-width="2.5"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/></svg>
                                </div>
                                <h4 style="font-size: 15px; font-weight: 800; color: white;">Sucursales de Destino</h4>
                            </div>
                            <label style="display: flex; align-items: center; gap: 8px; font-size: 12px; font-weight: 700; color: var(--sky); cursor: pointer; background: var(--sky-bg); padding: 4px 10px; border-radius: 6px; border: 1px solid var(--sky-border);">
                                <input type="checkbox" id="select-all-branches" class="form-checkbox" style="width:14px; height:14px;">
                                Todas
                            </label>
                        </div>
                        <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 10px;">
                            @foreach($branches as $id => $name)
                                <label class="checkbox-wrapper checkbox-wrapper--sky">
                                    <input type="checkbox" name="branches[]" value="{{ $id }}" class="branch-checkbox form-checkbox">
                                    <span class="checkbox-label">{{ $name }}</span>
                                </label>
                            @endforeach
                        </div>
                    </div>
                </div>

                <div style="margin-top: 40px; display: flex; justify-content: flex-end; gap: 16px;">
                    <button id="preview-btn" class="btn btn--ghost" style="padding: 12px 24px; border-radius: var(--radius-lg);">
                        <svg viewBox="0 0 24 24" fill="none" width="16" height="16" stroke="currentColor" stroke-width="2.5"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                        Vista Previa
                    </button>
                    <button id="process-btn" class="btn btn--primary" style="padding: 12px 32px; border-radius: var(--radius-lg); box-shadow: 0 10px 20px -5px rgba(245,158,11,0.3);">
                        <svg viewBox="0 0 24 24" fill="none" width="16" height="16" stroke="currentColor" stroke-width="2.5"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
                        Ejecutar Actualización
                    </button>
                </div>
            </div>
        </div>
    </div>

    {{-- Previsualización --}}
    <div id="preview-container" class="card card--dark" style="display: none; height: 500px; display: flex; flex-direction: column; border-color: var(--amber-border); box-shadow: 0 20px 40px -10px rgba(0,0,0,0.5);">
        <div class="card-header card-header--row" style="background: rgba(245,158,11,0.03);">
            <div style="display: flex; align-items: center; gap: 12px;">
                <div style="width: 36px; height: 36px; background: var(--amber-bg); color: var(--amber); border-radius: 10px; display: flex; align-items: center; justify-content: center;">
                    <svg viewBox="0 0 24 24" fill="none" width="18" height="18" stroke="currentColor" stroke-width="2.5"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/><polyline points="10 9 9 9 8 9"/></svg>
                </div>
                <div>
                    <h3 class="card-title" style="color: var(--amber-light);">Previsualización del CSV</h3>
                    <p class="card-subtitle">Verifica que las columnas coincidan antes de procesar</p>
                </div>
            </div>
            <div style="display: flex; gap: 8px;">
                <button id="select-changed-btn" class="btn btn--primary" style="padding: 6px 14px; font-size: 11px; background: var(--amber); border: none; display:none;">
                    <svg viewBox="0 0 24 24" fill="none" width="14" height="14" stroke="currentColor" stroke-width="2.5" style="margin-right:4px;"><path d="m9 11 3 3L22 4"/><path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"/></svg>
                    Seleccionar solo campos con cambios
                </button>
                <button onclick="document.getElementById('preview-container').style.display = 'none'" class="btn btn--ghost" style="padding: 6px 12px; font-size: 11px;">Ocultar</button>
            </div>
        </div>
        <div class="card-body" style="flex: 1; overflow: auto; padding: 0;">
            <div class="table-wrap">
                <table class="data-table" id="preview-table">
                    <thead>
                        <tr id="preview-header-row">
                            {{-- Se llena con JS --}}
                        </tr>
                    </thead>
                    <tbody id="preview-body" style="font-size: 12px;">
                        {{-- Se llena con JS --}}
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<style>
    @keyframes slideIn {
        from { opacity: 0; transform: translateY(16px); }
        to { opacity: 1; transform: translateY(0); }
    }
    
    .checkbox-wrapper {
        display: flex;
        align-items: center;
        gap: 10px;
        padding: 10px 14px;
        background: rgba(255,255,255,0.02);
        border: 1px solid var(--border);
        border-radius: var(--radius-md);
        cursor: pointer;
        transition: all 0.2s;
        user-select: none;
    }
    .checkbox-wrapper:hover {
        background: rgba(255,255,255,0.05);
        border-color: var(--violet-light);
    }
    .checkbox-wrapper--sky:hover {
        border-color: var(--sky);
    }
    
    .checkbox-label {
        font-size: 13px;
        color: var(--text-secondary);
        font-weight: 500;
        transition: color 0.2s;
    }
    .checkbox-wrapper:has(.form-checkbox:checked) {
        background: var(--violet-bg);
        border-color: var(--violet);
    }
    .checkbox-wrapper--sky:has(.form-checkbox:checked) {
        background: var(--sky-bg);
        border-color: var(--sky);
    }
    .checkbox-wrapper:has(.form-checkbox:checked) .checkbox-label {
        color: white;
        font-weight: 700;
    }

    .form-checkbox {
        width: 18px; height: 18px; 
        accent-color: var(--violet);
        border: 1.5px solid var(--border);
        border-radius: 5px;
        background: var(--bg-page);
        cursor: pointer;
    }
    .checkbox-wrapper--sky .form-checkbox {
        accent-color: var(--sky);
    }

    #upload-container:hover {
        border-color: var(--amber);
        background: rgba(245,158,11,0.02);
    }
    #upload-container:hover .glow-effect {
        opacity: 1;
    }
    #upload-container:hover .upload-icon-wrapper {
        transform: scale(1.1);
        background: var(--amber);
        color: white;
    }
    #upload-container:hover .upload-icon-wrapper svg {
        color: white;
    }
</style>
@endsection

@push('scripts')
{{-- PapaParse para facilidad de parsing --}}
<script src="https://cdnjs.cloudflare.com/ajax/libs/PapaParse/5.3.2/papaparse.min.js"></script>

<script>
document.addEventListener('DOMContentLoaded', () => {
    const dropZone = document.getElementById('drop-zone');
    const fileInput = document.getElementById('csv_file_input');
    const fileInfo = document.getElementById('file-info');
    const configOptions = document.getElementById('config-options');
    const filenameBadge = document.getElementById('filename-badge');
    const changeFileBtn = document.getElementById('change-file-btn');
    const previewBtn = document.getElementById('preview-btn');
    const processBtn = document.getElementById('process-btn');
    const selectAllBranches = document.getElementById('select-all-branches');
    const branchCheckboxes = document.querySelectorAll('.branch-checkbox');
    const selectChangedBtn = document.getElementById('select-changed-btn');

    let currentData = null;
    let selectedFile = null;
    let lastChangedCols = [];

    // Handle File Selection
    dropZone.addEventListener('click', () => fileInput.click());

    fileInput.addEventListener('change', (e) => {
        if (e.target.files.length) {
            handleFile(e.target.files[0]);
        }
    });

    changeFileBtn.addEventListener('click', () => fileInput.click());

    // Drag and Drop
    dropZone.addEventListener('dragover', (e) => {
        e.preventDefault();
        dropZone.parentElement.style.borderColor = 'var(--amber)';
    });

    dropZone.addEventListener('dragleave', () => {
        dropZone.parentElement.style.borderColor = 'var(--border)';
    });

    dropZone.addEventListener('drop', (e) => {
        e.preventDefault();
        dropZone.parentElement.style.borderColor = 'var(--border)';
        if (e.dataTransfer.files.length) {
            handleFile(e.dataTransfer.files[0]);
        }
    });

    function handleFile(file) {
        if (!file.name.endsWith('.csv')) {
            Swal.fire('Error', 'Por favor sube solo archivos .csv', 'error');
            return;
        }
        selectedFile = file;
        filenameBadge.textContent = file.name;
        
        // Formatear tamaño de archivo
        const size = file.size / 1024;
        document.getElementById('filesize-badge').textContent = size > 1024 
            ? (size / 1024).toFixed(2) + ' MB' 
            : size.toFixed(2) + ' KB';

        dropZone.style.display = 'none';
        fileInfo.style.display = 'block';
        configOptions.style.display = 'block';

        Papa.parse(file, {
            complete: (results) => {
                currentData = results.data;
            },
            header: true,
            skipEmptyLines: true
        });
    }

    // Select All Branches
    selectAllBranches.addEventListener('change', () => {
        branchCheckboxes.forEach(cb => cb.checked = selectAllBranches.checked);
    });

    // Preview Logic (Backend-powered)
    previewBtn.addEventListener('click', async () => {
        const columns = Array.from(document.querySelectorAll('input[name="columns[]"]:checked')).map(cb => cb.value);
        if (!selectedFile) {
            Swal.fire('Atención', 'Sube un archivo primero.', 'warning');
            return;
        }

        Swal.fire({
            title: 'Generando comparativa...',
            text: 'Estamos comparando tu CSV con la Base Maestra',
            allowOutsideClick: false,
            didOpen: () => Swal.showLoading()
        });

        const formData = new FormData();
        formData.append('csv_file', selectedFile);
        columns.forEach(c => formData.append('columns[]', c));

        try {
            const response = await fetch('{{ route("articulos.subir.preview") }}', {
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
                body: formData
            });

            const data = await response.json();
            Swal.close();

            if (!data.success) {
                Swal.fire('Error', data.message, 'error');
                return;
            }

            lastChangedCols = data.changed_cols || [];
            selectChangedBtn.style.display = (lastChangedCols.length > 0) ? 'flex' : 'none';

            const headerRow = document.getElementById('preview-header-row');
            const body = document.getElementById('preview-body');
            const previewContainer = document.getElementById('preview-container');

            headerRow.innerHTML = '<th style="padding:10px; font-size:11px;">CLAVE</th><th style="padding:10px; font-size:11px;">CAMBIOS DETECTADOS (VALOR VIEJO ➜ NUEVO)</th>';
            body.innerHTML = '';

            if (data.diffs.length === 0) {
                body.innerHTML = '<tr><td colspan="2" style="padding:20px; text-align:center; color:var(--emerald);">✓ Todos los artículos coinciden con la Base Maestra. No hay cambios necesarios.</td></tr>';
            } else {
                data.diffs.forEach(item => {
                    const tr = document.createElement('tr');
                    tr.style.borderBottom = '1px solid var(--border-light)';
                    
                    let diffHtml = '';
                    if (item.status === 'new') {
                        diffHtml = '<span style="color:var(--sky); font-weight:bold;">[NUEVO ARTÍCULO]</span>';
                    } else {
                        Object.keys(item.diff).forEach(field => {
                            const d = item.diff[field];
                            diffHtml += `<div style="margin-bottom:4px;">
                                <strong style="color:var(--text-muted); font-size:10px;">${field.toUpperCase()}:</strong> 
                                <span style="text-decoration:line-through; color:var(--rose); opacity:0.7;">${d.old ?? 'NULL'}</span> 
                                <span style="color:var(--emerald); font-weight:bold;">➜ ${d.new}</span>
                            </div>`;
                        });
                    }

                    tr.innerHTML = `
                        <td style="padding:12px; font-family:monospace; vertical-align:top;">
                            <div style="font-weight:bold; color:var(--amber);">${item.clave}</div>
                            <div style="font-size:10px; color:var(--text-muted);">${item.description || ''}</div>
                        </td>
                        <td style="padding:12px;">${diffHtml}</td>
                    `;
                    body.appendChild(tr);
                });
            }

            previewContainer.style.display = 'flex';
            previewContainer.scrollIntoView({ behavior: 'smooth' });

        } catch (error) {
            Swal.fire('Error', 'Fallo al conectar con el servidor para la vista previa.', 'error');
        }
    });

    // Lógica para el botón mágico de selección
    selectChangedBtn.addEventListener('click', () => {
        if (!lastChangedCols.length) return;

        const columnCheckboxes = document.querySelectorAll('input[name="columns[]"]');
        columnCheckboxes.forEach(cb => {
            cb.checked = lastChangedCols.includes(cb.value);
        });

        Swal.fire({
            icon: 'info',
            title: 'Columnas Seleccionadas',
            text: `Se han marcado automáticamente las ${lastChangedCols.length} columnas que presentan cambios en el CSV.`,
            toast: true,
            position: 'top-end',
            showConfirmButton: false,
            timer: 3000,
            background: '#1a1d27',
            color: '#fff'
        });
        
        // Desplazarse de nuevo a la configuración para ver los cambios
        configOptions.scrollIntoView({ behavior: 'smooth' });
    });

    // Process Update
    processBtn.addEventListener('click', async () => {
        const branches = Array.from(document.querySelectorAll('input[name="branches[]"]:checked')).map(cb => cb.value);
        const columns = Array.from(document.querySelectorAll('input[name="columns[]"]:checked')).map(cb => cb.value);

        if (branches.length === 0) {
            Swal.fire('Atención', 'Selecciona al menos una sucursal.', 'warning');
            return;
        }

        if (columns.length === 0) {
            Swal.fire('Atención', 'Selecciona al menos una columna para actualizar.', 'warning');
            return;
        }

        const result = await Swal.fire({
            title: '¿Confirmar actualización masiva?',
            text: `Se actualizará el DB MASTER y las ${branches.length} sucursales seleccionadas para todos los artículos en el CSV. Esta acción no se puede deshacer.`,
            icon: 'warning',
            showCancelButton: true,
            background: '#1a1d27',
            color: '#fff',
            confirmButtonText: 'Sí, procesar',
            cancelButtonText: 'Cancelar'
        });

        if (!result.isConfirmed) return;

        const formData = new FormData();
        formData.append('csv_file', selectedFile);
        branches.forEach(b => formData.append('branches[]', b));
        columns.forEach(c => formData.append('columns[]', c));

        Swal.fire({
            title: 'Procesando...',
            text: 'Por favor no cierres la ventana.',
            allowOutsideClick: false,
            didOpen: () => Swal.showLoading()
        });

        try {
            const response = await fetch('{{ route("articulos.subir.proceso") }}', {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                body: formData
            });

            const data = await response.json();

            if (data.success) {
                Swal.fire('¡Éxito!', data.message, 'success').then(() => {
                    location.reload();
                });
            } else {
                Swal.fire('Error', data.message, 'error');
            }
        } catch (error) {
            Swal.fire('Error', 'Ocurrió un error en la conexión.', 'error');
        }
    });
});
</script>
@endpush
