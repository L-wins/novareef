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
- Spatie Permission: **NO** (excluido en `config/permission.php` con `'guards' => ['web']`). **Decisión de producto deliberada**: solo existe (y va a existir) un único superadmin (`admin@novareef.com`) que cubre todas las funciones del panel — no hay niveles de acceso ni rendición de cuentas entre varios admins que gestionar, así que no hay RBAC granular en este guard. `AdminAuth` solo exige sesión válida y `activo = true` (revalidado en cada request, no solo al login).

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
| `/designaciones/*` | auth, verificar.colegio, permission:ver-designaciones, modulo:designaciones | M04 completo: creación de partido, asignación/reasignación de árbitros, publicación, veedor, acta, historial, importador de partidos desde Word (`.docx`) y listado PDF con árbitros designados |
| `/finanzas/*` | auth, verificar.colegio, permission:ver-finanzas, modulo:finanzas | M06 completo: movimientos (ingreso/egreso), abonos, pago acumulado con neteo, balance, reportes (PDF/CSV) — ver tabla detallada abajo |
| `/academico/*` | auth, verificar.colegio, permission:ver-academico, modulo:academico | M08 completo: sesiones académicas, asistencia manual/scanner en tiempo real (Reverb), justificaciones, catálogo de tipos por colegio — ver tabla detallada abajo |
| `/sanciones/*` | auth, verificar.colegio, permission:ver-sanciones, modulo:sanciones | M07 completo: sanciones disciplinarias con multa económica opcional, catálogo de tipos por colegio — ver tabla detallada abajo |

### Rutas especiales de árbitros

| URI | Descripción |
|---|---|
| GET `/arbitros/mi-perfil` | Perfil del árbitro autenticado |
| PUT `/arbitros/mi-perfil` | Actualizar perfil del árbitro |
| GET `/arbitros/completar-perfil` | Wizard de completar perfil (primer acceso) |
| POST `/arbitros/guardar-perfil` | Guardar datos del wizard |
| POST `/arbitros/{id}/foto` | Subir foto de perfil |
| DELETE `/arbitros/{id}/foto` | Eliminar foto de perfil |
| GET `/mi-estado-cuenta` | Estado de cuenta financiero del árbitro (saldo pendiente, pagos recibidos, multas, descuentos) — **fuera** del grupo `finanzas`, sin `permission:ver-finanzas` (el árbitro no lo tiene), mismo criterio que `finalizarPartido` fuera de `ver-designaciones` |

### Rutas de Finanzas (M06)

Prefix `finanzas`, middleware `permission:ver-finanzas` + `modulo:finanzas`. Controllers en `App\Http\Controllers\Finanza\`.

| URI | Controller | Descripción |
|---|---|---|
| GET `/finanzas` | `MovimientoFinancieroController@index` | Listado con filtros + resumen agregado (ingresos/egresos/neto/pendientes) |
| GET\|POST `/finanzas/crear` | `MovimientoFinancieroController@create/store` | Alta de movimiento (`permission:crear-finanzas`) |
| GET `/finanzas/exportar` | `MovimientoFinancieroController@exportarCsv` | Export CSV del listado filtrado |
| GET `/finanzas/balance` | `BalanceFinancieroController@index` | Saldo en caja + cuánto se debe con cada árbitro |
| GET\|POST `/finanzas/pagos-arbitro` | `PagoArbitroController@index/store` | Pago acumulado con neteo de deudas (`permission:crear-finanzas` para store) |
| GET `/finanzas/pagos-arbitro/comprobante/{lote}` | `PagoArbitroController` | Comprobante PDF de un lote de pago (`idLotePago`) |
| GET `/finanzas/reportes` | `ReporteFinancieroController@index` | Rango de fechas libre, gráfico mensual, comparativa vs período anterior |
| GET `/finanzas/reportes/pdf` | `ReporteFinancieroController@pdf` | Export PDF del reporte |
| GET `/finanzas/{id}` | `MovimientoFinancieroController@show` | Detalle + abonos + historial |
| POST `/finanzas/{id}/abonos` | `MovimientoFinancieroController@abonar` | Registrar abono (`permission:crear-finanzas`) |
| PUT `/finanzas/{id}/anular` | `MovimientoFinancieroController@anular` | Anular — solo sin abonos previos (`permission:editar-finanzas`) |

**Rutas de rango fijo (`/balance`, `/reportes`, `/pagos-arbitro`, `/exportar`) van declaradas antes de `/{id}` en `routes/web.php`** — si no, Laravel las capturaría como si `{id}` fuera el literal `"balance"`/`"reportes"`/etc.

**Services**: `FinanzasService` (escritura — registrar movimiento/abono, anular, generar pagos de nómina, pago acumulado) y `ReporteFinanzasService` (solo lectura/agregación — resumen del listado, reporte por rango, serie mensual, balance general sin N+1, datos de comprobante). Separación deliberada: escritura vs. lectura agregada, para no mezclar transacciones con queries de reporte.

**PDF**: `barryvdh/laravel-dompdf` (`app('dompdf.wrapper')`) — mismo paquete que ya se usaba para el historial/acta de designaciones. Vistas en `resources/views/pdf/`.

### Rutas de Sanciones (M07)

Prefix `sanciones`, middleware `permission:ver-sanciones` + `modulo:sanciones`. Controllers en `App\Http\Controllers\Sancion\`.

| URI | Controller | Descripción |
|---|---|---|
| GET `/sanciones` | `SancionController@index` | Listado — si `rolUsuario==='arbitro'` filtra automáticamente a sus propias sanciones |
| GET\|POST `/sanciones/crear` | `SancionController@create/store` | Alta de sanción, con multa económica opcional (`permission:crear-sanciones`) |
| GET `/sanciones/{id}` | `SancionController@show` | Detalle + historial + acciones |
| PUT `/sanciones/{id}/estado` | `SancionController@cambiarEstado` | `accion`: cumplir\|apelar (permiso `crear-sanciones`) o anular\|resolver (permiso `editar-sanciones`, chequeado en el controller, no en la ruta) |
| `/tipos-sancion/*` | `TipoSancionController` | Catálogo de tipos de sanción **por colegio** (`permission:editar-sanciones` + `modulo:sanciones`) |

**`SancionService`**: `crearSancion` (engancha la multa a `FinanzasService::registrarMovimiento` en la misma transacción si aplica), `cumplir`, `anular`, `apelar`, `resolverApelacion`. **`VencerSancionesJob`** (diario, `routes/console.php`) cierra automáticamente sanciones vencidas.

**⚠️ Sanciones NO bloquea designaciones** — es un registro disciplinario/económico puro. Un árbitro sancionado sigue siendo designable con normalidad; `DesignacionService` y `PartidoStateMachine` no tienen ninguna validación de M07. Si un colegio quiere sacar de circulación a un árbitro, usa el toggle manual ya existente de M02 (`Arbitro.estadoArbitro = 'suspendido'`).

### Rutas de Académico (M08)

Prefix `academico`, middleware `permission:ver-academico` + `modulo:academico` (ya incluido en `Plan.modulosJSON` de los planes que lo traen — no hay flag propio en `ConfiguracionColegio`). Controllers en `App\Http\Controllers\Academico\`.

| URI | Controller | Descripción |
|---|---|---|
| GET `/academico` | `SesionAcademicaController@index` | Listado de sesiones (`permission:crear-academico` — instructor/ejecutivo) |
| GET\|POST `/academico/crear` | `SesionAcademicaController@create/store` | Alta de sesión — genera automáticamente un registro de asistencia `ausente` por cada árbitro que aplique (`permission:crear-academico`) |
| GET `/academico/mis-clases` | `SesionAcademicaController@misClases` | Próximas, historial y % de asistencia del árbitro autenticado |
| GET `/academico/{id}` | `SesionAcademicaController@show` | Detalle + registro de asistencia en tiempo real (manual o scanner) |
| PUT `/academico/{id}/abrir` | `SesionAcademicaController@abrir` | Activa `sesionAbierta` |
| PUT `/academico/{id}/cerrar` | `SesionAcademicaController@cerrar` | Cierra la sesión **y** confirma la lista como definitiva — es una sola acción, no dos |
| POST `/academico/scanner` | `AsistenciaController@scanner` | Recibe `codigoCarnet`, responde JSON — usado por la terminal del instructor en modo scanner |
| POST `/academico/asistencias/{id}/marcar` | `AsistenciaController@marcar` | El árbitro marca su propia asistencia (modo manual, requiere `sesionAbierta`) |
| PUT `/academico/asistencias/{id}` | `AsistenciaController@corregir` | Instructor corrige una marca antes de cerrar la sesión |
| GET\|POST `/academico/asistencias/{id}/justificar` | `JustificacionController@create/store` | El árbitro justifica una inasistencia (hasta `fechaSesion + 3 días`), PDF opcional |
| GET `/academico/justificaciones/pendientes` | `JustificacionController@pendientes` | Para instructor/ejecutivo/sanciones (`permission:editar-academico`) |
| PUT `/academico/justificaciones/{id}` | `JustificacionController@revisar` | Aprobar o rechazar — basta con que la apruebe uno de los roles autorizados |
| `/tipos-sesion-academica/*` | `TipoSesionAcademicaController` | Catálogo de tipos de sesión **por colegio** (`permission:editar-academico`) — no hay un enum fijo de tipos, cada colegio define los suyos (uno o más puede marcarse `esOficial`, ej. prueba FCF) |

**Servicios**: `SesionAcademicaService` (ciclo de vida de la sesión), `AsistenciaAcademicaService` (marcar web/scanner/corregir), `JustificacionAcademicaService` (crear/aprobar/rechazar), `TipoSesionAcademicaService` (catálogo).

**Tiempo real (Reverb)**: canal privado `colegio.{idColegio}.academico` — eventos `AsistenciaActualizadaEvent` (marca web, scanner o corrección del instructor — se distinguen por `registradoPor` en el payload) y `SesionAcademicaCerradaEvent`.

**`CerrarJustificacionesVencidasJob`** (diario, `routes/console.php`): cierra la ventana de justificación de asistencias vencidas sin justificar, y finaliza automáticamente sesiones pasadas que el instructor nunca cerró.

**Permiso `editar-academico`** (nuevo, M08) — otorgado a `tecnico`, `ejecutivo` y `sanciones`; distinto de `crear-academico` porque aprobar/rechazar una justificación (como anular en Finanzas/Sanciones) es una acción de revisión, no de flujo normal de creación. Sin este permiso el rol `sanciones` no podría cumplir su parte en la aprobación de justificaciones.

**⚠️ Académico NO bloquea designaciones ni genera multas automáticas** — es un registro de asistencia/capacitación puro. `NovaReef` solo lleva el control; si el colegio decide sancionar por inasistencias repetidas, lo hace manualmente vía M07.

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
| PATCH `/preferencias/tema` | Preferencia de tema del admin |
| GET `/colegios` | Listado de colegios (CRUD completo) |
| GET\|POST\|PUT `/colegios/*` (crear/editar/estado) | Alta y edición de colegios |
| POST `/colegios/{id}/impersonar` | Entrar como la cuenta ejecutivo del colegio (ver abajo) |
| GET `/planes` | Listado de planes (CRUD parcial — sin crear/eliminar, se siembran) |
| GET\|PUT `/planes/{id}/*` (editar/visible/activo) | Edición de planes |
| GET `/suscripciones` | Listado transversal de suscripciones (todos los colegios) |
| PUT `/suscripciones/colegio/{id}/{plan\|extender\|cancelar}` | Cambiar plan, extender o cancelar la suscripción de un colegio |
| GET `/usuarios` | Listado de cuentas de colegio (todos), con filtro por colegio/rol |
| PUT `/usuarios/{id}/estado` | Activar/suspender una cuenta de colegio |
| GET `/logs` | Accesos (`admin_login_logs`) + impersonaciones (`admin_action_logs`) |
| GET `/2fa/configurar` | Config 2FA |
| POST `/2fa/activar` | Activar 2FA |
| POST `/2fa/desactivar` | Desactivar 2FA |

Ninguna ruta admin lleva middleware `permission:` — no hay RBAC en este guard (ver nota en "Guard admin" más arriba). `AdminAuth` solo exige sesión válida y `activo = true`, revalidado en cada request.

### Impersonación de colegio (`admin.colegios.impersonar`)

El superadmin puede entrar como la cuenta `ejecutivo` principal de cualquier colegio (`ColegioService::adminPrincipal()`) — útil para soporte/depuración. Mecánica:

- Hace login en el guard `web` con esa cuenta; la sesión del guard `admin` sigue viva en paralelo (son guards independientes sobre la misma sesión PHP) — no hace falta volver a loguearse al salir.
- Guarda `impersonacion.idAdmin` / `impersonacion.idColegio` / `impersonacion.expira` (45 min) en sesión.
- `TerminarImpersonacionExpirada` (middleware `web`, en `append`, **nunca** `prepend` — necesita la sesión ya iniciada) cierra la sesión del guard `web` sola si se cumplió el plazo.
- `resources/views/layouts/app.blade.php` muestra un banner fijo con botón "Salir" (`POST /impersonacion/salir`, `ImpersonacionController@salir`) mientras dura.
- Cada impersonación (entrada y salida) se registra en `admin_action_logs` vía `App\Services\AdminAuditService` — es el **único** caso que sigue dejando rastro en esa tabla. Es un registro de transparencia (el superadmin está viendo datos de un cliente), no de rendición de cuentas entre varios admins — solo existe uno.

**⚠️ Lecciones de un bug real que se dio aquí** (por si se vuelve a tocar el guard admin o se agrega RBAC en el futuro):
- Spatie's `PermissionMiddleware` hace `Auth::guard($guard)` — si el middleware se escribe como `'permission:xxx'` sin especificar guard, usa el guard **default** de Laravel (`'web'`), no el guard bajo el que corre la ruta. En una ruta admin eso siempre da 403 sin importar los permisos reales, porque nadie está logueado en `web` en una sesión de panel admin pura. Habría que escribir `'permission:xxx,admin'` siempre.
- `actingAs($modelo, 'admin')` en tests llama internamente `Auth::shouldUse('admin')`, cambiando el guard *default* de Laravel para el resto del test — esto puede enmascarar el bug de arriba y dar falsos positivos. Para probar código bajo un guard no-`web`, forzar `Auth::shouldUse('web')` después de `actingAs()`.
- `resources/views/errors/403.blade.php` se renderiza para cualquier 403 sin importar el guard — no puede asumir `Auth::user()` (guard web). Detecta `auth('admin')->check()` y extiende `admin.layouts.app` o `layouts.app` según corresponda.

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

### `MovimientoFinanciero` (`movimientos_financieros`) — M06
- Ingreso o egreso unificado en una sola tabla (discriminador `tipoMovimiento` + `categoria`, ver constantes de clase `CATEGORIAS_POR_TIPO`)
- Categorías ingreso: `ingreso_torneo`, `mensualidad`, `multa`, `otro_ingreso`. Egreso: `nomina_arbitro`, `arbitro_externo`, `gasto_fijo`, `gasto_institucional`, `gasto_vario`
- `estadoMovimiento`: pendiente | parcial | pagado | anulado (denormalizado, se recalcula en cada abono)
- `idUsuarioRegistro` nullable — null cuando el movimiento lo genera un job del sistema (ej. `GenerarPagosJob` al finalizar un partido nómina), no un usuario
- Multa con origen desacoplado sin FK: `tipoOrigenMulta` (`sancion`\|`academico`\|`manual`) + `idOrigenMulta`
- Método `saldoPendiente()`: `montoTotal` − suma de `abonos()` no anulados (siempre calculado en vivo, no cacheado)
- Relación nueva en `Arbitro`: `movimientosFinancieros()`

### `AbonoMovimiento` (`abonos_movimiento`) — M06
- Pagos parciales o totales sobre un `MovimientoFinanciero`; inmutables (solo se anulan, no se editan), `timestamps=false` con `created_at` manual
- `metodoPago`: efectivo\|pago_digital\|nomina — `nomina` es el ajuste interno que hace `FinanzasService::compensarDeudaConNomina()` (neteo contra la nómina pendiente del árbitro), nunca dinero real (se excluye de "saldo en caja" y de "historial de pagos recibidos"). `pago_digital` cubre cualquier transferencia/consignación/otro medio digital — no se distingue el banco/canal concreto. Sin campo `referencia` (se eliminó, se consideró innecesario)
- `idLotePago` (UUID) agrupa los abonos de una misma operación de pago acumulado — es la clave para comprobantes de pago y `lotesRecientes()`

### `Sancion` (`sanciones`) / `TipoSancion` (`tipos_sancion`, catálogo **por colegio**) — M07
- `estadoSancion`: activa | cumplida | anulada | apelada
- **No bloquea designaciones** (decisión de negocio explícita — ver sección M07 abajo)
- `tieneMultaEconomica` + `idMovimientoFinanciero` opcional (enganche a Finanzas)

### `SesionAcademica` (`sesiones_academicas`) / `TipoSesionAcademica` (`tipos_sesion_academica`, catálogo **por colegio**) — M08
- `estadoSesion`: programada | en_curso | finalizada | cancelada; `sesionAbierta` (bool) habilita el registro de asistencia en tiempo real
- `dirigidaA`: todos | categoria (`idCategoria` solo si aplica) — el set de árbitros esperados se congela en `AsistenciaAcademica` al crear la sesión, no es editable después
- `modoAsistencia`: manual (el árbitro marca desde su perfil) | scanner (instructor lee `codigoCarnet`)
- Accessor `fechaLimiteJustificacion` = `fechaSesion + 3 días`; accessor `esOficial` delega en `tipo->esOficial` del catálogo
- **Sin soft deletes** en `AsistenciaAcademica`/`JustificacionAcademica` (igual que M06/M07); `SesionAcademica` **sí** tiene `SoftDeletes`

### `AsistenciaAcademica` (`asistencias_academicas`) / `JustificacionAcademica` (`justificaciones_academicas`) — M08
- `estadoAsistencia`: presente | ausente | justificacion_pendiente | justificado | justificacion_rechazada — único `(idSesion, idArbitro)`
- `registradoPor`: arbitro (marca web) | instructor (scanner o corrección manual) | sistema (jobs)
- `JustificacionAcademica`: única por asistencia (`idAsistencia` unique), aprobable por instructor, ejecutivo o rol sanciones — basta una aprobación
- **Sin multas automáticas** — Académico solo lleva el control; `tipoOrigenMulta='academico'` en `MovimientoFinanciero` ya estaba anticipado en M06 pero M08 no lo usa todavía

---

## Spatie Permission — configuración

`config/permission.php`:
```php
'guards' => ['web'],  // admin guard excluido
```

**18 permisos**: ver/crear/editar-arbitros, ver/crear/editar-torneos, ver/crear-designaciones, ver/crear/editar-finanzas, ver/crear/editar-academico, gestionar-asistencia, ver/crear/editar-sanciones

`editar-finanzas` (anular movimiento), `editar-sanciones` (anular sanción / resolver apelación) y `editar-academico` (aprobar/rechazar justificación) se agregaron con M06/M07/M08 — son deliberadamente distintos de `crear-*` porque son acciones de reversión/revisión, no de flujo normal. `gestionar-asistencia` es específico del instructor en M08.

**6 roles**:
| Rol | Permisos |
|---|---|
| `ejecutivo` | todos (18) + `gestionar-cuentas-admin` y `crear-calificaciones` (otorgados por seeders aparte, ver advertencia abajo) |
| `tesorero` | ver/crear/editar-finanzas, ver-arbitros, ver-torneos, ver-designaciones |
| `designador` | ver/crear-designaciones, ver-arbitros, ver-torneos |
| `sanciones` | ver/crear/editar-sanciones, ver-arbitros, ver-academico, editar-academico (revisa justificaciones) |
| `tecnico` | ver/crear/editar-academico, gestionar-asistencia, ver-arbitros |
| `arbitro` | ver-arbitros, ver-torneos, ver-designaciones, ver-sanciones, ver-academico |

**⚠️ `RolesPermisosSeeder::run()` usa `syncPermissions()` — nunca correrlo solo.** `syncPermissions()` **reemplaza por completo** el set de permisos del rol, no lo complementa. `gestionar-cuentas-admin` (`CuentasAdminRolSeeder`) y `crear-calificaciones` (`VeedorRolSeeder`) se los otorgan a `ejecutivo` de forma *aditiva* (`givePermissionTo`) en seeders que corren **después** en `DatabaseSeeder`. Si se re-siembra `RolesPermisosSeeder` de forma aislada (ej. al agregar un permiso nuevo), esos dos permisos se pierden silenciosamente — hay que re-correr `VeedorRolSeeder` y `CuentasAdminRolSeeder` a continuación, o el `DatabaseSeeder` completo.

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
- Inicialización global: `resources/js/shared/nova-selects.js` → `window.initNovaSelects()` (importado por `app.js` y `admin/admin.js`), llamar también al abrir modales con contenido dinámico
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

- CSS: `resources/css/admin/admin.css` — **manifiesto de solo `@import`**; el contenido vive en archivos hermanos por dominio: `variables.css` (alias legacy → tokens `--nv-*`, reset, utilidades), `navbar.css`, `layout.css`, `components.css` (cards, tablas, badges, botones, filtros, paginación, dropdown), `login.css` (login + OTP + 2FA), `forms.css`, `detail.css`
- Overrides de vendors (SweetAlert2/Flatpickr/Choices.js): `resources/css/vendor/overrides.css` — **compartido** entre `app/vendor-overrides.css` y el manifiesto admin, para que popups/selects/date pickers se vean idénticos en ambos paneles
- JS: `resources/js/admin/admin.js`
- Layout: `resources/views/admin/layouts/app.blade.php`
- Paginación de listados: `@include('admin.partials.pagination', ['paginator' => $x, 'etiqueta' => 'usuarios'])`

### CSS variables principales
Alias legacy definidos en `admin/variables.css`, todos mapeados a tokens (`tokens.css`):
```css
--primary: var(--nv-accent)
--bg-navbar: var(--nv-elevated)
--bg-body: var(--nv-bg)
--bg-card: var(--nv-card-alt)
--text-bright: var(--nv-text)
--text: var(--nv-text-2)
--text-muted: var(--nv-text-3)
--border-color: var(--nv-border)
```

### JS (admin.js)
- Comparte módulos con el panel usuario: `shared/nova-selects.js` (`initNovaSelects` — Choices.js + Flatpickr), `shared/nova-alert.js` (`novaAlert` + `initConfirmSubmit`), `shared/auto-filter.js`, `shared/theme.js`
- Confirmaciones destructivas: marcar el `<form>` con `data-confirm-submit` (+ `data-confirm-title/text/color/btn`) — NO usar `confirm()` nativo ni `onsubmit` inline
- Dropdowns simples: `[data-dropdown]` + `[data-dropdown-toggle]` + `.admin-dropdown__menu` (cierra con click fuera)
- Selección de plan en colegios/create: listener de `.plan-card` (marca radio oculto)
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
- Patrón para CSS (ya aplicado en `app.css`, `designaciones.css`, `arbitros.css`, `admin/admin.css`): el archivo original queda como manifiesto de solo `@import`, apuntando a archivos hermanos en su propia subcarpeta — un `variables.css` con los alias/tokens locales, y uno por dominio o sección (ej. `layout.css`, `forms.css`, `table.css`). Cada archivo de dominio lleva su propio bloque de overrides de tema claro al final (`:root[data-theme="light"] ...`) — nunca un archivo catch-all separado para eso.
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

## Cache y sesión — `file` en dev, `redis` en producción

**Diagnóstico real (detectado por lentitud reportada en cada acción del panel):**
`SESSION_DRIVER` y `CACHE_STORE` estaban en `database`. Eso significa que, en
cada request autenticado, además de las queries propias de la vista se suman:
lectura/escritura de la sesión (tabla `sessions`), lectura del caché de
permisos de Spatie (`permission.cache_store` usa el store por defecto), y
lecturas/escrituras de `RateLimiter` (usado por `ProtegerEscrituraMasiva`) —
todo contra MySQL. Con miles de usuarios concurrentes esto no escala: cada uno
de esos puntos es un round-trip extra a la base de datos por request.

**Estado actual:**
- Dev local (`.env`): `SESSION_DRIVER=file`, `CACHE_STORE=file`,
  `QUEUE_CONNECTION=database` (sin cambios — no hay servidor Redis local
  corriendo, no tiene sentido moverlo sin uno).
- `predis/predis` ya está instalado como cliente Redis (`REDIS_CLIENT=predis`
  en `.env`/`.env.example`) — es 100% PHP, no requiere la extensión `phpredis`
  compilada, así que es más portable entre distintos hosts de producción.

**Recomendación para producción (cuando salga a miles de usuarios reales):**
Cambiar únicamente en `.env` del servidor de producción — no requiere tocar
código:
```env
SESSION_DRIVER=redis
CACHE_STORE=redis
QUEUE_CONNECTION=redis        # opcional, mismo razonamiento si las colas crecen
REDIS_CLIENT=predis
REDIS_HOST=<host-redis-produccion>
REDIS_PASSWORD=<password>
REDIS_PORT=6379
```
Redis resuelve sesión/caché en memoria (sub-milisegundo) en vez de una query
MySQL por lectura/escritura, y es la opción estándar para cuando hay más de
un servidor web (session/cache compartidos entre instancias, cosa que `file`
no soporta). No hay razón para tocar `file` en dev local — ahí ya no hay
problema de escala y evita depender de un servicio extra corriendo.

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
