# AI Context Log

Shared handoff notes for any AI tool editing this project. Capped at 10 entries, most recent first.

## 2026-11-10 — Claude Code (Opus 5.5)
Integrada rama `bug-07` en main: BUG-07 (rangos numéricos), BUG-12 (Sync/FPMPFac desplegable 0/1, ya no forzado a 0), BUG-09 (fecha alta = hoy en alta; en edición no posterior a la registrada; importar toma la más antigua de las sucursales), régimen fiscal + CP obligatorios por CFDI 4.0.
Decisiones del usuario: CFDI solo se exige en ALTA (clientes viejos sin régimen/CP deben poder editarse). Fecha mínima 1970 (`FECHA_ALTA_MINIMA`), elegida por Claude, confirmar.
Pendiente: BUG-06 (cod_postal es INTEGER en maestro y sucursales: 04100 se guarda 4100), BUG-03 (RFC genéricos), BUG-11, recorte por bytes en toBranchFormat (`substr` → `mb_substr`), ¿Prefijo Descripción también 0/1?
Datos QA nuevos: QAX261110V01 (2863) y V02 (2864).

## 2026-11-10 — Antigravity
BUG-01 / Mejoras de Validación de Formulario (Clientes):
- Validación de email: Se agregó `email:filter` (máx 255) en alta y edición. Rechaza cadenas incompletas como `a@b` o sin dominio válido.
- Validación de teléfonos/fax (`telefono1`, `telefono2`, `telefono3`, `fax`): Se agregó regex `/^[+]?[0-9\s\-().]{7,15}$/` (mínimo 7 dígitos). Rechaza letras como `ABCDEFG`.
- BUG-05/06: Resuelto HTTP 500 en PowerSales cuando se registra cliente con solo RFC y Razón Social (fallback de `InvoiceAddress` y `Address1` a `' '`).
- Validación previa de `fecha_alta` (`nullable|date`) y rollback de registro en maestro huérfano si 0 sucursales reciben el cliente.

## 2026-11-10 — Claude Code (Opus 5.5)
BUG-04 (QA): `validarCaracteresSucursal()` rechaza (422) textos con caracteres fuera de Windows-1252 en alta, Editar y Editar sucursal.
Gotcha: tablas `clientes` de sucursales son latin1 (=cp1252 en MySQL); maestro es utf8mb4. Emojis/flechas/CJK pasan al maestro y truenan en las 4 sucursales. Acentos, Ñ, €, comillas tipográficas sí caben.
Pendientes del usuario: BUG-03 (RFC genéricos XAXX/XEXX necesitan rediseño: rfc UNIQUE en maestro + rutas por RFC), BUG-12 (¿Sync/Prefijo forzados a 0 a propósito?).

## 2026-11-10 — Claude Code (Opus 5.5)
BUG-02 (QA): alta de cliente ahora rechaza RFC o razón social ya existentes (maestro y sucursales) ANTES de crear el maestro; responde 409 `cliente_existente` y crear.blade muestra aviso con "Ver / actualizar".
Por qué: sucursales (ERP) tienen índice único `KRazon_Social`; sin la revisión el alta quedaba solo en maestro y decía éxito (BUG-01). No quitar la revisión "por simplificar".
Decisión del usuario: en Editar, RFC y razón social NO editables, también forzado en servidor (`update()`). Cambiar razón social de una empresa real = proceso de administrador.
Datos de prueba QA en ambiente de pruebas: clientes RFC `QAX261109*`/`QAX261110*`, IdGlobal 2817–2860. No borrar sin autorización del dueño de la BD.

## 2026-11-09 — Antigravity
Inicializado el log de contexto compartido para el proyecto Unidata.
Preparando entorno colaborativo para trabajo en el módulo/formulario de clientes (`ClientesController.php` y vistas asociadas).
Precaución: Entorno en ruta compartida de red (`\\192.168.60.117`), coordinar cambios de archivos para evitar sobreescritura.
