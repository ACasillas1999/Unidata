# AI Context Log

Shared handoff notes for any AI tool editing this project. Capped at 10 entries, most recent first.

## 2026-11-10 — Claude Code (Opus 5.5)
BUG-02 (QA): alta de cliente ahora rechaza RFC o razón social ya existentes (maestro y sucursales) ANTES de crear el maestro; responde 409 `cliente_existente` y crear.blade muestra aviso con "Ver / actualizar".
Por qué: sucursales (ERP) tienen índice único `KRazon_Social`; sin la revisión el alta quedaba solo en maestro y decía éxito (BUG-01). No quitar la revisión "por simplificar".
Decisión del usuario: en Editar, RFC y razón social NO editables, también forzado en servidor (`update()`). Cambiar razón social de una empresa real = proceso de administrador.
Datos de prueba QA en ambiente de pruebas: clientes RFC `QAX261109*`/`QAX261110*`, IdGlobal 2817–2860. No borrar sin autorización del dueño de la BD.

## 2026-11-09 — Antigravity
Inicializado el log de contexto compartido para el proyecto Unidata.
Preparando entorno colaborativo para trabajo en el módulo/formulario de clientes (`ClientesController.php` y vistas asociadas).
Precaución: Entorno en ruta compartida de red (`\\192.168.60.117`), coordinar cambios de archivos para evitar sobreescritura.

