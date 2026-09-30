# Médicos Integrados

Sistema integral para la gestión de pacientes de un centro médico: historias clínicas, citas, pagos y portal del paciente, con una landing pública.

## Contenido

- [Funcionalidades](#funcionalidades)
- [Roles y permisos](#roles-y-permisos)
- [Stack tecnológico](#stack-tecnológico)
- [Requisitos](#requisitos)
- [Instalación](#instalación)
- [Ejecución en desarrollo](#ejecución-en-desarrollo)
- [Usuarios de prueba](#usuarios-de-prueba)
- [Arquitectura](#arquitectura)
- [Modelo de datos](#modelo-de-datos)
- [Seguridad y privacidad](#seguridad-y-privacidad)
- [Pruebas y calidad de código](#pruebas-y-calidad-de-código)
- [Convenciones](#convenciones)
- [Próximos pasos](#próximos-pasos)

## Funcionalidades

El sistema tiene tres partes:

1. **Landing pública** (`/`): presentación del centro, servicios, especialidades (se leen de la base de datos) y accesos a registro e inicio de sesión.
2. **Gestión clínica y administrativa**, para el personal de la clínica:
   - Pacientes: alta, búsqueda, edición y ficha con datos personales, antecedentes, citas, consultas y pagos.
   - Historia clínica: consultas con signos vitales, diagnóstico, tratamiento, notas internas, recetas y archivos adjuntos (PDF o imágenes).
   - Citas: agenda, confirmación y cancelación. Se valida el horario de atención del médico y que no haya otra cita en el mismo horario.
   - Pagos: registro manual (efectivo, tarjeta, transferencia u otro), en estado pendiente o pagado.
   - Médicos, especialidades y horarios de atención.
   - Auditoría de accesos y cambios sobre la información clínica.
   - **Todas las tablas** tienen paginación, buscador y filtros en el servidor, y exportación a **Excel y PDF** de lo que se está viendo.
3. **Portal del paciente**: su historial médico, sus citas (puede solicitarlas y cancelarlas), sus pagos, la descarga de sus adjuntos y la actualización de sus datos de contacto.

Además incluye:

- **Un solo inicio de sesión para todos los roles.** No hay rutas separadas por tipo de usuario (`/admin`, `/portal`, etc.): todos usan las mismas rutas y el rol decide qué menú, qué datos y qué acciones se muestran.
- **Notificaciones por correo** en español: solicitud, confirmación y cancelación de citas.
- **Autenticación completa** con Laravel Fortify: registro, recuperación de contraseña, verificación de correo y verificación en dos pasos (2FA).
- **Modo claro y oscuro** elegido por el usuario. El claro es el predeterminado y no depende de la configuración del sistema operativo.

## Roles y permisos

Los roles están en el enum `App\Enums\UserRole`. Cada permiso se aplica mediante las **Policies** de `app/Policies`.

| Acción | Admin | Recepción | Médico | Paciente |
|---|:-:|:-:|:-:|:-:|
| Ver listado de pacientes | ✅ | ✅ | Solo los que atiende | ❌ |
| Registrar o editar pacientes | ✅ | ✅ | Los que atiende | Solo sus datos de contacto |
| Ver historia clínica | ✅ | ❌ | Los que atiende | La suya |
| Registrar consultas y recetas | ❌ | ❌ | Los que atiende | ❌ |
| Subir o eliminar adjuntos | ❌ | ❌ | Sus consultas | ❌ |
| Agendar citas | ✅ (confirmadas) | ✅ (confirmadas) | ❌ | Solicitar (quedan pendientes) |
| Confirmar o cancelar citas | ✅ | ✅ | Su agenda | Solo cancelar las suyas |
| Registrar pagos | ✅ | ✅ | ❌ | ❌ |
| Ver pagos | Todos | Todos | ❌ | Los suyos |
| Médicos y especialidades | ✅ | ❌ | ❌ | ❌ |
| Horarios de atención | Todos | ❌ | El suyo | ❌ |
| Auditoría | ✅ | ❌ | ❌ | ❌ |

Un médico "atiende" a un paciente cuando tiene al menos una cita o una consulta con él.

Quien se registra desde la web queda como **paciente**. Si recepción ya había creado su ficha con el mismo correo, la cuenta se vincula automáticamente a esa ficha.

## Stack tecnológico

| Capa | Tecnología |
|---|---|
| Backend | PHP **8.2**, Laravel 12, Laravel Fortify |
| Frontend | Vue 3 + TypeScript, Inertia.js 2, Tailwind CSS 4, componentes shadcn-vue (reka-ui), iconos lucide |
| Rutas tipadas | Laravel Wayfinder (genera `resources/js/routes` y `resources/js/actions`) |
| Exportación | PhpSpreadsheet (Excel) y barryvdh/laravel-dompdf (PDF) |
| Base de datos | MySQL 8 |
| Colas y correo | Cola `database`; en desarrollo el correo se escribe en el log |
| Pruebas | Pest 3 |
| Calidad | Laravel Pint, ESLint, Prettier, vue-tsc |
| Entorno local | Laravel Herd |

La base es el starter kit oficial de Laravel para Vue, en su versión compatible con Laravel 12.

> El proyecto usa **PHP 8.2**. `composer.json` fija `config.platform.php = 8.2.32` para que las dependencias sean compatibles. No uses sintaxis exclusiva de PHP 8.3 o superior.

## Requisitos

- PHP 8.2 con las extensiones habituales de Laravel (`pdo_mysql`, `mbstring`, `openssl`, `fileinfo`…)
- Composer 2
- Node.js 20 o superior y npm
- MySQL 8
- Laravel Herd (recomendado en Windows y macOS)

## Instalación

```bash
# 1. Dependencias
composer install
npm install

# 2. Variables de entorno
cp .env.example .env
php artisan key:generate
```

Configura la base de datos en `.env`:

```dotenv
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306          # en el equipo de desarrollo actual MySQL usa el 3378
DB_DATABASE=medicos_integrados
DB_USERNAME=root
DB_PASSWORD=
```

Crea la base de datos (`utf8mb4_unicode_ci`) y ejecuta:

```bash
# 3. Tablas y datos de prueba
php artisan migrate --seed

# 4. Compilar el frontend
npm run build
```

Con Herd, el sitio queda disponible en `https://medicos_integrados.test` (con el sitio asegurado mediante `herd secure`). Mantén `APP_URL` con la misma URL, porque los enlaces de los correos se generan a partir de ella.

### Variables de entorno relevantes

| Variable | Valor sugerido | Nota |
|---|---|---|
| `APP_LOCALE` | `es` | Mensajes de validación y fechas en español (`lang/es`) |
| `QUEUE_CONNECTION` | `database` | Las notificaciones se envían en cola |
| `MAIL_MAILER` | `log` en desarrollo, `smtp` en producción | |
| `MAIL_FROM_ADDRESS` | Correo real de la clínica | |
| `FILESYSTEM_DISK` | `local` | Los adjuntos clínicos se guardan en `storage/app/private` |

## Ejecución en desarrollo

```bash
composer run dev
```

Este comando levanta a la vez el servidor, **el procesador de colas** (necesario para los correos), los logs (`pail`) y Vite con recarga en caliente.

Si usas Herd para servir el sitio, basta con:

```bash
npm run dev               # Vite con recarga en caliente
php artisan queue:work    # procesa las notificaciones en cola
```

Con `MAIL_MAILER=log`, los correos enviados aparecen en `storage/logs/laravel.log`.

Wayfinder regenera las rutas TypeScript automáticamente al ejecutar Vite. Para hacerlo a mano: `php artisan wayfinder:generate --with-form`.

## Usuarios de prueba

`php artisan migrate --seed` crea especialidades, 5 médicos con horario de lunes a viernes, 26 pacientes, y citas, consultas, recetas y pagos de ejemplo.

| Rol | Correo | Contraseña |
|---|---|---|
| Administrador | `admin@medicos.test` | `password` |
| Recepción | `recepcion@medicos.test` | `password` |
| Médico | `medico@medicos.test` | `password` |
| Paciente | `paciente@medicos.test` | `password` |

## Arquitectura

### Flujo general

```
Navegador (Vue + Inertia)
      │  visitas Inertia / formularios <Form> con rutas de Wayfinder
      ▼
routes/web.php ──► Controlador ──► Form Request (validación + autorización)
                        │                 │
                        │                 └─► Policy (permisos por rol)
                        ▼
                 Modelos Eloquent ──► MySQL
                        │
                        ▼
              API Resources ──► Inertia::render('pagina', props)
```

### Estructura de carpetas

```
app/
├── Actions/Fortify/        Registro (crea o vincula la ficha del paciente) y reseteo de contraseña
├── Concerns/               Traits de validación y opciones de enums
├── Enums/                  UserRole, AppointmentStatus, PaymentMethod, PaymentStatus, Gender, AuditAction, ExportFormat
├── Exports/                TableExporter (genera Excel y PDF) y una clase *Export por tabla con sus columnas
├── Http/
│   ├── Controllers/        Un controlador por módulo (Patient, Appointment, Consultation, Payment…)
│   ├── Middleware/         HandleInertiaRequests (props compartidos), HandleAppearance (tema)
│   ├── Requests/           Form Requests: validación y autorización
│   └── Resources/          Serialización de modelos hacia Vue
├── Models/                 Modelos Eloquent y scopes (visibleTo, treatedBy, search)
├── Notifications/          Correos de citas (en cola)
└── Policies/               Permisos por rol
database/
├── factories/              Factories con estados (admin(), doctor(), confirmed(), withAccount()…)
├── migrations/
└── seeders/                SpecialtySeeder y DemoSeeder
lang/es/                    Traducciones de validación, autenticación y paginación
resources/js/
├── components/             Componentes propios (StatusBadge, ThemeToggle…) y ui/ (shadcn-vue)
├── composables/            useAppearance, useTwoFactorAuth…
├── layouts/                Layout de la aplicación, de autenticación y de configuración
├── lib/format.ts           Formato de fechas, montos y tamaños de archivo
├── pages/                  Páginas Inertia, agrupadas por módulo
└── types/                  Tipos TypeScript (models.ts, auth.ts…)
tests/Feature/              Pruebas Pest por módulo
```

### Props compartidos con el frontend

`HandleInertiaRequests` comparte en todas las páginas:

- `auth.user`: el usuario autenticado.
- `auth.role`: su rol, como `{ value, label }`.
- `auth.patientId` y `auth.doctorId`: la ficha del paciente o del médico asociada al usuario.
- `flash.success` y `flash.error`: mensajes que muestra `FlashMessage`.

El menú lateral (`AppSidebar.vue`) se arma según `auth.role`.

### Rutas principales

| Ruta | Descripción |
|---|---|
| `GET /` | Landing |
| `GET /dashboard` | Inicio adaptado al rol |
| `/patients` | Pacientes (resource sin `destroy`). `patients/{id}` es también "Mi historial" del paciente |
| `/patients/{patient}/consultations/create` y `POST` | Registrar consulta |
| `GET /consultations/{consultation}` | Detalle de consulta |
| `POST /consultations/{consultation}/attachments` | Subir adjunto |
| `GET` y `DELETE /attachments/{attachment}` | Descargar o eliminar adjunto |
| `/appointments` y `PATCH /appointments/{id}/status` | Citas y cambio de estado |
| `/payments` | Pagos |
| `/doctors` y `/doctors/{doctor}/schedules` | Médicos y horarios |
| `/specialties` | Especialidades |
| `GET /audit-logs` | Auditoría |
| `/settings/*` | Perfil, seguridad (contraseña, 2FA) y apariencia |

Para ver el listado completo: `php artisan route:list --except-vendor`.

## Modelo de datos

```
users ─┬─ 1:1 ─ doctors ─┬─ N:1 ─ specialties
       │                 └─ 1:N ─ doctor_schedules
       └─ 1:1 ─ patients (user_id opcional: puede existir sin cuenta)

patients ─┬─ 1:N ─ appointments ── N:1 ─ doctors
          ├─ 1:N ─ consultations ─┬─ N:1 ─ doctors
          │                       ├─ 1:1 ─ appointments (opcional)
          │                       ├─ 1:N ─ prescriptions
          │                       └─ 1:N ─ consultation_attachments
          ├─ 1:N ─ payments ── N:1 ─ appointments (opcional)
          └─ 1:N ─ audit_logs (también polimórfico: auditable_type/id)
```

Estados:

- **Cita:** `requested` (solicitada) → `confirmed` (confirmada) → `completed` (completada), o `cancelled` (cancelada). Pasa a completada automáticamente al registrar la consulta asociada.
- **Pago:** `pending` (pendiente) o `paid` (pagado). Existe también `voided` (anulado), aún sin interfaz.

## Tablas: búsqueda, filtros y exportación

Todas las tablas funcionan del lado del servidor:

1. El controlador de cada listado tiene un método `filteredQuery(TableQueryRequest)` que aplica los permisos del rol, la búsqueda y los filtros.
2. `index()` pagina esa consulta de 10 en 10 (`Controller::TABLE_PAGE_SIZE`) y `export()` la reutiliza. Así, **lo que se exporta es exactamente lo que se ve**.
3. En el frontend, `useTableFilters` sincroniza los filtros con la URL (la búsqueda espera 300 ms mientras escribes), y `TableToolbar` muestra el buscador, los filtros extra y los botones **Excel** / **PDF**.

| Tabla | Búsqueda | Filtros |
|---|---|---|
| Pacientes | Nombre, documento, correo | — |
| Citas | Paciente, documento, médico, motivo | Estado, rango de fechas |
| Pagos | Paciente, concepto, referencia | Estado, rango de fechas (los totales se recalculan) |
| Médicos | Nombre, correo, especialidad, colegiatura | — |
| Especialidades | Nombre, descripción | — |
| Auditoría | Usuario, paciente, descripción, IP | Acción, paciente, rango de fechas |

Sobre la exportación (`GET /{tabla}/export?format=xlsx|pdf&…filtros`):

- **Excel:** encabezados con formato, autofiltro, primera fila congelada y montos numéricos. Los textos se guardan como texto literal, para que un valor como `=HYPERLINK(...)` nunca se ejecute como fórmula.
- **PDF:** hoja A4 horizontal con los filtros aplicados, la fecha y el usuario que lo generó. Muestra como máximo 1.000 filas; para más, usa Excel.
- **Datos excluidos:** el listado de pacientes **no incluye datos clínicos**.
- **Auditoría:** cada exportación queda registrada (acción "Exportó").
- **Agregar una tabla nueva:** crear una clase en `app/Exports` que extienda `TableExport` (título, encabezados y filas), agregar la acción `export()` y la ruta `…/export` **antes** del `Route::resource`.

## Seguridad y privacidad

- **Cifrado en base de datos:** alergias, enfermedades crónicas, antecedentes, motivo, síntomas, diagnóstico, tratamiento y notas usan el cast `encrypted` de Laravel. Conserva el `APP_KEY`: si se pierde, estos datos no se pueden recuperar.
- **Permisos en el servidor:** toda acción pasa por una Policy, y los listados se filtran con scopes (`visibleTo`, `treatedBy`). Ocultar un botón en el frontend no es la protección real.
- **Datos que cada rol no ve:** recepción no ve antecedentes ni consultas, y los pacientes no ven las notas internas del médico.
- **Adjuntos privados:** se guardan en un disco que no es público y solo se descargan a través de un controlador que verifica permisos.
- **Auditoría:** se registra quién consulta historias clínicas o consultas, las altas y modificaciones de pacientes, las consultas creadas y las subidas, descargas y eliminaciones de adjuntos, con usuario, IP y fecha.
- **Autenticación:** incluye límite de intentos de login, verificación en dos pasos opcional y confirmación de contraseña para las acciones sensibles.

## Pruebas y calidad de código

```bash
php artisan test --compact            # suite completa (Pest)
php artisan test --filter=Appointment # un módulo

vendor/bin/pint                       # estilo PHP
npm run lint                          # ESLint
npm run format                        # Prettier
npm run types:check                   # vue-tsc
```

- Las pruebas usan SQLite en memoria (`phpunit.xml`) y `RefreshDatabase`, así que no tocan la base de MySQL.
- Las pruebas que renderizan páginas Inertia necesitan el manifest de Vite. Si fallan con `Unable to locate file in Vite manifest`, ejecuta `npm run build`.

## Convenciones

- **Idioma:** el código (clases, métodos, variables, columnas) está en inglés; los textos visibles, en español.
- **Validación:** siempre con Form Requests, que también autorizan mediante la Policy.
- **Datos hacia el frontend:** con API Resources (sin envoltura `data`: `JsonResource::withoutWrapping()`). Los listados paginados se mapean con `->through()`.
- **Frontend:** formularios con `<Form>` de Inertia y acciones de Wayfinder (`Controller.store.form()`). Los módulos de rutas se importan con el sufijo `Routes` (`patientRoutes`, `appointmentRoutes`) para no chocar con props del mismo nombre.
- **Enums:** con `label()` en español y el trait `HasEnumOptions` (`options()` y `toOption()`) para los selects.
- **Archivos nuevos:** crearlos con `php artisan make:*`.
- **Antes de hacer commit:** `vendor/bin/pint --dirty`, `npm run lint`, `npm run format` y `php artisan test`.

## Próximos pasos

- API con Laravel Sanctum para una futura app móvil.
- Pasarela de pagos en línea (hoy los pagos se registran a mano).
- Interfaz para anular pagos y para reprogramar citas.
- Recordatorios automáticos de citas.
- Reportes (ingresos, citas por médico o especialidad).
- Reemplazar los datos de contacto de ejemplo de la landing por los reales.
