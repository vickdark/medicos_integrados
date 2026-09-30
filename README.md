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
   - **Calendario de citas** (médico, recepción y paciente, sobre `/appointments`): en pantallas grandes vistas Día (por defecto), Semana y Mes; en móvil, mini calendario mensual con vista Día y Agenda como segunda opción. Alterna con la vista Lista (filtros y exportación). Los datos vienen de `GET /appointments/calendar?from&to` (máx. 62 días) y respetan la visibilidad por rol.
   - **Agendar y reprogramar con calendario:** al agendar, solicitar o reprogramar se elige el horario en un calendario semanal (diario en móvil) que muestra los espacios libres, los ocupados y los que están fuera del horario de atención. Cada médico define la duración de sus citas (10 a 60 min, `doctors.slot_minutes`) y el sistema evita citas que se solapan. Los datos vienen de `GET /doctors/{doctor}/availability?from&to`. En Horario del médico se pueden crear bloques para varios días a la vez y ver la semana completa; en la agenda del médico las horas fuera de horario aparecen rayadas.
   - **Reprogramar con otro médico (admin y recepción):** al reprogramar una cita activa pueden cambiar también el médico. La disponibilidad y los choques se validan contra el nuevo médico, la cita conserva su estado, el cobro pendiente se ajusta a la tarifa del nuevo médico (uno ya pagado no se toca) y se avisa por correo al paciente, al médico anterior y al nuevo. También pueden editar el motivo y las notas. Se puede mover al mismo día si hay espacios libres.
   - **El paciente edita su cita mientras no esté confirmada:** con la cita en estado *Solicitada* puede cambiar el médico, el motivo, las notas y el horario (`GET/PUT /appointments/{id}`, política `editDetails`); la cita sigue solicitada y se avisa a admin, recepción y a ambos médicos. Una vez confirmada solo puede pedir otro horario (vuelve a *Solicitada* para que la clínica la reconfirme) y el médico y el motivo quedan bloqueados. Los médicos no pueden cambiar la cita a otro médico.
   - **Pago rápido:** el botón de pago en la fila de una cita abre el formulario con paciente, cita, concepto y monto (tarifa del médico) precargados; en Pagos, los pendientes se marcan como pagados desde un diálogo (`PATCH /payments/{id}/paid`).
   - **Facturas en PDF:** cada pago pagado tiene su factura (`GET /payments/{id}/invoice`, número `F-000123`), descargable por admin, recepción y por el propio paciente desde Pagos, el detalle del pago y su historial. Los pagos pendientes no se facturan.
   - **Historia clínica en PDF:** desde la ficha del paciente (`GET /patients/{id}/clinical-history?from&to`). Con rango de fechas incluye solo las consultas de ese período; sin fechas, todo el historial. Incluye datos del paciente, antecedentes, consultas con signos vitales y recetas. Las notas privadas solo se incluyen para el personal, solo la ven quienes pueden leer la historia clínica y cada descarga queda en la auditoría.
   - **Receta en PDF:** desde el detalle de una consulta con medicamentos (`GET /consultations/{id}/prescription`), se abre en una pestaña nueva. El médico que la escribió obtiene la copia oficial (datos del paciente y del médico, diagnóstico, medicamentos con dosis, frecuencia y duración, indicaciones y firma) y puede enviarla por correo como adjunto al correo registrado del paciente o a otro que este le indique (`POST /consultations/{id}/prescription/email`, sin cola para no guardar el PDF en `jobs`). El administrador puede verla, pero como copia marcada "Este documento no es válido" con marca de agua, solo de consulta. Recepción y pacientes no tienen acceso. Cada emisión, consulta o envío queda en la auditoría.
   - **Color de la aplicación (solo admin):** en Configuración → Color de la aplicación el administrador elige el color de acento global (colores sugeridos o uno personalizado, con vista previa en vivo). Se guarda en la tabla `app_settings` (`brand_color`), se aplica como variable CSS `--brand` y de ella salen los tonos `brand-50…950` de Tailwind que usan el logo, las gráficas, las etiquetas, la página de inicio y la visita guiada; también colorea los PDF (facturas, receta, historia clínica, tablas) y el encabezado de los Excel. Se rechazan colores demasiado claros (contraste mínimo 3:1 con blanco) y hay botón para restablecer el original (verde azulado). Los cambios quedan en la auditoría.
   - **Médicos en la landing (solo admin):** en Configuración → Personalización el administrador puede activar "Mostrar a los médicos en la página de inicio" (apagado por defecto, clave `landing_show_doctors`). Si está activo, la sección *Quiénes somos* muestra tarjetas con foto, nombre, especialidad y reseña de los médicos con cuenta activa (nunca colegiatura, tarifa ni correo). La foto (JPG, PNG o WebP de hasta 2 MB) se carga al crear o editar al médico en Usuarios; se redimensiona (máx. 800 px) y se comprime a WebP al subirla, se guarda en el disco privado y se sirve por `GET /doctors/{id}/photo`, público solo mientras la landing muestra a los médicos y visible siempre para usuarios con sesión.
   - Las acciones de las tablas usan botones de icono con tooltip (`IconButton`).
   - Pagos: registro manual (efectivo, tarjeta, transferencia u otro), en estado pendiente o pagado.
   - Médicos, especialidades y horarios de atención.
   - Auditoría de accesos y cambios sobre la información clínica.
   - **Todas las tablas** tienen paginación, buscador y filtros en el servidor, y exportación a **Excel y PDF** de lo que se está viendo.
   - **Tablas como tarjetas en móvil:** por debajo de 768 px cada fila se muestra como una tarjeta con el nombre de la columna junto a cada valor (clase `cards` en la tabla y atributo `data-label` en cada celda; estilos en `resources/css/app.css`). Toda tabla nueva debe usar ambos.
3. **Portal del paciente**: su historial médico, sus citas (puede solicitarlas y cancelarlas), sus pagos, la descarga de sus adjuntos y la actualización de sus datos de contacto.
   - **Datos del paciente:** el paciente completa sus datos básicos (documento, fecha de nacimiento, sexo, grupo sanguíneo, teléfono) hasta que un médico lo atiende por primera vez; después esos datos se bloquean para él, pero teléfono, dirección y contacto de emergencia siempre son editables. Los antecedentes clínicos (alergias, enfermedades, antecedentes) solo los registra el personal médico. Desde el segundo inicio de sesión el panel muestra un recordatorio para completar o actualizar los datos (sin redirigir).

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
| Reprogramar citas | ✅ (puede cambiar el médico) | ✅ (puede cambiar el médico) | Su agenda (solo fecha y hora) | Las suyas; el médico solo si aún no está confirmada |
| Registrar pagos | ✅ | ✅ | ❌ | ❌ |
| Ver pagos | Todos | Todos | ❌ | Los suyos |
| Usuarios (crear cuentas y asignar rol) | ✅ | ❌ | ❌ | ❌ |
| Médicos y especialidades | ✅ | ❌ | ❌ | ❌ |
| Horarios de atención | Todos | ❌ | El suyo | ❌ |
| Catálogo de medicamentos | ✅ | ❌ | ✅ | ❌ |
| Reportes | Citas, ingresos y consultas | Citas, ingresos y consultas | Sus citas y consultas | ❌ |
| Auditoría | ✅ | ❌ | ❌ | ❌ |

Un médico "atiende" a un paciente cuando tiene al menos una cita o una consulta con él.

### Gestión de usuarios (solo administrador)

El módulo **Usuarios** crea las cuentas y asigna el rol. Al elegir el rol, el formulario muestra los campos que corresponden, para completar todo en un solo paso:

| Rol | Campos que se piden |
|---|---|
| Administrador / Recepción | Nombre, correo y contraseña |
| Médico | Cuenta + especialidad, colegiatura, teléfono, tarifa y reseña |
| Paciente | Cuenta + datos personales, contacto y antecedentes médicos |

- Las cuentas creadas por el administrador quedan con el correo verificado y una contraseña **temporal**: al iniciar sesión, el usuario es llevado a Configuración → Seguridad y no puede usar el sistema hasta cambiarla (campo `users.must_change_password`, middleware `EnsurePasswordIsChanged`). Lo mismo ocurre si el admin asigna una nueva contraseña al editar.
- **Inactivar acceso:** desde la tabla, el botón *Inactivar/Activar* (con confirmación) bloquea el inicio de sesión y cierra la sesión activa del usuario en su siguiente petición, sin borrar datos. El admin no puede inactivarse a sí mismo; la tabla se filtra por acceso y se exporta con esa columna. Queda en la auditoría.
- Con la casilla **"Enviar los datos de acceso por correo"** (marcada por defecto), el usuario recibe su correo, la contraseña temporal y el enlace de inicio de sesión. Al editar, la casilla envía la nueva contraseña, pero solo si el admin escribió una.
- Este correo **no va por la cola** a propósito: una notificación en cola guardaría la contraseña en texto plano en la tabla `jobs`. Si el envío falla, la cuenta igual se guarda y el admin recibe un aviso.
- Con `MAIL_MAILER=log`, el correo aparece en `storage/logs/laravel.log`, con la contraseña en claro. Úsalo solo en desarrollo.
- Si ya existía una ficha de paciente con el mismo correo y sin cuenta, se vincula en lugar de duplicarla.
- **El rol no se puede cambiar** en cuentas de médico o paciente (tienen historial asociado). Administrador y Recepción sí se pueden intercambiar, salvo en la propia cuenta.
- Cada alta y cada cambio queda en la auditoría.
- La creación y la actualización viven en `app/Actions/Users`, y `Médicos → Nuevo médico` lleva al mismo formulario.

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
php artisan schedule:work # tareas programadas (recordatorios de citas, cada hora)
```

**Recordatorios de citas:** cada hora el comando `appointments:send-reminders` envía por correo un recordatorio al paciente de las citas confirmadas que ocurren en las próximas 24 horas (no se envía a menos de 2 horas de la cita ni dos veces; si la cita se reprograma, se vuelve a enviar). Usa la cuenta del paciente o, si no tiene, el correo de su ficha. En producción agrega al cron: `* * * * * php artisan schedule:run`.

**Notificaciones push (navegador):** el paciente las activa en Configuración → Notificaciones (por dispositivo) y recibe el mismo recordatorio como aviso del navegador, además del correo. Usa Web Push con claves VAPID: generarlas una vez con `php artisan webpush:vapid` (quedan en `VAPID_PUBLIC_KEY` y `VAPID_PRIVATE_KEY` del `.env`) y definir `VAPID_SUBJECT`. Requiere HTTPS (o `localhost`) y el worker de colas. En Windows, si PHP falla con *Unable to create the key*, define `OPENSSL_CONF` apuntando a un `openssl.cnf` (p. ej. el de Herd: `~/.config/herd/bin/php82/extras/ssl/openssl.cnf`). En iPhone/iPad solo funciona si la página se agrega a la pantalla de inicio. Las suscripciones vencidas se eliminan solas.

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
├── Actions/Users/          Crear y actualizar una cuenta junto con el perfil de su rol
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
| `/users` | Usuarios: cuentas, roles y perfil (solo admin) |
| `/doctors` y `/doctors/{doctor}/schedules` | Médicos y horarios |
| `/specialties` | Especialidades |
| `/reports` | Reportes de citas, ingresos y consultas, agrupados por médico o especialidad y filtrables por período, médico y especialidad; exportables a Excel y PDF (admin y recepción; el médico ve solo sus citas y consultas, sin ingresos) |
| `/privacidad` | Política de tratamiento de datos personales (pública; enlazada en la landing, el login y el registro). El registro exige aceptarla y guarda fecha y versión en `users.privacy_accepted_at` y `users.privacy_policy_version` |
| `/medications` | Catálogo de medicamentos (admin y médicos) |
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
- **Pago:** `pending` (pendiente) o `paid` (pagado). Existe también `voided` (anulado): admin y recepción pueden anular un pago pendiente o pagado desde el listado indicando el motivo (queda en las notas); también se aplica al cancelar una cita con cobro pendiente.

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
| Usuarios | Nombre, correo | Rol |
| Médicos | Nombre, correo, especialidad, colegiatura | — |
| Especialidades | Nombre, descripción | — |
| Medicamentos | Nombre, presentación, concentración | — |
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

**Pendientes**

- **Sistema de turnos:** pensado para dos casos: el paciente que nunca apartó cita y se registra en sitio (recepción le genera un turno), y el paciente que ya tiene una cita y quiere ver su orden de atención. Incluye una **pantalla de turnos** (visible en la sala de espera) que muestre qué turno se está atendiendo y cuáles siguen, además de la consulta del turno propio desde el portal del paciente.
- **Completar y revisar la política de datos personales (Colombia):** `/privacidad` está redactada con base en la Ley 1581 de 2012 y normas relacionadas, pero debe revisarla un abogado. Configurar en `.env` los datos del responsable (`PRIVACY_COMPANY`, `PRIVACY_NIT`, `PRIVACY_ADDRESS`, `PRIVACY_PHONE`, `PRIVACY_CONTACT_EMAIL`) y verificar si la clínica debe inscribir sus bases de datos en el Registro Nacional de Bases de Datos (RNBD) de la SIC. Al cambiar el texto de fondo, subir `version` en `config/privacy.php`.
- **Contenido de la landing editable:** reemplazar los datos de contacto de ejemplo (teléfono, correo, dirección) y los textos de *Quiénes somos* y *Servicios médicos* por los reales, y permitir editarlos desde Configuración.
- **API con Laravel Sanctum** para una futura app móvil.
- **Pasarela de pagos en línea** (hoy los pagos se registran a mano).
