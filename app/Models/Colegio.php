<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Arbitro;
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

    // ── Relaciones ───────────────────────────────────────────────────────────

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
                fn ($query) => $query->where('estado', 'activa'),
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
        )->where('suscripciones.estado', 'activa')
         ->orderByDesc('suscripciones.fechaVencimiento');
    }
}
