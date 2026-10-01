# 7. Pruebas, calidad de código y convenciones de commit

## 7.1 Pruebas automatizadas

```bash
php artisan test --compact            # suite completa (Pest)
php artisan test --filter=Appointment # un módulo en particular
```

- El framework de pruebas es **Pest 3** sobre PHPUnit 11.
- Las pruebas usan **SQLite en memoria** (configurado en `phpunit.xml`) junto con `RefreshDatabase`, por lo que **no tocan la base de datos MySQL** de desarrollo.
- Hay una clase de prueba de `Feature` por módulo en `tests/Feature/` (citas, consultas, adjuntos, notas aclaratorias, documentos clínicos, pagos, usuarios, auditoría, catálogo CIE-10, turnos, reportes, privacidad, notificaciones push, exportaciones, filtros de tabla, firma del médico, horarios, verificación de documentos, registro de pacientes, autoservicio del paciente, formato de horas, página de inicio, 2FA, etc.).
- Las pruebas que renderizan páginas Inertia necesitan el **manifest de Vite**. Si fallan con `Unable to locate file in Vite manifest`, ejecutar `npm run build` antes.
- Crear una prueba nueva: `php artisan make:test --pest NombreTest` (agregar `--unit` para pruebas unitarias; la mayoría del proyecto son pruebas de `Feature`).

## 7.2 Calidad de código

```bash
vendor/bin/pint                       # estilo PHP (Laravel Pint)
npm run lint                          # ESLint (Vue/TypeScript)
npm run format                        # Prettier
npm run types:check                   # vue-tsc (chequeo de tipos)
```

- `vendor/bin/pint --dirty` aplica el estilo solo sobre los archivos modificados — es el que debe correrse antes de cada commit.
- No ejecutar `vendor/bin/pint --test` (solo reporta); usar `vendor/bin/pint` directamente, que corrige.

## 7.3 Checklist antes de hacer commit

1. `vendor/bin/pint --dirty`
2. `npm run lint`
3. `npm run format`
4. `php artisan test --compact` (o al menos `--filter` del módulo tocado)

## 7.4 Convenciones generales (resumen)

Ver el detalle completo en `01-arquitectura.md §1.5`. En síntesis:

- Código (clases, métodos, variables, columnas) en **inglés**; todo texto visible en **español**.
- Validación siempre vía Form Requests, que también autorizan delegando en la Policy.
- Serialización hacia el frontend siempre vía API Resources (`withoutWrapping()`, `->through()` en listados paginados).
- Formularios en Vue con `<Form>` de Inertia y acciones de Wayfinder; módulos de rutas importados con sufijo `Routes`.
- Enums con `label()` en español y el trait `HasEnumOptions` para alimentar selects.
- Archivos nuevos siempre generados con `php artisan make:*`.
- No se cambian las dependencias del proyecto, ni se crean carpetas base nuevas, sin acordarlo primero.
- No crear archivos de documentación adicionales si no se piden explícitamente (al trabajar sobre el código de la aplicación; esta carpeta `docs/` es la excepción solicitada para la documentación técnica y de usuario).

## 7.5 Herramientas de apoyo del proyecto (Laravel Boost)

El repositorio incluye **Laravel Boost**, un servidor MCP con herramientas pensadas para este proyecto (ver `AGENTS.md` en la raíz para el detalle completo):

- `search-docs`: documentación oficial de Laravel/Pest/Tailwind ya filtrada por las versiones exactas instaladas — se recomienda consultarla antes de implementar algo nuevo del ecosistema Laravel.
- `tinker` / `database-query`: ejecutar o consultar directamente sobre la base de datos de desarrollo.
- `list-artisan-commands`: ver los parámetros exactos de un comando Artisan antes de ejecutarlo.
- `browser-logs`: leer logs/errores recientes del navegador durante el desarrollo.
- `get-absolute-url`: generar URLs correctas (esquema, dominio, puerto) al compartir enlaces del proyecto.

Dos skills activas en el proyecto que conviene seguir al tocar esas áreas: `pest-testing` (pruebas) y `tailwindcss-development` (estilos).
