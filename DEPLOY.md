# Despliegue en producción

Guía para publicar **Médicos Integrados** en un servidor Linux propio (VPS). Maneja datos clínicos: no omitas la sección de seguridad.

## 1. Requisitos del servidor

- Ubuntu 22.04/24.04 (o similar) con acceso SSH y un dominio apuntando al servidor.
- **PHP 8.2** con extensiones: `bcmath ctype curl dom fileinfo gd intl mbstring openssl pdo_mysql tokenizer xml zip`.
- **MySQL 8** (o MariaDB 10.6+) con base `utf8mb4_unicode_ci`.
- **Nginx** (o Apache) + PHP-FPM, **Composer 2**, **Node 20+** (solo para compilar el frontend; puede hacerse en otra máquina o en CI).
- **Supervisor** (procesador de colas) y **cron**.
- **HTTPS obligatorio** (Let's Encrypt/certbot). Sin HTTPS no funcionan las notificaciones push y las cookies de sesión viajarían sin protección.
- Zona horaria del servidor/PHP: `America/Bogota` (las citas se guardan con la hora local de la clínica).

## 2. Primer despliegue

```bash
cd /var/www
git clone <repositorio> medicos_integrados
cd medicos_integrados

composer install --no-dev --optimize-autoloader
npm ci && npm run build

cp .env.example .env
php artisan key:generate
```

Edita `.env` (ver sección 3) y luego:

```bash
php artisan migrate --force
php artisan storage:link        # solo si algún día sirves archivos públicos; los adjuntos NO se publican
php artisan optimize            # cachea config, rutas, vistas y eventos
```

Permisos: el usuario del servidor web (`www-data`) debe poder escribir en `storage/` y `bootstrap/cache/`.

```bash
sudo chown -R www-data:www-data storage bootstrap/cache
sudo chmod -R ug+rwX storage bootstrap/cache
```

### Datos iniciales (¡sin usuarios de prueba!)

**No ejecutes `php artisan migrate --seed` ni `DemoSeeder`:** crean cuentas con la contraseña `password` y datos ficticios. En producción carga solo los catálogos:

```bash
php artisan db:seed --class=SpecialtySeeder --force
php artisan db:seed --class=MedicationSeeder --force
php artisan db:seed --class=InsurerSeeder --force
php artisan db:seed --class=DiagnosisSeeder --force   # catálogo CIE-10 de arranque
```

Para el catálogo CIE-10 completo, importa la tabla oficial de SISPRO (primero simula):

```bash
php artisan diagnoses:import /ruta/cie10.xlsx --dry-run
php artisan diagnoses:import /ruta/cie10.xlsx
```

Crea el primer administrador desde `php artisan tinker` (contraseña fuerte y única) y cámbiala/activa la verificación en dos pasos en el primer ingreso:

```php
App\Models\User::factory()->admin()->create([
    'name' => 'Nombre Apellido',
    'email' => 'admin@tuclinica.com',
    'password' => 'una-contraseña-larga-y-única',
]);
```

El resto de usuarios (médicos, recepción) se crean desde la aplicación.

Después de ingresar, completa en **Configuración → Política de datos** los datos del responsable (razón social, NIT, dirección, teléfono, correo de contacto y, si aplica, el registro RNBD). Sin esto, `/privacidad` publica solo la razón social y el correo por defecto. Haz revisar el texto de `/privacidad` por un abogado antes de abrir el registro de pacientes.

Reemplaza también los datos de contacto y los textos de ejemplo de la página pública en **Configuración → Página de inicio** (teléfono, correo y dirección del pie, *Quiénes somos* y *Servicios médicos*).

## 3. Variables de entorno (`.env`)

```dotenv
APP_NAME="Médicos Integrados"
APP_ENV=production
APP_DEBUG=false                  # nunca true en producción
APP_URL=https://tudominio.com    # los enlaces de los correos se generan con este valor
APP_LOCALE=es
APP_TIMEZONE=America/Bogota

LOG_LEVEL=warning

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=medicos_integrados
DB_USERNAME=medicos            # usuario propio con permisos solo sobre esa base, no root
DB_PASSWORD=...

SESSION_DRIVER=database
SESSION_SECURE_COOKIE=true
SESSION_ENCRYPT=true
QUEUE_CONNECTION=database
CACHE_STORE=database
FILESYSTEM_DISK=local           # adjuntos clínicos en storage/app/private

MAIL_MAILER=smtp
MAIL_HOST=...
MAIL_PORT=587
MAIL_USERNAME=...
MAIL_PASSWORD=...
MAIL_FROM_ADDRESS="notificaciones@tuclinica.com"
MAIL_FROM_NAME="${APP_NAME}"

VAPID_SUBJECT="mailto:contacto@tuclinica.com"
VAPID_PUBLIC_KEY=
VAPID_PRIVATE_KEY=              # php artisan webpush:vapid (una sola vez)
```

Después de cambiar `.env` en producción: `php artisan config:cache` (o `optimize`) y reiniciar las colas (`php artisan queue:restart`).

## 4. Nginx

```nginx
server {
    listen 443 ssl http2;
    server_name tudominio.com;
    root /var/www/medicos_integrados/public;
    index index.php;

    client_max_body_size 12M;   # adjuntos de hasta 10 MB

    add_header X-Frame-Options "SAMEORIGIN";
    add_header X-Content-Type-Options "nosniff";
    add_header Strict-Transport-Security "max-age=31536000; includeSubDomains" always;

    location / { try_files $uri $uri/ /index.php?$query_string; }

    location ~ \.php$ {
        fastcgi_pass unix:/run/php/php8.2-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
    }

    location ~ /\.(?!well-known).* { deny all; }
}
```

Ajusta también `upload_max_filesize = 12M` y `post_max_size = 12M` en `php.ini`. Redirige el puerto 80 a HTTPS.

## 5. Procesos en segundo plano

**Cola** (correos y notificaciones push). Supervisor, `/etc/supervisor/conf.d/medicos-worker.conf`:

```ini
[program:medicos-worker]
command=php /var/www/medicos_integrados/artisan queue:work --sleep=3 --tries=3 --max-time=3600
user=www-data
autostart=true
autorestart=true
stopasgroup=true
killasgroup=true
stdout_logfile=/var/www/medicos_integrados/storage/logs/worker.log
stopwaitsecs=3600
```

```bash
sudo supervisorctl reread && sudo supervisorctl update && sudo supervisorctl start medicos-worker:*
```

**Tareas programadas** (recordatorios de citas cada hora). Cron del usuario `www-data`:

```cron
* * * * * cd /var/www/medicos_integrados && php artisan schedule:run >> /dev/null 2>&1
```

## 6. Cifrado de los adjuntos clínicos (consideración final)

Los archivos de las consultas se guardan **cifrados** en `storage/app/private/consultations/` con el `APP_KEY` (extensión `.enc`), y solo se descargan a través del controlador, que verifica permisos y los descifra al vuelo.

- **Instalación nueva:** no hay nada que hacer; todo adjunto nuevo ya se guarda cifrado.
- **Si migras una base con adjuntos anteriores** (guardados sin cifrar), ejecuta **una sola vez** tras `migrate`:

  ```bash
  php artisan attachments:encrypt
  ```

  Hasta entonces esos archivos antiguos siguen funcionando, pero en claro. El comando es repetible y avisa de los archivos que ya no estén en disco.
- **El `APP_KEY` es crítico:** cifra los adjuntos, los campos clínicos de la base de datos (diagnóstico, tratamiento, antecedentes, notas…) y los motivos de corrección/anulación. **Si se pierde o se cambia, esos datos son irrecuperables.** Guárdalo en un gestor de secretos, fuera del servidor y fuera del repositorio. No rotes la clave sin un plan de re-cifrado.
- **Copias de seguridad:** respalda **juntos** la base de datos y `storage/app/private/`, y guarda el `APP_KEY` por separado. Un respaldo de la base sin los archivos (o al revés) deja la historia clínica incompleta. Prueba la restauración periódicamente.
- Los archivos cifrados se leen completos en memoria al descargarse; con el límite de 10 MB por archivo es adecuado.

## 7. Seguridad antes de abrir al público

- [ ] `APP_DEBUG=false` y `APP_ENV=production`.
- [ ] Sin usuarios ni datos de demostración (`admin@medicos.test`, etc.).
- [ ] HTTPS activo, `SESSION_SECURE_COOKIE=true`.
- [ ] Usuario de base de datos propio y contraseñas fuertes; MySQL accesible solo desde `localhost`.
- [ ] Firewall: solo 22 (idealmente restringido), 80 y 443.
- [ ] `.env` fuera del alcance web (el `root` de Nginx es `public/`) y con permisos `640`.
- [ ] Verificación en dos pasos activada para administradores y médicos.
- [ ] Respaldos automáticos probados (base de datos + `storage/app/private/`).
- [ ] Revisar con asesoría legal el cumplimiento de la Ley 1581 (datos personales) y la normativa de historia clínica (Res. 1995 de 1999) antes de operar.

## 8. Actualizar una versión

```bash
cd /var/www/medicos_integrados
php artisan down --retry=60
git pull origin main
composer install --no-dev --optimize-autoloader
npm ci && npm run build
php artisan migrate --force
php artisan optimize
php artisan queue:restart
php artisan up
```

Haz un respaldo de la base de datos antes de cada `migrate`. Si una versión nueva incluye un comando de datos (como `attachments:encrypt`), ejecútalo después de migrar.

## 9. Verificación posterior

- Entra con el administrador, crea un médico y un paciente de prueba, y elimínalos al terminar (o usa datos reales desde el inicio).
- Sube un adjunto a una consulta y descárgalo: debe abrirse correctamente, y el archivo en `storage/app/private/consultations/` debe ser ilegible.
- Envía una cita de prueba y confirma que el correo llega (cola y SMTP funcionando).
- Revisa `storage/logs/laravel.log` y `supervisorctl status`.
