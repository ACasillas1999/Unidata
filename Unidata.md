# Análisis Profesional del Proyecto: Unidata

## 🛠 Stack Tecnológico
* **Backend:** PHP 8.2+, Laravel 11 (Arquitectura Multi-conexión de bases de datos).
* **Frontend:** Blade, CSS Nativo (Variables CSS, Diseño Moderno Dashboard), JavaScript (Vanilla + SweetAlert2).
* **Bases de Datos:** MySQL / MariaDB (Entorno Distribuido).
* **Herramientas & Entorno:** Servidor Windows (XAMPP), comandos de consola asíncronos nativos (`start /B`), JSON IPC.

## 🚀 Funcionalidades Clave Desarrolladas
* **¿Qué construí?** Desarrollé **Unidata**, un portal centralizado (Hub) para la orquestación y sincronización de catálogos de artículos a través de múltiples sucursales con bases de datos distribuidas e independientes.
* **¿Qué problema resuelve?** Elimina la inconsistencia de catálogos entre sucursales físicas y evita el bloqueo del servidor web al procesar o exportar bases de datos gigantescas.
* **Mi rol técnico:** Arquitecto de Software y Desarrollador Full-Stack, responsable desde el diseño de la interfaz de usuario moderna hasta el motor de fondo de sincronización multi-base de datos y la gestión asíncrona de reportes.

## 🏆 Logros Técnicos Destacables
* **Arquitectura de Datos Distribuidos:** Implementación de un motor dinámico en Laravel capaz de orquestar conexiones simultáneas a múltiples bases de datos de sucursales externas para cruzar la información con una "Base de Datos Maestra".
* **Sistemas de Ejecución Asíncrona (Windows/XAMPP):** Ante las limitaciones de no contar con colas avanzadas (como Redis), construí un motor de trabajos en segundo plano utilizando comandos nativos del SO (`start /B`), con un sistema de *polling* en tiempo real para reportar el progreso al frontend.
* **Centro de Descargas Asíncrono (GDC):** Desarrollo de un sistema de generación de reportes pesados en Excel en segundo plano, notificando al usuario en una campana del dashboard una vez que el archivo está listo.
* **Carga Masiva con Rollback:** Creación de un importador de archivos CSV para miles de registros, incorporando vistas previas de validación y un historial de subidas con capacidad de revertir cambios masivos (*Rollback*).

## 🧠 Soft Skills Implícitas
* **Resolución Creativa de Problemas:** Superación de restricciones técnicas del entorno de producción (Windows) aplicando soluciones asíncronas ingeniosas y efectivas.
* **Visión Arquitectónica:** Capacidad para diseñar sistemas descentralizados, planificando flujos de datos complejos (diagramas de secuencia, master-branch replicas).
* **Orientación a Experiencia de Usuario (UX):** Cuidado en la interfaz implementando notificaciones no intrusivas, barras de progreso en tiempo real y estética limpia ("Dashboard") que mejora la operatividad del usuario final.

## 📄 Descripción Lista para el CV
> "Fui el arquitecto y desarrollador principal de 'Unidata', un orquestador centralizado en Laravel 11 que sincroniza catálogos maestros a través de múltiples bases de datos distribuidas. Resolví problemas de bloqueos en el servidor implementando un motor asíncrono de procesamiento masivo y exportación de reportes (adaptado a Windows Server), integrando además un importador de CSV transaccional con capacidad de rollback y una interfaz de usuario en tiempo real."
