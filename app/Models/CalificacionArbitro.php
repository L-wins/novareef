<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CalificacionArbitro extends Model
{
    protected $table      = 'calificaciones_arbitro';
    protected $primaryKey = 'idCalificacion';
    protected $keyType    = 'int';
    public    $incrementing = true;

    protected $fillable = [
        'idDesignacion',
        'idVeedor',
        'idColegio',
        'nota',
        'comentario',
    ];

    protected $casts = [
        'nota' => 'decimal:1',
    ];

    // ── Accessors ───

    public function getNotaLabelAttribute(): string
    {
        $nota = (float) $this->nota;

        return match (true) {
            $nota >= 4.5 => '⭐ Excelente',
            $nota >= 3.5 => '✅ Bueno',
            $nota >= 2.5 => '⚠️ Regular',
            default      => '❌ Deficiente',
        };
    }

    public function getNotaColorAttribute(): string
    {
        $nota = (float) $this->nota;

        return match (true) {
            $nota >= 4.5 => 'green',
            $nota >= 3.5 => 'blue',
            $nota >= 2.5 => 'yellow',
            default      => 'red',
        };
    }

    // ── Relaciones ──

    public function designacion(): BelongsTo
    {
        return $this->belongsTo(Designacion::class, 'idDesignacion', 'idDesignacion');
    }

    public function veedor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'idVeedor', 'idUsuario');
    }

    public function colegio(): BelongsTo
    {
        return $this->belongsTo(Colegio::class, 'idColegio', 'idColegio');
    }
}
