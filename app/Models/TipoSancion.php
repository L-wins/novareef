<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TipoSancion extends Model
{
    protected $table        = 'tipos_sancion';
    protected $primaryKey   = 'idTipoSancion';
    protected $keyType      = 'int';
    public    $incrementing = true;

    // ── Severidad ──────────────────────────
    public const SEVERIDAD_LEVE     = 'leve';
    public const SEVERIDAD_MODERADA = 'moderada';
    public const SEVERIDAD_GRAVE    = 'grave';

    protected $fillable = [
        'idColegio',
        'etiqueta',
        'articuloReglamento',
        'severidad',
        'diasSuspensionSugeridos',
        'descripcion',
        'esActivo',
        'orden',
    ];

    protected $casts = [
        'esActivo' => 'boolean',
    ];

    public function colegio(): BelongsTo
    {
        return $this->belongsTo(Colegio::class, 'idColegio', 'idColegio');
    }

    public function sanciones(): HasMany
    {
        return $this->hasMany(Sancion::class, 'idTipoSancion', 'idTipoSancion');
    }
}
