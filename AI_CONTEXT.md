# AI Context Log

Shared handoff notes for any AI tool editing this project. Capped at 10 entries, most recent first.

## 2026-11-18 — Claude Code (Opus 5.5)
QA Artículos e Inventario (`DBMasterController`, `ArticulosController`, `InventarioController`, `inventario/index.blade.php`):
- **ED-08:** precios `max:99999999999` (cabe en decimal(15,4) del maestro) + revisión del precio de venta calculado; `ValidationException` se relanza (antes daba 500), otros errores → mensaje genérico + log.
- **AU-01:** carga masiva rechaza celdas numéricas inválidas (`parseNumeroCsv`, acepta `1,234.5`), omite la fila y responde `filas_invalidas`. Vista previa valida TODO el archivo con la misma lista (`columnasNumericasCsv()`), calcula con "1,234.50" = 1234.50, pinta la celda en rojo "NO ES NÚMERO · se omitirá la fila" y avisa. Decisión del usuario: **no bloquear** (opción 2): se puede procesar y esas filas se omiten; la confirmación lo dice.
- **AU-05:** historial de carga masiva (`csv_historial_detalles`) compara números como número (`valorCambio()`): `35.7800` = `35.78` ya no se anota como cambio.
- **AU-02:** claves que no existen en el maestro ya no se omiten en silencio: el mensaje lista "Claves no encontradas… (N)" y el JSON trae `claves_no_encontradas`. Vista previa (`subir.blade.php`) muestra "NO EXISTE · se omitirá" en rojo en lugar de "NUEVO" (la carga masiva no crea artículos).
- **AU-04:** Desc. P. Venta usa la regla del ERP: si P. Venta = 0 → 100 (antes la app ponía 0). Función común `ArticuloValidacion::descPrecioVenta()` en carga masiva (procesar y vista previa), alta manual y Editar individual; JS de `crear.blade.php` y `db_master/index.blade.php` igual. Por qué: en las 4 sucursales ~580 mil artículos (≈45%) tienen P. Lista y P. Venta en 0 con descuento 100; con la regla vieja, editarlos desde la app los cambiaba a 0. No hubo que corregir datos.
- **AU-03 (= ED-13):** artículos no llegaban a PowerSales desde el commit `3a060e4` (1-oct), que manda `""` en todo campo de `field_mapping` sin columna ERP; PowerSales lo vuelve NULL y `products` lo rechaza (`IsDecimal`…). Fix: `buildArticuloPayload()` omite campos vacíos/null (clientes sin cambio); `post()` marca FALLO si la respuesta HTTP 200 no es JSON objeto (el error `SQLSTATE` llega como texto); carga masiva manda a PowerSales el artículo completo del maestro (`toBranchFormat($masterCurrent->fresh())`), no solo columnas del CSV. Probado sin red (`Http::fake` + `preventStrayRequests`). **Falta prueba real contra PowerSales** (crea/actualiza producto en otro sistema; decisión del usuario). Revertir del historial no deshace PowerSales (no resuelto).
- **IN-01:** buscador de inventario con `LIKE CONVERT(? USING latin1)` (mezcla de collations). **IN-02:** la vista muestra `session('error')` y `$errors`. **IN-05:** modal Min/Max distingue éxito / advertencia / error. **IN-06:** datos escapados con `Js::from()` y HTML de modales en `<template>`. **IN-07:** CSV sin encabezado ya no pierde la primera fila. **IN-03:** CSV rechaza máximo/mínimo no numérico o negativo y lista las filas omitidas. **IN-04:** al insertar en `articuloalm` se usa la clave del catálogo (`value('Clave_Articulo')`), no la escrita (aplicado, pendiente de commit).
Gotcha: columnas de sucursales son latin1_swedish_ci (no distingue mayúsculas ni acentos): `where(...)->exists()` encuentra `qatest-a03` = `QATEST-A03`; usar el valor real de la BD.
Decisiones del usuario: se permite mínimo > máximo y sin tope en inventario (~160 filas reales así, tubos almacén 1); E03, E10, C05 se aceptan. No mover datos de BD en pruebas (usar claves inexistentes `QATEST-NOEXISTE*`).
- **Clientes BUG-03 (RFC):** solo en ALTA (`store`): RFC se normaliza (mayúsculas, sin espacios/guiones), se valida formato SAT `^[A-ZÑ&]{3,4}\d{6}[A-Z0-9]{3}$` (mensaje en español) y se guarda así. Búsqueda de duplicados compara además sin espacios/guiones (`REPLACE`) en maestro y sucursales: `AOC1212063B5` ya encuentra `AOC 1212063B5` (409). Por qué solo alta: ~48% de los RFC reales del ERP llevan espacio y ~1,100 son basura (`0`, `.`); Editar tiene RFC bloqueado, los existentes no se tocan. RFC genéricos (varios XAXX) siguen sin resolver (rfc UNIQUE en maestro).
- **Clientes, recorte por bytes:** `toBranchFormat()` (ClientesController y SyncClientesMaestro) usaba `substr` (bytes): 60 Ñ quedaban en 30 y una Ñ en el límite se partía (UTF-8 roto). Ahora `mb_substr` (30 campos en cada una); sucursales latin1 = 1 letra 1 byte, así que el largo coincide. ASCII queda idéntico.
- **Clientes, decisiones del usuario (no corregir):** BUG-06 (CP `integer` pierde el 0 inicial) se deja así. BUG-11 (valores fuera de catálogo) se deja: solo pasa forzando el formulario; País/Estado ni se guardan (solo filtran la Ciudad). Datos reales: ~880 clientes con ciudad fuera del catálogo.
- **Clientes, Prefijo Descripción:** era campo numérico libre (aceptaba -5) y el servidor siempre guardaba 0. En el ERP es bandera 0/1 (tinyint; solo 10 clientes con 1). Ahora desplegable 0/1 en crear/editar, `in:0,1` en `validarNumeros`, se guarda lo elegido; importar de sucursal y `SyncClientesMaestro` leen el valor real. En Editar, si no cambió, NO se manda a sucursales (en el maestro pudo quedar 0 al importarse y pisaría el 1; ej. IdGlobal 672 y 2674).
- **Hallazgo por confirmar con dueño del ERP:** `clientes.Status` en sucursales usa `''` (mayoría), `N`, `S`, `I`; la app manda `A`/`I`. ¿El ERP reconoce `A`? No se tocó.
Gotcha: procesar la carga masiva crea un renglón en `csv_historial` aunque no actualice nada; para probar sin tocar BD usar solo la vista previa.
- **Paginación:** `->links()` default pinta la vista Tailwind (no cargada → flechas gigantes). Inventario, historial, auditoría y geografía (estados/ciudades) usan `links('pagination::bootstrap-4')` en contenedor `display:flex; justify-content:flex-end`. Queda `welcome.blade.php:280` sin tocar.
- **ART-07 (2026-11-19, solo Crear Artículo `storeManual`):** reglas sacadas de datos reales de las 4 sucursales. Área `in` tabla `areas` de sucursal (`areasValidas()`, hoy 0-5; fallback 0-5). IDSAT: se quitan espacios incl. U+00A0 (11 mil reales traen NBSP de Excel) y exige `^\d{8}$`; vacío sigue `01010101`. Sustituto/1/2: vacío o `0`, o clave existente en maestro y distinta a la propia. Línea y unidad: mayúsculas, `^[A-Z0-9]+$`; si ningún artículo del maestro la usa → regresa con `session('confirmar_catalogo')` y Swal "Sí, es nueva" (campo oculto `confirmar_catalogo=1`), no bloquea (~15 líneas reales nuevas por año). Form: unidad por defecto `PZA` (antes `PZ`, solo 3 reales), área min 0 max 5. Editar/carga masiva sin cambio.
Gotcha: ZZZZ y XX ya existen en el maestro por los artículos QA (QATEST-C04/C02), así que no disparan la confirmación. Una prueba con ellos creó **QATEST-ART07-NOGUARDAR** en maestro, 4 sucursales y PowerSales (products OK) el 19-nov 18:28. Pendiente de limpiar junto con los QATEST.
Pendiente: ED-05 (descripción vacía + `array_filter` de nulls en sync), mensajes en español en `updateItem` de inventario, C07 con archivo binario, ED-07/ED-12, prueba real de AU-03/ED-13 en PowerSales.

## 2026-11-13 — Antigravity
Corrección de Moneda (mn_usd) en Alta de Artículos:
- **Validación Flexible (`ArticulosController.php`):** Se reemplazó la regla `required|boolean` por `['required', 'string', 'in:M,U,m,u,0,1']` en `storeManual()`. La pantalla envía `M` (MXN) y `U` (USD), eliminando el fallo de validación 422 ("The mn usd field must be true or false.").
- **Normalización Estricta para Sucursales y DB Master:** En lugar de forzar a `1:0` (`!empty`), se normalizan las variantes `['U', '1', 'USD']` hacia `'U'` y el resto hacia `'M'`.
- **Integridad en ERP y PowerSales:** La columna `articulo.MN_USD` en las sucursales es `char(1)` y ahora siempre recibe `'M'` o `'U'`. Hacia PowerSales, `PowerSalesService` mapea `'U'` a `'USD'` y `'M'` a `'MXN'` en el campo `Currency` de `/pricelistsdetails`.

## 2026-11-12 — Antigravity
Soporte de Múltiples Correos en Formularios y Sincronización PowerSales:
- **UI Dinámica (`crear.blade.php` y `editar.blade.php`):** Se agregó botón `+` ("Agregar correo") para añadir múltiples campos de correo (`emails[]`) con botón de eliminar individual. En edición, los correos existentes separados por comas/punto y coma se separan y precargan en renglones independientes.
- **Validación y Normalización (`ClientesController.php`):** Método `validarYNormalizarEmails()` valida individualmente cada correo usando `FILTER_VALIDATE_EMAIL` (rechazando formatos inválidos como `a@b`). Los correos válidos se concatenan separados por coma y espacio (`, `) y se verifica longitud máxima de 255 caracteres para la base de datos.
- **Persistencia en Maestro y Sucursales:** El string completo con todos los correos se guarda en `clientes_maestro.email` y se replica en la tabla `clientes_email.e_mail` de las sucursales del ERP.
- **PowerSales (Regla del Primer Correo):** En `PowerSalesService::syncCliente()`, el payload de cliente solo toma y envía el primer correo (`$emailList[0]`) hacia `customers.Email`, manteniendo la integridad de la API de PowerSales sin romper los múltiples correos en ERP y base de datos maestra.
- **Consecutivo Real de Sucursales (`calcularSiguienteClienteSucursal`):** Al asignar el ID local `Cliente` en sucursales para nuevos registros, el sistema ignoraba el salto artificial provocado por las cuentas de CEDIS/Cotizaciones (`888888`-`888913`) y cuentas históricas. Se implementó filtro `< 800000` con exclusión de manuales, restableciendo el consecutivo continuo en cada ERP (AIESA: 120k+, DEASA: 147k+, DIMEGSA: 126k+, TAPATIA: 162k+). Probado y verificado con 5 altas continuas.

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
