# Manuales de usuario — Médicos Integrados

Estos manuales explican, paso a paso y en lenguaje sencillo, cómo usar el sistema **Médicos Integrados** según el rol de cada persona. Están pensados para importarse directamente a Notion (una página por manual) y entregarse al personal de la clínica y a los pacientes.

Todos los roles inician sesión desde la **misma pantalla** y con la **misma dirección web**; no hay accesos distintos por tipo de usuario. Lo que cada persona ve y puede hacer depende únicamente de su rol, asignado por el administrador.

## Manuales disponibles

| Rol | Manual | Para quién es |
|---|---|---|
| 🛠️ Administrador | [manual-administrador.md](./manual-administrador.md) | Dueños o gerentes de la clínica, encargados de configurar el sistema, crear usuarios y supervisar todo |
| 💁 Recepción | [manual-recepcion.md](./manual-recepcion.md) | Personal de recepción/secretaría: agenda, pagos, turnos, registro de pacientes |
| 🩺 Médico | [manual-medico.md](./manual-medico.md) | Médicos que atienden pacientes, registran consultas, emiten recetas y documentos clínicos |
| 🧑 Paciente | [manual-paciente.md](./manual-paciente.md) | Pacientes que usan el portal para ver su historial, pedir citas y pagos |

## Convenciones usadas en estos manuales

- **Menú →** indica la ruta de navegación dentro del sistema. Por ejemplo, *Configuración → Seguridad* significa: abrir el menú **Configuración** y luego la opción **Seguridad**.
- Los nombres de botones aparecen en **negrita**.
- 🔒 marca una acción o una pantalla restringida a ciertos roles.
- 💡 marca un consejo o una aclaración importante.
- ⚠️ marca una advertencia (algo que no se puede deshacer, o un requisito legal/de seguridad).

## Datos de acceso de prueba (solo en el entorno de pruebas/demo)

| Rol | Correo | Contraseña |
|---|---|---|
| Administrador | `admin@medicos.test` | `password` |
| Recepción | `recepcion@medicos.test` | `password` |
| Médico | `medico@medicos.test` | `password` |
| Paciente | `paciente@medicos.test` | `password` |

⚠️ Estas cuentas existen solo en el entorno de desarrollo/demostración. En el sistema real de la clínica, cada persona recibe su propio correo y una contraseña temporal por correo electrónico la primera vez.
