<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

class SesionAcademica extends Model
{
    use HasFactory, SoftDeletes;

    protected $table        = 'sesiones_academicas';
    protected $primaryKey   = 'idSesion';
    protected $keyType      = 'int';
    public    $incrementing = true;

    // ── Días para justificar una inasistencia ──
    public const DIAS_LIMITE_JUSTIFICACION = 3;

    // ── Modalidad ──────────────────────────
    public const MODALIDAD_PRESENCIAL = 'presencial';
    public const MODALIDAD_VIRTUAL    = 'virtual';

    // ── A quién va dirigida ────────────────
    public const DIRIGIDA_TODOS     = 'todos';
    public const DIRIGIDA_CATEGORIA = 'categoria';

    // ── Modo de asistencia ─────────────────
    public const MODO_MANUAL  = 'manual';
    public const MODO_SCANNER = 'scanner';

    // ── Estados ────────────────────────────
    public const ESTADO_PROGRAMADA = 'programada';
    public const ESTADO_EN_CURSO   = 'en_curso';
    public const ESTADO_FINALIZADA = 'finalizada';
    public const ESTADO_CANCELADA  = 'cancelada';

    protected $fillable = [
        'idColegio',
        'idInstructor',
        'idTipoSesion',
        'modalidad',
        'urlVirtual',
        'tema',
        'descripcion',
        'lugar',
        'fechaSesion',
        'horaSesion',
        'duracionMinutos',
        'dirigidaA',
        'idCategoria',
        'modoAsistencia',
        'esObligatoria',
        'estadoSesion',
        'sesionAbierta',
    ];

    protected $casts = [
        'fechaSesion'     => 'date',
        'duracionMinutos' => 'integer',
        'sesionAbierta'   => 'boolean',
        'esObligatoria'   => 'boolean',
    ];

    protected $appends = ['esOficial'];

    // ── Accessors ──────────────────────────

    /**
     * Fecha límite para que un árbitro justifique su inasistencia a esta
     * sesión — fechaSesion + 3 días corridos (DIAS_LIMITE_JUSTIFICACION).
     */
    public function getFechaLimiteJustificacionAttribute(): Carbon
    {
        return $this->fechaSesion->copy()->addDays(self::DIAS_LIMITE_JUSTIFICACION);
    }

    /**
     * Si el tipo de sesión (catálogo del colegio) está marcado como oficial
     * (ej. prueba oficial FCF). Usa la relación cargada si ya está en
     * memoria para evitar N+1 en listados.
     */
    public function getEsOficialAttribute(): bool
    {
        $tipo = $this->relationLoaded('tipo') ? $this->tipo : $this->tipo()->first();

        return (bool) ($tipo?->esOficial ?? false);
    }

    public function estaAbierta(): bool
    {
        return $this->sesionAbierta;
    }

    // ── Relaciones ─────────────────────────

    public function colegio(): BelongsTo
    {
        return $this->belongsTo(Colegio::class, 'idColegio', 'idColegio');
    }

    public function instructor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'idInstructor', 'idUsuario');
    }

    public function tipo(): BelongsTo
    {
        return $this->belongsTo(TipoSesionAcademica::class, 'idTipoSesion', 'idTipoSesion');
    }

    public function categoria(): BelongsTo
    {
        return $this->belongsTo(CategoriaArbitro::class, 'idCategoria', 'idCategoria');
    }

    public function asistencias(): HasMany
    {
        return $this->hasMany(AsistenciaAcademica::class, 'idSesion', 'idSesion');
    }

    public function materiales(): HasMany
    {
        return $this->hasMany(MaterialAcademico::class, 'idSesion', 'idSesion')->orderByDesc('created_at');
    }
}
