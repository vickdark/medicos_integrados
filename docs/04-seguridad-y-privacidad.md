# 4. Seguridad y privacidad

## 4.1 Autorización

- **Policies** (`app/Policies`, una por modelo): toda acción sensible pasa por una Policy, invocada desde el Form Request correspondiente (`authorize()`).
- **Scopes** en los modelos (`visibleTo`, `treatedBy`, `search`…): filtran los listados según el rol, para que un médico solo vea pacientes/citas que atiende, por ejemplo.
- **Regla de oro:** ocultar un botón o un menú en el frontend (Vue) **no es la protección real**; cualquier endpoint debe suponerse alcanzable directamente y estar protegido en el servidor.
- Tabla completa de permisos por acción y rol: ver `README.md §Roles y permisos`.

### Qué no ve cada rol
- **Recepción:** no ve antecedentes clínicos ni consultas.
- **Pacientes:** no ven las notas internas del personal médico, ni el historial de versiones/motivos de corrección de adjuntos, ni la copia oficial de recetas o documentos clínicos que no sean propios.

## 4.2 Cifrado en base de datos

Laravel cifra con el `APP_KEY` (cast `encrypted` / `encrypted:array`) todos los campos clínicos o sensibles — ver la tabla completa en `02-modelo-de-datos.md §2.4`. **Si se pierde el `APP_KEY`, estos datos no se pueden recuperar.**

## 4.3 Adjuntos clínicos cifrados en disco

- Se guardan en un disco **no público** (`storage/app/private`), cifrados con el `APP_KEY` (extensión `.enc`), y solo se sirven a través de un controlador que verifica permisos y los descifra al vuelo (nunca se exponen por URL directa).
- Adjuntos subidos **antes** de esta función se cifran una sola vez con `php artisan attachments:encrypt` (ejecutar tras migrar en una instalación existente).
- Si se pierde el `APP_KEY`, estos archivos tampoco son recuperables — por eso backups de base de datos y de `storage/app/private/` deben respaldarse **juntos**, y el `APP_KEY` por separado (ver `DEPLOY.md §6`).

## 4.4 Auditoría

`AuditLog::record()` registra, con usuario, IP y fecha:
- Consultas de historias clínicas y de consultas individuales.
- Altas y modificaciones de pacientes.
- Creación de consultas.
- Subida, descarga y cambio de estado (corrección/anulación) de adjuntos.
- Emisión y envío de recetas y documentos clínicos.
- Exportaciones de tablas (Excel/PDF).
- Altas, inactivaciones y cambios sobre usuarios.
- Cambios de configuración (color de marca, textos de landing, política de datos).
- Cargas reales del catálogo CIE-10 (no las simulaciones).

Solo el administrador puede consultar `/audit-logs`.

## 4.5 Verificación pública por código/QR

Ver el detalle funcional en `03-modulos-y-rutas.md §3.9`. Puntos de seguridad relevantes:
- Los códigos tienen ~55 bits de aleatoriedad.
- La ruta pública `/verificar/{código}` tiene límite de **30 peticiones por minuto**.
- Nunca se expone el diagnóstico completo ni el contenido clínico — solo los datos mínimos para detectar alteración del documento.
- El paciente se muestra enmascarado (iniciales + últimos 4 dígitos del documento).
- `APP_URL` debe apuntar al dominio público real en producción, porque de ahí se construye el QR.

## 4.6 Derechos del titular de los datos (Ley 1581 de 2012, Colombia)

- En Configuración → **Mis datos personales**, el paciente puede:
  - Descargar una copia de sus datos en JSON (sin las notas internas del personal; pide confirmación de contraseña; queda en la auditoría).
  - Registrar solicitudes de **acceso**, **corrección** o **supresión/revocatoria** (`App\Enums\DataRequestType`).
- Cada solicitud guarda su **plazo legal en días hábiles** (10 para consultas, 15 para reclamos — arts. 14 y 15 de la Ley 1581), sin contar festivos; el detalle y la respuesta quedan cifrados, junto con quién respondió y cuándo.
- El administrador las atiende desde **Solicitudes de datos** (`/data-requests`), marcándolas como atendidas o rechazadas con una respuesta; el paciente recibe un correo.
- Una solicitud de **supresión** sobre datos de la historia clínica normalmente se **rechaza**, porque esta debe conservarse por ley (Resolución 1995 de 1999).

## 4.7 Política de tratamiento de datos (`/privacidad`)

- En Configuración → **Política de datos** (solo admin) se editan razón social, NIT, dirección, teléfono, correo de contacto y el registro RNBD (solo se publican los campos completados). Se guardan en `app_settings`; lo no completado usa los valores por defecto de `config/privacy.php` (variables `PRIVACY_COMPANY`, `PRIVACY_NIT`, etc. en `.env`).
- Botón **Publicar nueva versión**: sube la versión (1.0 → 2.0…) y la fecha, y obliga a **todos** los pacientes a aceptarla de nuevo. Usarlo solo cuando cambie el texto de fondo y lo haya revisado un abogado.
- Cambiar solo los datos del responsable (sin cambiar el texto) **no** exige nueva aceptación.
- El registro exige aceptar la política y guarda `users.privacy_accepted_at` / `users.privacy_policy_version`.
- El middleware `EnsurePrivacyPolicyIsAccepted` redirige a cada paciente a `/privacidad/aceptar` cuando hay una versión más nueva que la que aceptó (o si nunca la aceptó, p. ej. cuentas creadas por el personal), antes de dejarlo usar el sistema.

> **Pendiente antes de producción:** `/privacidad` está redactada con base en la Ley 1581 de 2012 y normas relacionadas, pero **debe revisarla un abogado** antes de abrir el registro de pacientes.

## 4.8 Cierre de sesión por inactividad (solo personal)

- La sesión de administradores, médicos y recepción se cierra tras `STAFF_IDLE_MINUTES` minutos sin actividad (15 por defecto; `0` la desactiva), para que una pantalla abierta no deje expuesta información clínica.
- El navegador avisa 60 segundos antes con un diálogo y la opción *Seguir conectado*; la actividad se comparte entre pestañas.
- El servidor aplica el mismo límite (+1 minuto de margen) mediante el middleware `LogoutInactiveStaff`; el login muestra «Tu sesión se cerró por inactividad».
- Las consultas automáticas de fondo (p. ej. el refresco de Turnos cada 5 s) envían la cabecera `X-Background` para **no** contar como actividad.
- **Los pacientes no se ven afectados** por este límite; las notificaciones push siguen llegándoles porque las envía el servidor sin depender de la sesión activa.

## 4.9 Autenticación

Gestionada por Laravel Fortify:
- Límite de intentos de login (rate limiting).
- Verificación en dos pasos (2FA) **opcional** por usuario (activable en Configuración → Seguridad).
- Confirmación de contraseña exigida antes de acciones sensibles (p. ej. descargar la copia de datos personales).
- Contraseñas temporales obligatorias a cambiar en el primer ingreso cuando la cuenta la crea un administrador (`must_change_password`).

## 4.10 Checklist de seguridad antes de producción

Ver la lista completa en `DEPLOY.md §7`; resumen:
- `APP_DEBUG=false`, `APP_ENV=production`, HTTPS obligatorio, `SESSION_SECURE_COOKIE=true`.
- Sin usuarios ni datos de demostración.
- Usuario de base de datos propio (no root), firewall restringido.
- `.env` fuera del `document root` y con permisos `640`.
- 2FA activada para administradores y médicos.
- Backups automáticos probados (base de datos + `storage/app/private/`, y el `APP_KEY` guardado aparte).
- Revisión legal de la política de datos y de la normativa de historia clínica antes de operar.
