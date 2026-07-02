<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Partido extends Model
{
    use HasFactory;

    protected $table        = 'partidos';
    protected $primaryKey   = 'idPartido';
    protected $keyType      = 'int';
    public    $incrementing = true;

    // ── Estados del partido ───────────────────────────────────────────────────
    public const ESTADO_BORRADOR   = 'borrador';
    public const ESTADO_PROGRAMADO = 'programado';
    public const ESTADO_EN_CURSO   = 'en_curso';
    public const ESTADO_CONFIRMADO = 'confirmado';
    public const ESTADO_CRITICO    = 'critico';
    public const ESTADO_APLAZADO   = 'aplazado';
    public const ESTADO_CANCELADO  = 'cancelado';
    public const ESTADO_FINALIZADO = 'finalizado';

    protected $fillable = [
        'idTorneo',
        'idColegio',
        'idDivision',
        'idSede',
        'idFormato',
        'equipoLocal',
        'equipoVisitante',
        'fechaPartido',
        'horaPartido',
        'estadoPartido',
        'version',
        'modalidadPago',
        'observaciones',
        'idVeedor',
        'horaInicio',
    ];

    protected $casts = [
        'fechaPartido' => 'date',
        'horaInicio'   => 'datetime',
    ];

    // ── Accessors ─────────────────────────────────────────────────────────────

    /**
     * Verdadero cuando todas las designaciones del partido están confirmadas
     * según el número máximo de árbitros que exige su formato.
     */
    public function estaCompleto(): bool
    {
        $maxArbitros = $this->formato?->maxArbitros ?? 0;

        if ($maxArbitros === 0) {
            return false;
        }

        return $this->designacionesConfirmadas()->count() >= $maxArbitros;
    }

    /**
     * Verdadero cuando la fecha del partido es anterior a hoy y el partido
     * no tiene todas las designaciones confirmadas.
     */
    public function esCritico(): bool
    {
        return $this->fechaPartido->isPast() && !$this->estaCompleto();
    }

    // ── Relaciones ────────────────────────────────────────────────────────────

    public function torneo(): BelongsTo
    {
        return $this->belongsTo(Torneo::class, 'idTorneo', 'idTorneo');
    }

    public function colegio(): BelongsTo
    {
        return $this->belongsTo(Colegio::class, 'idColegio', 'idColegio');
    }

    public function division(): BelongsTo
    {
        return $this->belongsTo(DivisionTorneo::class, 'idDivision', 'idDivision');
    }

    public function sede(): BelongsTo
    {
        return $this->belongsTo(SedeTorneo::class, 'idSede', 'idSede');
    }

    public function formato(): BelongsTo
    {
        return $this->belongsTo(FormatoDesignacion::class, 'idFormato', 'idFormato');
    }

    public function designaciones(): HasMany
    {
        return $this->hasMany(Designacion::class, 'idPartido', 'idPartido');
    }

    /** Solo las designaciones en estado 'confirmada'. */
    public function designacionesConfirmadas(): HasMany
    {
        return $this->hasMany(Designacion::class, 'idPartido', 'idPartido')
                    ->where('estadoDesignacion', Designacion::ESTADO_CONFIRMADA);
    }

    public function historial(): HasMany
    {
        return $this->hasMany(HistorialDesignacion::class, 'idPartido', 'idPartido')
                    ->orderByDesc('created_at');
    }

    public function veedor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'idVeedor', 'idUsuario');
    }
}
