# Módulos M01–M08 — NovaReef

NovaReef tiene 8 módulos funcionales. Estado auditado contra el código real.

---

## M01 — Colegios ✅ Implementado

**Responsabilidad:** entidad raíz del tenant. Gestión de colegios desde el superadmin.

**Tabla:** `colegios`

**Campos reales:**
- `idColegio` (PK), `tenantId` (FK a `tenants` de Stancl), `nombreColegio`, `codigoColegio`
- `emailColegio`, `telefonoColegio`, `direccionColegio`, `ciudadColegio`, `departamentoColegio`, `paisColegio`
- `logoColegio`, `estadoColegio`, `fechaSuscripcion`, `fechaExpiracion`
- `estadoColegio` ENUM: `'activo'`, `'prueba'`, `'suspendido'`

**Lo que existe:** CRUD completo desde panel admin y desde el rol `'superadmin'` dentro del panel web. Middleware `solo.superadmin` protege las rutas.

**Reglas:**
- `Colegio` NO usa `BelongsToTenant` (es la entidad raíz).
- Borrar un colegio = soft delete no implementado (sin `deleted_at`) — cambiar `estadoColegio` a `'suspendido'`.
- Tiene relación `tenantId` → `tenants` para preparar la activación de Stancl.

---

## M02 — Árbitros ✅ Implementado

**Responsabilidad:** registro y gestión de árbitros del colegio.

**Entidades reales:** `Arbitro`, `DocumentoArbitro`, `CategoriaArbitro` (catálogo), `EstadoArbitro` (catálogo), `HistorialEstadoArbitro`

**Campos clave de `Arbitro`:**
- `idArbitro`, `idUsuario` (FK → `usuarios`), `idColegio`, `idCategoria`
- `numeroDocumento`, `tipoDocumento` ENUM: `'cedula'`, `'pasaporte'`, `'extranjeria'`
- `pesoArbitro`, `estaturaArbitro`, `rhArbitro`, `epsArbitro`, `profesionArbitro`
- `fechaIngresoColegio`, `direccionArbitro`, `barrioArbitro`
- `tieneVehiculo`, `tipoVehiculo`, `marcaVehiculo`, `placaVehiculo`, `colorVehiculo`
- `fotoPerfil` (path local en `storage/app/public`), `codigoCarnet` (generado automático: `NR-{idColegio}-{año}-{secuencial}`)
- `estadoArbitro` ENUM: `'activo'`, `'inactivo'`, `'suspendido'`, `'retirado'`, `'aprendiz'`, `'proceso_ingreso'`

**Catálogo `estados_arbitro`:** cada estado tiene `nombre`, `etiqueta`, `color`, `permiteDesignar` (boolean), `esActivo`, `orden`. El campo `permiteDesignar` se usa en M04 para validar si el árbitro puede recibir designaciones.

**Relación árbitro ↔ usuario:** un `Arbitro` siempre tiene `idUsuario` único → `usuarios`. El usuario tiene `rolUsuario = 'arbitro'`. El árbitro usa `Auth::user()->idColegio` para el filtro de tenant.

**Accessor `porcentajePerfil`:** calcula 0-100% según campos completados. `colorPerfil`: red (<41), yellow (41-70), blue (71-99), green (100).

**Soft delete:** ✅ en `arbitros`. Archivar = soft delete; restaurar = restore.

**Reglas:**
- `codigoCarnet` único, generado en `creating` event.
- `tipoVehiculo`, `marcaVehiculo`, `placaVehiculo`, `colorVehiculo` = null si `tieneVehiculo = false` (limpiado en `saving` event).
- Árbitros archivados muestran banner de advertencia en la UI.

---

## M03 — Torneos ✅ Implementado

**Responsabilidad:** gestión de torneos, estructura de competición, partidos, sedes y reglamentos.

**Entidades reales** (no las del skill original — esas eran incorrectas):

| Entidad | Tabla | Descripción |
|---|---|---|
| `Torneo` | `torneos` | El torneo en sí |
| `DivisionTorneo` | `divisiones_torneo` | Categorías del torneo (Sub-17, Mayores, etc.) |
| `SedeTorneo` | `sedes_torneo` | Estadios/canchas donde se juega |
| `TarifaTorneo` | `tarifas_torneo` | Tarifas por división y formato |
| `Partido` | `partidos` | Partido individual |
| `ReglamentoTorneo` | `reglamentos_torneo` | PDFs de reglamento — solo un `esActual = true` por torneo |
| `EmergenteTorneo` | `emergentes_torneo` | Valores de emergencia por partido |
| `FormatoDesignacion` | `formatos_designacion` | Catálogo: Solo, Dupla, Terna, Cuarto-Terna |
| `RolPartido` | `roles_partido` | Catálogo: Central, Asistente, Cuarto, VAR, AVAR |

**Campos clave de `Torneo`:**
- `tipoTorneo` ENUM: `'local'`, `'zonal'`, `'oficial'`
- `modalidadPago` ENUM: `'campo'`, `'nomina'`
- `estadoTorneo` ENUM: `'proximo'`, `'activo'`, `'finalizado'`, `'cancelado'`
- `temporada` (año), `fechaInicio`, `fechaFin`, `organizadorNombre`, `organizadorTelefono`, `organizadorEmail`
- `valorEmergente` (decimal) — pago adicional en emergencias

**Campos clave de `Partido`:**
- `estadoPartido` ENUM: `'programado'`, `'en_curso'`, `'finalizado'`, `'aplazado'`, `'cancelado'`
- `modalidadPago` ENUM: `'campo'`, `'nomina'`
- `version` (integer) — para optimistic locking en M04
- `resultadoLocal`, `resultadoVisitante` (nullable integers)
- FK a `idTorneo`, `idColegio`, `idDivision`, `idSede` (nullable), `idFormato`

**Soft deletes:** ✅ en `torneos`. ❌ en `partidos` (cambia `estadoPartido` a `'cancelado'`).

**Formatos de designación** (seeder `FormatosDesignacionSeeder`):

| Nombre | maxArbitros |
|---|---|
| Solo | 1 |
| Dupla | 2 |
| Terna | 3 |
| Cuarto-Terna | 4 |

**Roles de partido** (seeder `RolesPartidoSeeder`): `Central`, `Asistente`, `Cuarto`, `VAR`, `AVAR`.

**Reglas:**
- Todas las entidades de M03 son tenant → filtrar por `idColegio`.
- `ReglamentoTorneo` no tiene `updated_at` (gestionado en `booted()`). Solo un reglamento puede tener `esActual = true` por torneo.

---

## M04 — Designaciones ⚠️ CRÍTICO — 🚧 En desarrollo activo

**Es el corazón del sistema.** Si esto falla, el producto no sirve.

**Responsabilidad:** asignar árbitros a partidos según formato, disponibilidad y categoría.

### Entidades reales implementadas

| Entidad | Tabla | Estado |
|---|---|---|
| `Designacion` | `designaciones` | ✅ Migración + modelo + controller |
| `HistorialDesignacion` | `historial_designaciones` | ✅ Migración + modelo |
| `DisponibilidadArbitro` | `disponibilidad_arbitros` | ✅ Migración + modelo + controller |
| `IndisponibilidadExtraordinaria` | `indisponibilidades_extraordinarias` | ✅ Migración + modelo |
| `FormatoDesignacion` | `formatos_designacion` | ✅ Catálogo con seeder |
| `RolPartido` | `roles_partido` | ✅ Catálogo con seeder |
| `ConfiguracionColegio` | `configuracion_colegio` | ✅ Configuración por colegio |

### Estructura real de `Designacion`

Una designación = **un árbitro en un rol para un partido**. No hay tabla pivot separada.

```
designaciones:
  idDesignacion, idPartido, idArbitro, idRol, idColegio
  estadoDesignacion, motivoRechazo
  fechaConfirmacion, fechaRechazo
  notificacionEnviada, fechaNotificacion
  idUsuarioDesignador
```

Para asignar una terna completa → 3 registros en `designaciones` (uno por árbitro/rol).

Constraint: `UNIQUE(idPartido, idArbitro)` — un árbitro no puede tener dos roles en el mismo partido.

### Estados reales

**`estadoDesignacion`:** `'pendiente'`, `'confirmada'`, `'rechazada'`

**`estadoPartido`** (constantes en modelo `Partido`):

| Constante | Valor |
|---|---|
| `ESTADO_PROGRAMADO` | `'programado'` |
| `ESTADO_EN_CURSO` | `'en_curso'` |
| `ESTADO_CONFIRMADO` | `'confirmado'` |
| `ESTADO_CRITICO` | `'critico'` |
| `ESTADO_APLAZADO` | `'aplazado'` |
| `ESTADO_CANCELADO` | `'cancelado'` |
| `ESTADO_FINALIZADO` | `'finalizado'` |

**Nota:** `'confirmado'` y `'critico'` son constantes del modelo pero no están en el ENUM de la migration original — se calculan como lógica de negocio (`estaCompleto()`, `esCritico()`).

### Disponibilidad

`DisponibilidadArbitro`: el árbitro registra disponibilidad por fecha y franja horaria.
- `franjaHoraria` ENUM: `'am'`, `'pm'`, `'noche'`, `'am_pm'`, `'am_noche'`, `'pm_noche'`, `'todo_el_dia'`
- Unique: `(idArbitro, fechaDisponibilidad)`

`IndisponibilidadExtraordinaria`: bloqueos puntuales (viajes, lesiones, etc.).

### Optimistic locking

`partidos.version` (integer) — para evitar condiciones de carrera al cambiar estado. El controller verifica que la versión del request coincide con la de la DB antes de actualizar.

### Rutas implementadas

```
GET  /designaciones              → index (lista de partidos con estado de designación)
GET  /designaciones/crear        → crearPartido (formulario nuevo partido)
POST /designaciones              → guardarPartido
GET  /designaciones/{id}         → show (detalle partido + asignación árbitros)
POST /designaciones/{id}/asignar → asignarArbitro (AJAX)
DELETE /designacion/{id}         → quitarDesignacion (AJAX)
PUT  /designaciones/{id}/estado  → cambiarEstadoPartido (AJAX)

GET  /mis-partidos               → árbitro ve sus designaciones
POST /mis-partidos/{id}/confirmar → árbitro confirma
POST /mis-partidos/{id}/rechazar  → árbitro rechaza

GET  /api/torneos/{id}/divisiones            → AJAX
GET  /api/torneos/{id}/sedes                 → AJAX
GET  /api/partidos/{id}/arbitros-disponibles → AJAX
```

### Reglas de negocio críticas

1. **Árbitro designable:** `estadoArbitro` debe tener `permiteDesignar = true` en `estados_arbitro`. Estados bloqueantes: `'proceso_ingreso'`, `'suspendido'`, `'retirado'`.
2. **Disponibilidad:** verificar `DisponibilidadArbitro` y `IndisponibilidadExtraordinaria` antes de asignar.
3. **No doble asignación:** constraint `UNIQUE(idPartido, idArbitro)` en DB + validación en controller.
4. **Notificaciones por email:** al crear designación → email vía Resend al árbitro. Implementado con Jobs/Events/Mails.
5. **Historial:** toda acción (crear, confirmar, rechazar, cambiar estado partido) se registra en `historial_designaciones`.

### Mensaje para Claude cuando se trabaje en M04

> Lee este archivo COMPLETO antes de escribir una sola línea en M04. La estructura real es diferente al diseño original del skill — hay una designación por árbitro/rol (no una tabla pivot separada). Verificar siempre: filtro `idColegio`, optimistic lock en `version`, y que los estados usados coincidan con los ENUMs reales (`'pendiente'`, `'confirmada'`, `'rechazada'`).

---

## M05 — Académico ⬜ No implementado

**Responsabilidad:** capacitaciones, cursos, asistencia de árbitros a formación.

**Estado:** solo rutas placeholder que redirigen al dashboard. No hay migraciones ni modelos.

**Entidades planeadas:** `Curso`, `SesionCurso`, `AsistenciaCurso`.

**Quien opera:** rol `'tecnico'` (permiso `ver-academico`, `crear-academico`).

---

## M06 — Finanzas ⬜ No implementado

**Responsabilidad:** pagos a árbitros por partidos, tarifas, balance del colegio.

**Estado:** solo rutas placeholder. No hay migraciones ni modelos.

**Quien opera:** rol `'tesorero'` (permisos `ver-finanzas`, `crear-finanzas`).

**Nota:** las `TarifaTorneo` de M03 son un precursor — definen cuánto paga cada torneo por división/formato.

---

## M07 — Sanciones ⬜ No implementado

**Responsabilidad:** sanciones disciplinarias a árbitros, bloqueo de designaciones.

**Estado:** solo rutas placeholder. No hay migraciones ni modelos.

**Quien opera:** rol `'sanciones'` (permisos `ver-sanciones`, `crear-sanciones`).

**Cruce con M04:** un árbitro sancionado no puede recibir designaciones en el período de sanción. Pendiente implementar.

---

## M08 — Superadmin 🚧 Parcialmente implementado

**Responsabilidad:** panel del superadmin de NovaReef. Gestión de colegios, suscripciones, métricas.

**Quien opera:** guard `admin` (NO guard `web`).

**Lo que existe:**
- Login admin con throttle
- 2FA con Google Authenticator (setup, activar/desactivar, verificación en login)
- Dashboard (vista básica)
- Placeholder para colegios, planes, usuarios, logs

**Prefijo URL:** `novareef-panel` (configurable vía `ADMIN_PREFIX` en `.env`).

**Reglas:**
- Rutas bajo `routes/admin.php`, nunca en `web.php`.
- El admin NO puede ver datos personales de árbitros de un colegio.
- `ADMIN_MAX_INTENTOS=3`, `ADMIN_BLOQUEO_SEGUNDOS=300` (configurable en `.env`).

---

## Cronograma de referencia (18 semanas)

```
Fase 0  (1-2)    → Config inicial, setup
Fase 1  (3-4)    → Auth + M01 Colegios         ✅
Fase 2  (5-6)    → M02 Árbitros                ✅
Fase 3  (7-8)    → M03 Torneos                 ✅
Fase 4  (9-11)   → M04 Designaciones ⚠️        🚧 en curso
Fase 5  (12-13)  → M05 Académico               ⬜
Fase 6  (14-15)  → M06 Finanzas                ⬜
Fase 7  (16)     → M07 Sanciones               ⬜
Fase 8  (17)     → M08 Superadmin completo      🚧 parcial
Fase 9  (18)     → Activar Stancl subdominios + QA + Deploy
```
