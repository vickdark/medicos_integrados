# 3. Módulos funcionales y rutas

Todas las rutas viven en `routes/web.php` (y `routes/settings.php` para Configuración). Para el listado exacto y completo: `php artisan route:list --except-vendor`. Ningún módulo tiene rutas separadas por rol: el mismo conjunto de rutas se usa para todos, y la Policy/Form Request de cada acción decide qué puede hacer cada usuario.

## 3.1 Landing pública (`HomeController`)

| Ruta | Método | Descripción |
|---|---|---|
| `GET /` | — | Presentación del centro, especialidades (leídas de `specialties`), médicos si `landing_show_doctors` está activo, accesos a registro/login |

Contenido editable desde Configuración → *Página de inicio* (solo admin): teléfono, correo, dirección del pie, textos de *Quiénes somos* y *Servicios médicos*. Los valores por defecto están en `config/landing.php`; lo editado se guarda en `app_settings` y puede restablecerse.

## 3.2 Autenticación (Laravel Fortify)

Registro, login, recuperación de contraseña, verificación de correo, verificación en dos pasos (2FA), confirmación de contraseña para acciones sensibles. Acciones personalizadas en `app/Actions/Fortify`:
- **Registro:** quien se registra desde la web queda como `patient`. Si recepción ya había creado su ficha de paciente con el mismo correo, la cuenta se vincula a esa ficha en vez de duplicarla.
- **Reseteo de contraseña:** flujo estándar de Fortify, localizado en español.

## 3.3 Dashboard (`DashboardController`)

`GET /dashboard`: panel de inicio cuyo contenido depende del rol (`App\Actions\Dashboard`). Por ejemplo, el paciente ve su turno del día si aplica; admin/recepción ven indicadores operativos; el médico ve su agenda del día.

## 3.4 Pacientes (`PatientController`, `PatientHistoryController`)

| Ruta | Descripción |
|---|---|
| `/patients` (resource, sin `destroy`) | Alta, listado, edición y ficha del paciente |
| `/patients/{id}` | También funciona como "Mi historial" cuando lo abre el propio paciente |
| `/patients/{patient}/consultations/create` (`GET`/`POST`) | Registrar una nueva consulta para ese paciente |
| `GET /patients/{id}/clinical-history?from&to` | Historia clínica completa en PDF |

Reglas:
- Un médico solo ve/edita pacientes que **atiende** (tiene al menos una cita o consulta con ellos).
- Recepción no ve antecedentes clínicos ni consultas.
- El paciente solo edita sus datos de contacto (teléfono, dirección, contacto de emergencia); el resto se bloquea tras su primera atención (`hasBeenAttended()`).
- El listado de pacientes **nunca incluye datos clínicos**, ni en pantalla ni en su exportación.

## 3.5 Consultas / historia clínica (`ConsultationController`, `ConsultationAddendumController`, `ConsultationAttachmentController`)

| Ruta | Descripción |
|---|---|
| `GET /consultations/{consultation}` | Detalle de la consulta |
| `POST /consultations/{consultation}/attachments` | Subir un adjunto |
| `GET /attachments/{attachment}` | Descargar un adjunto (se descifra al vuelo) |
| `POST /attachments/{attachment}/status` | Corregir (con archivo nuevo) o anular un adjunto, con motivo |
| `POST /consultations/{id}/addenda` | Agregar una nota aclaratoria |

Reglas clave:
- Una consulta registrada **no se edita ni se borra**, ni siquiera por código (bloqueado a nivel de modelo).
- Las correcciones se hacen con **notas aclaratorias**: el médico que registró la consulta indica qué parte aclara, el motivo y el texto. Quedan con autor, fecha y hora, cifradas, inmutables y en la auditoría. Se muestran debajo del registro original (que se ve tal cual se escribió), en la ficha del paciente (con contador por consulta), en la historia clínica en PDF y las ve también el paciente.
- Los adjuntos son parte de la historia clínica y nunca se eliminan; solo se pueden **corregir** (subiendo el archivo correcto; el original queda enlazado y marcado) o **anular**, una sola vez, con motivo obligatorio. El motivo y el historial de versiones son **internos**: el paciente solo ve y descarga los adjuntos vigentes.

## 3.6 Diagnósticos CIE-10 (`DiagnosisSearchController`, `Cie10CatalogController`)

| Ruta | Descripción |
|---|---|
| `GET /diagnoses/search?q=` | Búsqueda en el catálogo por código (`J00`, `E11.9`) o por palabras, usada por el selector de diagnósticos |
| `/cie10` | Carga del catálogo oficial (ETL), historial de cargas y búsqueda (solo admin) |

Cada consulta nueva exige un diagnóstico principal CIE-10 con su `DiagnosisType` (impresión diagnóstica, confirmado nuevo, confirmado repetido) y admite hasta 3 diagnósticos relacionados; el campo de texto libre se mantiene en paralelo. Las consultas anteriores a esta función conservan solo su diagnóstico en texto. Ver el ETL completo en `06-tareas-programadas-e-integraciones.md`.

## 3.7 Citas (`AppointmentController`, `DoctorAvailabilityController`)

| Ruta | Descripción |
|---|---|
| `/appointments` | Listado/calendario de citas |
| `GET /appointments/calendar?from&to` | Datos del calendario (máx. 62 días), respeta visibilidad por rol |
| `GET /doctors/{doctor}/availability?from&to` | Espacios libres/ocupados de un médico, para agendar o reprogramar |
| `PATCH /appointments/{id}/status` | Cambiar estado (confirmar, cancelar, completar) |
| `GET/PUT /appointments/{id}` | Editar una cita (política `editDetails`) |

**Calendario** (`/appointments`): en pantallas grandes, vistas Día (por defecto) / Semana / Mes; en móvil, mini calendario mensual con vista Día y Agenda. Alterna con vista Lista (filtros + exportación).

**Agendar/reprogramar:** se elige el horario en un calendario semanal (diario en móvil) que distingue libres, ocupados y fuera de horario. Cada médico define su `slot_minutes` (10–60 min) y el sistema evita solapes. En *Horario del médico* se pueden crear bloques para varios días a la vez.

**Reprogramar con otro médico** (admin/recepción): la disponibilidad y los choques se validan contra el nuevo médico; la cita conserva su estado; el cobro pendiente se ajusta a la tarifa del nuevo médico (uno ya pagado no se toca); se notifica por correo al paciente, al médico anterior y al nuevo. También pueden editar motivo y notas.

**El paciente edita su cita** mientras esté *Solicitada*: puede cambiar médico, motivo, notas y horario; sigue solicitada y se avisa a admin, recepción y ambos médicos. Una vez *Confirmada*, solo puede pedir otro horario (vuelve a *Solicitada* para reconfirmación); médico y motivo quedan bloqueados. Los médicos no pueden reasignar la cita a otro médico.

**Pago rápido:** el botón de pago en la fila de una cita precarga paciente, cita, concepto y monto (tarifa del médico).

## 3.8 Recetas y documentos clínicos (`ConsultationPrescriptionController`, `SendConsultationPrescriptionController`, `ClinicalDocumentController`, `SendClinicalDocumentController`, `DoctorSignatureController`, `DoctorPhotoController`)

| Ruta | Descripción |
|---|---|
| `GET /consultations/{consultation}/prescription` | Receta en PDF (se abre en pestaña nueva) |
| `POST /consultations/{consultation}/prescription/email` | Enviar la receta por correo (sin cola, para no guardar el PDF en `jobs`) |
| `POST /consultations/{id}/documents` | Emitir un documento clínico |
| `GET /clinical-documents/{id}` | Ver/descargar un documento clínico |
| `POST /clinical-documents/{id}/email` | Enviar un documento clínico por correo |
| `GET /doctors/{id}/signature` | Imagen de la firma del médico (solo médico y admin) |
| `GET /doctors/{id}/photo` | Foto del médico (pública solo si la landing la muestra; siempre visible con sesión) |

**Receta:** solo el médico que la escribió obtiene la copia oficial (firmada); el admin la ve marcada "Este documento no es válido" con marca de agua; recepción y pacientes no tienen acceso directo (el paciente sí recibe el PDF por correo si el médico lo envía).

**Documentos clínicos** (`App\Enums\ClinicalDocumentType`), todos emitidos solo por el médico que registró la consulta, numerados (`CI-`, `INC-`, `REM-`, `ORD-`), inmutables y auditados:
- **Consentimiento informado:** procedimiento, en qué consiste, riesgos, beneficios, alternativas, firma del paciente y del médico.
- **Incapacidad médica:** fecha de inicio, días (máx. 30 por documento; el resto se emite como prórroga), fecha de fin calculada, origen (`SickLeaveOrigin`: enfermedad general, accidente de trabajo, enfermedad laboral, accidente de tránsito).
- **Remisión:** especialidad o servicio, prioridad (`CarePriority`), motivo, resumen clínico.
- **Orden de exámenes:** lista de exámenes, prioridad, indicaciones.

Todos (salvo el consentimiento) incluyen el diagnóstico CIE-10 de la consulta. Solo quien emitió el documento obtiene la copia oficial firmada; el paciente, el admin y otros médicos tratantes ven una copia marcada **«SIN VALIDEZ»**.

**Firma del médico:** imagen (PNG/JPG/WebP ≤ 1 MB), registrada al crear/editar al médico (admin) o subida por el propio médico en Configuración → Perfil profesional; se redimensiona y guarda como PNG en disco privado. Sin firma registrada: la copia oficial sale marcada **«SIN FIRMA»**, la consulta muestra el aviso, el listado de médicos marca «Sin firma registrada» y no se permite enviar receta ni documentos por correo. Es firma **digitalizada** (imagen), no firma digital con certificado.

## 3.9 Verificación pública de documentos (`DocumentVerificationController`)

| Ruta | Descripción |
|---|---|
| `GET /verificar` y `GET /verificar/{código}` | Verificación pública, sin sesión |

Receta, documentos clínicos e historia clínica en PDF llevan un código único (`R…`, `D…`, `H…`, ~55 bits de aleatoriedad) y un QR hacia `/verificar/{código}`, construido con `APP_URL` (**debe ser el dominio público real en producción**). La página confirma tipo, número, fecha, médico (con registro médico), si está firmado, paciente enmascarado (iniciales + últimos 4 dígitos del documento) y los datos clave para detectar alteraciones (fechas/días de incapacidad, medicamentos/dosis de receta, exámenes de la orden, especialidad/procedimiento) — **nunca** el diagnóstico ni contenido clínico completo. Las copias «SIN VALIDEZ» no llevan QR. Límite: 30 peticiones por minuto a la ruta pública.

Cada historia clínica en PDF generada queda registrada en `clinical_history_exports` (número `HC-…`, quién la generó y su rol, período, consultas incluidas, si incluye notas internas); su verificación muestra quién la emitió, la fecha y el médico de cada consulta incluida, para detectar páginas agregadas o quitadas.

## 3.10 Pagos (`PaymentController`)

| Ruta | Descripción |
|---|---|
| `/payments` | Listado, registro manual, marcar como pagado |
| `PATCH /payments/{id}/paid` | Marcar un pago pendiente como pagado |
| `GET /payments/{id}/invoice` | Factura en PDF (número `F-000123`) |

Registro manual (efectivo, tarjeta, transferencia, otro). Admin y recepción pueden anular un pago (pendiente o pagado) indicando motivo; lo mismo ocurre automáticamente al cancelar una cita con cobro pendiente. Solo los pagos `paid` tienen factura.

## 3.11 Usuarios, médicos, especialidades, horarios (`UserController`, `DoctorController`, `DoctorScheduleController`, `SpecialtyController`)

| Ruta | Descripción |
|---|---|
| `/users` | Cuentas, roles y perfil asociado (solo admin) |
| `/doctors` y `/doctors/{doctor}/schedules` | Médicos y su horario de atención |
| `/specialties` | Especialidades |

El módulo **Usuarios** crea la cuenta y el perfil de su rol en un solo paso: el formulario muestra campos distintos según el rol elegido (admin/recepción: nombre, correo, contraseña; médico: + especialidad, registro médico, teléfono, firma, tarifa, reseña; paciente: + datos personales, contacto, antecedentes). `Médicos → Nuevo médico` lleva al mismo formulario.

- Cuentas creadas por el admin quedan con correo verificado y contraseña **temporal**; al iniciar sesión se fuerza el cambio (`users.must_change_password`, middleware `EnsurePasswordIsChanged`). Igual si el admin reasigna contraseña al editar.
- **Inactivar acceso** (no borra datos): bloquea login y cierra la sesión activa en la siguiente petición del usuario. El admin no puede inactivarse a sí mismo.
- Casilla **"Enviar los datos de acceso por correo"** (marcada por defecto): envía correo, contraseña temporal y enlace de login. Este correo **no va por la cola a propósito** (una notificación en cola guardaría la contraseña en texto plano en la tabla `jobs`); si falla, la cuenta igual se guarda y el admin recibe un aviso.
- Si ya existía una ficha de paciente con el mismo correo sin cuenta, se vincula en vez de duplicarse.
- **El rol no se puede cambiar** en cuentas de médico o paciente (tienen historial asociado); admin y recepción sí pueden intercambiarse (salvo la propia cuenta).
- Creación/actualización centralizadas en `app/Actions/Users`.

## 3.12 Turnos y pantalla de sala de espera (`TurnController`, `TurnBoardController`)

| Ruta | Descripción |
|---|---|
| `/turns` | Turnos del día |
| `/pantalla-de-turnos` | Pantalla pública para sala de espera (se actualiza cada 5 s) |

Recepción genera turnos (pacientes sin cita o con cita del día al llegar), llama, marca atendido, devuelve a la fila o cancela. El médico gestiona solo su fila y abre la consulta del paciente llamado. La pantalla pública muestra turnos en atención y próximos, **solo código y médico, nunca nombres de pacientes**.

## 3.13 Reportes (`ReportController`)

`GET /reports`: citas, ingresos y consultas, agrupables por médico o especialidad, filtrables por período/médico/especialidad, exportables a Excel/PDF. Admin y recepción ven todo; el médico ve solo sus citas y consultas, **sin ingresos**.

## 3.14 Tablas: búsqueda, filtros y exportación (patrón transversal)

Todas las tablas del sistema funcionan del lado del servidor:

1. El controlador de cada listado expone un método `filteredQuery(TableQueryRequest $request)` que aplica permisos de rol, búsqueda y filtros.
2. `index()` pagina esa consulta de 10 en 10 (`Controller::TABLE_PAGE_SIZE`) y `export()` **reutiliza la misma consulta** — lo que se exporta es exactamente lo que se ve en pantalla.
3. En el frontend, el composable `useTableFilters` sincroniza filtros con la URL (debounce de 300 ms en la búsqueda); `TableToolbar` muestra buscador, filtros extra y botones **Excel**/**PDF**.
4. **Responsive:** por debajo de 768 px cada fila se muestra como tarjeta (clase `cards` en la tabla + atributo `data-label` en cada celda; estilos en `resources/css/app.css`). Toda tabla nueva debe implementar ambos modos.

| Tabla | Búsqueda | Filtros |
|---|---|---|
| Pacientes | Nombre, documento, correo | — |
| Citas | Paciente, documento, médico, motivo | Estado, rango de fechas |
| Pagos | Paciente, concepto, referencia | Estado, rango de fechas (totales recalculados) |
| Usuarios | Nombre, correo | Rol |
| Médicos | Nombre, correo, especialidad, registro médico | — |
| Especialidades | Nombre, descripción | — |
| Medicamentos | Nombre, presentación, concentración | — |
| Auditoría | Usuario, paciente, descripción, IP | Acción, paciente, rango de fechas |

**Exportación** (`GET /{tabla}/export?format=xlsx|pdf&…filtros`):
- **Excel** (`PhpSpreadsheet`): encabezados con formato, autofiltro, primera fila congelada, montos numéricos; los textos se guardan como texto **literal** (nunca como fórmula, para que algo como `=HYPERLINK(...)` no se ejecute).
- **PDF**: hoja A4 horizontal con filtros aplicados, fecha y usuario que lo generó; máximo 1.000 filas (para más, usar Excel).
- El listado de pacientes **no incluye datos clínicos** en su exportación.
- Cada exportación queda en la auditoría (acción "Exportó").

**Agregar una tabla nueva:** crear una clase en `app/Exports` que extienda `TableExport` (título, encabezados, filas), agregar el método `export()` al controlador y registrar la ruta `…/export` **antes** del `Route::resource` correspondiente.

## 3.15 Catálogos administrables

| Módulo | Ruta | Quién administra |
|---|---|---|
| Medicamentos | `/medications` | Admin y médicos |
| Aseguradoras / EPS | `/insurers` | Solo admin (no se puede eliminar una asignada a pacientes) |
| Catálogo CIE-10 | `/cie10` | Solo admin (ver ETL en `06-tareas-programadas-e-integraciones.md`) |

## 3.16 Auditoría (`AuditLogController`)

`GET /audit-logs`: registra quién consulta historias clínicas o consultas, las altas y modificaciones de pacientes, las consultas creadas, las subidas/descargas/cambios de estado de adjuntos, con usuario, IP y fecha. Solo accesible por admin.

## 3.17 Privacidad y derechos del titular (`DataSubjectRequestController`, `PrivacyPolicyController`, `PrivacyAcceptanceController`, Settings `PersonalDataController` / `PrivacySettingsController`)

Ver el detalle completo en `04-seguridad-y-privacidad.md §4`.

## 3.18 Configuración (`routes/settings.php`, `app/Http/Controllers/Settings`)

| Ruta | Controlador | Quién |
|---|---|---|
| `/settings/profile` | `ProfileController` | Todos |
| `/settings/security` | `SecurityController` | Todos (contraseña, 2FA) |
| `/settings/appearance` | — (`Appearance.vue`) | Todos (modo claro/oscuro) |
| `/settings/notifications` | — | Paciente (activar notificaciones push) |
| `/settings/branding` (Color de la aplicación) | `BrandingController` | Solo admin |
| `/settings/landing` (Página de inicio) | `LandingSettingsController` / `LandingContentController` | Solo admin |
| `/settings/privacy` (Política de datos) | `PrivacySettingsController` | Solo admin |
| `/settings/personal-data` (Mis datos personales) | `PersonalDataController` | Paciente |
| Perfil profesional / firma del médico | `DoctorProfileController`, `DoctorSignatureController` | Médico |

**Color de la aplicación** (solo admin): acento global guardado en `app_settings.brand_color`, aplicado como variable CSS `--brand` (de ahí salen los tonos `brand-50…950` de Tailwind usados en logo, gráficas, etiquetas, landing, recorrido guiado y también en los PDF y encabezados de Excel). Se rechazan colores con contraste < 3:1 sobre blanco; hay botón para restablecer el color original.
