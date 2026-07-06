<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DisponibilidadArbitro extends Model
{
    use HasFactory;

    protected $table        = 'disponibilidad_arbitros';
    protected $primaryKey   = 'idDisponibilidad';
    protected $keyType      = 'int';
    public    $incrementing = true;

    // ── Franjas horarias disponibles ──────
    public const FRANJA_AM        = 'am';
    public const FRANJA_PM        = 'pm';
    public const FRANJA_NOCHE     = 'noche';
    public const FRANJA_AM_PM     = 'am_pm';
    public const FRANJA_AM_NOCHE  = 'am_noche';
    public const FRANJA_PM_NOCHE  = 'pm_noche';
    public const FRANJA_TODO_DIA  = 'todo_el_dia';

    /** Marca explícita de "no disponible" — distinta de no tener ningún registro (sin reportar). */
    public const FRANJA_NO_DISPONIBLE = 'no_disponible';

    protected $fillable = [
        'idArbitro',
        'fechaDisponibilidad',
        'franjaHoraria',
        'motivo',
    ];

    protected $casts = [
        'fechaDisponibilidad' => 'date',
    ];

    // ── Catálogo legible en español ───────

    /**
     * Retorna todas las franjas con su etiqueta en español.
     *
     * @return array<string, string>
     */
    public static function getFranjas(): array
    {
        return [
            self::FRANJA_AM       => 'AM',
            self::FRANJA_PM       => 'PM',
            self::FRANJA_NOCHE    => 'Noche',
            self::FRANJA_AM_PM    => 'AM - PM',
            self::FRANJA_AM_NOCHE => 'AM - Noche',
            self::FRANJA_PM_NOCHE => 'PM - Noche',
            self::FRANJA_TODO_DIA => 'Todo el día',
        ];
    }

    /**
     * Devuelve la etiqueta legible de la franja actual (incluye "No disponible",
     * que no forma parte del catálogo de franjas seleccionables de getFranjas()).
     */
    public function franjaLegible(): string
    {
        if ($this->franjaHoraria === self::FRANJA_NO_DISPONIBLE) {
            return 'No disponible';
        }

        return self::getFranjas()[$this->franjaHoraria] ?? $this->franjaHoraria;
    }

    /**
     * Falso si el árbitro marcó explícitamente "no disponible" para este día.
     * Distinto de que no exista ningún registro (ese caso no es una instancia).
     */
    public function esDisponible(): bool
    {
        return $this->franjaHoraria !== self::FRANJA_NO_DISPONIBLE;
    }

    // ── Relaciones ──

    public function arbitro(): BelongsTo
    {
        return $this->belongsTo(Arbitro::class, 'idArbitro', 'idArbitro');
    }
}
