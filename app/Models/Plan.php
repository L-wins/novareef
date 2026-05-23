<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Plan extends Model
{
    protected $table      = 'planes';
    protected $primaryKey = 'idPlan';

    protected $fillable = [
        'nombre',
        'precio',
        'periodicidad',
        'limiteArbitros',
        'modulosJSON',
        'limiteRoles',
        'incluyePaginaWeb',
        'incluyeOnboarding',
        'esVisible',
        'esActivo',
        'orden',
    ];

    protected $casts = [
        'precio'            => 'decimal:2',
        'modulosJSON'       => 'array',
        'incluyePaginaWeb'  => 'boolean',
        'incluyeOnboarding' => 'boolean',
        'esVisible'         => 'boolean',
        'esActivo'          => 'boolean',
    ];

    public function suscripciones(): HasMany
    {
        return $this->hasMany(Suscripcion::class, 'idPlan', 'idPlan');
    }

    public function getLimiteArbitrosTextoAttribute(): string
    {
        return $this->limiteArbitros === null ? 'Ilimitado' : (string) $this->limiteArbitros;
    }

    public function getLimiteRolesTextoAttribute(): string
    {
        return $this->limiteRoles === null ? 'Ilimitado' : (string) $this->limiteRoles;
    }
}
