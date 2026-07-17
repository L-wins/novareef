<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Sancion extends Model
{
    use HasFactory;

    protected $table        = 'sanciones';
    protected $primaryKey   = 'idSancion';
    protected $keyType      = 'int';
    public    $incrementing = true;

    // ── Estados ────────────────────────────
    public const ESTADO_ACTIVA   = 'activa';
    public const ESTADO_CUMPLIDA = 'cumplida';
    public const ESTADO_ANULADA  = 'anulada';
    public const ESTADO_APELADA  = 'apelada';

    /** Igual patrón que MovimientoFinanciero::ETIQUETAS_ESTADO — una sola fuente de verdad para las vistas. */
    public const ETIQUETAS_ESTADO = [
        self::ESTADO_ACTIVA   => ['Activa', 'amber'],
        self::ESTADO_CUMPLIDA => ['Cumplida', 'green'],
        self::ESTADO_ANULADA  => ['Anulada', 'red'],
        self::ESTADO_APELADA  => ['Apelada', 'blue'],
    ];

    protected $fillable = [
        'idColegio',
        'idArbitro',
        'idTipoSancion',
        'idPartido',
        'motivoSancion',
        'fechaHecho',
        'fechaInicioSancion',
        'fechaFinSancion',
        'estadoSancion',
        'tieneMultaEconomica',
        'idMovimientoFinanciero',
        'idUsuarioImpuso',
        'version',
    ];

    protected $casts = [
        'fechaHecho'          => 'date',
        'fechaInicioSancion'  => 'date',
        'fechaFinSancion'     => 'date',
        'tieneMultaEconomica' => 'boolean',
    ];

    // ── Inspectores de estado ─────────────

    public function estaActiva(): bool
    {
        return $this->estadoSancion === self::ESTADO_ACTIVA;
    }

    public function estaApelada(): bool
    {
        return $this->estadoSancion === self::ESTADO_APELADA;
    }

    // ── Relaciones ──

    public function colegio(): BelongsTo
    {
        return $this->belongsTo(Colegio::class, 'idColegio', 'idColegio');
    }

    public function arbitro(): BelongsTo
    {
        return $this->belongsTo(Arbitro::class, 'idArbitro', 'idArbitro');
    }

    public function tipo(): BelongsTo
    {
        return $this->belongsTo(TipoSancion::class, 'idTipoSancion', 'idTipoSancion');
    }

    public function partido(): BelongsTo
    {
        return $this->belongsTo(Partido::class, 'idPartido', 'idPartido');
    }

    public function movimientoFinanciero(): BelongsTo
    {
        return $this->belongsTo(MovimientoFinanciero::class, 'idMovimientoFinanciero', 'idMovimiento');
    }

    public function usuarioImpuso(): BelongsTo
    {
        return $this->belongsTo(User::class, 'idUsuarioImpuso', 'idUsuario');
    }

    public function historial(): HasMany
    {
        return $this->hasMany(HistorialSancion::class, 'idSancion', 'idSancion')
                    ->orderByDesc('created_at');
    }
}
