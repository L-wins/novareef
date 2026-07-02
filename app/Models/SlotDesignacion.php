<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SlotDesignacion extends Model
{
    protected $table        = 'slots_designacion';
    protected $primaryKey   = 'idSlot';
    protected $keyType      = 'int';
    public    $incrementing = true;

    protected $fillable = [
        'idPartido',
        'idRol',
        'numeroSlot',
        'idDesignacion',
    ];

    protected $casts = [
        'numeroSlot' => 'integer',
    ];

    // ── Inspectores ───────────────────────────────────────────────────────────

    /** Verdadero cuando el slot no tiene designación asignada. */
    public function estaLibre(): bool
    {
        return $this->idDesignacion === null;
    }

    // ── Relaciones ────────────────────────────────────────────────────────────

    public function partido(): BelongsTo
    {
        return $this->belongsTo(Partido::class, 'idPartido', 'idPartido');
    }

    public function rol(): BelongsTo
    {
        return $this->belongsTo(RolPartido::class, 'idRol', 'idRol');
    }

    public function designacion(): BelongsTo
    {
        return $this->belongsTo(Designacion::class, 'idDesignacion', 'idDesignacion');
    }
}
