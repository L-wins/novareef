<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HistorialDesignacion extends Model
{
    protected $table      = 'historial_designaciones';
    protected $primaryKey = 'idHistorial';
    protected $keyType    = 'int';
    public    $incrementing = true;
    public    $timestamps   = false;

    // ── Tipos de acción ───────────────────
    public const TIPO_ASIGNADO               = 'asignado';
    public const TIPO_CONFIRMADO             = 'confirmado';
    public const TIPO_RECHAZADO              = 'rechazado';
    public const TIPO_QUITADO                = 'quitado';
    public const TIPO_PARTIDO_CREADO         = 'partido_creado';
    public const TIPO_ESTADO_PARTIDO_CAMBIADO = 'estado_partido_cambiado';
    public const TIPO_EMERGENTE_CUBRIO       = 'emergente_cubrio';

    protected $fillable = [
        'idDesignacion',
        'idPartido',
        'idArbitro',
        'idColegio',
        'idUsuarioAccion',
        'tipoAccion',
        'estadoAnterior',
        'estadoNuevo',
        'detalle',
    ];

    protected $casts = [
        'created_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $historial): void {
            if (empty($historial->created_at)) {
                $historial->created_at = now();
            }
        });
    }

    // ── Relaciones ──

    public function partido(): BelongsTo
    {
        return $this->belongsTo(Partido::class, 'idPartido', 'idPartido');
    }

    public function arbitro(): BelongsTo
    {
        return $this->belongsTo(Arbitro::class, 'idArbitro', 'idArbitro');
    }

    public function colegio(): BelongsTo
    {
        return $this->belongsTo(Colegio::class, 'idColegio', 'idColegio');
    }

    public function usuarioAccion(): BelongsTo
    {
        return $this->belongsTo(User::class, 'idUsuarioAccion', 'idUsuario');
    }

    public function designacion(): BelongsTo
    {
        return $this->belongsTo(Designacion::class, 'idDesignacion', 'idDesignacion');
    }
}
