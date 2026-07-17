# Auditoría de plataforma NovaReef — julio 2026 (pendientes)

Auditoría original completa (seguridad, arquitectura, rendimiento/concurrencia,
errores humanos) ejecutada sobre Arbitros/Torneos/Designaciones/Sanciones/
Académico/Admin. Este documento pasó por dos rondas de corrección en la
misma sesión: la primera cubrió los hallazgos de seguridad/concurrencia más
críticos y varios de arquitectura; la segunda cerró prácticamente todo lo
que quedaba — condición de carrera restante, cobertura de tests, y el
split de los 4 archivos que superaban el límite de tamaño documentado.
**Lo que queda abajo es realmente lo último, y es todo de bajo riesgo/baja
prioridad — nada bloquea que el proyecto se considere sólido hoy.**

---

## Resuelto en esta sesión (referencia rápida)

No hace falta releer esto para trabajar — queda para no volver a auditar lo
mismo dos veces.

**Ronda 1 — seguridad y concurrencia:**
- Sentry conectado (`bootstrap/app.php`, canal `sentry` en `config/logging.php`).
- Condición de carrera en `SancionService::transicionar()` — `lockForUpdate()` + revalidación.
- Límites de plan sin bloqueo de fila (`LimiteService::bloquearColegio()`, usado en `ArbitroService`/`CuentaAdminService`).
- SVG removido de mimes de foto de árbitro (XSS); `javascript:` bloqueado en logo de colegio.
- Confirmación en "Cambiar estado" de colegio (detalle admin).
- `SESSION_SECURE_COOKIE` forzado fuera de local/testing; documentado junto con `RESEND_WEBHOOK_SECRET`, `SENTRY_LARAVEL_DSN`, `ADMIN_MAX_INTENTOS`/`ADMIN_BLOQUEO_SEGUNDOS` en `.env.example`.
- `config/admin.php` lee límites de intentos/bloqueo desde env.
- Los 7 `Notificar*Job` son idempotentes (`NotificacionEnviada::reclamar()`) y reportan fallos de `Mail::send()` a Sentry.
- `DesignacionController::asignarArbitro`/`reasignarArbitro` ya no filtran `$e->getMessage()` crudo para errores inesperados.
- Índice en `arbitros.estadoArbitro`; `ArbitroFotoController` usa el trait `ResuelveColegio`.
- Queries sin límite acotadas (`CobroMasivoController`, `ColegioController` paginado, dropdown de torneos en Designaciones).
- Código huérfano resuelto: `torneos.archivar`, `academico.sesiones.destroy/cancelar`, `emergentes.index` con trigger real; `bootstrap.js`, `routes/tenant.php`, `config/tenancy.php` eliminados.
- `TipoSancionController`/`TipoSesionAcademicaController` unificados en `CatalogoActivableController`.
- Mapas estado→etiqueta duplicados movidos a constantes de modelo (`Sancion`, `SesionAcademica`).
- `DesignacionController::cambiarEstadoPartido` dividido en métodos con nombre propio; literales crudos reemplazados por constantes.
- Las 14 validaciones inline de Designaciones y Admin convertidas a FormRequest.
- `torneos.css` dividido en manifiesto `@import`.

**Ronda 2 — lo que quedaba:**
- **Condición de carrera restante** — `DesignacionService::confirmarDesignacion`/`rechazarDesignacion` ahora usan `lockForUpdate()` + revalidación, mismo patrón que el resto.
- **2FA admin con throttle dedicado** — `AdminLoginController::verify2fa()` ya no depende solo del throttle genérico de escritura.
- **Cobertura de tests** — Académico completo, CRUD de Árbitro (incluyendo perfil propio y wizard), registro de Colegio, sub-recursos de Torneo (División/Sede/Tarifa/Emergente), `CalificacionController`, catálogos de tipo, `DesignacionController::index()`, login/2FA/preferencias/suscripciones de Admin. **~120 tests nuevos en total entre las dos rondas.**
- **`CalificacionController::store`** convertido de `$request->validate()` inline a `StoreCalificacionRequest`.
- **Split de los 4 archivos que superaban ~700 líneas:**
  - `DesignacionController.php` (860 líneas) → 4 controladores: `DesignacionController` (CRUD de partido, 318), `DesignacionAccionesController` (asignar/reasignar/estado/veedor, 228), `MisPartidosController` (vista árbitro, 346), y `disponibilidadGeneral()` movido a `DisponibilidadController` (estaba mal ubicado desde el inicio). Cero cambios de nombre de ruta — solo cambió qué clase resuelve cada una.
  - `DesignacionService.php` (757 líneas) → `DesignacionService` (escritura, 507) + `CandidatosDesignacionService` (cálculo de candidatos/advertencias de disponibilidad, 279).
  - `designaciones.js` (982 líneas) → 8 módulos ES (`realtime`, `advertencias`, `asignacion`, `busqueda-arbitros`, `estado-partido`, `mi-designacion`, `torneo-selects`, `helpers`) orquestados por un `designaciones.js` de 99 líneas. Verificado: inventario de funciones/`window.X` idéntico al original, bundle compilado del mismo tamaño (22.53 kB vs 22.55 kB).
  - `welcome.blade.php` (1021 líneas, había crecido desde la auditoría original) → manifiesto de 17 líneas + 11 partials en `resources/views/welcome/`. Verificado con diff byte a byte contra el original antes de reescribir.

**Verificación de toda la sesión:** suite completa de tests en verde después
de cada cambio (última corrida: ver commit), `npm run build` limpio.

**Ronda 3 — limpieza final:**
- **Superadmin cross-tenant en guard `web` eliminado por completo**
  (era el punto #1 de "lo que queda", el único que requería decisión de
  producto). Se confirmó por grep exhaustivo de los 12 seeders que ningún
  camino legítimo crea `usuarios.rolUsuario = 'superadmin'` — la fila solo
  podía existir como "fantasma" (ver `LimpiarUsuariosFantasma`). Eliminados:
  `ColegioController` (guard web), `SoloSuperAdmin` middleware, vistas
  `resources/views/colegios/*`, ruta `/colegios`, el bloque de nav
  condicional en `layouts/app.blade.php`, y su test. El único superadmin
  real del sistema sigue siendo el del guard `admin`.
- **Nomenclatura de rutas unificada** (era el punto #6): sub-recursos de
  Torneo renombrados a `torneos.divisiones.*`, `torneos.sedes.*`,
  `torneos.divisiones.tarifas.*`, `torneos.emergentes.*`. "Cambiar estado"
  unificado a un único patrón (`PUT /{id}/estado` + método `cambiarEstado`)
  en Arbitro, CategoriaArbitro, TipoSancion y TipoSesionAcademica —
  `AdminPlanController::toggleActivo/toggleVisible` se dejó fuera a
  propósito: son dos flags independientes (visible y activo), no un único
  estado, y forzarlos al mismo patrón sería incorrecto.

---

## Lo que queda — todo bajo, nada urgente

### 1. Mass assignment — defensa adicional opcional (Bajo, no urgente)

Sin hallazgos explotables (cero `$request->all()` pasado a `create()`), la
seguridad depende de que esa disciplina se mantenga. Recomendación
opcional: migrar modelos con columnas sensibles a `$guarded` o DTOs
explícitos como defensa adicional. Hardening preventivo, no un hallazgo activo.

### 2. Patrón "listado con filtros" — centralización incompleta (Bajo)

El idioma quedó unificado (`->when()` en todos lados) y Designaciones ya
tiene su propio Service de lectura, pero Torneo/Partido/Sancion siguen
armando el filtro directo en el controlador en vez de delegarlo a un
Service dedicado, a diferencia de Finanzas. No urgente — son métodos
cortos (15-20 líneas) y ya consistentes entre sí.

### 3. Read/write split incompleto en Sanciones y Académico (Bajo)

`SancionService` y `SesionAcademicaService`/`AsistenciaAcademicaService`
mezclan escritura y lectura/agregación en un solo Service, a diferencia de
Finanzas y ahora Designaciones. Ninguno de los dos se acerca al límite de
tamaño — dividir ahora sería abstracción prematura. Revisar si crecen.

### 4. Métodos largos a vigilar (Bajo, no roto)

`DesignacionService::reasignarArbitro` (~90 líneas, una sola transacción
que valida + escribe historial + reasigna + despacha job + emite 2
eventos) y `FinanzasService::registrarCobroMasivo` (~108 líneas, mejor
organizado internamente). Candidatos a dividir si se tocan de nuevo, no
urgente por sí solos.

### 5. Admin/AdminUsuarioController.php (Bajo, aceptado por ahora)

Lista completa de colegios para un `<select>` de filtro — aceptable hoy,
necesitará búsqueda async cuando el número de colegios crezca. No es
acción inmediata.

---

## Cómo usar este documento

Todo lo que queda arriba es legibilidad/consistencia o hardening
preventivo opcional — no hay ningún bug funcional, hallazgo de seguridad
abierto, ni decisión de producto pendiente. Se puede abordar en cualquier
momento sin apuro, idealmente cuando se vuelva a tocar el archivo en
cuestión por otra razón.
