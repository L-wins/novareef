<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class AsistenciaAcademica extends Model
{
    use HasFactory;

    protected $table        = 'asistencias_academicas';
    protected $primaryKey   = 'idAsistencia';
    protected $keyType      = 'int';
    public    $incrementing = true;

    // ── Estados ────────────────────────────
    public const ESTADO_PRESENTE                 = 'presente';
    public const ESTADO_AUSENTE                  = 'ausente';
    public const ESTADO_JUSTIFICACION_PENDIENTE  = 'justificacion_pendiente';
    public const ESTADO_JUSTIFICADO              = 'justificado';
    public const ESTADO_JUSTIFICACION_RECHAZADA  = 'justificacion_rechazada';

    // ── Quién registró la marca ────────────
    public const REGISTRADO_ARBITRO    = 'arbitro';
    public const REGISTRADO_INSTRUCTOR = 'instructor';
    public const REGISTRADO_SISTEMA    = 'sistema';

    /** Etiqueta + color de badge por estado — única fuente para las vistas. */
    public const ETIQUETAS_ESTADO = [
        self::ESTADO_PRESENTE                => ['Presente', 'green'],
        self::ESTADO_AUSENTE                  => ['Ausente', 'red'],
        self::ESTADO_JUSTIFICACION_PENDIENTE  => ['Justificación pendiente', 'amber'],
        self::ESTADO_JUSTIFICADO              => ['Justificado', 'blue'],
        self::ESTADO_JUSTIFICACION_RECHAZADA  => ['Justificación rechazada', 'gray'],
    ];

    protected $fillable = [
        'idColegio',
        'idSesion',
        'idArbitro',
        'estadoAsistencia',
        'horaMarca',
        'registradoPor',
        'confirmadoInstructor',
    ];

    protected $casts = [
        'horaMarca'             => 'datetime',
        'confirmadoInstructor'  => 'boolean',
    ];

    public function estaPresente(): bool
    {
        return $this->estadoAsistencia === self::ESTADO_PRESENTE;
    }

    public function etiquetaEstado(): string
    {
        return self::ETIQUETAS_ESTADO[$this->estadoAsistencia][0] ?? $this->estadoAsistencia;
    }

    // ── Relaciones ─────────────────────────

    public function colegio(): BelongsTo
    {
        return $this->belongsTo(Colegio::class, 'idColegio', 'idColegio');
    }

    public function sesion(): BelongsTo
    {
        return $this->belongsTo(SesionAcademica::class, 'idSesion', 'idSesion');
    }

    public function arbitro(): BelongsTo
    {
        return $this->belongsTo(Arbitro::class, 'idArbitro', 'idArbitro');
    }

    public function justificacion(): HasOne
    {
        return $this->hasOne(JustificacionAcademica::class, 'idAsistencia', 'idAsistencia');
    }
}
