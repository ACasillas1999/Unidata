# Mockups Visuales - Proyecto Unidata

Esta sección presenta la visión visual del portal central de Unidata, siguiendo una estética moderna y profesional compatible con el stack tecnológico actual.

## 1. Dashboard de Sincronización y Sucursales
Este mockup representa la pantalla principal donde se gestionan las conexiones de las sucursales y se monitorea el estado de sincronización global.

![Dashboard de Sincronización](/C:/Users/compras-ovalo6/.gemini/antigravity/brain/43bd426e-6cad-4a37-bf91-7f8c30707925/artifacts/dashboard_mockup.png)

### Elementos Clave:
- **Sidebar Colapsable**: Acceso rápido a Artículos, Homologación, DB Master y Conexiones.
- **Tarjetas de Sucursal**: Indicadores visuales claros del estado de conexión (Online/Offline) de cada base de datos remota.
- **Notificación Flotante (Toast)**: Interfaz de polling que muestra el progreso de la sincronización en segundo plano sin bloquear al usuario.

---

## 2. Módulo de Carga de Artículos (Visión Técnica)
Aunque el sistema es funcionalmente robusto, la propuesta visual para la carga de CSV se centra en la claridad de datos:

### Flujo de Usuario:
1. **Drag & Drop**: Zona de carga intuitiva para archivos CSV/Excel.
2. **Mapeo Dinámico**: Interfaz para asociar las columnas del archivo subido con los campos técnicos de la base de datos (clave, descripción, precio, etc.).
3. **Validación Previa**: Visualización en verde/rojo de los datos que se van a insertar o actualizar antes de confirmar el proceso.

---

## 3. Centro de Descargas Globales (GDC)
Propuesta de interfaz para la campana de notificaciones:
- **Panel Desplegable**: Muestra una lista de trabajos de exportación recientes.
- **Barras de Progreso**: Indicador en tiempo real del avance en la generación de archivos pesados.
- **Botón de Descarga Directa**: Aparece automáticamente cuando el proceso en fondo termina.

---

## 4. Guía de Estilos (UI Kit)
El sistema utiliza una paleta de colores coherente:
- **Primario**: `#2063cf` (Azul corporativo para acciones principales).
- **Éxito**: `#10b981` (Para estados de conexión "Online" y finalización).
- **Peligro**: `#f43f5e` (Para errores de conexión o procesos cancelados).
- **Tipografía**: Inter (Sistema) e Instrument Sans (Títulos).
