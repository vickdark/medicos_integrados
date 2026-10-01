# Manual del Administrador

El rol **Administrador** tiene acceso completo al sistema: configuración general, gestión de usuarios y médicos, catálogos, todos los módulos clínicos y administrativos, reportes y auditoría. Es el único rol que puede crear cuentas, cambiar la configuración de la aplicación y ver la auditoría completa.

## Índice

1. [Iniciar sesión y primeros pasos](#1-iniciar-sesión-y-primeros-pasos)
2. [Panel de inicio](#2-panel-de-inicio)
3. [Gestión de usuarios](#3-gestión-de-usuarios)
4. [Médicos, especialidades y horarios](#4-médicos-especialidades-y-horarios)
5. [Pacientes](#5-pacientes)
6. [Citas y calendario](#6-citas-y-calendario)
7. [Consultas e historia clínica](#7-consultas-e-historia-clínica)
8. [Recetas y documentos clínicos](#8-recetas-y-documentos-clínicos)
9. [Pagos y facturación](#9-pagos-y-facturación)
10. [Turnos y pantalla de sala de espera](#10-turnos-y-pantalla-de-sala-de-espera)
11. [Catálogo de diagnósticos CIE-10](#11-catálogo-de-diagnósticos-cie-10)
12. [Catálogo de medicamentos](#12-catálogo-de-medicamentos)
13. [Catálogo de EPS / aseguradoras](#13-catálogo-de-eps--aseguradoras)
14. [Reportes](#14-reportes)
15. [Auditoría](#15-auditoría)
16. [Configuración del sistema](#16-configuración-del-sistema)
17. [Privacidad y derechos de los pacientes](#17-privacidad-y-derechos-de-los-pacientes)
18. [Verificación pública de documentos](#18-verificación-pública-de-documentos)
19. [Mi configuración personal](#19-mi-configuración-personal)
20. [Buenas prácticas y preguntas frecuentes](#20-buenas-prácticas-y-preguntas-frecuentes)

---

## 1. Iniciar sesión y primeros pasos

1. Abrir la dirección web de la clínica en el navegador.
2. Hacer clic en **Iniciar sesión**.
3. Escribir el correo y la contraseña.
   - Si la cuenta la creó otro administrador, la contraseña es **temporal**: al entrar, el sistema lleva automáticamente a *Configuración → Seguridad* y pide definir una contraseña nueva antes de poder usar el resto del sistema.
4. Si la cuenta tiene activada la verificación en dos pasos (2FA), se pedirá el código del segundo factor.

💡 Si ya existe una cuenta de administrador y se quiere crear una **nueva**, no hace falta registrarse desde la página pública: se crea directamente desde el módulo **Usuarios** (ver sección 3).

## 2. Panel de inicio

Al iniciar sesión se abre automáticamente el **Panel de inicio** (`Dashboard`), con indicadores generales del funcionamiento de la clínica (por ejemplo, citas del día, pagos pendientes, actividad reciente). El contenido se adapta automáticamente al rol; como administrador se ve la vista con mayor nivel de detalle operativo.

## 3. Gestión de usuarios

🔒 Este módulo es exclusivo del administrador. Desde **Usuarios** se crean todas las cuentas de acceso al sistema y se les asigna un rol.

### 3.1 Crear una cuenta nueva

1. Ir a **Usuarios → Nuevo usuario**.
2. Elegir el **rol**: Administrador, Recepción, Médico o Paciente. El formulario cambia automáticamente para pedir los datos que corresponden a ese rol:

   | Rol | Datos que se piden |
   |---|---|
   | Administrador / Recepción | Nombre, correo y contraseña |
   | Médico | Los anteriores + especialidad, número de registro médico, teléfono, firma, tarifa de consulta y una breve reseña |
   | Paciente | Los anteriores + datos personales (documento, fecha de nacimiento, sexo), contacto y antecedentes médicos |

3. Marcar o desmarcar la casilla **"Enviar los datos de acceso por correo"** (viene marcada por defecto). Si queda marcada, la persona recibe un correo con su usuario, una contraseña temporal y el enlace para iniciar sesión.
4. Hacer clic en **Guardar**.

💡 Si ya existía una ficha de paciente con ese mismo correo (por ejemplo, cargada antes por recepción sin cuenta de acceso), el sistema la **vincula automáticamente** en lugar de crear un duplicado.

⚠️ La contraseña que recibe la persona es **temporal**: en su primer ingreso el sistema la obliga a cambiarla antes de poder usar cualquier otra función.

### 3.2 Editar una cuenta

1. En el listado de **Usuarios**, hacer clic en el usuario a editar.
2. Cambiar los datos necesarios.
3. Si se escribe una contraseña nueva y queda marcada la casilla de envío por correo, la persona recibe esa nueva contraseña por correo.

⚠️ **El rol no se puede cambiar** en cuentas de médico o paciente, porque ya tienen historial clínico/administrativo asociado. Entre Administrador y Recepción sí se puede alternar el rol (excepto en la propia cuenta: nadie puede cambiarse el rol a sí mismo).

### 3.3 Inactivar o reactivar una cuenta

1. En el listado de **Usuarios**, usar el botón **Inactivar** (o **Activar** si ya estaba inactiva) en la fila del usuario.
2. Confirmar la acción.

Efecto: la persona no podrá volver a iniciar sesión y, si tenía una sesión abierta, se cierra automáticamente en su siguiente acción. **No se borra ningún dato.**

⚠️ El administrador no puede inactivar su propia cuenta.

### 3.4 Buscar y filtrar usuarios

En el listado se puede buscar por nombre o correo, y filtrar por rol. La tabla se puede exportar a **Excel** o **PDF** (incluye la columna de estado de acceso) con los botones correspondientes.

### 3.5 Registro desde la página pública

Cualquier persona que se registre desde la landing pública queda automáticamente con rol **Paciente**. Si recepción ya había creado su ficha con el mismo correo, la cuenta se vincula sola a esa ficha existente.

---

## 4. Médicos, especialidades y horarios

🔒 Crear y editar médicos y especialidades es exclusivo del administrador. Los horarios los puede ver y editar también el propio médico sobre el suyo.

### 4.1 Crear un médico

1. Ir a **Médicos → Nuevo médico** (lleva al mismo formulario que crear un usuario con rol Médico, ver sección 3.1).
2. Completar: nombre, correo, especialidad, número de registro médico, teléfono, tarifa de consulta, reseña y, opcionalmente, foto y firma.
3. Guardar.

### 4.2 Firma del médico

La firma es una **imagen** (PNG, JPG o WebP de hasta 1 MB) que se imprime sobre la línea de firma de recetas y documentos clínicos. Se puede cargar al crear/editar al médico desde este módulo, o el propio médico puede subirla desde su configuración personal.

⚠️ Si un médico no tiene firma registrada, sus recetas y documentos clínicos salen marcados **"SIN FIRMA"** y **no se pueden enviar por correo**. En el listado de médicos aparece la marca **"Sin firma registrada"**.

### 4.3 Foto del médico

Imagen (JPG, PNG o WebP, hasta 2 MB) que se muestra en la landing pública si está activada la opción "Mostrar a los médicos en la página de inicio" (ver sección 16.3).

### 4.4 Especialidades

En **Especialidades** se crean, editan y buscan las especialidades médicas que luego se asignan a cada médico y se muestran en la landing pública.

### 4.5 Horarios de atención

1. Ir a **Médicos → [elegir médico] → Horarios**.
2. Definir los bloques de atención por día de la semana. Se pueden crear bloques para varios días a la vez y ver la semana completa.
3. Cada médico tiene además una **duración de cita** configurable (entre 10 y 60 minutos) que determina cómo se dividen los espacios disponibles en el calendario.

💡 En la agenda de cada médico, las horas que quedan fuera de su horario de atención aparecen rayadas (no disponibles para agendar).

---

## 5. Pacientes

### 5.1 Registrar un paciente nuevo

1. Ir a **Pacientes → Nuevo paciente**.
2. Completar documento (tipo y número), nombre, fecha de nacimiento, sexo, teléfono, dirección, contacto de emergencia, EPS/aseguradora y tipo de afiliación.
3. Guardar.

💡 El tipo de documento es obligatorio solo si se escribe el número de documento.

### 5.2 Buscar, editar y ver la ficha de un paciente

- El listado de **Pacientes** permite buscar por nombre, documento o correo.
- Al abrir la ficha de un paciente se ve su información personal, antecedentes, citas, consultas y pagos.
- Los antecedentes clínicos (alergias, enfermedades crónicas, antecedentes) solo los puede registrar o editar personal médico, no se cargan desde este formulario administrativo general salvo por el propio médico tratante.

⚠️ El listado de pacientes **nunca muestra ni exporta datos clínicos** (diagnósticos, tratamientos, etc.), solo datos de identificación y contacto.

### 5.3 Historia clínica en PDF

Desde la ficha del paciente: **Descargar historia clínica**. Se puede elegir un rango de fechas (incluye solo las consultas de ese período) o dejarlo vacío para traer todo el historial. Incluye datos del paciente, antecedentes, consultas con signos vitales y recetas. Cada descarga queda registrada en la auditoría.

---

## 6. Citas y calendario

### 6.1 Ver el calendario de citas

En **Citas** hay dos formas de verlas:
- **Calendario:** vistas Día (por defecto), Semana y Mes en pantallas grandes; en el teléfono, un mini calendario mensual con vista Día y Agenda.
- **Lista:** con buscador, filtros (estado, rango de fechas) y exportación a Excel/PDF.

### 6.2 Agendar una cita

1. En **Citas**, hacer clic en **Nueva cita**.
2. Elegir paciente y médico.
3. Elegir el horario en el calendario semanal que aparece: se ven en colores distintos los espacios libres, los ocupados y los que quedan fuera del horario de atención del médico.
4. Completar motivo y notas si aplica.
5. Guardar: como administrador, la cita queda **confirmada** directamente (no pasa por "solicitada").

### 6.3 Confirmar o cancelar una cita

Desde el listado, usar el botón de cambio de estado en la fila de la cita (confirmar o cancelar, según corresponda).

### 6.4 Reprogramar una cita (incluye cambiar de médico)

1. Abrir la cita y elegir **Reprogramar**.
2. Elegir el nuevo horario en el calendario (se puede mover incluso al mismo día si hay espacio libre).
3. Como administrador, también se puede **cambiar el médico** asignado: la disponibilidad se valida contra el nuevo médico.
4. Se pueden editar también el motivo y las notas.

Efectos automáticos:
- La cita conserva su estado (si estaba confirmada, sigue confirmada).
- Si tenía un cobro **pendiente**, el monto se ajusta a la tarifa del nuevo médico (un cobro ya **pagado** no se modifica).
- Se notifica por correo al paciente, al médico anterior y al nuevo médico.

### 6.5 Pago rápido desde una cita

El botón de pago en la fila de una cita abre el formulario de pago con paciente, cita, concepto y monto (la tarifa del médico) ya precargados.

---

## 7. Consultas e historia clínica

El administrador **no registra consultas** (esa acción es exclusiva del médico), pero puede ver el detalle de las consultas de cualquier paciente desde su ficha, incluyendo:
- Motivo, síntomas, diagnóstico (texto y código CIE-10), tratamiento.
- Signos vitales registrados.
- Recetas (como copia de solo consulta, marcada "Este documento no es válido", nunca la copia oficial firmada).
- Adjuntos vigentes (no ve el historial de versiones ni los motivos de corrección/anulación — eso es información interna exclusiva del médico que registró la consulta).
- Notas aclaratorias agregadas por el médico sobre esa consulta.

⚠️ Una consulta registrada **no se puede editar ni eliminar**, por nadie, ni siquiera por el administrador. Las correcciones las hace el médico tratante mediante notas aclaratorias (ver manual del médico).

---

## 8. Recetas y documentos clínicos

El administrador puede **ver** (nunca emitir) las recetas y documentos clínicos de cualquier consulta, siempre como una copia de referencia, claramente marcada como no válida (con marca de agua), ya que la copia oficial firmada solo la obtiene el médico que la emitió.

Documentos clínicos disponibles en el sistema (emitidos solo por el médico tratante): consentimiento informado, incapacidad médica, remisión y orden de exámenes. Cada uno lleva un número de documento propio y un código de verificación con QR. Ver el detalle funcional completo en el manual del médico.

---

## 9. Pagos y facturación

### 9.1 Registrar un pago

1. Ir a **Pagos → Nuevo pago** (o usar el botón de pago rápido desde una cita).
2. Elegir paciente, concepto, monto y método (efectivo, tarjeta, transferencia u otro).
3. Guardar: queda en estado **pendiente** salvo que se marque como pagado.

### 9.2 Marcar un pago como pagado

Desde el listado de **Pagos**, abrir el diálogo de la fila correspondiente y confirmar.

### 9.3 Anular un pago

Admin y recepción pueden anular un pago (pendiente o pagado) indicando el motivo, que queda guardado en las notas del pago. También se anula automáticamente un cobro pendiente al cancelar la cita asociada.

### 9.4 Factura en PDF

Cada pago **pagado** tiene su factura (numerada `F-000123`), descargable desde el listado de Pagos o el detalle del pago.

### 9.5 Buscar, filtrar y exportar pagos

Se puede buscar por paciente, concepto o referencia, y filtrar por estado y rango de fechas (los totales mostrados se recalculan según el filtro). Exportable a Excel o PDF.

---

## 10. Turnos y pantalla de sala de espera

El administrador puede ver y gestionar la fila de turnos igual que recepción (crear turno, llamar, marcar atendido, devolver a la fila o cancelar). Ver el detalle paso a paso en el manual de Recepción, sección correspondiente.

La **pantalla pública de turnos** (`/pantalla-de-turnos`) se puede abrir en un monitor de la sala de espera; se actualiza sola cada 5 segundos y muestra solo el código del turno y el médico, **nunca el nombre del paciente**.

---

## 11. Catálogo de diagnósticos CIE-10

🔒 Exclusivo del administrador.

### 11.1 Para qué sirve

Cada consulta nueva exige un diagnóstico codificado con la tabla oficial CIE-10. El sistema trae de fábrica una muestra reducida de códigos frecuentes, pensada solo para pruebas; en producción debe cargarse la tabla oficial completa.

### 11.2 Obtener el archivo oficial

1. Abrir: `https://web.sispro.gov.co/WebPublico/Consultas/ConsultarDetalleReferenciaBasica.aspx?Code=CIE10`
2. Escribir un correo en el campo *Email para envío de datos exportados* y exportar.
3. El archivo (Excel o CSV) llega a ese correo.

### 11.3 Cargar el catálogo

1. Ir a **Catálogo CIE-10** (`/cie10`). La pantalla muestra cuántos códigos están habilitados/deshabilitados y la fecha de la última carga.
2. Subir el archivo descargado (Excel o CSV, hasta 50 MB).
3. Dejar marcada la opción **"Solo simular"** la primera vez, para revisar el resultado sin guardar nada.
4. Revisar el resumen: filas leídas, válidas, rechazadas, nuevas, actualizadas y sin cambios. Las filas rechazadas se pueden descargar en CSV con el motivo de cada rechazo.
5. Cuando el resultado de la simulación se vea correcto, repetir la carga **sin** marcar "Solo simular" para guardar los cambios de verdad.
6. Opcional: marcar **"Deshabilitar los códigos que ya no vienen en la tabla"** solo cuando se está cargando la tabla **completa** y actualizada (no un listado parcial).

⚠️ Los códigos **nunca se eliminan** del sistema, porque puede haber consultas antiguas que los usan; "deshabilitar" solo los oculta del buscador para consultas nuevas.

### 11.4 Buscar en el catálogo cargado

La misma pantalla permite buscar códigos por número o por palabras, útil para verificar que una carga quedó correcta.

### 11.5 Historial de cargas

La pantalla conserva el historial de todas las cargas realizadas (desde la interfaz o por consola), con fecha, resultado y quién la hizo. Cada carga real (no simulada) queda también en la auditoría general.

---

## 12. Catálogo de medicamentos

En **Medicamentos** (accesible también para médicos) se administra el catálogo de nombre, presentación y concentración que alimenta el selector de medicamentos al escribir una receta.

---

## 13. Catálogo de EPS / aseguradoras

🔒 Exclusivo del administrador. En **Aseguradoras** se administran las EPS y aseguradoras disponibles para asignar a los pacientes, cada una con su código.

⚠️ No se puede eliminar una aseguradora que ya esté asignada a algún paciente.

---

## 14. Reportes

En **Reportes** se consultan citas, ingresos y consultas, agrupables por médico o por especialidad, y filtrables por período, médico y especialidad. Como administrador se ve el detalle completo (incluyendo ingresos). Exportable a Excel y PDF.

---

## 15. Auditoría

🔒 Exclusivo del administrador. En **Auditoría** (`/audit-logs`) se consulta el registro completo de accesos y cambios sobre información clínica y administrativa: quién consultó una historia clínica o una consulta, quién dio de alta o modificó un paciente, quién creó una consulta, quién subió, descargó, corrigió o anuló un adjunto, quién exportó una tabla, quién cambió la configuración, etc. Cada registro incluye usuario, dirección IP y fecha/hora. Se puede buscar por usuario, paciente, descripción o IP, y filtrar por tipo de acción, paciente y rango de fechas.

---

## 16. Configuración del sistema

Todas estas opciones están en el menú **Configuración** y son exclusivas del administrador (salvo que se indique lo contrario).

### 16.1 Color de la aplicación

En *Configuración → Color de la aplicación*: elegir uno de los colores sugeridos o definir uno personalizado, con vista previa en vivo. Este color se usa en el logo, gráficas, etiquetas, la landing, el recorrido guiado y también colorea las facturas, recetas, historia clínica y tablas en PDF, además del encabezado de los Excel.

⚠️ No se aceptan colores demasiado claros (deben tener suficiente contraste sobre fondo blanco). Hay un botón para **restablecer** el color original.

### 16.2 Página de inicio (landing pública)

En *Configuración → Página de inicio* se editan: teléfono, correo y dirección que aparecen en el pie de página, y los textos de las secciones *Quiénes somos* (título, dos párrafos, misión y tres valores) y *Servicios médicos* (título, introducción y seis servicios). Hay un botón **Restablecer textos** para volver a los valores de fábrica. Un dato de contacto vacío simplemente no se muestra.

### 16.3 Mostrar médicos en la página de inicio

En *Configuración → Personalización*, activar **"Mostrar a los médicos en la página de inicio"** (apagada por defecto) para que la sección *Quiénes somos* de la landing muestre tarjetas con foto, nombre, especialidad y reseña de los médicos con cuenta activa. Nunca se muestra el registro médico, la tarifa ni el correo del médico en esa vista pública.

### 16.4 Política de datos personales

Ver el detalle completo en la sección 17.

### 16.5 Solicitudes de datos personales (bandeja)

Ver el detalle completo en la sección 17.

---

## 17. Privacidad y derechos de los pacientes

### 17.1 Configurar los datos del responsable del tratamiento

En *Configuración → Política de datos*: completar razón social, NIT, dirección, teléfono, correo de contacto y, si aplica, el número de registro ante el RNBD. Solo se publican en `/privacidad` los campos que se completen; lo que falte usa un valor de ejemplo por defecto.

⚠️ El texto de `/privacidad` está redactado con base en la ley colombiana de protección de datos personales, pero **debe ser revisado por un abogado** antes de abrir el registro público de pacientes.

### 17.2 Publicar una nueva versión de la política

El botón **Publicar nueva versión** sube la numeración de la política (por ejemplo, de 1.0 a 2.0) y obliga a **todos los pacientes** a volver a aceptarla antes de seguir usando el sistema.

⚠️ Usar esta opción **solo** cuando cambie el contenido de fondo de la política y ya haya sido revisado por un abogado. Cambiar solo los datos de contacto del responsable **no** requiere nueva aceptación.

### 17.3 Atender solicitudes de derechos del titular

Los pacientes pueden pedir **acceso**, **corrección** o **supresión/revocatoria** de sus datos desde su propio portal. El administrador las gestiona en *Solicitudes de datos* (`/data-requests`):

1. Abrir la solicitud pendiente.
2. Revisar el tipo y el detalle que escribió el paciente.
3. Marcarla como **atendida** (con una respuesta) o **rechazada** (con el motivo).
4. Guardar: el paciente recibe un correo con la respuesta.

💡 Cada solicitud tiene un plazo legal: 10 días hábiles para consultas de acceso/corrección y 15 días hábiles para reclamos, sin contar festivos.

⚠️ Una solicitud de **supresión** sobre datos de la historia clínica normalmente debe **rechazarse**, porque la ley obliga a conservar la historia clínica durante un período mínimo.

---

## 18. Verificación pública de documentos

Cualquier persona (sin necesidad de iniciar sesión) puede verificar la autenticidad de una receta, un documento clínico o una historia clínica en PDF entrando a `/verificar` y escribiendo el código impreso en el documento, o escaneando el código QR que trae impreso. La página muestra los datos clave del documento (tipo, número, fecha, médico, si está firmado, etc.) sin revelar el diagnóstico ni el contenido clínico completo, y permite detectar si el documento fue alterado.

💡 Como administrador conviene probar esta pantalla al menos una vez tras la puesta en marcha, para confirmar que el enlace del QR apunta al dominio público correcto de la clínica.

---

## 19. Mi configuración personal

En el menú **Configuración**, todos los roles (incluido el administrador) tienen acceso a:
- **Perfil:** nombre y correo.
- **Seguridad:** cambio de contraseña y activación de la verificación en dos pasos (2FA).
- **Apariencia:** modo claro u oscuro (el claro es el predeterminado).

💡 Se recomienda activar la verificación en dos pasos en las cuentas de administrador, por el nivel de acceso que tienen.

---

## 20. Buenas prácticas y preguntas frecuentes

**¿Puedo editar una consulta que un médico registró mal?**
No. Ninguna consulta se puede editar ni borrar, ni siquiera por el administrador. El médico que la registró debe agregar una nota aclaratoria indicando la corrección.

**Un médico renunció, ¿puedo borrar su cuenta?**
No se eliminan cuentas con historial asociado. Lo correcto es **inactivarla** (sección 3.3): deja de poder iniciar sesión, pero conserva su historial clínico y administrativo intacto.

**¿Por qué un paciente no puede editar su fecha de nacimiento?**
Porque ya fue atendido al menos una vez. Esos datos de identificación básica se bloquean para el paciente después de su primera consulta, para proteger la integridad del expediente; sí sigue pudiendo editar teléfono, dirección y contacto de emergencia.

**¿Qué pasa si pierdo la clave `APP_KEY` del servidor?**
Es un asunto técnico para el equipo de desarrollo/infraestructura: esa clave cifra la información clínica sensible y los archivos adjuntos; si se pierde, esos datos no se pueden recuperar. Ver la documentación técnica (`docs/04-seguridad-y-privacidad.md`).

**¿Puedo activar el 2FA obligatorio para todo el personal?**
Hoy el 2FA es opcional por cuenta; cada usuario lo activa desde su propia Configuración → Seguridad. Se recomienda pedir a médicos y administradores que lo activen.
