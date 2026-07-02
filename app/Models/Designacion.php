<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Designacion extends Model
{
    use HasFactory;

    protected $table        = 'designaciones';
    protected $primaryKey   = 'idDesignacion';
    protected $keyType      = 'int';
    public    $incrementing = true;

    // ── Estados posibles ──────────────────────────────────────────────────────
    public const ESTADO_PENDIENTE  = 'pendiente';
    public const ESTADO_CONFIRMADA = 'confirmada';
    public const ESTADO_RECHAZADA  = 'rechazada';

    protected $fillable = [
        'idPartido',
        'idArbitro',
        'idRol',
        'idColegio',
        'estadoDesignacion',
        'motivoRechazo',
        'fechaConfirmacion',
        'fechaRechazo',
        'notificacionEnviada',
        'fechaNotificacion',
        'idUsuarioDesignador',
    ];

    protected $casts = [
        'fechaConfirmacion'   => 'datetime',
        'fechaRechazo'        => 'datetime',
        'fechaNotificacion'   => 'datetime',
        'notificacionEnviada' => 'boolean',
    ];

    // ── Inspectores de estado ─────────────────────────────────────────────────

    public function estaConfirmada(): bool
    {
        return $this->estadoDesignacion === self::ESTADO_CONFIRMADA;
    }

    public function estaRechazada(): bool
    {
        return $this->estadoDesignacion === self::ESTADO_RECHAZADA;
    }

    public function estaPendiente(): bool
    {
        return $this->estadoDesignacion === self::ESTADO_PENDIENTE;
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

    public function rol(): BelongsTo
    {
        return $this->belongsTo(RolPartido::class, 'idRol', 'idRol');
    }

    public function colegio(): BelongsTo
    {
        return $this->belongsTo(Colegio::class, 'idColegio', 'idColegio');
    }

    public function designador(): BelongsTo
    {
        return $this->belongsTo(User::class, 'idUsuarioDesignador', 'idUsuario');
    }

    public function historial(): HasMany
    {
        return $this->hasMany(HistorialDesignacion::class, 'idDesignacion', 'idDesignacion')
                    ->orderByDesc('created_at');
    }

    public function calificacion(): HasOne
    {
        return $this->hasOne(CalificacionArbitro::class, 'idDesignacion', 'idDesignacion');
    }
}
