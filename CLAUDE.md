# NovaReef — Guía del proyecto

Sistema SaaS de gestión para colegios de árbitros de fútbol en Colombia.
Laravel 11 · PHP 8.2 · MySQL · Vite · CSS puro (sin Tailwind en el panel admin)

---

## Stack y versiones

| Dependencia | Versión |
|---|---|
| PHP | ^8.2 |
| Laravel | ^11.0 |
| Spatie Permission | ^6.25 |
| Google 2FA (PragmaRX) | ^3.0 (pragmarx/google2fa-laravel) |
| BaconQrCode | ^3.1 |
| Resend Laravel | ^1.4 |
| Sentry | ^4.25 |
| Tenancy (stancl) | ^3.10 |
| Choices.js | ^11.2.3 |
| Flatpickr | ^4.6.13 |
| SweetAlert2 | ^11.x |

---

## Comandos de desarrollo

```bash
# Iniciar servidor PHP (XAMPP)
# Apache en http://localhost/novareef/public

# Compilar assets (Vite)
npm run dev          # desarrollo con HMR
npm run build        # producción

# Artisan frecuentes
php artisan migrate
php artisan db:seed
php artisan db:seed --class=RolesPermisosSeeder
php artisan novareef:asignar-roles        # asignar roles Spatie a usuarios existentes
php artisan novareef:limpiar-fantasmas    # eliminar usuarios con rolUsuario=superadmin

# Caché
php artisan config:clear
php artisan route:clear
php artisan view:clear
php artisan permission:cache-reset
```

---

## Arquitectura multi-guard

### Guard `web` — Usuarios de colegios

- Tabla: `usuarios`
- Modelo: `App\Models\User`
- Provider: `App\Auth\CustomUserProvider` (sobrescribe `validateCredentials` usando `getAuthPasswordName()`)
- Columnas propias: `emailUsuario`, `passwordUsuario`, `nombreUsuario`, `rolUsuario`, `idColegio`
- Spatie Permission: **SÍ** (`HasRoles` trait, `guard_name = 'web'`)

### Guard `admin` — Superadministradores

- Tabla: `admins`
- Modelo: `App\Models\Admin`
- Provider: estándar Eloquent
- Columnas: `email`, `password`, `nombre`, `two_factor_enabled`, `google2fa_secret`, `ultimo_acceso`, `activo`
- Spatie Permission: **NO** (excluido en `config/permission.php` con `'guards' => ['web']`)

---

## Estructura de rutas

### `routes/web.php` — Usuarios (guard web)

| URI | Middleware | Descripción |
|---|---|---|
| `/` | — | Welcome page |
| `/login` | guest | Login usuarios |
| `/dashboard` | auth, verificar.colegio | Dashboard colegio |
| `/arbitros/*` | auth, verificar.colegio, permission:ver-arbitros | CRUD árbitros |
| `/colegios/*` | auth, verificar.colegio, solo.superadmin | CRUD colegios |
| `/torneos/*` | auth, verificar.colegio, permission:ver-torneos | CRUD torneos |
| `/torneos/{id}/partidos/*` | auth, verificar.colegio, permission:ver-torneos | CRUD partidos |
| `/designaciones/*` | auth, verificar.colegio, permission:ver-designaciones | Placeholder |
| `/finanzas/*` | auth, verificar.colegio, permission:ver-finanzas | Placeholder |
| `/academico/*` | auth, verificar.colegio, permission:ver-academico | Placeholder |
| `/sanciones/*` | auth, verificar.colegio, permission:ver-sanciones | Placeholder |

### Rutas especiales de árbitros

| URI | Descripción |
|---|---|
| GET `/arbitros/mi-perfil` | Perfil del árbitro autenticado |
| PUT `/arbitros/mi-perfil` | Actualizar perfil del árbitro |
| GET `/arbitros/completar-perfil` | Wizard de completar perfil (primer acceso) |
| POST `/arbitros/guardar-perfil` | Guardar datos del wizard |
| POST `/arbitros/{id}/foto` | Subir foto de perfil |
| DELETE `/arbitros/{id}/foto` | Eliminar foto de perfil |

### `routes/admin.php` — Superadmin (guard admin)

Prefix: `novareef-panel` (config `admin.prefix`)

| Ruta | Descripción |
|---|---|
| GET `/login` | Vista login admin |
| POST `/login` | Autenticar admin |
| GET `/2fa` | Vista verificación 2FA |
| POST `/2fa` | Verificar código TOTP |
| POST `/logout` | Cerrar sesión |
| GET `/` | Dashboard |
| GET `/colegios` | Placeholder colegios |
| GET `/planes` | Placeholder planes |
| GET `/usuarios` | Placeholder usuarios |
| GET `/logs` | Placeholder logs |
| GET `/2fa/configurar` | Config 2FA |
| POST `/2fa/activar` | Activar 2FA |
| POST `/2fa/desactivar` | Desactivar 2FA |

---

## Middleware registrados

En `bootstrap/app.php`:

| Alias | Clase |
|---|---|
| `admin.auth` | `AdminAuth` |
| `verificar.colegio` | `VerificarEstadoColegio` |
| `solo.superadmin` | `SoloSuperAdmin` |
| Web prepend | `BlockResendWebhook` |
| Web append | `VerificarCambioContrasena` |

---

## Modelos principales

### `User` (`usuarios`)
- Guard: `web`
- Traits: `HasFactory`, `HasRoles`, `Notifiable`, `SoftDeletes`
- Auth columns: `emailUsuario` (email), `passwordUsuario` (password)
- Clave foránea: `idColegio` → `colegios`
- `rolUsuario`: string local (arbitro, ejecutivo, tesorero, designador, sanciones, tecnico, superadmin)

### `Colegio` (`colegios`)
- Columnas: `nombreColegio`, `emailColegio`, `codigoColegio`, `paisColegio`, `estadoColegio`
- `estadoColegio`: activo | suspendido | inactivo
- Relación: `hasMany(Suscripcion)`

### `Arbitro` (`arbitros`)
- Traits: `SoftDeletes`
- Clave: `idColegio`
- Campos nuevos: `fotoPerfil` (path storage), `estadoArbitro` (FK → `estados_arbitro`)
- Relaciones: `hasMany(DocumentoArbitro)`, `belongsTo(EstadoArbitro)`, `hasMany(HistorialEstadoArbitro)`
- Accessors: `porcentajePerfil`, `colorPerfil`

### `Torneo` (`torneos`)
- Traits: `SoftDeletes`
- Campos clave: `idColegio`, `idOrganizador`, `tipoTorneo`, `estadoTorneo`, `temporada`, `modalidadPago`
- Relaciones: `divisiones()`, `sedes()`, `partidos()`, `reglamentos()`, `reglamentoActual()`

### `SedeTorneo` (`sedes_torneo`)
- Campos: `nombreSede`, `municipio`, `urlMaps` (NO `barrio` — fue migrado)

### `ReglamentoTorneo` (`reglamentos_torneo`)
- Sin `updated_at`, solo `created_at` (gestionado en `booted()`)
- `esActual` (bool): solo uno activo por torneo
- Accessor: `tamanoLegibleAttribute()`

### `Suscripcion` (`suscripciones`)
- `estado`: trial | activo | vencido | cancelado
- Claves: `idColegio`, `idPlan`

### `Admin` (`admins`)
- Guard: `admin`
- 2FA: `two_factor_enabled` (bool), `google2fa_secret` (en `$hidden`)
- Acceder al secret: `$admin->getRawOriginal('google2fa_secret')`

---

## Spatie Permission — configuración

`config/permission.php`:
```php
'guards' => ['web'],  // admin guard excluido
```

**14 permisos**: ver/crear/editar-arbitros, ver/crear/editar-torneos, ver/crear-designaciones, ver/crear-finanzas, ver/crear-academico, ver/crear-sanciones

**6 roles**:
| Rol | Permisos |
|---|---|
| `ejecutivo` | todos (14) |
| `tesorero` | ver/crear-finanzas, ver-arbitros, ver-torneos, ver-designaciones |
| `designador` | ver/crear-designaciones, ver-arbitros, ver-torneos |
| `sanciones` | ver/crear-sanciones, ver-arbitros |
| `tecnico` | ver/crear-academico, ver-arbitros |
| `arbitro` | ver-arbitros, ver-torneos, ver-designaciones |

---

## Frontend — librerías y patrones

### Font Awesome 7.x
- Instalado via npm (`@fortawesome/fontawesome-free ^7.2.0`)
- Webfonts copiados a `public/webfonts/`
- `@font-face` manual en `resources/css/vendor/fontawesome-fonts.css` (evita rutas absolutas Windows de Vite)
- **NO usar CDN** para iconos — todo via npm + Vite

### Choices.js v11 (selects con búsqueda)
- Usar atributo `data-nova-select` en el `<select>` para activar
- Agregar `data-searchable="true"` cuando la lista tiene >10 items
- Agregar `data-placeholder="Texto..."` para el placeholder
- **Tematización**: usar `!important` en los overrides CSS (v11 usa CSS custom properties internamente que necesitan ser forzadas)
- Inicialización global en `app.js` → `window.initNovaSelects()`, llamar también al abrir modales con contenido dinámico
- El selector `estadoNuevo` en modales por-partido NO usa Choices (tiene listener nativo que muestra/oculta campos de resultado)

### Flatpickr v4 (date picker)
- Usar `type="text"` + atributo `data-nova-date` (NO `type="date"`)
- `dateFormat: 'Y-m-d'` (backend Laravel-compatible) + `altFormat: 'd/m/Y'` (visible usuario)
- El input original se oculta con `.flatpickr-input.form-input { display: none !important; }`
- **Tematización**: usar `!important` en todos los overrides CSS

### SweetAlert2
- `window.novaAlert.success(mensaje)` — toast verde, 3s, sin botón
- `window.novaAlert.error(mensaje)` — modal error rojo
- `window.novaAlert.confirm({ titulo, texto, icono, confirmColor, confirmarTexto, iconColor })` — confirmación destructiva
- Flash messages del servidor se disparan automáticamente desde `layouts/app.blade.php`

### Auto-filter (formularios de filtros GET)
- Módulo global: `resources/js/shared/auto-filter.js` (importado en `app.js` y `admin/admin.js`)
- Marcar el `<form method="GET">` con `data-auto-filter` — NUNCA en formularios de acciones (POST/PUT/DELETE)
- select / `data-nova-date` / checkbox / radio → submit al cambiar; inputs de texto → debounce 450 ms
- Botón "Filtrar" con `data-auto-filter-hide`: se oculta con JS activo (queda como fallback sin JS)
- Campos vacíos se excluyen del querystring; el foco del buscador se restaura tras recargar
- Backend sin cambios: el controlador lee `Request` y las listas paginadas usan `->withQueryString()` (obligatorio para no perder filtros al paginar)

### Sistema de temas (light / dark / system)
- **Tokens**: `resources/css/tokens.css` — única fuente de verdad. Dark = default (`:root`), light via `:root[data-theme="light"]`
- **Regla**: componentes consumen tokens `--nv-*` (o los alias legacy `--bg-*`/`--text-*`/`--c-*` ya mapeados) — **NUNCA hex directos** en CSS de componentes
- **BD**: `usuarios.temaPreferencia` VARCHAR(10) — valores `light|dark|system` (default `dark`)
- **Anti-FOUC**: `layouts/partials/theme-boot.blade.php` — único JS inline permitido del proyecto (render-blocking, resuelve `system` → `data-theme` antes del primer paint)
- **Lógica**: `resources/js/shared/theme.js` — cambia `data-theme`, persiste via `PATCH /preferencias/tema`, sigue `prefers-color-scheme` en vivo cuando la preferencia es `system`
- **Selector**: botones `[data-theme-set="light|dark|system"]` en el navbar del layout usuario
- El panel admin también tiene toggle completo (`admins.temaPreferencia`, ruta propia bajo guard `admin`, `AdminPreferenciaController`) — no reutiliza el controlador del guard web

### Separación de capas (regla dura)
- No mezclar JS/CSS inline en Blade: JS va en `resources/js/` (módulos), CSS en `resources/css/`
- Blade solo puede tender "puentes de datos" al JS: atributos `data-*` o `window.x = "{{ ... }}"` — datos, no lógica
- Única excepción documentada: `theme-boot` (ver arriba)

---

## Panel Admin — assets

- CSS: `resources/css/admin/admin.css`
- JS: `resources/js/admin/admin.js`
- Layout: `resources/views/admin/layouts/app.blade.php`

### CSS variables principales
```css
--primary: #4f8ef7
--bg-navbar: #1a1f2e
--bg-body: #0f1117
--bg-card: #131927
--text-bright: #e2e8f0
--text: #8892a4
--text-muted: #3d4558
--border-color: rgba(255,255,255,0.06)
```

### JS (admin.js)
- Inicializa Feather Icons con `feather.replace({ 'stroke-width': 1.8, width: 18, height: 18 })`
- Lógica OTP: 6 inputs individuales `.otp-digit`, auto-avance, backspace, paste, auto-submit al completar, sincroniza con `#otp-code` hidden input

### Sidebar navbar
- `.navbar`: fixed 72px → 230px en hover
- `.navbar__link.active`: badge izquierdo + efecto "gooey" via `::after`
- Labels: `opacity:0 → 1` con `translateX` al expandir

---

## Panel usuario — CSS palette (app.css)

```css
--bg-base:      #020617
--bg-surface:   #0f172a
--bg-card:      #1e293b
--accent:       #4f8ef7   /* azul primario — NO cambiar a verde */
--accent-light: #7aa8f9
--text-primary: #f8fafc
--text-secondary: #94a3b8
--text-muted:   #475569
```

---

## 2FA Admin

### Activación
1. `Admin2FAController::show()` genera secret con `Google2FA::generateSecretKey()` si no existe
2. Genera QR SVG directo con `BaconQrCode\Writer` + `SvgImageBackEnd` (NO usar `getQRCodeInline()`)
3. Vista muestra QR + inputs OTP
4. `enable()` verifica con `Google2FA::verifyKey()` y setea `two_factor_enabled = true`

### Login con 2FA
1. `AdminLoginController::login()` detecta `two_factor_enabled`
2. Guarda `admin_2fa_pending` en sesión
3. Redirige a `admin.2fa` → formulario 6 OTP inputs
4. `verify2fa()` valida y hace login completo

---

## Convenciones del proyecto

- **`declare(strict_types=1)`** en todos los archivos PHP
- Columnas en camelCase español: `nombreColegio`, `emailUsuario`, `idColegio`
- PKs propias: `bigIncrements('idXxx')` — **NO usar** `$table->id()`
- Rutas nombradas con prefijo de módulo: `arbitros.*`, `torneos.*`, `partidos.*`, `colegios.*`
- No Tailwind en el panel admin — CSS puro con variables
- Feather Icons vía CDN con `defer` (antes del `@vite()` para garantizar orden de ejecución)
- Vistas admin: `resources/views/admin/` — siempre extienden `admin.layouts.app`
- Vistas usuario: `resources/views/` — extienden `layouts.app`
- Multi-tenant: siempre filtrar por `Auth::user()->idColegio` en queries, `abort_unless` checks en controladores
- Archivado suave: `SoftDeletes` en `Arbitro` y `Torneo`; árbitros archivados muestran banner de advertencia

### Tamaño de archivos (regla dura)
- Ningún archivo de código (PHP, JS, Blade, CSS) debe superar ~600-700 líneas. Al acercarse a ese umbral, dividir por responsabilidad **antes** de seguir agregando código, no después.
- Patrón para CSS (ya aplicado en `app.css`, `designaciones.css`, `arbitros.css`): el archivo original queda como manifiesto de solo `@import`, apuntando a archivos hermanos en su propia subcarpeta — un `variables.css` con los alias/tokens locales, y uno por dominio o sección (ej. `layout.css`, `forms.css`, `table.css`). Cada archivo de dominio lleva su propio bloque de overrides de tema claro al final (`:root[data-theme="light"] ...`) — nunca un archivo catch-all separado para eso.
- Antes de dividir un archivo grande, buscar selectores/bloques duplicados (mismo selector redefinido dos veces — normalmente resto de un rediseño que nunca reemplazó al bloque anterior) y **fusionarlos** en el archivo nuevo, no copiarlos duplicados. Fusionar propiedad por propiedad (la declaración más reciente en el archivo original gana esa propiedad; lo que no toca se hereda de la más vieja), no "borrar el primer bloque a lo bruto".
- Aplica igual a PHP: si un Controller o Service crece demasiado, extraer lógica a Services/Actions por dominio (ya se hizo con `app/Actions` → `app/Services`).
- Después de dividir, verificar contra el CSS/código compilado (build de Vite, o el autoload de PHP) que ningún selector/método se perdió — no basta con que compile sin errores.

---

## Seeders (orden en DatabaseSeeder)

1. `AdminSeeder` — crea superadmin en tabla `admins`
2. `RolesPermisosSeeder` — 14 permisos + 6 roles Spatie (guard web)
3. `User::firstOrCreate` — usuario superadmin en tabla `usuarios`
4. `ColegioSeeder`
5. `CategoriaArbitroSeeder`
6. `PlanSeeder`
7. `SuscripcionColegioSeeder`
8. `EstadoArbitroSeeder` — estados del árbitro con colores y etiquetas

---

## Variables de entorno relevantes

```env
DB_DATABASE=novareef
ADMIN_PREFIX=novareef-panel   # prefijo URL panel admin
ADMIN_MAX_INTENTOS=3
ADMIN_BLOQUEO_SEGUNDOS=300
GOOGLE2FA_SECRET=             # no se configura aquí — se genera por admin
RESEND_API_KEY=               # para envío de emails (credenciales, bienvenida)
```

---

## Migraciones M03 pendientes de ejecutar

Si es una instalación nueva, correr en orden:
```bash
php artisan migrate
# o si las migraciones de árbitros/torneos no corrieron aún:
php artisan migrate --path=database/migrations/2026_05_28_000010_create_estados_arbitro_table.php
php artisan migrate --path=database/migrations/2026_05_28_000020_create_historial_estados_arbitro_table.php
php artisan migrate --path=database/migrations/2026_05_28_000030_update_estado_arbitro_in_arbitros_table.php
php artisan migrate --path=database/migrations/2026_05_28_000040_add_foto_to_arbitros_table.php
```
