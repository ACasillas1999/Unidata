# Manual de Despliegue a Producción — Sistema Unidata

**Fecha:** Noviembre 2026  
**Framework:** Laravel 12.x | **PHP:** >= 8.2 | **Frontend:** Vite + Tailwind CSS v4

---

## 1. Requisitos del Servidor Productivo

### 1.1 Sistema Operativo
> **IMPORTANTE:** El sistema ejecuta procesos pesados en segundo plano (sincronización de Matriz de Homologación, DB Master, Clientes Maestro y exportaciones Excel) mediante el comando de Windows `start "" /B` y `popen()` en `HomologacionController.php`, `DBMasterController.php`, `ClientesController.php` y `EstadisticasController.php`.
> Por lo tanto, el entorno debe ser **Windows / Windows Server** (XAMPP, Laragon o IIS). Si se desea migrar a Linux, se debe reemplazar el comando `start "" /B` por `nohup ... > /dev/null 2>&1 &`.

### 1.2 Software y Extensiones Requeridas
| Componente | Versión / Configuración Requerida |
| :--- | :--- |
| **PHP** | `>= 8.2` |
| **Extensiones PHP** | `pdo_mysql`, `mysqli`, `mbstring`, `openssl`, `curl`, `zip`, `xml`, `fileinfo`, `ctype`, `json` |
| **Configuración `php.ini`** | `memory_limit = 1024M` / `max_execution_time = 300` / `post_max_size = 64M` / `upload_max_filesize = 64M`. Verificar que `popen` y `pclose` **no** estén en `disable_functions`. |
| **Servidor Web** | Apache 2.4+ (con `mod_rewrite` habilitado) o IIS |
| **Motor de Base de Datos** | MySQL 8.0+ o MariaDB 10.6+ |
| **Gestores de Paquetes** | Composer 2.x y Node.js 18+ / NPM (solo si se compila el frontend en el servidor) |

### 1.3 Conectividad y Firewall
El servidor productivo debe contar con acceso de red hacia:
1. **Bases de datos de sucursales (Puerto TCP `3307`):**
   * `DEASA` (`192.168.20.1`)
   * `AIESA` (`192.168.40.1`)
   * `SEGSA` (`192.168.30.1`)
   * `FESA` (`192.168.50.1`)
   * `TAPATIA` (`192.168.70.1`)
   * `DIMEGSA` (`192.168.10.1`)
   * `GABSA` (`192.168.1.1`)
   * `VALLARTA` (`192.168.120.1`)
   * `QUERETARO` (`192.168.140.1`)
   * `ILUMINACION` (`192.168.2.1`)
   * `CODI / WASHINGTON` (`192.168.150.1`)
   * `CEDIS` (`192.168.100.20`)
2. **Bases de datos locales/centrales (Puerto TCP `3306`):**
   * Base del sistema (`unidata` / `laravel`)
   * Base maestra (`db_master`)
   * Base Proteo (`proteo_db`)
3. **Salida a Internet (Puerto TCP `443`):**
   * Endpoint de la API de **PowerSales**.

---

## 2. Instalación Paso a Paso

### Paso 1: Despliegue del Código Fuente
1. Copiar o clonar el repositorio en el directorio del servidor web (por ejemplo, `C:\xampp\htdocs\Unidata`).
2. **Limpieza preventiva:** No incluir (o eliminar en producción) la carpeta `scratch/`, ya que contiene scripts de diagnóstico y pruebas locales.

### Paso 2: Instalación de Dependencias y Compilación de Assets
Abrir una terminal (PowerShell o CMD) como Administrador en la raíz del proyecto (`C:\xampp\htdocs\Unidata`) y ejecutar:

```powershell
# 1. Instalar paquetes de PHP para producción (sin librerías de desarrollo)
composer install --no-dev --optimize-autoloader

# 2. Instalar paquetes de Node y compilar CSS/JS con Vite
npm install
npm run build
```

*(Nota: Si no deseas instalar Node.js en el servidor de producción, puedes ejecutar `npm run build` en tu equipo de desarrollo y copiar únicamente la carpeta `public/build` generada hacia el servidor).*

---

## 3. Configuración de Variables de Entorno (`.env`)

1. Crear el archivo `.env` a partir de la plantilla `.env.example`:
   ```powershell
   copy .env.example .env
   ```
2. Editar el archivo `.env` con los parámetros productivos:

```ini
# ── Configuración General de la Aplicación ──────────────────────────
APP_NAME="Unidata"
APP_ENV=production
APP_KEY=
APP_DEBUG=false
APP_URL=http://ip-o-dominio-productivo

LOG_CHANNEL=stack
LOG_STACK=single
LOG_LEVEL=error

# ── Base de Datos Principal del Sistema ─────────────────────────────
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=unidata
DB_USERNAME=usuario_prod
DB_PASSWORD=password_prod

# ── Conexión Local Secundaria ───────────────────────────────────────
BRANCH_LOCAL_CONNECTION=${DB_CONNECTION}
DB_LOCAL_HOST=127.0.0.1
DB_LOCAL_PORT=3306
DB_LOCAL_DATABASE=unidata
DB_LOCAL_USERNAME=usuario_prod
DB_LOCAL_PASSWORD=password_prod

# ── Conexiones a DB Master y Proteo ─────────────────────────────────
DB_MASTER_HOST=127.0.0.1
DB_MASTER_PORT=3306
DB_MASTER_DATABASE=db_master
DB_MASTER_USERNAME=usuario_prod
DB_MASTER_PASSWORD=password_prod

DB_PROTEO_HOST=127.0.0.1
DB_PROTEO_PORT=3306
DB_PROTEO_DATABASE=proteo_db
DB_PROTEO_USERNAME=usuario_prod
DB_PROTEO_PASSWORD=password_prod

# ── Sesiones, Caché y Colas ─────────────────────────────────────────
SESSION_DRIVER=database
SESSION_LIFETIME=120
QUEUE_CONNECTION=database
CACHE_STORE=database

# ── Integración con PowerSales API ──────────────────────────────────
PS_BASE_URL=https://url-productiva-powersales/api
PS_TOKEN=token_productivo_powersales
```

3. Generar la llave de encriptación de la aplicación:
   ```powershell
   php artisan key:generate
   ```

> **ADVERTENCIA:** Nunca dejes `APP_DEBUG=true` en el entorno productivo, ya que en caso de una excepción se mostrarían credenciales y rutas internas en el navegador.

---

## 4. Inicialización de la Base de Datos

Existen dos escenarios según cómo desees iniciar el servidor productivo:

### Escenario A: Migrar con los datos actuales (Recomendado si ya hay catálogos configurados)
Si en tu entorno actual ya configuraste usuarios, permisos, configuración de líneas de homologación (`homologacion_lineas_config`) y las tablas de mapeo geográfico de PowerSales:
1. Exporta el respaldo `.sql` de tu base de datos actual.
2. Impórtalo en el servidor MySQL productivo.
3. Ejecuta `php artisan migrate --force` para asegurar que cualquier migración reciente quede aplicada.

### Escenario B: Instalación limpia desde cero
Si vas a levantar la base de datos vacía desde cero:

```powershell
# 1. Crear todas las tablas del sistema
php artisan migrate --force

# 2. Poblar roles y permisos del sistema (Administrador, Coordinador, Auxiliar)
php artisan db:seed --class=RolesSeeder

# 3. Poblar catálogo inicial de las 12 sucursales
php artisan db:seed --class=BranchesSeeder

# 4. Poblar configuración de campos de Matriz de Homologación
php artisan db:seed --class=MatrizSyncCamposSeeder

# 5. Poblar configuración de campos del Maestro de Clientes
php artisan db:seed --class=ClientesCamposSeeder
```

---

## 5. Configuración del Servidor Web y Caché

### 5.1 Configuración de Apache (VirtualHost)
Apuntar el `DocumentRoot` de Apache directamente a la carpeta `public` del proyecto:

```apache
<VirtualHost *:80>
    ServerName unidata.empresa.local
    DocumentRoot "C:/xampp/htdocs/Unidata/public"

    <Directory "C:/xampp/htdocs/Unidata/public">
        Options Indexes FollowSymLinks
        AllowOverride All
        Require all granted
    </Directory>

    ErrorLog "logs/unidata-error.log"
    CustomLog "logs/unidata-access.log" common
</VirtualHost>
```

### 5.2 Permisos de Carpetas
Verifica que el servicio de Apache/PHP tenga permisos totales de **Lectura y Escritura** en:
* `C:\xampp\htdocs\Unidata\storage\` (incluyendo `storage/app` y `storage/logs`, donde se guardan los archivos `.json` de progreso de sincronización y los archivos Excel exportados).
* `C:\xampp\htdocs\Unidata\bootstrap\cache\`

### 5.3 Optimización de Rendimiento en Producción
Ejecuta los siguientes comandos para compilar las configuraciones, rutas y vistas en caché:

```powershell
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

*(Nota: Si posteriormente modificas algún valor en el archivo `.env`, deberás ejecutar `php artisan config:clear` y nuevamente `php artisan config:cache` para que tome los cambios).*

---

## 6. Checklist de Verificación Post-Despliegue

Ejecuta estas pruebas antes de liberar el sistema a los usuarios finales:

- [ ] **Prueba de conexión a sucursales por consola:**
  ```powershell
  php artisan branches:status
  php artisan branches:test DEASA
  ```
- [ ] **Acceso Web y Login:** Ingresar desde el navegador a la URL productiva e iniciar sesión con una cuenta de Administrador.
- [ ] **Verificación de Conexiones en UI:** Ir a **Configuración → Conexiones** y probar la conexión de las sucursales activas.
- [ ] **Prueba de Procesos en Segundo Plano:**
  - Ejecutar una sincronización de prueba en **Matriz de Homologación** o **Clientes** y verificar que la barra de progreso avance (confirma que `popen` y `PHP_BINARY` funcionan correctamente).
  - Revisar `storage/logs/sync_bg.log` o `storage/logs/laravel.log` en caso de cualquier incidencia.
- [ ] **Prueba de Exportación:** Descargar un reporte Excel para confirmar que el directorio temporal y la extensión `zip` funcionan sin errores.

