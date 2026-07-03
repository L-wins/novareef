<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    use HasFactory, HasRoles, Notifiable, SoftDeletes;

    protected $table      = 'usuarios';
    protected $primaryKey = 'idUsuario';
    protected $keyType    = 'int';
    public    $incrementing = true;

    protected $fillable = [
        'idColegio',
        'nombreUsuario',
        'emailUsuario',
        'usernameUsuario',
        'passwordUsuario',
        'telefonoUsuario',
        'rolUsuario',
        'estadoUsuario',
        'temaPreferencia',
        'tokenRecuperacion',
        'tokenExpiracion',
        'dobleFactorActivo',
        'dobleFactorCodigo',
        'ultimoAcceso',
        'must_change_password',
    ];

    protected $hidden = [
        'passwordUsuario',
        'tokenRecuperacion',
        'dobleFactorCodigo',
        'remember_token',
    ];

    protected $casts = [
        'tokenExpiracion'    => 'datetime',
        'ultimoAcceso'       => 'datetime',
        'dobleFactorActivo'    => 'boolean',
        'must_change_password' => 'boolean',
        'rolUsuario'         => 'string',
        'estadoUsuario'      => 'string',
        'temaPreferencia'    => 'string',
        'passwordUsuario'    => 'hashed',
        'deleted_at'         => 'datetime',
    ];

    //  Auth overrides ─

    /**
     * Indica a Laravel qué columna contiene la contraseña para autenticación.
     */
    public function getAuthPasswordName(): string
    {
        return 'passwordUsuario';
    }

    /**
     * Devuelve el valor de la contraseña hasheada para validación.
     */
    public function getAuthPassword(): string
    {
        return $this->passwordUsuario;
    }

    //  Relaciones ─

    public function colegio(): BelongsTo
    {
        return $this->belongsTo(Colegio::class, 'idColegio', 'idColegio');
    }
}
