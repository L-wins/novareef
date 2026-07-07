<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Arbitro;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\HasOneThrough;

class Colegio extends Model
{
    protected $table      = 'colegios';
    protected $primaryKey = 'idColegio';
    protected $keyType    = 'int';
    public    $incrementing = true;

    protected $fillable = [
        'tenantId',
        'nombreColegio',
        'codigoColegio',
        'emailColegio',
        'telefonoColegio',
        'direccionColegio',
        'ciudadColegio',
        'departamentoColegio',
        'paisColegio',
        'logoColegio',
        'estadoColegio',
        'fechaSuscripcion',
        'fechaExpiracion',
    ];

    protected $casts = [
        'fechaSuscripcion' => 'date',
        'fechaExpiracion'  => 'date',
        'estadoColegio'    => 'string',
    ];

    //  Accessors ─

    /**
     * URL lista para mostrar el logo: logoColegio guarda una URL externa
     * (cargada por el superadmin) o una ruta de storage (subida por el
     * ejecutivo desde Configuración).
     */
    public function getLogoUrlAttribute(): ?string
    {
        if (! $this->logoColegio) {
            return null;
        }

        if (str_starts_with($this->logoColegio, 'http://') || str_starts_with($this->logoColegio, 'https://')) {
            return $this->logoColegio;
        }

        return \Illuminate\Support\Facades\Storage::disk('public')->url($this->logoColegio);
    }

    //  Relaciones ─

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(\Stancl\Tenancy\Database\Models\Tenant::class, 'tenantId');
    }

    public function arbitros(): HasMany
    {
        return $this->hasMany(Arbitro::class, 'idColegio', 'idColegio');
    }

    public function suscripciones(): HasMany
    {
        return $this->hasMany(Suscripcion::class, 'idColegio', 'idColegio');
    }

    public function suscripcionActiva(): HasOne
    {
        return $this->hasOne(Suscripcion::class, 'idColegio', 'idColegio')
            ->ofMany(
                ['fechaVencimiento' => 'max'],
                fn ($query) => $query->whereIn('estado', Suscripcion::ESTADOS_VIGENTES),
            );
    }

    public function plan(): HasOneThrough
    {
        return $this->hasOneThrough(
            Plan::class,
            Suscripcion::class,
            'idColegio',
            'idPlan',
            'idColegio',
            'idPlan',
        )->whereIn('suscripciones.estado', Suscripcion::ESTADOS_VIGENTES)
         ->orderByDesc('suscripciones.fechaVencimiento');
    }

    public function usuarios(): HasMany
    {
        return $this->hasMany(User::class, 'idColegio', 'idColegio');
    }

    public function configuraciones(): HasMany
    {
        return $this->hasMany(ConfiguracionColegio::class, 'idColegio', 'idColegio');
    }
}
