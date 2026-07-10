<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class JustificacionAcademica extends Model
{
    use HasFactory;

    protected $table        = 'justificaciones_academicas';
    protected $primaryKey   = 'idJustificacion';
    protected $keyType      = 'int';
    public    $incrementing = true;

    // ── Estados ────────────────────────────
    public const ESTADO_PENDIENTE  = 'pendiente';
    public const ESTADO_APROBADA   = 'aprobada';
    public const ESTADO_RECHAZADA  = 'rechazada';

    protected $fillable = [
        'idColegio',
        'idAsistencia',
        'idArbitro',
        'motivo',
        'documentoPdf',
        'estadoJustificacion',
        'motivoRechazo',
        'idUsuarioRevision',
        'fechaRevision',
        'fechaLimite',
    ];

    protected $casts = [
        'fechaRevision' => 'datetime',
        'fechaLimite'   => 'date',
    ];

    public function estaPendiente(): bool
    {
        return $this->estadoJustificacion === self::ESTADO_PENDIENTE;
    }

    public function estaVencida(): bool
    {
        return $this->estaPendiente() && $this->fechaLimite->endOfDay()->isPast();
    }

    // ── Relaciones ─────────────────────────

    public function colegio(): BelongsTo
    {
        return $this->belongsTo(Colegio::class, 'idColegio', 'idColegio');
    }

    public function asistencia(): BelongsTo
    {
        return $this->belongsTo(AsistenciaAcademica::class, 'idAsistencia', 'idAsistencia');
    }

    public function arbitro(): BelongsTo
    {
        return $this->belongsTo(Arbitro::class, 'idArbitro', 'idArbitro');
    }

    public function usuarioRevision(): BelongsTo
    {
        return $this->belongsTo(User::class, 'idUsuarioRevision', 'idUsuario');
    }
}
