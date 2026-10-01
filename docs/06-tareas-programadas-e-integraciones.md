# 6. Tareas programadas, colas e integraciones

## 6.1 Colas y correo

- Driver de cola: `database` (tabla `jobs`). El worker se levanta con `php artisan queue:work` (o dentro de `composer run dev` / Supervisor en producción, ver `DEPLOY.md §5`).
- Notificaciones por correo en español (`app/Notifications`): solicitud, confirmación, reprogramación y cancelación de citas; credenciales de cuenta; respuesta a solicitudes de datos personales, etc.
- **Excepción intencional a la cola:** el correo de credenciales de una cuenta nueva (con la contraseña temporal) y el envío de recetas/documentos clínicos **no pasan por la cola**, para no dejar la contraseña o el PDF en texto plano en la tabla `jobs`. Si el envío falla, la operación principal (crear la cuenta, emitir el documento) igual se completa y quien la ejecutó recibe un aviso.
- Con `MAIL_MAILER=log` (desarrollo), los correos quedan escritos en `storage/logs/laravel.log`.

## 6.2 Recordatorios de citas

- Comando: `php artisan appointments:send-reminders`, programado para correr **cada hora** vía el scheduler de Laravel (`php artisan schedule:work` en desarrollo; cron `* * * * * php artisan schedule:run` en producción).
- Envía por correo un recordatorio al paciente de las citas **confirmadas** que ocurren en las próximas 24 horas.
- No se envía si faltan menos de 2 horas para la cita, ni se envía dos veces (columna `appointments.reminder_sent_at`).
- Si la cita se reprograma, el recordatorio se vuelve a enviar.
- Usa la cuenta de usuario del paciente si existe; si no, el correo registrado en su ficha.

## 6.3 Notificaciones push (navegador)

- El paciente las activa en Configuración → Notificaciones (por dispositivo) y recibe el mismo recordatorio como notificación del navegador, además del correo.
- Implementadas con **Web Push** y claves VAPID: generarlas una sola vez con `php artisan webpush:vapid` (quedan en `.env` como `VAPID_PUBLIC_KEY` / `VAPID_PRIVATE_KEY`) y definir `VAPID_SUBJECT`.
- Requiere HTTPS (o `localhost`) y el worker de colas activo.
- En Windows, si PHP falla con *Unable to create the key*, definir `OPENSSL_CONF` apuntando a un `openssl.cnf` válido (p. ej. el de Herd).
- En iPhone/iPad solo funciona si la página se agrega a la pantalla de inicio (PWA).
- Las suscripciones vencidas (`push_subscriptions`) se eliminan automáticamente.

## 6.4 Catálogo CIE-10 (ETL)

**Fuente oficial:** tabla de referencia CIE-10 de SISPRO (Ministerio de Salud y Protección Social de Colombia), la misma que usan los validadores de RIPS:
`https://web.sispro.gov.co/WebPublico/Consultas/ConsultarDetalleReferenciaBasica.aspx?Code=CIE10`

Para descargarla: abrir el enlace, escribir un correo en *Email para envío de datos exportados* y exportar; el archivo (Excel o CSV) llega a ese correo, con columnas como `Codigo`, `Nombre`, `Descripcion`, `Habilitado` y `Extra_VI:Capitulo`.

### Carga desde la interfaz (solo admin)

Menú **Catálogo CIE-10** (`/cie10`): muestra cuántos códigos hay habilitados/deshabilitados y la fecha de la última carga; permite subir el archivo (Excel o CSV, hasta 50 MB) con las opciones:
- **Solo simular** (marcada por defecto): no guarda nada, solo muestra el resultado.
- **Deshabilitar los códigos que ya no vienen en la tabla**.

Presenta el resultado con las filas rechazadas (descargables en CSV), guarda el historial de cargas (también las hechas por consola) y permite buscar en el catálogo cargado. El archivo subido se elimina después de procesarlo; cada carga **real** (no simulada) queda en la auditoría.

### Carga desde la consola

```bash
php artisan diagnoses:import ruta/CIE10.xlsx --dry-run              # simula y muestra el resultado, sin guardar
php artisan diagnoses:import ruta/CIE10.xlsx                        # carga o actualiza
php artisan diagnoses:import ruta/CIE10.xlsx --deactivate-missing   # además deshabilita los códigos retirados
```

### Etapas del proceso (`App\Actions\Diagnoses\ImportCie10Catalog`)

1. **Extracción:** lee Excel (`.xlsx`, `.xls`, `.ods`) o CSV/TXT (detecta separador `;`, `,`, `|` o tabulador; convierte de Windows-1252 a UTF-8). Ubica columnas por su encabezado; si el archivo no tiene encabezado, toma el código de la primera columna y la descripción de la segunda.
2. **Transformación:** normaliza el código al formato de cuatro caracteres (`I10` → `I10X`, `E11.9` → `E119`); toma `Nombre` como descripción, `Descripcion` como categoría, `Capitulo` como capítulo; interpreta `Habilitado` (SI/NO). Rechaza filas sin código, con código inválido, sin descripción o con código repetido, indicando el motivo.
3. **Carga:** inserta los códigos nuevos y actualiza los que cambiaron, por lotes; los que no cambian no se tocan (permite ejecutar el mismo archivo varias veces sin efectos duplicados). Los códigos **nunca se borran** (pueden estar referenciados por consultas); con `--deactivate-missing` los que ya no vienen en la tabla quedan deshabilitados. Usar esta opción **solo** con la tabla completa.

Al terminar muestra un resumen (leídas, válidas, rechazadas, nuevas, actualizadas, sin cambios, deshabilitadas). Si hubo rechazos, guarda el detalle en `storage/app/private/imports/cie10-rechazos-<fecha>.csv`. Los códigos deshabilitados no aparecen en el buscador ni se pueden asignar a consultas nuevas, pero se siguen mostrando en las consultas que ya los tienen.

## 6.5 Exportación a Excel y PDF

Ver el patrón transversal en `03-modulos-y-rutas.md §3.14`. Librerías: PhpSpreadsheet (Excel) y barryvdh/laravel-dompdf (PDF). Documentos PDF adicionales: facturas, recetas, documentos clínicos, historia clínica completa y reportes — todos generados con dompdf a partir de vistas Blade en `resources/views/pdf`.

## 6.6 Otros comandos Artisan relevantes

| Comando | Qué hace |
|---|---|
| `php artisan diagnoses:import` | ETL del catálogo CIE-10 (ver §6.4) |
| `php artisan attachments:encrypt` | Cifra en disco los adjuntos clínicos guardados antes de habilitarse el cifrado (ejecutar una sola vez tras migrar una base existente) |
| `php artisan appointments:send-reminders` | Envía los recordatorios de citas de las próximas 24 h (programado cada hora) |
| `php artisan webpush:vapid` | Genera el par de claves VAPID para notificaciones push |

Ver el listado completo con `php artisan list` dentro de `app/Console/Commands`.
