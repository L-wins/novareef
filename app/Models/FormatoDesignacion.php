<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class FormatoDesignacion extends Model
{
    use HasFactory;

    protected $table        = 'formatos_designacion';
    protected $primaryKey   = 'idFormato';
    protected $keyType      = 'int';
    public    $incrementing = true;

    protected $fillable = [
        'nombre',
        'descripcion',
        'maxArbitros',
        'esActivo',
        'orden',
    ];

    protected $casts = [
        'maxArbitros' => 'integer',
        'esActivo'    => 'boolean',
        'orden'       => 'integer',
    ];

    public function tarifas(): HasMany
    {
        return $this->hasMany(TarifaTorneo::class, 'idFormato', 'idFormato');
    }

    public function partidos(): HasMany
    {
        return $this->hasMany(Partido::class, 'idFormato', 'idFormato');
    }

    /** Formatos activos ordenados para poblar selects — evita repetir el mismo query en cada controller. */
    public function scopeActivos(Builder $query): Builder
    {
        return $query->where('esActivo', true)->orderBy('orden');
    }
}
