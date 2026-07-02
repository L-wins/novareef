# Database — Convenciones de NovaReef

## Configuración base

- **Motor:** InnoDB (siempre, sin excepción — necesitamos FKs y transacciones)
- **Charset:** `utf8mb4`
- **Collation:** `utf8mb4_unicode_ci`
- **Engine:** MySQL 8+

En `config/database.php` la conexión `mysql` ya tiene estos defaults. El default de Laravel apunta a SQLite — en desarrollo local y producción **siempre usar la conexión `mysql`** via `DB_CONNECTION=mysql` en `.env`.

## Naming de columnas

**Regla absoluta:** columnas de dominio en **camelCase español**, columnas técnicas de Laravel en **snake_case inglés**.

### ✅ Correcto

```php
Schema::create('arbitros', function (Blueprint $table) {
    $table->bigIncrements('idArbitro');
    $table->string('nombreCompleto', 150);
    $table->string('numeroDocumento', 30);
    $table->date('fechaNacimiento');
    $table->unsignedBigInteger('idColegio');

    // Timestamps técnicos de Laravel — snake_case inglés
    $table->timestamps();   // created_at, updated_at
    $table->softDeletes();  // deleted_at
});
```

### ❌ Incorrecto

```php
$table->bigIncrements('id_arbitro');   // snake_case en dominio
$table->string('nombre_completo');     // snake_case en dominio
$table->string('fechaCreacion');       // camelCase en columna técnica (usa created_at)
```

### Excepción: modelo `Admin`

Las columnas de `admins` usan snake_case inglés (`nombre`, `email`, `password`, `ultimo_acceso`, `two_factor_enabled`) porque es una entidad técnica del sistema, no del dominio colombiano. No replicar este patrón en modelos de colegio.

### ¿Por qué este mix?

- **Dominio en español:** el equipo piensa en español, los stakeholders hablan español, los reportes salen en español.
- **Timestamps en inglés:** Laravel los maneja automáticamente. Renombrarlos rompe el framework.

## Primary keys

- Siempre `bigIncrements` (BIGINT UNSIGNED AUTO_INCREMENT).
- Nombre: `id<Entidad>` en camelCase. Ejemplos reales: `idColegio`, `idArbitro`, `idTorneo`, `idUsuario`, `idPartido`, `idDesignacion`, `idDivision`, `idSede`, `idFormato`, `idRol`.
- En el modelo, declarar explícitamente:

```php
protected $primaryKey = 'idArbitro';
protected $keyType    = 'int';
public    $incrementing = true;
```

## Foreign keys

- Mismo nombre que la PK referenciada: `idColegio` apunta a `colegios.idColegio`.
- **Siempre** declarar `onUpdate` y `onDelete` explícitamente.
- Política por defecto:
  - `onUpdate('cascade')` — si cambia la PK, propagar.
  - `onDelete('restrict')` — no permitir borrar padres con hijos.
- Excepciones reales en el código:
  - `usuarios.idColegio` → `onDelete('set null')` (un usuario puede existir sin colegio)
  - `arbitros.idUsuario` → `onDelete('cascade')` (si se borra el usuario, se borra el árbitro)
  - `sedes_torneo.idSede` en `partidos` → `onDelete('set null')` (un partido puede quedar sin sede)

```php
$table->foreign('idColegio')
      ->references('idColegio')
      ->on('colegios')
      ->onUpdate('cascade')
      ->onDelete('restrict');
```

## ENUMs — valores reales

**Regla:** valores en **snake_case minúscula en español**. Nunca capitalizado, nunca inglés.
- ✅ `'activo'`, `'proceso_ingreso'`, `'en_curso'`, `'todo_el_dia'`
- ❌ `'Activo'`, `'EnCurso'`, `'active'`, `'ACTIVO'`

Para validación en requests, usar `Rule::in([...])` con los mismos valores exactos.

### ENUMs existentes en el proyecto

| Tabla | Columna | Valores |
|---|---|---|
| `usuarios` | `rolUsuario` | `'arbitro'`, `'ejecutivo'`, `'tesorero'`, `'designador'`, `'sanciones'`, `'tecnico'`, `'superadmin'` |
| `usuarios` | `estadoUsuario` | `'activo'`, `'inactivo'`, `'suspendido'` |
| `usuarios` | `temaPreferencia` | `'oscuro'`, `'claro'` |
| `arbitros` | `tipoDocumento` | `'cedula'`, `'pasaporte'`, `'extranjeria'` |
| `arbitros` | `tipoVehiculo` | `'carro'`, `'moto'`, `'ambos'` |
| `arbitros` | `estadoArbitro` | `'activo'`, `'inactivo'`, `'suspendido'`, `'retirado'`, `'aprendiz'`, `'proceso_ingreso'` |
| `colegios` | `estadoColegio` | `'activo'`, `'prueba'`, `'suspendido'` |
| `torneos` | `tipoTorneo` | `'local'`, `'zonal'`, `'oficial'` |
| `torneos` | `modalidadPago` | `'campo'`, `'nomina'` |
| `torneos` | `estadoTorneo` | `'proximo'`, `'activo'`, `'finalizado'`, `'cancelado'` |
| `partidos` | `estadoPartido` | `'programado'`, `'en_curso'`, `'finalizado'`, `'aplazado'`, `'cancelado'` |
| `partidos` | `modalidadPago` | `'campo'`, `'nomina'` |
| `designaciones` | `estadoDesignacion` | `'pendiente'`, `'confirmada'`, `'rechazada'` |
| `disponibilidad_arbitros` | `franjaHoraria` | `'am'`, `'pm'`, `'noche'`, `'am_pm'`, `'am_noche'`, `'pm_noche'`, `'todo_el_dia'` |

**Nota:** `estadoArbitro` existe como ENUM en la tabla `arbitros` Y como tabla de catálogo `estados_arbitro` (con etiqueta, color, `permiteDesignar`). La tabla catálogo es la fuente de verdad para la UI; el ENUM en `arbitros` es la restricción de integridad en DB.

## Soft deletes

Tablas que **llevan** soft deletes:
- `usuarios` ✅
- `arbitros` ✅
- `torneos` ✅

Tablas que **NO llevan** soft deletes (borrado lógico no aplica):
- `partidos` — se cambia su `estadoPartido` a `'cancelado'` en lugar de borrar
- `designaciones` — historial gestionado por `historial_designaciones`
- Tablas de catálogo (`roles_partido`, `formatos_designacion`, `estados_arbitro`) — usan campo `esActivo` boolean
- Tablas pivot y de log

En el modelo:

```php
use Illuminate\Database\Eloquent\SoftDeletes;

class Arbitro extends Model
{
    use SoftDeletes;
}
```

## Timestamps

- `created_at`, `updated_at`: con `$table->timestamps()`.
- `deleted_at`: con `$table->softDeletes()`.
- No agregar otros timestamps de dominio con esos nombres. Si necesitas "fecha de aprobación", llámala `fechaAprobacion`, no `approved_at`.
- Excepción: `ReglamentoTorneo` no tiene `updated_at` — gestionado en `booted()`.

## Índices

- Toda FK tiene su índice automático (Laravel lo agrega al declarar `foreign()`).
- Columnas usadas en `WHERE` frecuente: índice explícito. Ejemplos reales:
  ```php
  $table->index('idColegio',    'idx_torneos_colegio');
  $table->index('estadoTorneo', 'idx_torneos_estado');
  $table->index('fechaPartido', 'idx_partidos_fecha');
  ```
- Constraints de unicidad compuestas donde aplica:
  ```php
  $table->unique(['idPartido', 'idArbitro'], 'uq_designacion_partido_arbitro');
  $table->unique(['idArbitro', 'fechaDisponibilidad'], 'uq_disponibilidad_arbitro_fecha');
  ```

## Migrations: estilo

- Una migration = una operación lógica.
- Nombrar descriptivamente: `2026_05_30_000070_create_partidos_table.php`.
- Siempre declarar `$table->engine`, `$table->charset`, `$table->collation` al inicio de cada `Schema::create`.
- En `down()`, revertir exactamente lo que hizo `up()` (primero `dropForeign`, luego `dropIfExists`).

## Seeders

- Un seeder por entidad de dominio o catálogo.
- Usar `updateOrCreate` o `firstOrCreate` (no `create`) — idempotente, re-ejecutable.
- Orden en `DatabaseSeeder`:
  1. `AdminSeeder`
  2. `RolesPermisosSeeder`
  3. `ColegioSeeder`
  4. `CategoriaArbitroSeeder`
  5. `PlanSeeder`
  6. `RolesPartidoSeeder`
  7. `FormatosDesignacionSeeder`
  8. `EstadoArbitroSeeder`
  9. `SuscripcionColegioSeeder`
  10. `ConfiguracionColegioSeeder`

## Convenciones Eloquent

- `protected $table` siempre declarado — las tablas están en español y Laravel pluraliza en inglés (ej: `Designacion` → Laravel diría `designacions` ❌, declarar `$table = 'designaciones'`).
- `protected $primaryKey` siempre declarado (no es `id`).
- `protected $keyType = 'int'` y `public $incrementing = true` siempre declarados explícitamente.
- `protected $fillable` siempre declarado — nunca dejar mass assignment abierto.
- `protected $casts` para fechas (`'date'`, `'datetime'`), booleanos, decimales.

## Resumen mental

```
Dominio  → camelCase español  → idArbitro, nombreColegio
Laravel  → snake_case inglés  → created_at, deleted_at
PK       → bigIncrements      → idEntidad
FK       → mismo nombre que PK → idColegio
ENUM     → snake_case minúscula → 'activo', 'proceso_ingreso'
Engine   → InnoDB utf8mb4_unicode_ci (no negociable)
```
