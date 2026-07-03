<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Plan extends Model
{
    protected $table      = 'planes';
    protected $primaryKey = 'idPlan';

    /** Periodicidades válidas — única fuente de verdad para validaciones y lógica de negocio. */
    public const PERIODICIDADES = ['mensual', 'trimestral', 'semestral', 'anual'];

    protected $fillable = [
        'nombre',
        'precio',
        'periodicidad',
        'limiteArbitros',
        'modulosJSON',
        'limiteCuentasAdmin',
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

    public function getLimiteCuentasAdminTextoAttribute(): string
    {
        return $this->limiteCuentasAdmin === null ? 'Ilimitado' : (string) $this->limiteCuentasAdmin;
    }

    /**
     * Calcula la fecha de vencimiento a partir de una fecha de inicio
     * según la periodicidad del plan. Centraliza esta regla de negocio
     * para que no se repita en controladores ni Actions.
     */
    public function calcularVencimiento(\Illuminate\Support\Carbon $inicio): \Illuminate\Support\Carbon
    {
        return match ($this->periodicidad) {
            'anual'      => $inicio->copy()->addYear(),
            'semestral'  => $inicio->copy()->addMonths(6),
            'trimestral' => $inicio->copy()->addMonths(3),
            default      => $inicio->copy()->addMonth(),
        };
    }
}
