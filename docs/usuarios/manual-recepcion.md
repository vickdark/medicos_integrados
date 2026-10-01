# Manual de Recepción

El rol **Recepción** gestiona la operación diaria de la clínica: pacientes, agenda de citas, turnos de sala de espera y pagos. No tiene acceso a información clínica (antecedentes, consultas, diagnósticos) ni a la configuración del sistema.

## Índice

1. [Iniciar sesión](#1-iniciar-sesión)
2. [Panel de inicio](#2-panel-de-inicio)
3. [Pacientes](#3-pacientes)
4. [Citas y calendario](#4-citas-y-calendario)
5. [Turnos y pantalla de sala de espera](#5-turnos-y-pantalla-de-sala-de-espera)
6. [Pagos y facturación](#6-pagos-y-facturación)
7. [Reportes](#7-reportes)
8. [Lo que Recepción no puede ver ni hacer](#8-lo-que-recepción-no-puede-ver-ni-hacer)
9. [Mi configuración personal](#9-mi-configuración-personal)
10. [Preguntas frecuentes](#10-preguntas-frecuentes)

---

## 1. Iniciar sesión

1. Abrir la dirección web de la clínica.
2. Hacer clic en **Iniciar sesión** y escribir el correo y la contraseña entregados por el administrador.
3. Si es el primer ingreso, el sistema pedirá definir una contraseña nueva antes de continuar (la que llegó por correo es temporal).

---

## 2. Panel de inicio

Al entrar se abre el **Panel de inicio**, con un resumen operativo del día (citas, turnos, pagos pendientes, según cómo esté configurado para este rol).

---

## 3. Pacientes

### 3.1 Registrar un paciente nuevo

1. Ir a **Pacientes → Nuevo paciente**.
2. Completar tipo y número de documento, nombre, fecha de nacimiento, sexo, teléfono, dirección, contacto de emergencia, EPS/aseguradora y tipo de afiliación.
3. Guardar.

💡 Si la persona ya se había registrado por su cuenta en la página web con el mismo correo, la ficha se vincula automáticamente a esa cuenta en vez de duplicarse (y viceversa: si luego esa persona se registra con el mismo correo, se vincula a la ficha creada aquí).

### 3.2 Editar los datos de un paciente

Desde el listado de **Pacientes**, abrir el registro y actualizar los datos de contacto o personales que falten.

⚠️ Una vez que un médico atendió al paciente por primera vez, los **datos de identificación básica** (documento, fecha de nacimiento, sexo, grupo sanguíneo) quedan bloqueados para evitar alterar el expediente; solo pueden seguir editándose teléfono, dirección y contacto de emergencia.

### 3.3 Buscar pacientes

El listado permite buscar por nombre, documento o correo.

⚠️ Recepción **no ve** antecedentes clínicos (alergias, enfermedades, antecedentes) ni el historial de consultas del paciente — esa información es exclusiva del personal médico y del administrador.

---

## 4. Citas y calendario

### 4.1 Ver el calendario

En **Citas** hay dos vistas intercambiables:
- **Calendario:** Día, Semana y Mes (en el teléfono: mini calendario mensual + vista Día/Agenda).
- **Lista:** con buscador (paciente, documento, médico, motivo), filtros (estado, rango de fechas) y exportación a Excel/PDF.

### 4.2 Agendar una cita nueva

1. En **Citas → Nueva cita**, elegir paciente y médico.
2. En el calendario semanal que aparece, elegir el horario: se distinguen los espacios libres, los ocupados y los que están fuera del horario de atención del médico.
3. Completar motivo y notas.
4. Guardar: la cita queda **confirmada** directamente.

💡 Cada médico tiene su propia duración de cita (entre 10 y 60 minutos); el sistema impide agendar citas que se solapen.

### 4.3 Confirmar o cancelar una cita

Desde la fila de la cita en el listado, usar el botón de cambio de estado correspondiente.

### 4.4 Reprogramar una cita (incluye cambiar de médico)

1. Abrir la cita y elegir **Reprogramar**.
2. Elegir el nuevo horario (se puede mover incluso dentro del mismo día si hay espacio libre).
3. Recepción también puede **cambiar el médico** asignado a la cita: el sistema valida la disponibilidad y los choques contra el nuevo médico.
4. Se pueden editar motivo y notas.

Efectos automáticos:
- La cita conserva su estado.
- Si tiene un cobro **pendiente**, se ajusta a la tarifa del nuevo médico (uno ya pagado no se toca).
- Se avisa por correo al paciente, al médico anterior y al nuevo.

### 4.5 Pago rápido desde una cita

El botón de pago en la fila de la cita precarga paciente, cita, concepto y monto (según la tarifa del médico), para no tener que volver a escribirlos.

---

## 5. Turnos y pantalla de sala de espera

El módulo **Turnos** organiza el orden de atención del día.

### 5.1 Generar un turno

1. Ir a **Turnos**.
2. Generar un turno nuevo para: un paciente que llega **sin cita previa**, o un paciente que tiene **cita para hoy** y acaba de llegar a la clínica.
3. El turno queda en espera, asignado al médico correspondiente.

### 5.2 Gestionar la fila

Desde **Turnos** se puede:
- **Llamar** al siguiente turno en espera.
- **Marcar como atendido** cuando el médico termina.
- **Devolver a la fila** (si el paciente llamado no se presentó, por ejemplo).
- **Cancelar** un turno.

### 5.3 Pantalla pública de sala de espera

La ruta `/pantalla-de-turnos` está pensada para abrirse en un monitor o televisor de la sala de espera. Se actualiza sola cada 5 segundos y muestra únicamente el **código del turno y el médico** — nunca el nombre del paciente, para proteger su privacidad.

---

## 6. Pagos y facturación

### 6.1 Registrar un pago

1. Ir a **Pagos → Nuevo pago** (o usar el pago rápido desde una cita, sección 4.5).
2. Elegir paciente, concepto, monto y método de pago (efectivo, tarjeta, transferencia u otro).
3. Guardar: queda **pendiente** salvo que se marque como pagado en el mismo paso.

### 6.2 Marcar un pago como pagado

Desde el listado de **Pagos**, abrir el diálogo en la fila del pago pendiente y confirmar.

### 6.3 Anular un pago

Se puede anular un pago (pendiente o pagado) indicando el motivo. También se anula automáticamente el cobro pendiente al cancelar la cita asociada.

### 6.4 Descargar la factura

Cada pago **pagado** tiene una factura en PDF (numerada `F-000123`), descargable desde el listado o el detalle del pago. Los pagos pendientes no tienen factura todavía.

### 6.5 Buscar, filtrar y exportar

Buscar por paciente, concepto o referencia; filtrar por estado y rango de fechas (los totales se recalculan según el filtro aplicado); exportar a Excel o PDF.

---

## 7. Reportes

En **Reportes** se consultan citas, ingresos y consultas, agrupables por médico o especialidad y filtrables por período. Recepción ve el detalle completo, igual que el administrador (incluye ingresos). Exportable a Excel y PDF.

---

## 8. Lo que Recepción no puede ver ni hacer

Para proteger la confidencialidad de la información clínica, Recepción **no tiene acceso** a:
- Antecedentes clínicos del paciente ni historial de consultas.
- Registrar consultas, recetas o documentos clínicos.
- Subir, descargar o anular archivos adjuntos clínicos.
- El módulo de Usuarios (crear cuentas o asignar roles).
- Médicos, especialidades y horarios (solo puede **ver** los horarios para agendar, no crearlos ni editarlos).
- El catálogo de medicamentos ni el de EPS/aseguradoras.
- La Auditoría.
- La Configuración del sistema (color, landing, política de datos).

---

## 9. Mi configuración personal

En el menú **Configuración**:
- **Perfil:** nombre y correo.
- **Seguridad:** cambio de contraseña y activación opcional de la verificación en dos pasos (2FA).
- **Apariencia:** modo claro u oscuro.

---

## 10. Preguntas frecuentes

**¿Puedo ver por qué un paciente tiene una cita (el motivo clínico)?**
Puedes ver el **motivo** general escrito al agendar (p. ej. "control", "dolor de cabeza"), pero no los detalles clínicos de la consulta en sí (síntomas, diagnóstico, tratamiento): eso solo lo ve el médico tratante y el administrador.

**Un paciente quiere cambiar de médico una cita ya confirmada, ¿puedo hacerlo?**
Sí. Al reprogramar una cita (sección 4.4), Recepción puede cambiar tanto el horario como el médico asignado.

**¿Qué hago si un paciente llega sin cita?**
Generar un turno nuevo desde el módulo **Turnos** (sección 5.1); no hace falta agendar una cita formal para atenderlo el mismo día si el médico tiene espacio.

**El pago de una cita ya se registró mal, ¿lo puedo borrar?**
No se borran los pagos; se **anulan** indicando el motivo (sección 6.3), y ese motivo queda guardado para trazabilidad.
