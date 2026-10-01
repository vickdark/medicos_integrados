# 5. Instalación y entorno de desarrollo

## 5.1 Requisitos

- PHP **8.2** con extensiones habituales de Laravel (`pdo_mysql`, `mbstring`, `openssl`, `fileinfo`…).
- Composer 2.
- Node.js 20+ y npm.
- MySQL 8.
- Laravel Herd (recomendado en Windows/macOS, sirve el sitio sin configurar Nginx a mano).

## 5.2 Instalación paso a paso

```bash
# 1. Dependencias
composer install
npm install

# 2. Variables de entorno
cp .env.example .env
php artisan key:generate
```

Configurar la base de datos en `.env`:

```dotenv
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306          # en el equipo de desarrollo de referencia MySQL usa el 3378
DB_DATABASE=medicos_integrados
DB_USERNAME=root
DB_PASSWORD=
```

Crear la base de datos con collation `utf8mb4_unicode_ci` y ejecutar:

```bash
# 3. Tablas y datos de prueba
php artisan migrate --seed

# 4. Compilar el frontend
npm run build
```

Con Herd, el sitio queda disponible en `https://medicos_integrados.test` (asegurado con `herd secure`). Mantener `APP_URL` igual a esa URL, porque los enlaces de los correos y los QR de verificación se generan a partir de ella.

## 5.3 Variables de entorno relevantes

| Variable | Valor sugerido | Nota |
|---|---|---|
| `APP_LOCALE` | `es` | Mensajes de validación y fechas en español (`lang/es`) |
| `APP_TIMEZONE` | `America/Bogota` | Debe coincidir con la zona horaria que ven los usuarios (ver `01-arquitectura.md §1.6`) |
| `QUEUE_CONNECTION` | `database` | Las notificaciones se envían en cola |
| `STAFF_IDLE_MINUTES` | `15` | Minutos de inactividad antes de cerrar sesión del personal (`0` lo desactiva) |
| `MAIL_MAILER` | `log` en desarrollo, `smtp` en producción | Con `log`, los correos aparecen en `storage/logs/laravel.log` |
| `MAIL_FROM_ADDRESS` | Correo real de la clínica | |
| `FILESYSTEM_DISK` | `local` | Los adjuntos clínicos se guardan cifrados en `storage/app/private` |
| `VAPID_PUBLIC_KEY` / `VAPID_PRIVATE_KEY` / `VAPID_SUBJECT` | — | Notificaciones push (ver `06-tareas-programadas-e-integraciones.md §6.3`) |

## 5.4 Ejecución en desarrollo

```bash
composer run dev
```

Levanta a la vez el servidor, **el procesador de colas** (necesario para los correos), los logs (`pail`) y Vite con recarga en caliente.

Si el sitio ya se sirve con Herd, basta con:

```bash
npm run dev               # Vite con recarga en caliente
php artisan queue:work    # procesa las notificaciones en cola
php artisan schedule:work # tareas programadas (recordatorios de citas, cada hora)
```

Wayfinder regenera las rutas TypeScript automáticamente al correr Vite. A mano: `php artisan wayfinder:generate --with-form`.

## 5.5 Usuarios de prueba

`php artisan migrate --seed` crea especialidades, 5 médicos con horario de lunes a viernes, 26 pacientes, y citas, consultas, recetas y pagos de ejemplo (`DemoSeeder`).

| Rol | Correo | Contraseña |
|---|---|---|
| Administrador | `admin@medicos.test` | `password` |
| Recepción | `recepcion@medicos.test` | `password` |
| Médico | `medico@medicos.test` | `password` |
| Paciente | `paciente@medicos.test` | `password` |

> **Importante:** estos usuarios y `DemoSeeder` son solo para desarrollo. **Nunca** deben ejecutarse en producción — ver `DEPLOY.md §2 "Datos iniciales"`.

## 5.6 Despliegue en producción

La guía completa (requisitos del servidor, Nginx, Supervisor, cron, checklist de seguridad, actualización de versiones) está en [`../DEPLOY.md`](../DEPLOY.md).
