<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

class Suscripcion extends Model
{
    protected $table      = 'suscripciones';
    protected $primaryKey = 'idSuscripcion';

    /**
     * Estados que cuentan como suscripción vigente (colegio con acceso al sistema).
     * Única fuente de verdad — evita repetir el whereIn en cada controller.
     */
    public const ESTADOS_VIGENTES = ['activa', 'trial'];

    protected $fillable = [
        'idColegio',
        'idPlan',
        'fechaInicio',
        'fechaVencimiento',
        'estado',
        'notas',
    ];

    protected $casts = [
        'fechaInicio'      => 'date',
        'fechaVencimiento' => 'date',
    ];

    public function colegio(): BelongsTo
    {
        return $this->belongsTo(Colegio::class, 'idColegio', 'idColegio');
    }

    public function plan(): BelongsTo
    {
        return $this->belongsTo(Plan::class, 'idPlan', 'idPlan');
    }

    public function estaVigente(): bool
    {
        return $this->fechaVencimiento !== null
            && $this->fechaVencimiento->gte(Carbon::today());
    }

    public function scopeActivas(Builder $query): Builder
    {
        return $query->whereIn('estado', self::ESTADOS_VIGENTES);
    }

    public function scopeEnTrial(Builder $query): Builder
    {
        return $query->where('estado', 'trial');
    }
}
