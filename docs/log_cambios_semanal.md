# Registro de Cambios - Unidata Portal (Abril 2026)

Este documento resume los avances realizados exclusivamente en la plataforma **Unidata Portal** durante la semana actual.

## 1. Módulo de Artículos
- **Nuevo Flujo de Creación:** Se rediseñó la interfaz de `articulos/crear` reemplazando los tabs antiguos por una disposición vertical seccionada.
- **Normalización de Inputs:** 
    - Estandarización del campo de color para aceptar valores numéricos.
    - Los checkboxes de inventariable, grabado e incompleto ahora envían valores binarios consistentes (0/1).
- **Premium Design:** Aplicación de estilos de alta densidad y alineación visual en el formulario de alta manual.

## 2. Matriz de Homologación
- **Sincronización en Segundo Plano:** Implementación de un comando de Artisan y lógica en el controlador para sincronizar la matriz con sucursales sin bloquear la interfaz.
- **Exportación masiva:** Motor de exportación a Excel mediante streaming para manejar grandes volúmenes de datos.
- **Filtros de Cobertura:** Nuevos filtros para identificar artículos que faltan en sucursales específicas o tienen cobertura parcial.

## 3. DB Master Independiente
- **Gestión Snapshot:** Independencia del módulo DB Master para mantener un catálogo maestro estático.
- **Control de Sincronización:** Interfaz para disparar actualizaciones manuales desde las fuentes de datos principales hacia el motor maestro.

## 4. Gestión de Descargas Globales
- **Centro de Control:** Implementación de `DownloadsController` para centralizar todos los archivos generados por el sistema (Excel, Reportes).
- **Monitoreo Asíncrono:** Capacidad de ver el progreso de las exportaciones pesadas y descargar los archivos terminados posteriormente.

## 5. Panel de Estadísticas y Dashboard
- **Dashboard Analítico:** Transformación de las vistas estáticas en un centro de métricas funcionales (Rankings y Brechas).
- **Modales Informativos:** Adición de modales detallados que explican la procedencia de la lógica de cada indicador para el usuario final.

## 6. Seguridad y Configuración
- **Roles y Permisos Dinámicos:** Migración del sistema de accesos a una estructura de base de datos gestionable desde el portal.
- **Navegación Colapsable:** Reestructuración del sidebar con un diseño tipo acordeón para agrupar módulos de configuración de forma jerárquica.
- **Gestión de Conexiones:** Módulo para administrar las credenciales y el estado de las bases de datos de las sucursales vinculadas.
