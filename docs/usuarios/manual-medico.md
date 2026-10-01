# Manual del Médico

El rol **Médico** registra la atención clínica de sus pacientes: consultas, signos vitales, diagnósticos codificados con CIE-10, recetas, documentos clínicos legales y adjuntos. Solo ve y gestiona los pacientes que **atiende** (aquellos con los que tiene al menos una cita o una consulta registrada).

## Índice

1. [Iniciar sesión](#1-iniciar-sesión)
2. [Panel de inicio y mi agenda](#2-panel-de-inicio-y-mi-agenda)
3. [Mis pacientes](#3-mis-pacientes)
4. [Mis citas](#4-mis-citas)
5. [Registrar una consulta](#5-registrar-una-consulta)
6. [Diagnóstico codificado con CIE-10](#6-diagnóstico-codificado-con-cie-10)
7. [Corregir una consulta ya registrada: notas aclaratorias](#7-corregir-una-consulta-ya-registrada-notas-aclaratorias)
8. [Archivos adjuntos de la consulta](#8-archivos-adjuntos-de-la-consulta)
9. [Receta médica](#9-receta-médica)
10. [Documentos clínicos](#10-documentos-clínicos)
11. [Mi firma profesional](#11-mi-firma-profesional)
12. [Verificación pública de mis documentos](#12-verificación-pública-de-mis-documentos)
13. [Historia clínica en PDF](#13-historia-clínica-en-pdf)
14. [Turnos (mi fila de atención)](#14-turnos-mi-fila-de-atención)
15. [Catálogo de medicamentos](#15-catálogo-de-medicamentos)
16. [Mis horarios de atención](#16-mis-horarios-de-atención)
17. [Reportes](#17-reportes)
18. [Lo que un médico no puede hacer](#18-lo-que-un-médico-no-puede-hacer)
19. [Mi configuración personal](#19-mi-configuración-personal)
20. [Preguntas frecuentes](#20-preguntas-frecuentes)

---

## 1. Iniciar sesión

1. Abrir la dirección web de la clínica y hacer clic en **Iniciar sesión**.
2. Escribir el correo y la contraseña entregados por el administrador.
3. En el primer ingreso, el sistema obliga a definir una contraseña nueva (la recibida por correo es temporal).

💡 Se recomienda activar la verificación en dos pasos (2FA) en *Configuración → Seguridad*, por el nivel de información clínica sensible al que se accede.

---

## 2. Panel de inicio y mi agenda

Al iniciar sesión se abre el **Panel de inicio**, con la agenda del día y accesos directos a lo más usado.

---

## 3. Mis pacientes

En **Pacientes** solo aparecen aquellos a los que el médico **atiende** (con al menos una cita o consulta en común). Desde la ficha de cada paciente se puede ver:
- Datos personales y de contacto.
- Antecedentes clínicos (alergias, enfermedades crónicas, antecedentes) — el médico es quien **registra y edita** esta información.
- Historial de citas, consultas y pagos.
- Botón para **registrar una consulta nueva** y para **descargar la historia clínica en PDF**.

💡 Si el paciente es nuevo para este médico, primero debe existir al menos una cita o registrarse una consulta para que aparezca en este listado.

---

## 4. Mis citas

En **Citas** el médico ve su propia agenda (calendario en vistas Día/Semana/Mes, o Lista con filtros). Puede:
- **Confirmar o cancelar** las citas de su propia agenda.
- **Reprogramar**, cambiando solo fecha y hora (el médico **no puede** reasignar la cita a otro colega; eso es exclusivo de administración y recepción).

El motivo y el médico de una cita confirmada quedan bloqueados para el paciente, pero el propio médico puede seguir viendo y gestionando el resto de los detalles desde su agenda.

---

## 5. Registrar una consulta

1. Desde la ficha del paciente (o desde su cita del día), hacer clic en **Registrar consulta**.
2. Completar:
   - **Motivo** de la consulta.
   - **Signos vitales**: peso, talla, temperatura, frecuencia cardíaca, entre otros.
   - **Síntomas**.
   - **Diagnóstico**: texto libre **y** el diagnóstico codificado en CIE-10 (ver sección 6, es obligatorio).
   - **Tratamiento**.
   - **Notas internas** (no las ve el paciente).
   - Medicamentos para la receta, si aplica (ver sección 9).
3. Adjuntar archivos si corresponde (ver sección 8).
4. Guardar.

Al guardar:
- Si la consulta estaba asociada a una cita, la cita pasa automáticamente a estado **Completada**.
- El registro de la consulta queda **inmutable**: ni el propio médico, ni el administrador, pueden editarlo o borrarlo después. Cualquier corrección debe hacerse con una **nota aclaratoria** (sección 7).

⚠️ Revisar bien los datos antes de guardar: no hay forma de "deshacer" o editar una consulta ya registrada.

---

## 6. Diagnóstico codificado con CIE-10

Toda consulta **nueva** exige un diagnóstico principal codificado:

1. En el campo de diagnóstico, buscar por **código** (por ejemplo `J00`, `E11.9`) o por **palabras** (por ejemplo "rinofaringitis"). La búsqueda se hace en el catálogo oficial cargado por el administrador.
2. Elegir el **tipo** de diagnóstico: *impresión diagnóstica*, *confirmado nuevo* o *confirmado repetido*.
3. Opcionalmente, agregar hasta **3 diagnósticos relacionados** adicionales.
4. El campo de descripción en texto libre se mantiene en paralelo al código, por si se necesita precisar algo que el catálogo no recoge.

Los códigos elegidos aparecen automáticamente en el detalle de la consulta, en la historia clínica en PDF y en la receta.

💡 Si un código no aparece en el buscador, puede que esté deshabilitado en el catálogo (avisar al administrador) o que aún no se haya cargado la tabla CIE-10 completa.

---

## 7. Corregir una consulta ya registrada: notas aclaratorias

Como ninguna consulta se puede editar ni borrar, las correcciones o aclaraciones se hacen así:

1. Abrir el detalle de la consulta a corregir (debe ser una consulta que **este mismo médico** registró).
2. Hacer clic en **Agregar nota aclaratoria**.
3. Indicar:
   - **Qué parte aclara**: motivo, síntomas, signos vitales, diagnóstico, tratamiento, receta, notas u otro.
   - El **motivo** de la aclaración.
   - El **texto** de la aclaración.
4. Guardar.

La nota queda con el autor, la fecha y la hora, se guarda cifrada, y **no se puede modificar ni eliminar** una vez creada. Aparece debajo del registro original (que sigue viéndose tal como se escribió la primera vez), en la ficha del paciente (con un contador de notas por consulta) y en la historia clínica en PDF. El paciente también puede ver estas notas.

⚠️ Solo el médico que registró la consulta original puede agregarle notas aclaratorias.

---

## 8. Archivos adjuntos de la consulta

Se pueden adjuntar archivos (PDF o imágenes) a una consulta, por ejemplo resultados de exámenes o estudios.

### 8.1 Subir un adjunto

Desde el detalle de la consulta: **Subir adjunto**, elegir el archivo y guardar. El archivo se guarda cifrado en el servidor.

### 8.2 Corregir o anular un adjunto

Los adjuntos son parte de la historia clínica y **nunca se eliminan**. Solo el médico que registró la consulta puede, **una sola vez por archivo** y siempre indicando un motivo:

- **Corregirlo:** subir el archivo correcto. El original queda conservado y marcado como *Corregido*, enlazado a la nueva versión (que queda *Vigente*).
- **Anularlo:** el archivo se conserva, marcado como *Anulado*.

El motivo, la fecha y quién hizo el cambio quedan registrados (el motivo, cifrado) y son **información interna**: el personal de la clínica ve el historial de versiones y los motivos; **el paciente solo ve y descarga los adjuntos vigentes**, sin ver versiones anteriores ni motivos.

---

## 9. Receta médica

### 9.1 Escribir una receta

Al registrar una consulta (sección 5), agregar los medicamentos con su dosis, frecuencia y duración, e indicaciones adicionales.

### 9.2 Obtener la copia oficial en PDF

Desde el detalle de la consulta: **Ver receta**, se abre en una pestaña nueva con los datos del paciente y del médico, el diagnóstico, los medicamentos y la firma del médico (si está registrada).

⚠️ Solo el médico que escribió la receta obtiene esta copia oficial firmada. El administrador ve una copia marcada "Este documento no es válido" con marca de agua, solo de consulta; recepción y pacientes no tienen acceso directo a la copia oficial (el paciente sí puede recibirla por correo, ver 9.3).

### 9.3 Enviar la receta por correo

Desde el detalle de la consulta: **Enviar por correo**. Se puede enviar al correo registrado del paciente o escribir otro destinatario. Se adjunta la copia oficial firmada en PDF.

⚠️ Si el médico no tiene su firma registrada, **no se puede enviar** la receta por correo (sale marcada "SIN FIRMA"). Ver sección 11.

---

## 10. Documentos clínicos

Desde el detalle de una consulta, el médico que la registró puede emitir cuatro tipos de documentos clínicos legales, cada uno con su propio número (`CI-`, `INC-`, `REM-`, `ORD-`) y un código de verificación con QR:

### 10.1 Consentimiento informado
Describir el procedimiento, en qué consiste, riesgos, beneficios y alternativas. Incluye espacio para la firma del paciente y del médico.

### 10.2 Incapacidad médica
Indicar fecha de inicio, número de días (máximo 30 por documento; si se necesitan más, se emite como **prórroga**, un documento nuevo) y el origen: enfermedad general, accidente de trabajo, enfermedad laboral o accidente de tránsito. La fecha de finalización se calcula automáticamente.

### 10.3 Remisión
Especialidad o servicio al que se remite, prioridad, motivo y un resumen clínico.

### 10.4 Orden de exámenes
Lista de exámenes solicitados, prioridad e indicaciones.

### 10.5 Reglas comunes a todos los documentos clínicos

- Todos (salvo el consentimiento informado) incluyen automáticamente el diagnóstico CIE-10 de la consulta.
- Se guardan **cifrados** y, una vez emitidos, **no se pueden modificar ni eliminar**.
- Cada emisión queda en la auditoría.
- Solo el médico que lo emitió obtiene la **copia oficial** (con su firma) para imprimir o enviar. El paciente, el administrador y otros médicos tratantes ven una copia marcada **"SIN VALIDEZ"**.
- Se pueden enviar por correo al paciente desde la consulta, igual que la receta (sección 9.3), y están sujetos a la misma restricción: sin firma registrada, no se pueden enviar.

---

## 11. Mi firma profesional

La firma es una **imagen** (no una firma digital con certificado) que se imprime sobre la línea de firma de recetas y documentos clínicos.

1. Ir a **Configuración → Perfil profesional**.
2. Subir una imagen PNG, JPG o WebP de hasta 1 MB.
3. El sistema la reduce y la guarda como PNG en un almacenamiento privado, visible solo para el propio médico y el administrador.

⚠️ Sin firma registrada:
- La copia oficial de recetas y documentos sale marcada **"SIN FIRMA"**, con el aviso "Documento no válido por falta de firma del médico".
- La consulta muestra el mismo aviso.
- El listado de médicos marca "Sin firma registrada".
- **No se puede enviar** ninguna receta ni documento clínico por correo.

💡 También se puede subir la foto que aparece en la landing pública (si esa opción está activada) desde la misma pantalla de configuración, o la registra el administrador al crear/editar al médico.

---

## 12. Verificación pública de mis documentos

Cada receta, documento clínico e historia clínica emitidos llevan un código único y un QR que permiten verificarlos públicamente (sin iniciar sesión) en `/verificar`. Cualquiera que escanee el QR o escriba el código ve el tipo de documento, el número, la fecha, el médico (con su registro médico), si está firmado, y los datos clave para detectar alteraciones — nunca el diagnóstico completo ni el contenido clínico. Es una forma de que terceros (por ejemplo, una EPS o un empleador) confirmen que el documento realmente lo emitió la clínica.

---

## 13. Historia clínica en PDF

Desde la ficha del paciente: **Descargar historia clínica**, con la opción de elegir un rango de fechas o traer todo el historial. Incluye datos del paciente, antecedentes, consultas con signos vitales y recetas; las notas internas se incluyen porque el médico tiene permiso de leerlas. Cada descarga queda en la auditoría.

---

## 14. Turnos (mi fila de atención)

En **Turnos**, el médico gestiona únicamente **su propia fila** de pacientes del día: ve quién está en espera, quién ya fue llamado, y puede abrir directamente la consulta del paciente que acaba de llamar (o que recepción llamó) para registrar su atención.

---

## 15. Catálogo de medicamentos

El médico puede consultar y también **administrar** el catálogo de medicamentos (nombre, presentación, concentración) que alimenta el buscador al escribir una receta, en **Medicamentos**.

---

## 16. Mis horarios de atención

En **Médicos → Horarios**, el médico puede ver y, según la configuración de la clínica, ajustar su propio horario de atención por día de la semana, y la duración de sus citas (entre 10 y 60 minutos). Las horas fuera de ese horario aparecen rayadas en su agenda y no se pueden agendar.

---

## 17. Reportes

En **Reportes**, el médico ve únicamente **sus propias** citas y consultas (nunca ingresos/pagos de la clínica), filtrables por período. Exportable a Excel y PDF.

---

## 18. Lo que un médico no puede hacer

- Ver o atender pacientes que no le corresponden (solo los que atiende).
- Editar o eliminar una consulta ya registrada (ni propia ni ajena) — solo agregar notas aclaratorias a las propias.
- Agendar citas directamente (puede confirmarlas o cancelarlas, y reprogramar solo fecha/hora de las suyas).
- Reasignar una cita a otro médico.
- Registrar pagos ni ver los ingresos de la clínica.
- Crear usuarios, especialidades ni ver la auditoría.
- Administrar el catálogo CIE-10 ni el de EPS/aseguradoras.
- Ver recetas o documentos clínicos emitidos por otro médico como copia oficial (solo como copia "SIN VALIDEZ", igual que el administrador, si es también el médico tratante del paciente).

---

## 19. Mi configuración personal

En **Configuración**:
- **Perfil:** nombre y correo.
- **Perfil profesional:** firma y foto (sección 11).
- **Seguridad:** contraseña y verificación en dos pasos (2FA) — muy recomendable activarla.
- **Apariencia:** modo claro u oscuro.

---

## 20. Preguntas frecuentes

**Me equivoqué al escribir el diagnóstico de una consulta, ¿cómo lo corrijo?**
No se edita el registro original. Abre la consulta y agrega una **nota aclaratoria** (sección 7) indicando que aclara el "diagnóstico", el motivo y el texto correcto. La nota queda junto al registro original, con tu nombre y la fecha.

**Subí el archivo equivocado como adjunto, ¿lo puedo borrar?**
No se borran los adjuntos. Debes **corregirlo** (sección 8.2): subes el archivo correcto, indicas el motivo, y el archivo equivocado queda marcado como "Corregido" en el historial interno (el paciente ya no lo verá, solo verá el vigente).

**¿Por qué no puedo enviar una receta por correo?**
Lo más probable es que no tengas tu **firma profesional** registrada (sección 11). Sin firma, el sistema no permite enviar recetas ni documentos clínicos.

**¿Puedo emitir una incapacidad de 45 días?**
No en un solo documento (el máximo es 30 días). Emite el documento inicial con hasta 30 días y, si se necesita más tiempo, emite una **prórroga** como un nuevo documento de incapacidad.

**Un paciente insiste en que le reasigne su cita a otro médico, ¿puedo hacerlo?**
No; esa acción es exclusiva de administración y recepción. Indícale que lo solicite con ellos, o que lo pida él mismo desde su portal si la cita aún está en estado "Solicitada".
