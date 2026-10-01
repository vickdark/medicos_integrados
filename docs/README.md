# Documentación técnica — Médicos Integrados

Esta carpeta contiene la documentación técnica detallada del sistema, pensada para desarrolladores, DevOps y cualquier persona que necesite mantener, extender o auditar el código. Complementa (no reemplaza) al `README.md` y `DEPLOY.md` de la raíz del repositorio, que siguen siendo la referencia rápida.

> Todo el código (clases, métodos, variables, columnas) está en **inglés**; los textos visibles para el usuario están en **español**. Esta documentación sigue esa misma convención: términos técnicos en inglés cuando corresponde, explicación en español.

## Índice

| # | Documento | Contenido |
|---|---|---|
| 1 | [01-arquitectura.md](./01-arquitectura.md) | Stack tecnológico, flujo de una petición, estructura de carpetas, convenciones de código, props compartidos Inertia |
| 2 | [02-modelo-de-datos.md](./02-modelo-de-datos.md) | Entidades, relaciones, migraciones, enums, estados de citas y pagos, campos cifrados |
| 3 | [03-modulos-y-rutas.md](./03-modulos-y-rutas.md) | Detalle módulo por módulo (pacientes, citas, consultas, documentos clínicos, turnos, reportes, etc.) con sus rutas, controladores y reglas de negocio |
| 4 | [04-seguridad-y-privacidad.md](./04-seguridad-y-privacidad.md) | Policies, cifrado, auditoría, verificación QR, Ley 1581, sesión por inactividad |
| 5 | [05-instalacion-y-entorno.md](./05-instalacion-y-entorno.md) | Requisitos, instalación local paso a paso, variables de entorno, usuarios de prueba |
| 6 | [06-tareas-programadas-e-integraciones.md](./06-tareas-programadas-e-integraciones.md) | Colas, correo, notificaciones push, recordatorios, ETL del catálogo CIE-10, exportaciones Excel/PDF |
| 7 | [07-pruebas-calidad-convenciones.md](./07-pruebas-calidad-convenciones.md) | Pest, Pint, ESLint, Prettier, vue-tsc, checklist antes de hacer commit |
| 8 | [../DEPLOY.md](../DEPLOY.md) | Guía de despliegue en producción (VPS, Nginx, Supervisor, checklist de seguridad) |

## Cómo usar esta documentación

- Si vas a **tocar código por primera vez**: lee en orden 1 → 2 → 3.
- Si vas a **investigar un bug de permisos o de datos clínicos**: ve directo a 3 y 4.
- Si vas a **instalar el proyecto en tu máquina**: ve a 5.
- Si vas a **desplegar o mantener producción**: ve a `DEPLOY.md` en la raíz.
- Para la documentación **orientada al usuario final** (cómo usar la aplicación según el rol), ve a la carpeta [`docs/usuarios/`](./usuarios/00-indice.md).

## Resumen del sistema

**Médicos Integrados** es un sistema integral de gestión para un centro médico: historias clínicas, citas, pagos, turnos de sala de espera, documentos clínicos legales (recetas, incapacidades, remisiones, órdenes de examen) con verificación pública por código QR, y un portal de autoservicio para el paciente — todo detrás de un único inicio de sesión que adapta el menú y los datos visibles según el rol del usuario.

- **Backend:** PHP 8.2, Laravel 12, Laravel Fortify (autenticación).
- **Frontend:** Vue 3 + TypeScript, Inertia.js 2 (SPA sin API REST separada), Tailwind CSS 4, componentes shadcn-vue.
- **Base de datos:** MySQL 8.
- **Rutas tipadas:** Laravel Wayfinder genera automáticamente `resources/js/routes` y `resources/js/actions` a partir de las rutas de Laravel, de modo que el frontend llama a los controladores con funciones TypeScript en vez de URLs escritas a mano.
