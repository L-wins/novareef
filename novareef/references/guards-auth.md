# Guards y Autenticación — NovaReef

## Dos guards independientes

NovaReef tiene **dos universos de autenticación** que NO se cruzan:

| Guard | Modelo | Tabla | Dónde vive | Para qué |
|---|---|---|---|---|
| `web` | `User` | `usuarios` | Panel de colegio | Usuarios de un colegio (ejecutivo, tesorero, designador, árbitro, etc.) |
| `admin` | `Admin` | `admins` | `novareef-panel/*` (prefijo configurable) | Superadmin de NovaReef (gestiona colegios, no opera ninguno) |

**Regla crítica:** un admin **NO puede** loguearse como un user de colegio sin un mecanismo explícito de impersonación auditado. Y un user de colegio **NUNCA** puede acceder a rutas admin.

## Configuración real en `config/auth.php`

```php
'guards' => [
    'web' => [
        'driver'   => 'session',
        'provider' => 'users',
    ],
    'admin' => [
        'driver'   => 'session',
        'provider' => 'admins',
    ],
],

'providers' => [
    'users' => [
        'driver' => 'eloquent-custom',  // ← CustomUserProvider, NO eloquent estándar
        'model'  => App\Models\User::class,
    ],
    'admins' => [
        'driver' => 'eloquent',
        'model'  => App\Models\Admin::class,
    ],
],
```

**Nota importante:** el provider de `users` usa `'eloquent-custom'` porque `User` tiene columnas de auth no estándar (`emailUsuario`, `passwordUsuario`). El `CustomUserProvider` en `app/Auth/CustomUserProvider.php` sobrescribe `validateCredentials` usando `getAuthPasswordName()`.

## Modelo `User` (guard `web`)

```php
class User extends Authenticatable
{
    use HasFactory, HasRoles, Notifiable, SoftDeletes;

    protected $table      = 'usuarios';   // ← tabla 'usuarios', NO 'users'
    protected $primaryKey = 'idUsuario';

    // Auth overrides — columnas no estándar
    public function getAuthPasswordName(): string { return 'passwordUsuario'; }
    public function getAuthPassword(): string     { return $this->passwordUsuario; }
}
```

**Columnas de auth custom:** `emailUsuario` (email), `passwordUsuario` (password hasheada).

**`User` NO usa `BelongsToTenant`** — el aislamiento es manual por `idColegio` hoy. Cuando se active Stancl, se agregará el trait.

## Modelo `Admin` (guard `admin`)

```php
class Admin extends Authenticatable
{
    use Notifiable;

    protected $table      = 'admins';
    protected $primaryKey = 'idAdmin';
    protected $guard      = 'admin';

    // Columnas en inglés — excepción al naming del dominio
    // nombre, email, password, google2fa_secret, two_factor_enabled, activo, ultimo_acceso
}
```

Admin es una entidad técnica del sistema. Sus columnas **no siguen camelCase español** — usan snake_case inglés. No replicar este patrón en modelos de colegio.

Admin tiene **2FA con Google Authenticator** (`pragmarx/google2fa-laravel`). El secret se guarda en `google2fa_secret` (en `$hidden`). Para acceder: `$admin->getRawOriginal('google2fa_secret')`.

## Roles y permisos (Spatie Permission)

Spatie `HasRoles` está en `User` con `guard_name = 'web'`. **Admin no usa Spatie** — su acceso es todo o nada (guard `admin`).

### 14 permisos registrados

```
ver-arbitros     crear-arbitros    editar-arbitros
ver-torneos      crear-torneos     editar-torneos
ver-designaciones  crear-designaciones
ver-finanzas     crear-finanzas
ver-academico    crear-academico
ver-sanciones    crear-sanciones
```

### 6 roles y sus permisos

| Rol (ENUM en DB) | Permisos |
|---|---|
| `'ejecutivo'` | Todos los 14 permisos |
| `'tesorero'` | ver-arbitros, ver-torneos, ver-designaciones, ver-sanciones, ver-finanzas, crear-finanzas |
| `'designador'` | ver-arbitros, ver-torneos, ver-designaciones, crear-designaciones |
| `'sanciones'` | ver-arbitros, ver-sanciones, crear-sanciones |
| `'tecnico'` | ver-arbitros, ver-academico, crear-academico |
| `'arbitro'` | ver-designaciones, ver-academico, ver-sanciones |

**Nota:** `rolUsuario` es el ENUM propio de la tabla `usuarios`. Spatie sincroniza este rol al usuario mediante `RolesPermisosSeeder`. El rol Spatie y el `rolUsuario` ENUM deben mantenerse en sync.

### Uso en rutas

```php
// En web.php — proteger por permiso Spatie
Route::middleware('permission:ver-arbitros')->group(function () { ... });
Route::middleware('permission:crear-designaciones')->group(function () { ... });
```

### Uso en código

```php
// Verificar permiso
Auth::user()->can('editar-arbitros');

// Verificar rol
Auth::user()->hasRole('ejecutivo');
Auth::user()->rolUsuario === 'ejecutivo';  // forma directa desde columna
```

## Valores ENUM del rol en `usuarios`

```
'arbitro' | 'ejecutivo' | 'tesorero' | 'designador' | 'sanciones' | 'tecnico' | 'superadmin'
```

Todos en **snake_case minúscula**. El `'superadmin'` en la tabla `usuarios` es el usuario ejecutivo del colegio principal que puede gestionar colegios — **no** es el admin del guard `admin`.

## Middleware registrados

En `bootstrap/app.php`:

| Alias | Función |
|---|---|
| `admin.auth` | Verifica guard `admin` activo |
| `verificar.colegio` | Verifica que el usuario tiene `idColegio` válido y colegio activo |
| `verificar.perfil` | Verifica que el árbitro ha completado su perfil antes de acceder |
| `solo.superadmin` | Solo permite acceso a `rolUsuario = 'superadmin'` |
| Web prepend | `BlockResendWebhook` |
| Web append | `VerificarCambioContrasena` |

## Rutas protegidas

### Rutas de colegio (guard `web`) — hoy en `routes/web.php`

```php
Route::middleware(['auth', 'verificar.colegio', 'verificar.perfil'])->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    Route::prefix('arbitros')->middleware('permission:ver-arbitros')->group(function () {
        Route::get('/', ...)->name('arbitros.index');
        Route::get('/crear', ...)->middleware('permission:crear-arbitros');
    });
});
```

**Siempre** especificar el guard explícito en `auth:web` cuando no es el default, o usar simplemente `auth` (el default es `web`).

### Rutas de admin (guard `admin`) — en `routes/admin.php` (cargado via `bootstrap/app.php`)

```php
Route::prefix(config('admin.prefix'))  // 'novareef-panel' por defecto
    ->middleware(['web', 'admin.auth'])
    ->group(function () {
        Route::get('/dashboard', [Admin\DashboardController::class, 'index']);
    });
```

## Login

- Panel de colegio: `POST /login` → `Auth\LoginController` → guard `web`
- Panel admin: `POST /novareef-panel/login` → `Admin\Auth\AdminLoginController` → guard `admin`
- Admin con 2FA activo: después del login se redirige a `novareef-panel/2fa` para verificar TOTP

## Password reset

Ambos brokers configurados en `config/auth.php`, ambos usan la **misma tabla** `password_reset_tokens`:

```php
'passwords' => [
    'users'  => ['provider' => 'users',  'table' => 'password_reset_tokens', 'expire' => 60],
    'admins' => ['provider' => 'admins', 'table' => 'password_reset_tokens', 'expire' => 60],
],
```

## NO hay registro público

- ❌ No existe `/register` en ningún guard.
- ✅ Los `User` de colegio los crea el Ejecutivo desde dentro del panel.
- ✅ Los `Admin` se crean por seeder en setup inicial.
- ✅ Los `Colegio` los crea el superadmin desde el panel admin.

Si en el código aparece una ruta de registro abierta, está mal. Borrarla.

## Checklist de seguridad

Antes de mergear cualquier feature que toque auth:

- [ ] Las rutas privadas de colegio tienen `auth` (o `auth:web`) explícito
- [ ] Las rutas de admin tienen `admin.auth` middleware
- [ ] No hay ruta `/register` pública
- [ ] Cada controller que accede a datos tenant filtra por `Auth::user()->idColegio`
- [ ] Los permisos Spatie están aplicados en rutas sensibles (`permission:xxx`)
- [ ] `abort_unless` en controllers para verificar ownership del recurso
