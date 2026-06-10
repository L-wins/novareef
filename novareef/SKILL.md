---
name: novareef
description: "Convenciones del proyecto NovaReef, una plataforma SaaS multi-tenant para gestionar colegios de árbitros de fútbol. Usa este skill SIEMPRE que el usuario mencione NovaReef, trabaje en archivos del proyecto NovaReef, o pida crear/editar migrations, models, controllers, views, rutas, seeders o tests en este contexto — incluso si no nombra el proyecto explícitamente pero usa términos como colegio, árbitro, designación, terna, designador, M01-M08, o subdominios *.novareef.com. También úsalo cuando se discutan decisiones de arquitectura sobre tenancy, guards, o el dominio de arbitraje futbolístico colombiano (FCF, Primera C, FIFA badge). Sin este skill es muy fácil violar reglas críticas — nombres en camelCase español, dos guards independientes (web y admin), aislamiento por idColegio, y el módulo M04 Designaciones que es el corazón del sistema."
---

# NovaReef — Convenciones del Proyecto

NovaReef es una plataforma SaaS multi-tenant para que **colegios de árbitros** de fútbol gestionen su operación: árbitros, torneos, designaciones, finanzas, sanciones y reportes.

## Stack (no negociable)

| Capa | Tecnología |
|---|---|
| Backend | **Laravel 11** + PHP 8.2+ |
| Vistas | **Blade** + CSS custom (panel usuario) |
| CSS | **CSS puro con variables custom** (panel usuario) · Tailwind v4 instalado como base pero las vistas **no usan clases Tailwind** |
| JS | **Vanilla JS** modular por sección · Vue 3 instalado pero aún no en uso |
| DB | **MySQL 8+** (InnoDB, utf8mb4_unicode_ci) |
| Multi-tenancy | **Stancl/Tenancy v3** instalado · aislamiento actual por `idColegio` manual · subdominio y Global Scopes planificados (ver `references/tenancy.md`) |
| Storage | Local (`storage/app/public`) · **🚧 Cloudflare R2 planificado** |
| Email | **Resend** (`resend/resend-laravel ^1.4`) |
| WebSockets | **Laravel Reverb** (`laravel/reverb ^1.10`) + Laravel Echo + Pusher JS |
| Errores | **Sentry** (`sentry/sentry-laravel ^4.25`) |
| Auth avanzada | **Spatie Permission** (`spatie/laravel-permission ^6.25`) + **Google 2FA** (admin) |
| Hosting | **Hostinger** |

**Regla:** nunca propongas cambiar el stack ni añadir dependencias sin avisar primero. Si una librería nueva resuelve el problema, plantéalo como sugerencia, no como hecho consumado.

## Arquitectura en una frase

> Una sola base de datos compartida. Cada colegio tiene un `idColegio` que se usa para aislar sus datos en cada query. Hay dos guards: `web` para usuarios de colegios y `admin` para el superadmin de NovaReef. Stancl/Tenancy está instalado y la infraestructura de subdominios existe, pero la identificación por subdominio aún no está activa — ver `references/tenancy.md`.

## Reglas de oro (las que más se rompen)

1. **Columnas de dominio en camelCase español**, columnas técnicas de Laravel en snake_case inglés.
   ✅ `idArbitro`, `nombreColegio`, `fechaNacimiento`, `created_at`, `updated_at`, `deleted_at`
   ❌ `id_arbitro`, `nombre_colegio`, `fecha_nacimiento`
2. **Todo controller que accede a datos de tenant DEBE filtrar por `Auth::user()->idColegio`**. Olvidarlo = fuga de datos entre colegios = bug crítico. Cuando se active Stancl Global Scopes esto será automático — mientras tanto es manual y obligatorio.
3. **No mezclar guards.** Una ruta es de `web` o de `admin`, nunca ambos. El superadmin nunca debe poder loguearse como colegio sin un mecanismo explícito de impersonación.
4. **No hay registro público.** Los usuarios se crean desde dentro (superadmin crea colegios; ejecutivo crea usuarios de su colegio). Cualquier ruta tipo `/register` está mal.
5. **Soft deletes** en `usuarios`, `arbitros`, `torneos`. Nunca `forceDelete` sin confirmación explícita del usuario. `partidos` y `designaciones` no tienen soft delete.
6. **Dark theme permanente** en todas las vistas — implementado via CSS custom variables (`--bg-base: #020617`), no clases Tailwind `dark:`. El `<html>` no tiene `class="dark"`. Si haces una vista nueva sin usar las variables de `app.css`, está mal.
7. **Idioma de UI: español de Colombia.** Mensajes de error, validaciones, labels — todo en español.
8. **ENUMs en snake_case minúscula** — `'activo'`, `'proceso_ingreso'`, `'en_curso'`, nunca `'Activo'` ni `'EnCurso'`.
9. **`declare(strict_types=1)`** en todos los archivos PHP.

## Módulos (M01–M08)

| Código | Nombre | Estado real |
|---|---|---|
| M01 | Colegios | ✅ Implementado (CRUD completo, guard admin + web) |
| M02 | Árbitros | ✅ Implementado (CRUD, foto, documentos, estados, disponibilidad) |
| M03 | Torneos | ✅ Implementado (torneos, divisiones, sedes, tarifas, reglamentos, partidos) |
| **M04** | **Designaciones** | 🚧 En desarrollo activo — Bloque 3 en curso |
| M05 | Académico | ⬜ Solo rutas placeholder (redirect a dashboard) |
| M06 | Finanzas | ⬜ Solo rutas placeholder (redirect a dashboard) |
| M07 | Sanciones | ⬜ Solo rutas placeholder (redirect a dashboard) |
| M08 | Superadmin | 🚧 Panel admin parcial (login, 2FA, dashboard) |

Para detalles de cada módulo y reglas de negocio, ver `references/modulos.md`.

## Cuándo consultar las referencias

Antes de escribir código que toque cualquiera de estos temas, **lee la referencia correspondiente**. No improvises desde memoria — las convenciones son específicas y se rompen fácil.

| Si vas a... | Lee primero |
|---|---|
| Crear/modificar migrations, models, ENUMs, índices | `references/database.md` |
| Crear un modelo nuevo, una ruta protegida por colegio, un controller que filtra por idColegio | `references/tenancy.md` |
| Tocar auth, middleware, login, rutas protegidas, Spatie roles/permisos | `references/guards-auth.md` |
| Trabajar en cualquier módulo M01–M08 (especialmente M04) | `references/modulos.md` |
| Crear vistas, formularios, tablas, JS, CSS | `references/frontend.md` |

## Flujo de trabajo recomendado

Cuando Luis pide algo tipo *"créame el controller de torneos"*:

1. **Confirmar contexto**: ¿en qué fase está? ¿qué ya existe en el repo?
2. **Leer la referencia relevante** antes de escribir.
3. **Proponer** la estructura (nombres de archivos, métodos, rutas) antes de escribir bloques grandes de código. Luis prefiere ver el plan antes que un muro de código.
4. **Escribir** en pasos pequeños, explicando qué hace cada bloque.
5. **Verificar las reglas de oro** antes de cerrar (camelCase español, filtro `idColegio`, guard correcto, soft deletes donde aplica, variables CSS dark theme).

## Estilo de comunicación con Luis

- Español, directo, breve. Nada de párrafos enormes explicando lo obvio.
- Si Luis dice "no entiendo X", explicar X con un ejemplo concreto, no con teoría.
- Si una decisión tiene trade-offs reales, mostrarle las opciones, no decidir por él.
- Output visual: listas, tablas, bloques de código bien formateados. No prosa densa.
- Nunca asumir que "ya sabes esto" sobre OOP, design patterns o Laravel avanzado — él lo está aprendiendo.
