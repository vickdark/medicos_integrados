# 1. Arquitectura técnica

## 1.1 Stack tecnológico

| Capa | Tecnología | Notas |
|---|---|---|
| Backend | PHP **8.2**, Laravel **12** | `composer.json` fija `config.platform.php = 8.2.32`. No usar sintaxis exclusiva de PHP 8.3+ |
| Autenticación | Laravel Fortify ^1.30 | Registro, login, recuperación de contraseña, verificación de correo, 2FA |
| Frontend | Vue 3 + TypeScript | Composition API |
| Puente backend↔frontend | Inertia.js 2 | No hay API REST tradicional: los controladores devuelven `Inertia::render()` con props tipadas, no JSON genérico |
| Estilos | Tailwind CSS 4 | Variable CSS `--brand` para el color de marca configurable |
| Componentes UI | shadcn-vue (sobre reka-ui) + iconos lucide | En `resources/js/components/ui` |
| Rutas tipadas | Laravel Wayfinder ^0.1.9 | Genera `resources/js/routes/*` y `resources/js/actions/*` a partir de las rutas de Laravel. Se regenera solo al correr Vite; a mano: `php artisan wayfinder:generate --with-form` |
| Exportación Excel | PhpSpreadsheet ^5.10 | `app/Exports` |
| Exportación PDF | barryvdh/laravel-dompdf ^3.1 | Facturas, recetas, historia clínica, documentos clínicos, reportes |
| Notificaciones push | laravel-notification-channels/webpush ^13.0 | Web Push con claves VAPID |
| Base de datos | MySQL 8 | Collation `utf8mb4_unicode_ci` |
| Colas | Driver `database` | Tabla `jobs`; necesaria para los correos (excepto el de credenciales, ver §4 de `04-seguridad-y-privacidad.md`) |
| Tests | Pest 3 / PHPUnit 11 | SQLite en memoria, `RefreshDatabase` |
| Calidad | Laravel Pint, ESLint, Prettier, vue-tsc | Ver `07-pruebas-calidad-convenciones.md` |
| Entorno local recomendado | Laravel Herd | Windows/macOS; URL tipo `https://medicos_integrados.test` |

La base del proyecto es el **starter kit oficial de Laravel para Vue** (`laravel/vue-starter-kit`), adaptado a Laravel 12.

## 1.2 Flujo de una petición

```
Navegador (Vue + Inertia)
      │  visita Inertia / formulario <Form> con rutas generadas por Wayfinder
      ▼
routes/web.php ──► Controller ──► Form Request (validación + autorización)
                        │                 │
                        │                 └─► Policy (permisos por rol)
                        ▼
                 Modelo Eloquent ──► MySQL
                        │
                        ▼
              API Resource ──► Inertia::render('Pagina', props)
```

Puntos clave:

- **No hay una API JSON separada para el frontend.** Inertia hace que cada visita de página sea una petición que devuelve los props ya serializados; el componente Vue correspondiente a esa página los recibe directamente.
- **La autorización nunca vive en el frontend.** Ocultar un botón en Vue es solo UX; el permiso real se aplica en el `Form Request` (vía `authorize()`, delegando a la Policy) y en los scopes de los modelos (`visibleTo`, `treatedBy`). Cualquier endpoint debe asumir que puede ser llamado directamente.
- **Serialización con API Resources**, sin el envoltorio `data` (`JsonResource::withoutWrapping()`), y los listados paginados se mapean con `->through()` para no enviar campos de más (p. ej. nunca se envían campos clínicos cifrados a quien no debe verlos).

## 1.3 Estructura de carpetas

```
app/
├── Actions/
│   ├── Branding/            Color de marca de la aplicación
│   ├── ClinicalDocuments/   Emisión de documentos clínicos (consentimiento, incapacidad, remisión, orden)
│   ├── Dashboard/           Datos del panel de inicio según el rol
│   ├── Diagnoses/           ETL de importación del catálogo CIE-10 (ImportCie10Catalog)
│   ├── Fortify/             Registro (vincula o crea la ficha de paciente) y reseteo de contraseña
│   ├── Payments/            Lógica de pagos (anulación, factura…)
│   ├── Prescriptions/       Emisión de recetas
│   ├── Privacy/             Política de datos y derechos del titular (Ley 1581)
│   ├── Reports/             Cálculo de reportes de citas, ingresos y consultas
│   ├── Turns/                Lógica de la fila de turnos
│   ├── Users/                Crear/actualizar una cuenta junto con el perfil de su rol
│   └── Verification/         Verificación pública de documentos por código/QR
├── Concerns/                 Traits de validación reutilizables y HasEnumOptions (opciones para selects)
├── Console/Commands/         Comandos Artisan (diagnoses:import, attachments:encrypt, appointments:send-reminders, webpush:vapid…)
├── Enums/                    UserRole, AppointmentStatus, PaymentStatus, PaymentMethod, Gender, AuditAction,
│                              ExportFormat, ClinicalDocumentType, DocumentType, DiagnosisType, CarePriority,
│                              ConsultationSection, AttachmentStatus, SickLeaveOrigin, TurnStatus,
│                              DataRequestType, DataRequestStatus, AffiliationType, ReportGroup, ReportType
├── Exports/                  TableExporter (orquesta Excel/PDF) + una clase *Export por tabla (columnas y título)
├── Http/
│   ├── Controllers/          Un controlador por módulo (ver 03-modulos-y-rutas.md)
│   │   └── Settings/         Controladores de Configuración (perfil, seguridad, marca, landing, privacidad…)
│   ├── Middleware/            HandleInertiaRequests (props compartidos), HandleAppearance (tema),
│   │                           EnsurePasswordIsChanged, EnsurePrivacyPolicyIsAccepted, LogoutInactiveStaff
│   ├── Requests/               Form Requests: validación + autorización (delegando a la Policy)
│   └── Resources/              Serialización de modelos hacia Vue (API Resources)
├── Mail/                      Clases Mailable (PrescriptionMail, ClinicalDocumentMail)
├── Models/                    Modelos Eloquent y scopes (visibleTo, treatedBy, search)
├── Notifications/             Notificaciones por correo/push (citas, credenciales, solicitudes de datos…)
├── Policies/                  Autorización por rol, una por modelo
└── Providers/                 AppServiceProvider, FortifyServiceProvider

database/
├── factories/                 Factories con estados (admin(), doctor(), confirmed(), withAccount()…)
├── migrations/                 Historial de cambios al esquema (ver 02-modelo-de-datos.md)
└── seeders/                    SpecialtySeeder, MedicationSeeder, InsurerSeeder, DiagnosisSeeder, DemoSeeder

lang/es/                        Traducciones de validación, autenticación y paginación

resources/
├── css/app.css                 Estilos globales, incluida la clase `cards` para tablas responsivas
├── js/
│   ├── app.ts                  Punto de entrada de Inertia
│   ├── ssr.ts                  Entrada para renderizado en servidor (si se habilita)
│   ├── components/             Componentes propios (StatusBadge, ThemeToggle, IconButton, FlashMessage…) + ui/ (shadcn-vue)
│   ├── composables/             useAppearance, useTwoFactorAuth, useTableFilters…
│   ├── layouts/                 Layout de la app (con AppSidebar), de autenticación y de configuración
│   ├── lib/format.ts            Formato de fechas, montos y tamaños de archivo
│   ├── pages/                   Páginas Inertia agrupadas por módulo (ver 03-modulos-y-rutas.md)
│   ├── routes/ y actions/       Generados por Wayfinder — NO editar a mano
│   └── types/                    Tipos TypeScript (models.ts, auth.ts…)
└── views/                       Vistas Blade: app.blade.php (shell de Inertia), exports/, mail/, pdf/

routes/
├── web.php                      Rutas de la aplicación
├── settings.php                  Rutas de Configuración
└── console.php                   Comandos Artisan ad-hoc / scheduler

tests/Feature/                   Pruebas Pest, una por módulo (ver 07-pruebas-calidad-convenciones.md)
```

## 1.4 Props compartidos con el frontend

El middleware `App\Http\Middleware\HandleInertiaRequests` comparte en **todas** las páginas:

| Prop | Contenido |
|---|---|
| `auth.user` | Usuario autenticado |
| `auth.role` | Su rol como `{ value, label }` (usa `UserRole`) |
| `auth.patientId` / `auth.doctorId` | Id de la ficha de paciente/médico asociada al usuario, si existe |
| `flash.success` / `flash.error` | Mensajes que muestra el componente `FlashMessage` |

El menú lateral (`resources/js/components/AppSidebar.vue`) construye sus opciones a partir de `auth.role`: **no hay rutas separadas por rol** (no existe `/admin`, `/portal`, etc.), todos los roles navegan por las mismas rutas y el servidor decide qué datos y acciones están disponibles.

## 1.5 Convenciones de código

- **Idioma:** clases, métodos, variables y columnas en inglés; todo texto visible (labels, mensajes, validaciones) en español.
- **Validación:** siempre con Form Requests (`php artisan make:request`); nunca validación inline en el controlador. El Form Request también autoriza, delegando en la Policy correspondiente.
- **Datos hacia el frontend:** siempre con API Resources, sin envoltorio `data`. Los listados paginados usan `->through()` para mapear cada fila.
- **Frontend:** formularios con el componente `<Form>` de Inertia y acciones generadas por Wayfinder (`Controller.store.form()`). Los módulos de rutas se importan con el sufijo `Routes` (`patientRoutes`, `appointmentRoutes`) para no chocar con props del mismo nombre.
- **Enums:** cada enum PHP expone `label()` en español; el trait `HasEnumOptions` añade `options()` y `toOption()` para alimentar selects del frontend sin duplicar las etiquetas.
- **Archivos nuevos:** siempre creados con `php artisan make:*` (modelo, controlador, policy, request, etc.), nunca copiados a mano, para mantener los namespaces y el boilerplate consistentes.
- **Tablas del servidor:** toda tabla nueva sigue el patrón descrito en `03-modulos-y-rutas.md §3.14` (método `filteredQuery`, `TableExporter`, `useTableFilters`, clase `cards` para la vista en móvil).
- **Comentarios en PHP:** se prefieren bloques PHPDoc a comentarios inline; solo se comenta cuando la lógica es realmente compleja.
- **Tipos explícitos:** todo método/función PHP debe declarar su tipo de retorno; los parámetros deben tener type hints apropiados.

## 1.6 Zona horaria

El sistema opera con la hora de la clínica (`APP_TIMEZONE`, por defecto `America/Bogota`). Las citas se guardan con la hora **local** de la clínica, por lo que el servidor debe usar la misma zona horaria que ven los usuarios: si el servidor corre en otra zona (p. ej. UTC), los horarios libres del día actual pueden rechazarse incorrectamente como "pasados".

## 1.7 Catálogo de enums principales

| Enum | Valores | Uso |
|---|---|---|
| `UserRole` | `admin`, `doctor`, `receptionist`, `patient` | Autorización y menú |
| `AppointmentStatus` | `requested`, `confirmed`, `completed`, `cancelled` | Ciclo de vida de una cita |
| `PaymentStatus` | `pending`, `paid`, `voided` | Ciclo de vida de un pago |
| `PaymentMethod` | efectivo, tarjeta, transferencia, otro | Registro manual de pagos |
| `AttachmentStatus` | `current` (vigente), `corrected` (corregido), `voided` (anulado) | Versión de un adjunto clínico |
| `ClinicalDocumentType` | consentimiento, incapacidad, remisión, orden de exámenes | Documentos clínicos legales |
| `DiagnosisType` | impresión diagnóstica, confirmado nuevo, confirmado repetido | Tipo de diagnóstico CIE-10 en una consulta |
| `DataRequestType` / `DataRequestStatus` | acceso/corrección/supresión · pendiente/atendida/rechazada | Derechos del titular (Ley 1581) |
| `TurnStatus` | en espera, llamado, atendido, cancelado | Fila de turnos de sala de espera |
| `AuditAction` | ver, crear, actualizar, exportar, emitir, enviar… | Registro de auditoría |

Ver el código fuente en `app/Enums/` para la lista completa y las etiquetas exactas en español.
