# 2. Modelo de datos

## 2.1 Diagrama de entidades y relaciones

```
users ─┬─ 1:1 ─ doctors ─┬─ N:1 ─ specialties
       │                 └─ 1:N ─ doctor_schedules
       └─ 1:1 ─ patients (user_id opcional: puede existir sin cuenta de acceso)

patients ─┬─ 1:N ─ appointments ── N:1 ─ doctors
          ├─ 1:N ─ consultations ─┬─ N:1 ─ doctors
          │                       ├─ 1:1 ─ appointments (opcional)
          │                       ├─ 1:N ─ prescriptions
          │                       ├─ 1:N ─ consultation_attachments
          │                       ├─ 1:N ─ consultation_addenda
          │                       └─ 1:N ─ clinical_documents
          ├─ 1:N ─ payments ── N:1 ─ appointments (opcional)
          ├─ 1:N ─ turns ── N:1 ─ doctors / appointments (opcional)
          ├─ 1:N ─ data_subject_requests
          └─ 1:N ─ audit_logs (también polimórfico: auditable_type / auditable_id)

diagnoses (catálogo CIE-10) ── referenciado desde consultations (diagnóstico principal + hasta 3 relacionados)
insurers (catálogo EPS/aseguradoras) ── referenciado desde patients
medications (catálogo) ── referenciado desde prescriptions
app_settings (clave/valor) ── color de marca, textos de landing, política de datos, flags de configuración
```

Un `Patient` puede existir **sin** cuenta de usuario (`user_id` nulo): recepción puede crear la ficha antes de que el paciente se registre; si luego se registra con el mismo correo, la cuenta se vincula automáticamente a la ficha existente en vez de duplicarla (ver `app/Actions/Fortify`).

## 2.2 Entidades principales

### `users`
Cuenta de acceso. Campos relevantes además de los estándar de Laravel/Fortify:
- `role` (`UserRole`): `admin`, `doctor`, `receptionist`, `patient`.
- `must_change_password`: fuerza el cambio de contraseña en el primer ingreso (cuentas creadas por un admin).
- `is_active`: bloquea el login y cierra la sesión activa sin borrar datos.
- `login_count`: usado para mostrar el recordatorio de completar datos desde el segundo ingreso del paciente.
- `privacy_accepted_at`, `privacy_policy_version`: aceptación de la política de datos personales.
- Columnas de doble factor de autenticación (2FA) añadidas por Fortify.

### `doctors` (1:1 con `users`)
- `specialty_id` (FK a `specialties`), `medical_license` (registro médico), `phone`, `consultation_fee` (`decimal:2`), `bio`, `photo_path`, `signature_path`.
- `slot_minutes` (`integer`): duración de cada cita de ese médico. Opciones válidas: `Doctor::SLOT_OPTIONS = [10, 15, 20, 30, 45, 60]`; por defecto `Doctor::DEFAULT_SLOT_MINUTES = 30`.

### `doctor_schedules` (1:N desde `doctors`)
Bloques de horario de atención por día de la semana, usados para calcular disponibilidad real (`GET /doctors/{doctor}/availability`).

### `patients` (1:1 opcional con `users`)
- Identificación: `document_type` (enum `DocumentType`, códigos RIPS: CC, TI, RC, CE, PA, PE, PT…), `document_number`.
- Datos personales: `first_name`, `last_name`, `birth_date` (`date:Y-m-d`), `gender` (enum `Gender`), `phone`, `address`, contacto de emergencia.
- Afiliación en salud: `insurer_id` (FK a `insurers`), `affiliation_type` (enum `AffiliationType`: contributivo, subsidiado, particular…).
- Clínicos, **cifrados** (`cast: encrypted`): `allergies`, `chronic_conditions`, `medical_background`.
- `hasBeenAttended()`: true si tiene al menos una consulta — a partir de ahí se bloquean para el paciente los campos de identificación básica (ver `04-seguridad-y-privacidad.md`).

### `appointments`
- `patient_id`, `doctor_id`, `created_by` (usuario que la creó).
- `scheduled_at` (`datetime`), `status` (enum `AppointmentStatus`: `requested` → `confirmed` → `completed`, o `cancelled`).
- `reminder_sent_at`: evita reenviar el recordatorio de 24 h.
- `reason`, `notes`.

### `consultations`
Registro clínico de una atención. Campos clínicos **cifrados** (`encrypted`): `reason`, `symptoms`, `diagnosis` (texto libre, heredado), `treatment`, `notes`.
- Signos vitales: `weight_kg` / `height_cm` (`decimal:2`), `temperature_c` (`decimal:1`), `heart_rate` (`integer`), entre otros.
- `consulted_at` (`datetime`), `appointment_id` (opcional, 1:1), `patient_id`, `doctor_id`.
- `diagnosis_type` (enum `DiagnosisType`): impresión diagnóstica, confirmado nuevo, confirmado repetido.
- Diagnóstico principal y hasta 3 relacionados, codificados con CIE-10 (FK a `diagnoses`) — ver constante `Consultation::CLINICAL_FIELDS`.
- **Inmutable tras guardarse**: el modelo bloquea en `booted()` cualquier `update` o `delete` sobre los campos clínicos; las correcciones se hacen por medio de `consultation_addenda` (notas aclaratorias), nunca editando el registro original.

### `consultation_attachments`
Archivos adjuntos a una consulta (PDF o imágenes). Nunca se eliminan.
- `status` (enum `AttachmentStatus`): `current` (vigente), `corrected` (corregido — enlazado a la versión nueva), `voided` (anulado).
- `status_reason` (**cifrado**), `status_changed_at`: motivo y fecha del cambio de estado; **visibles solo para el personal**, nunca para el paciente.
- `is_encrypted` (`boolean`): si el archivo en disco está cifrado (extensión `.enc`); todo adjunto nuevo lo está.
- El modelo impide en `booted()` cualquier cambio fuera de "cerrar" un adjunto vigente una sola vez (`CLOSING_FIELDS`), y prohíbe el `delete`.

### `consultation_addenda`
Notas aclaratorias sobre una consulta ya registrada, sin alterar el original.
- `section` (enum `ConsultationSection`): qué parte aclara (motivo, síntomas, signos vitales, diagnóstico, tratamiento, receta, notas, otro).
- `reason` y `content`, ambos **cifrados**.
- Inmutable: el modelo bloquea `update` y `delete` en `booted()`.

### `prescriptions`
Recetas asociadas a una consulta: medicamentos (referencia a `medications`), dosis, frecuencia, duración, indicaciones. Se renderizan en PDF con firma del médico.

### `clinical_documents`
Documentos clínicos legales emitidos desde una consulta.
- `type` (enum `ClinicalDocumentType`): consentimiento informado, incapacidad médica, remisión, orden de exámenes.
- `data` (**cifrado como array JSON**): contenido específico de cada tipo de documento (p. ej. para incapacidad: fecha de inicio, días, origen `SickLeaveOrigin`).
- `verification_code`: código único generado al crear el registro (prefijo `D…`), usado en la verificación pública por QR.
- Inmutable: `update` y `delete` lanzan `LogicException` en `booted()`.

### `payments`
- `patient_id`, `appointment_id` (opcional), `recorder` (usuario que lo registró).
- `amount` (`decimal:2`), `method` (enum `PaymentMethod`), `status` (enum `PaymentStatus`: `pending`, `paid`, `voided`), `paid_at` (`date:Y-m-d`).
- Cada pago `paid` tiene una factura PDF con numeración `F-000123`.

### `turns`
Fila de atención del día (módulo de sala de espera).
- `turn_date` (`date:Y-m-d`), `number` (correlativo del día), `status` (enum `TurnStatus`), `called_at`, `finished_at`.
- Relacionado opcionalmente con `appointment_id` (si el paciente ya tenía cita) y siempre con `doctor_id`.

### `audit_logs`
Bitácora de auditoría. Polimórfica (`auditable_type` / `auditable_id`) más `patient_id` para poder filtrar por paciente aunque el objeto auditado no sea directamente un paciente.
- Se crea con el helper estático `AuditLog::record(AuditAction $action, ?Model $auditable, string $description, ?Patient $patient = null)`, que toma automáticamente el usuario autenticado, IP y user agent de la petición actual.
- `action` (enum `AuditAction`): ver, crear, actualizar, exportar, emitir, enviar, etc.

### `data_subject_requests`
Solicitudes de derechos del titular (Ley 1581 de Colombia: acceso, corrección, supresión/revocatoria).
- `type` (enum `DataRequestType`), `status` (enum `DataRequestStatus`).
- `details` y `response`, ambos **cifrados**.
- `due_at`: plazo legal en días hábiles, calculado al abrir la solicitud con `DataSubjectRequest::open()` (`addWeekdays($type->responseBusinessDays())`: 10 días hábiles para consultas, 15 para reclamos).

### Catálogos
- `specialties`: especialidades médicas.
- `insurers`: EPS/aseguradoras con su código.
- `medications`: catálogo de medicamentos (nombre, presentación, concentración).
- `diagnoses`: catálogo CIE-10 (`code`, `description`, `category`, `chapter`, `is_active`). Ver el ETL en `06-tareas-programadas-e-integraciones.md`.
- `diagnosis_imports`: historial de cargas del catálogo CIE-10 (origen, resultado, usuario).
- `app_settings`: pares clave/valor para configuración editable en tiempo de ejecución (color de marca, textos de la landing, datos del responsable de tratamiento de datos, flags como `landing_show_doctors`).
- `clinical_history_exports`: registro de cada historia clínica en PDF generada (quién, cuándo, período, consultas incluidas, si incluye notas internas) — permite que la verificación pública detecte páginas agregadas o quitadas.
- `tour_views`: registro de qué usuarios ya vieron el recorrido guiado de la interfaz.
- Tablas de infraestructura de Laravel: `cache`, `jobs`, `sessions` (si aplica), `push_subscriptions` (Web Push).

## 2.3 Estados y ciclos de vida

### Cita (`AppointmentStatus`)
```
requested (solicitada) → confirmed (confirmada) → completed (completada)
                                                 ↘
                                                   cancelled (cancelada)
```
Pasa a `completed` **automáticamente** al registrarse la consulta asociada.

### Pago (`PaymentStatus`)
```
pending (pendiente) → paid (pagado)
pending / paid → voided (anulado)  — requiere motivo; también ocurre al cancelar una cita con cobro pendiente
```

### Adjunto clínico (`AttachmentStatus`)
```
current (vigente) ──(corregir, con archivo nuevo)──► corrected (el original queda enlazado a la versión nueva, que queda current)
current (vigente) ──(anular, con motivo)──► voided
```
Cada adjunto solo puede cerrarse **una vez** (corregir o anular), nunca editarse libremente ni eliminarse.

### Turno (`TurnStatus`)
```
en espera → llamado → atendido
         ↘ cancelado
```

## 2.4 Campos cifrados en base de datos

Laravel cifra automáticamente (cast `encrypted` / `encrypted:array`) con el `APP_KEY` los siguientes campos, por contener información clínica o sensible:

| Tabla | Campos cifrados |
|---|---|
| `patients` | `allergies`, `chronic_conditions`, `medical_background` |
| `consultations` | `reason`, `symptoms`, `diagnosis`, `treatment`, `notes` |
| `consultation_attachments` | `status_reason` |
| `consultation_addenda` | `reason`, `content` |
| `clinical_documents` | `data` (JSON completo del documento) |
| `data_subject_requests` | `details`, `response` |

> **El `APP_KEY` es crítico.** Si se pierde o se rota sin un plan de re-cifrado, estos datos (y los archivos adjuntos cifrados en disco) son **irrecuperables**. Ver `DEPLOY.md §6`.

## 2.5 Migraciones

El historial completo vive en `database/migrations/`, ordenado cronológicamente; incluye, entre otras, la creación de las tablas base (usuarios, especialidades, médicos, horarios, pacientes, citas, consultas, recetas, pagos), la auditoría, los adjuntos, el control de contraseña temporal y de cuentas inactivas, el conteo de accesos, el recorrido guiado, la duración de cita por médico, los turnos, la configuración (`app_settings`), el catálogo CIE-10 y sus importaciones, las notas aclaratorias, los documentos clínicos, el catálogo de medicamentos, la aceptación de la política de privacidad, las suscripciones push, los códigos de verificación de documentos, el historial de exportación de historias clínicas y las solicitudes de derechos del titular.

Para aplicar el esquema completo: `php artisan migrate` (añadir `--seed` solo en desarrollo, ver `05-instalacion-y-entorno.md`).
