<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class EstadoArbitro extends Model
{
    use HasFactory;

    protected $table        = 'estados_arbitro';
    protected $primaryKey   = 'idEstado';
    protected $keyType      = 'int';
    public    $incrementing = true;

    protected $fillable = [
        'nombre',
        'etiqueta',
        'color',
        'descripcion',
        'permiteDesignar',
        'esActivo',
        'orden',
    ];

    protected $casts = [
        'permiteDesignar' => 'boolean',
        'esActivo'        => 'boolean',
        'orden'           => 'integer',
    ];

    public function arbitros(): HasMany
    {
        return $this->hasMany(Arbitro::class, 'estadoArbitro', 'nombre');
    }
}
