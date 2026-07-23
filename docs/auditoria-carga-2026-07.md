# Auditoría de carga — NovaReef (julio 2026)

Simulación de entorno real: 5 colegios nuevos (uno por plan) + refuerzo de
ASOCAFA a 150 árbitros, con torneos/partidos/designaciones con mezcla
realista de estados. Objetivo: encontrar y corregir problemas reales de
rendimiento y correctitud con volumen, no solo sembrar datos.

**Credenciales de todas las cuentas creadas**: `storage/app/credenciales-datos-carga.md`
(todas con password `password`).

## Resumen del dataset sembrado

| Colegio | Plan | Árbitros | Cuentas admin | Partidos |
|---|---|---|---|---|
| ASOCAFA | GodMode | 150 | 5 (sin veedor) | 0 (no se generaron, por pedido explícito) |
| Liga de Árbitros del Valle (NR001) | Rookie | 40 (tope del plan; se pidieron 60) | 1 (tope del plan) | 40 |
| Colegio de Árbitros de Antioquia (NR002) | Goliath | 100 (tope del plan; se pidieron 110) | 4 (tope del plan) | 90 |
| Corporación Arbitral de Santander (NR003) | Zenith | 160 | 6 | 150 |
| Federación Arbitral del Eje Cafetero (NR004) | GodMode | 200 | 6 | 200 |
| Asociación de Árbitros de la Costa (NR005) | Zenith | 150 | 6 | 300 |

**Totales**: 800 árbitros, 780 partidos, 1560 designaciones, 325 partidos con
veedor asignado (0 en ASOCAFA, confirmado).

Estados de partido (todos los colegios excepto ASOCAFA): 515 finalizado, 127
programado, 100 cancelado, 38 aplazado — mezcla deliberadamente distinta por
colegio (ver `SembrarDatosCargaCommand::sembrarColegiosNuevos()`).

---

## Simulación operativa real: "un colegio real un día cualquiera"

La primera pasada (índices/caché/rate-limit) midió rendimiento técnico sobre
datos ya sembrados, pero no ejercitó el ciclo de vida real de un ejecutivo
usando el sistema: generar nómina, cobrar/pagar, sancionar, dar clase,
manejar un imprevisto. Esta sección lo hace sobre la **Federación Arbitral
del Eje Cafetero** (idColegio=10, plan GodMode, 200 árbitros, 200 partidos —
el colegio más completo del dataset), usando los Services reales tal como
los usaría un controller, no atajos.

### 🔴 Hallazgo grave: la nómina se pierde en silencio si el flujo se salta

Al intentar generar la nómina de los 100 partidos finalizados del colegio
(`FinanzasService::generarMovimientosPorFinalizacionPartido()` sobre cada
uno), el resultado fue **0 movimientos financieros generados de 100
partidos**, sin ningún error. Dos causas combinadas, ambas silenciosas:

1. **Sin tarifas configuradas** (`tarifas_torneo` estaba vacía en toda la
   BD). `DesignacionService::calcularPago()` devuelve `valor: null` cuando no
   encuentra tarifa para la combinación división+rol+formato, y
   `FinanzasService` **omite el movimiento con un `Log::warning()`** — nunca
   un error visible para el ejecutivo. Un colegio real que activa modalidad
   "nómina" sin configurar tarifas primero pierde el pago de *todos* sus
   partidos sin saberlo hasta que un árbitro reclame que no le pagaron.
2. **Designaciones nunca confirmadas**: `generarMovimientosPorFinalizacionPartido()`
   solo paga las designaciones en estado `confirmada`
   (`$partido->designacionesConfirmadas()`). Las 200 designaciones de los
   partidos finalizados del colegio estaban en `pendiente` — nunca pasaron
   por `DesignacionService::confirmarDesignacion()`. **Esto por sí solo no
   es explotable desde la UI real**: revisé `PartidoStateMachine` y
   confirmé que `'confirmado'` nunca es un destino manual del selector de
   estado (`transicionesManuales()` lo excluye siempre) — la única vía real
   para llegar a `confirmado` es que todos los árbitros confirmen. Pero
   **a nivel de código no hay ninguna guarda que lo impida** si alguna vía
   futura (un comando, un job, una migración de datos) llama
   `PartidoStateMachine::transicionarCon($partido, 'finalizado', ...)`
   saltándose la UI — como hizo mi propio seeder. La única salvaguarda real
   hoy es que `generarMovimientosPorFinalizacionPartido()` filtra por
   confirmadas y avisa solo al log.

**Verificado que el flujo correcto sí funciona bien**: al confirmar
manualmente 40 designaciones de 20 partidos (simulando que los árbitros sí
confirmaron, como pasaría en uso real) y volver a correr la generación de
nómina, se generaron exactamente 40 movimientos financieros correctos, con
el egreso por rol calculado según tarifa (Central $120.000, Asistente
$90.000 en la simulación).

**Recomendación**: el `Log::warning()` de tarifa faltante debería ser visible
para el ejecutivo, no solo en logs de servidor — por ejemplo, un contador de
"partidos finalizados sin nómina generada" en el dashboard o un aviso al
finalizar el partido si su división/rol/formato no tiene tarifa. Es la
diferencia entre "el colegio decide no pagar" y "el colegio no sabe que dejó
de pagar".

### Balance financiero y compensación de deuda — funcionan correctamente

`ReporteFinanzasService::balanceGeneral()` sobre 200 árbitros: **67ms, 5
queries**, sin problema de N+1 (usa `leftJoinSub`). Cifras correctas: un
árbitro con 2 partidos Central pagados a $120.000 mostró `leDebemos:
240000`.

Probé el flujo real de compensación de deuda
(`FinanzasService::compensarDeudaConNomina()`): se creó una mensualidad
pendiente de $50.000 para un árbitro con $240.000 a favor, se compensó, y el
resultado fue exacto — mensualidad saldada a $0, saldo a favor del árbitro
bajó a $190.000. La aritmética es sólida y el diseño (compensación explícita,
sin neteo automático oculto — ver comentario en el propio método) es
razonable.

### Sanciones — funciona correctamente, engancha bien a Finanzas

Se creó una sanción sin multa (llegada tarde) y una con multa económica
($100.000, conducta antideportiva) sobre árbitros reales del colegio.
Ambas correctas: la sanción con multa generó su `MovimientoFinanciero`
(categoría `multa`, estado `pendiente`) y lo enganchó a `idMovimientoFinanciero`
en la misma transacción. Se confirmó la regla de negocio ya documentada en
CLAUDE.md: el árbitro con la sanción grave **sigue siendo designable**
(`puedeSerDesignado(): true`) — comportamiento correcto, sanciones es un
registro disciplinario/económico puro, no bloquea designaciones. El flujo de
"cumplir" una sanción transiciona el estado correctamente.

### Académico — funciona correctamente, costo medido con volumen real

Al crear una sesión académica dirigida a "todos" en un colegio de 200
árbitros: **200 registros de asistencia `ausente` generados en 39ms** (es
esperado — `SesionAcademicaService::crearSesion()` genera un registro por
cada árbitro del colegio al crear la sesión, documentado así en el propio
código). Se simularon 50 escaneos de carnet reales (modo scanner) sobre esa
sesión: **838ms total (~16.7ms por escaneo)**, sin errores. Al cerrar la
sesión, el conteo final fue exacto: 50 presentes + 150 ausentes = 200. Sin
inconsistencias.

### Designación crítica por rechazo de último momento — funciona correctamente

Se simuló el caso real más común de imprevisto: un árbitro rechaza su
designación en un partido ya `programado` ("tuve una emergencia familiar").
`DesignacionService::rechazarDesignacion()` escaló el partido a `critico`
automáticamente, quedó reflejado en el conteo de críticos del dashboard, y el
historial generó 3 entradas legibles y en orden correcto (rechazo → cambio de
estado → creación original). Sin problemas.

### 🟡 Hallazgo propio: el caché que yo mismo agregué podía ocultar un partido crítico

Al repetir el escenario anterior con el caché de `DashboardService::paraEjecutivo()`
ya poblado (ver sección de caché más abajo), confirmé un desfase real: un
segundo rechazo que llevó los partidos críticos reales de 1 a 2 seguía
mostrando "1 crítico" en el dashboard cacheado — el TTL de 60s aplicaba
también al conteo de críticos, que es información urgente (un partido
necesita reasignación antes de su hora), no un simple número de reporte.
**Corregido**: `criticosCount` se sacó del bloque cacheado y se recalcula en
cada carga (una query barata, ya con `idx_partidos_estado`) mientras el
resto del payload (finanzas, sanciones, académico — las partes caras)
mantiene el caché de 60s. Verificado tras la corrección: el dashboard
refleja el conteo real inmediatamente, sin esperar el TTL.

---

## Hallazgos y correcciones, módulo por módulo

### Herramienta de seeding (`app/Console/Commands/SembrarDatosCargaCommand.php`)

**Bug 1 — `Event::fake()` rompía la autogeneración de `codigoCarnet`.**
Primera corrida: `SQLSTATE[HY000]: Field 'codigoCarnet' doesn't have a
default value` en el primer árbitro creado. Causa real: el comando usaba
`Event::fake()` para evitar que `broadcast()` intentara conectar a Reverb
(que no corre en este entorno). Pero `Event::fake()` apaga el dispatcher de
eventos **completo**, incluidos los model events de Eloquent
(`creating`/`saving`) — y `Arbitro::booted()` depende de
`static::creating()` para autogenerar `codigoCarnet` y `estadoArbitro`. El
error de SQL no tenía relación aparente con la causa real; se confirmó
aislando el problema en `tinker` reproduciendo el mismo `Event::fake()`.
**Corrección**: se quitó `Event::fake()` del comando — se confirmó que
`broadcast()` no lanza excepción cuando Reverb no está corriendo, así que no
hacía falta neutralizarlo. Se mantienen `Mail::fake()` (evita correos reales
vía Resend) y `Queue::fake()` (evita encolar jobs reales en la tabla `jobs`).

**Bug 2 — credenciales inconsistentes en corridas idempotentes.**
La rama "el colegio ya existe, reutilizar" no fijaba la password común ni
registraba la credencial del ejecutivo — porque ese código solo vivía en la
rama "colegio nuevo". Tras la primera corrida (que falló a mitad de camino
por el Bug 1), el colegio NR001 quedó creado pero con la password aleatoria
real de `ColegioService::registrar()`, invisible en el archivo de
credenciales. Mismo problema con el ejecutivo de ASOCAFA (preexistía antes
de este comando). **Corrección**: ambas ramas ahora fijan la password común
y registran la credencial; el archivo final se regeneró desde el estado real
de la BD para no depender de una nueva corrida completa.

**Hallazgo de negocio (no bug) — plan Rookie con 1 sola cuenta admin es
restrictivo.** `LimiteService::ROLES_ADMIN` cuenta ejecutivo + tesorero +
designador + sanciones + tecnico + veedor contra el mismo cupo
`limiteCuentasAdmin`. Con Rookie (`limiteCuentasAdmin=1`), el propio
ejecutivo agota el cupo — no se puede crear ningún designador/tesorero
adicional sin subir de plan. Es el comportamiento correcto y esperado de
`LimiteService` (confirmado: bloqueó exactamente como debía, con log claro),
pero vale la pena que el negocio confirme que es la intención — un colegio
Rookie de verdad puede necesitar más de 1 cuenta operativa incluso siendo el
plan más económico.

### Árbitros — índices

**Medido con `EXPLAIN` sobre datos reales** (idColegio=10, GodMode, 200 árbitros):

- `WHERE arbitros.idColegio = ? AND arbitros.estadoArbitro = ?` (query real
  de `ArbitroController::index()`): antes escaneaba las 200 filas del
  colegio completo (`Using temporary; Using filesort`, índice de colegio
  solo). Con el índice compuesto nuevo, baja a 139 filas resueltas por índice
  (`idx_arbitros_colegio_estado`, tipo `ref`). El filesort restante es sobre
  `usuarios.nombreUsuario` (tabla joineada, no resoluble por un índice de
  `arbitros`).
- **Corrección**: migración
  `2026_07_22_000020_add_index_idcolegio_estado_to_arbitros_table.php` —
  índice compuesto `(idColegio, estadoArbitro)`.

### Usuarios / cuentas admin — índices

**Medido**: `WHERE idColegio = ? AND rolUsuario = ? AND estadoUsuario = ?`
(usada por `LimiteService::cuentasAdminActivas()` en cada alta de cuenta
admin, y por cualquier filtro de rol dentro de un colegio) escaneaba las 206
filas del colegio (solo índice de `idColegio`). Con el índice compuesto
nuevo: **206 filas → 1 fila exacta**, acceso `ref` directo.

**Corrección**: migración
`2026_07_22_000030_add_index_idcolegio_rol_to_usuarios_table.php` — índice
compuesto `(idColegio, rolUsuario)`.

### Partidos / Torneos — índice creado pero no confirmable con este dataset

Se agregó `2026_07_22_000040_add_index_torneo_estado_to_partidos_table.php`
(`idTorneo, estadoPartido, fechaPartido`) razonando sobre
`ReporteDesignacionesService::listadoPartidosDeTorneo()`. **Honestidad del
resultado**: con `EXPLAIN` real, MySQL **no eligió** el índice nuevo — siguió
usando `idx_partidos_colegio`. Causa: el seeder crea **un solo torneo por
colegio** con todos sus partidos, así que `idTorneo` e `idColegio` tienen
casi la misma cardinalidad en este dataset (5 torneos, 6 colegios) — filtrar
por cualquiera de los dos descarta prácticamente el mismo conjunto de filas,
y el optimizador prefiere el índice que ya conocía. El índice se mantiene
porque el razonamiento sigue siendo válido para un colegio real con múltiples
torneos simultáneos/históricos (el caso común en producción), pero **queda
pendiente de confirmar con un dataset que tenga varios torneos por colegio**
— limitación del seeder, no evidencia de que el índice sea inútil.

**No se tocó** `suscripciones` — con solo 6 filas en toda la BD (una por
colegio), cualquier índice ahí sería ruido sin beneficio medible. Se
documenta la decisión de *no* indexar en vez de indexar sin evidencia.

### Dashboards / reportes agregados — caché

**Medido**: `DashboardService::paraEjecutivo()` (colegio con 200 árbitros y
200 partidos) — **107ms, 26 queries** por carga. Es el dashboard del rol más
usado del sistema, cargado en cada visita al panel.

**Corrección**: `Cache::remember()` con TTL de 60s, clave por colegio
(`dashboard.ejecutivo.{idColegio}`). Mismo patrón aplicado a
`ReporteDesignacionesService::gridTorneosConConteos()` (listado de
designaciones sin filtro de torneo). Se eligió TTL corto sobre invalidación
manual dispersa: este payload se alimenta de escrituras en Finanzas,
Árbitros, Designaciones, Sanciones y Académico simultáneamente — invalidar en
cada punto de escritura de 5 módulos distintos sería frágil y fácil de
olvidar en un módulo nuevo; 60s de posible desfase es aceptable para datos
agregados de dashboard.

**Medido después**: segunda llamada (cache hit) — **1.43ms, 1 query** (la
propia lectura de caché, `CACHE_STORE=database` en este entorno). **Mejora
de ~75x** en la carga repetida del dashboard.

No se cacheó nada más — se midió antes de decidir en cada caso (`bolsillos()`
ya corría en 26ms/5 queries, `gridTorneosConConteos()` en 24ms/1 query;
ambos razonables por sí solos, el problema real era la composición de
múltiples llamadas en `paraEjecutivo()`).

**Corrección posterior (encontrada en la simulación operativa, ver sección
arriba)**: `criticosCount` — el número de partidos en estado crítico — se
sacó del bloque cacheado. Confirmado con un escenario real (dos rechazos de
designación seguidos) que el TTL de 60s podía dejar el dashboard mostrando un
crítico de menos justo cuando más urgente era verlo. Se recalcula en cada
carga (query barata, ya indexada) mientras el resto del payload sigue
cacheado.

### Rate limiting (`ProtegerEscrituraMasiva`)

Se escribió `tests/Feature/ProtegerEscrituraMasivaTest.php` con tráfico HTTP
real (sin bypasear el middleware) — 3 tests, todos pasan:

1. **Límite general**: 35 escrituras seguidas del mismo usuario → exactamente
   30 pasan, las siguientes 5 responden 429 JSON. Confirmado con el
   comportamiento real de producción (`resources/js/shared/theme.js`, que sí
   envía `Accept: application/json`).
2. **Override de académico**: 40 escaneos de carnet en la misma sesión
   (simulando un colegio grande marcando asistencia) → 0 bloqueados, gracias
   al override `academico.scanner` (500/60s) ya existente.
3. **Aislamiento por usuario**: el cupo agotado de un usuario no afecta a
   otro usuario del mismo colegio.

**Hallazgo del propio proceso de escritura del test (no del middleware)**:
la primera versión del test usaba `$this->patch()` en vez de
`$this->patchJson()` — sin el header `Accept: application/json`, el
middleware no puede distinguir una llamada AJAX de una navegación normal y
responde con `redirect 302 + flash error` en vez de `429 JSON`. El middleware
bloqueaba correctamente en ambos casos (confirmado depurando con
`RateLimiter::attempts()` en vivo, bloqueo exacto en el intento #31); lo que
cambiaba era el *formato* de la respuesta de bloqueo, no si bloqueaba. Esto
es correcto y coincide con cómo el JS real del proyecto llama a esa ruta — no
requirió ningún cambio en `ProtegerEscrituraMasiva`.

**Conclusión**: `ProtegerEscrituraMasiva` funciona como está documentado, sin
necesidad de ajustar límites — ni el general ni el override de académico
mostraron falsos positivos con volumen realista.

### Módulos cubiertos en la simulación operativa

Finanzas (nómina + compensación de deuda), Sanciones (con y sin multa) y
Académico (sesión + asistencia por scanner) se ejercitaron con datos reales
sobre la Federación Arbitral del Eje Cafetero — ver la sección "Simulación
operativa real" más arriba para el detalle completo, incluido el hallazgo
grave de nómina silenciosa.

No se replicó la misma simulación operativa en el resto de colegios
sembrados (solo en el elegido para esta pasada) ni en ASOCAFA — sería el
siguiente paso natural si se quiere confirmar que los mismos hallazgos (o
ausencia de ellos) se repiten con otra combinación de plan/volumen.

---

## Verificación final

- `php artisan test`: **359 passed**, 0 fallos, sin regresiones — corrido de
  nuevo después de la corrección del desfase de caché encontrada en la
  simulación operativa.
- Confirmado manualmente: ASOCAFA con exactamente 150 árbitros, 5 cuentas
  admin (ejecutivo preexistente + tesorero/designador/sanciones/tecnico
  nuevas, sin veedor), 0 partidos con `idVeedor` asignado.
- Todas las passwords de la muestra verificada (`Hash::check`) funcionan
  correctamente contra `password`.
- Simulación operativa real sobre la Federación Arbitral del Eje Cafetero:
  1 hallazgo grave (nómina silenciosa sin tarifas/confirmaciones, con causa
  raíz identificada y flujo correcto verificado), 1 hallazgo propio corregido
  en el momento (desfase de caché en `criticosCount`), Sanciones/Académico/
  Balance/Compensación de deuda verificados sin problemas.

## Resumen ejecutivo — qué tiene lógica, qué no, qué puede fallar

**Tiene lógica y funciona bien**: la máquina de estados de partidos (rechazo
→ crítico, con historial trazable), el balance financiero con 200 árbitros
(sin N+1), la compensación de deuda (aritmética correcta, diseño explícito
sin neteo oculto), sanciones con multa enganchada a Finanzas sin bloquear
designabilidad, y la asistencia académica por scanner con conteo exacto.

**No tiene lógica / puede fallar sin avisar**: un colegio que active
modalidad de pago "nómina" sin configurar tarifas por división/rol/formato
**pierde el pago de todos sus partidos en silencio** — solo un
`Log::warning()` en servidor, nada visible para el ejecutivo. Es el hallazgo
más serio de esta pasada porque es exactamente el tipo de error que un
colegio real cometería (olvidar configurar tarifas al arrancar) y solo lo
notaría cuando un árbitro reclame que no le pagaron, potencialmente semanas
después con decenas de partidos ya afectados.

**Cómo se corrigió/mejoró**: el hallazgo de nómina queda documentado con
recomendación (hacerlo visible al ejecutivo, no solo loguearlo) pero no se
implementó el fix en esta pasada — es un cambio de UX/alertas que amerita
decidir dónde mostrarlo (dashboard, al finalizar el partido, ambos). El
hallazgo de caché sí se corrigió de inmediato porque era una regresión
introducida por este mismo trabajo, no una decisión de producto pendiente.
