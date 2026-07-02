# Tenancy — Stancl/Tenancy v3 en NovaReef

## Estado actual vs objetivo

NovaReef tiene **dos estados de tenancy**. Entender cuál es cuál es crítico:

| | Estado actual (en código hoy) | Estado objetivo (para producción) |
|---|---|---|
| Aislamiento | Filtros manuales `idColegio` en cada controller | Global Scopes automáticos de Stancl |
| Identificación del tenant | `Auth::user()->idColegio` | Subdominio `<colegio>.novareef.com` |
| Rutas | Todo en `routes/web.php` | Rutas tenant en `routes/tenant.php` |
| Trait en modelos | Ninguno — `idColegio` a mano | `BelongsToTenant` en todos los modelos tenant |
| Riesgo | Si un controller olvida el filtro → fuga de datos | Imposible olvidar — el scope lo hace automático |

**Stancl está instalado** (`stancl/tenancy ^3.10`), las tablas `tenants` y `domains` existen, y `Colegio` tiene columna `tenantId` FK a `tenants`. La infraestructura existe. Lo que no está activo: la identificación por subdominio y los Global Scopes.

---

## Cómo funciona HOY (estado actual)

### Identificación del tenant

No hay subdominio. Cuando un usuario se autentica, su `idColegio` viene de la tabla `usuarios`:

```php
// En cualquier controller de colegio:
$idColegio = Auth::user()->idColegio;

$torneos = Torneo::where('idColegio', $idColegio)->get();
```

### Regla crítica actual

**Todo controller que accede a datos de tenant DEBE incluir el filtro `idColegio`.**

```php
// ✅ Correcto
$arbitros = Arbitro::where('idColegio', Auth::user()->idColegio)->get();

// ❌ FUGA DE DATOS — nunca hacer esto
$arbitros = Arbitro::all();
```

### Middleware de verificación

Middleware `verificar.colegio` confirma que el usuario tiene un `idColegio` válido y activo antes de entrar a rutas del panel. Definido en `bootstrap/app.php`.

---

## Cómo funcionará en PRODUCCIÓN (estado objetivo)

### Modelo de tenancy objetivo

**Single database, multi-tenant con Global Scopes.** Una sola base de datos compartida. El aislamiento es automático via el trait `BelongsToTenant`.

### Identificación del tenant (a implementar)

```
liga-bogota.novareef.com → tenant idColegio=1
fcf.novareef.com         → tenant idColegio=2
novareef.com             → landing pública o admin (NO tenant)
```

### Cambios requeridos en `config/tenancy.php`

```php
// Cambiar la identificación de dominio
'identification' => [
    'default_middleware' => InitializeTenancyBySubdomain::class,
    'central_domains' => ['novareef.com', 'www.novareef.com'],
],
```

### Trait `BelongsToTenant` (a agregar a todos los modelos tenant)

```php
use Stancl\Tenancy\Database\Concerns\BelongsToTenant;

class Arbitro extends Model
{
    use BelongsToTenant;  // ← agrega el Global Scope automáticamente
    use SoftDeletes;

    protected $primaryKey = 'idArbitro';
    protected $table = 'arbitros';
}
```

**Modelos tenant** (necesitarán el trait):
- `Arbitro`, `User`, `Torneo`, `Partido`, `Designacion`
- `DivisionTorneo`, `SedeTorneo`, `TarifaTorneo`, `ReglamentoTorneo`
- `DisponibilidadArbitro`, `IndisponibilidadExtraordinaria`
- `HistorialDesignacion`, `DocumentoArbitro`, `HistorialEstadoArbitro`
- `ConfiguracionColegio`

**Modelos que NO llevan trait** (datos globales):
- `Colegio` (es la entidad raíz del tenant)
- `Admin`, `Plan`, `Suscripcion`
- Catálogos compartidos: `CategoriaArbitro`, `EstadoArbitro`, `RolPartido`, `FormatoDesignacion`

### `routes/tenant.php` (a crear)

```php
// Cuando se active Stancl, mover aquí las rutas de colegio:
Route::middleware([
    'web',
    InitializeTenancyBySubdomain::class,
    PreventAccessFromCentralDomains::class,
    'auth:web',
])->group(function () {
    // todo lo que hoy está en web.php bajo middleware 'auth'
});
```

### Storage por tenant

Archivos de árbitros (fotos, documentos): el path debe incluir `idColegio` para aislamiento físico:

```php
$path = "colegios/{$idColegio}/arbitros/{$idArbitro}/foto.jpg";
```

---

## Checklist para activar Stancl (cuando llegue el momento)

- [ ] Actualizar `config/tenancy.php`: cambiar `central_domains` a `['novareef.com', 'www.novareef.com']` y activar identificación por subdominio
- [ ] Agregar `use BelongsToTenant` a todos los modelos tenant listados arriba
- [ ] Crear `routes/tenant.php` y mover rutas del panel de colegio desde `web.php`
- [ ] Configurar DNS para `*.novareef.com → servidor`
- [ ] Probar desde dos subdominios distintos: cada uno debe ver solo sus datos
- [ ] Eliminar los filtros `where('idColegio', ...)` manuales (el scope los hace automáticos)
- [ ] Verificar que `withoutGlobalScopes()` no aparece en ningún lugar sin justificación

## Errores comunes a evitar

### Acceder a modelos tenant fuera de contexto (futuro con Stancl activo)

```php
// ❌ Mal — en un comando artisan global, no habrá tenant activo
Artisan::command('reporte', function () {
    $arbitros = Arbitro::all(); // Error: no hay tenant
});

// ✅ Bien — iterar por colegio
foreach (Colegio::all() as $colegio) {
    tenancy()->initialize($colegio);
    $arbitros = Arbitro::all();
    tenancy()->end();
}
```

### JOINs con tablas no-tenant

Si haces `join('colegios', ...)` desde un modelo tenant, recuerda que `colegios` no tiene scope. Filtrar explícitamente si hace falta.
