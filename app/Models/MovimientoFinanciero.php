<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MovimientoFinanciero extends Model
{
    use HasFactory;

    protected $table        = 'movimientos_financieros';
    protected $primaryKey   = 'idMovimiento';
    protected $keyType      = 'int';
    public    $incrementing = true;

    // ── Tipo ──────────────────────────────
    public const TIPO_INGRESO = 'ingreso';
    public const TIPO_EGRESO  = 'egreso';

    // ── Categorías de ingreso ──────────────
    public const CATEGORIA_INGRESO_TORNEO = 'ingreso_torneo';
    public const CATEGORIA_MENSUALIDAD    = 'mensualidad';
    public const CATEGORIA_MULTA          = 'multa';
    public const CATEGORIA_OTRO_INGRESO   = 'otro_ingreso';

    // ── Categorías de egreso ───────────────
    public const CATEGORIA_NOMINA_ARBITRO       = 'nomina_arbitro';
    public const CATEGORIA_ARBITRO_EXTERNO      = 'arbitro_externo';
    public const CATEGORIA_GASTO_FIJO           = 'gasto_fijo';
    public const CATEGORIA_GASTO_INSTITUCIONAL  = 'gasto_institucional';
    public const CATEGORIA_GASTO_VARIO          = 'gasto_vario';

    /**
     * Única fuente de verdad de qué categorías pertenecen a cada tipo de
     * movimiento — el Service la usa para rechazar combinaciones inválidas.
     */
    public const CATEGORIAS_POR_TIPO = [
        self::TIPO_INGRESO => [
            self::CATEGORIA_INGRESO_TORNEO,
            self::CATEGORIA_MENSUALIDAD,
            self::CATEGORIA_MULTA,
            self::CATEGORIA_OTRO_INGRESO,
        ],
        self::TIPO_EGRESO => [
            self::CATEGORIA_NOMINA_ARBITRO,
            self::CATEGORIA_ARBITRO_EXTERNO,
            self::CATEGORIA_GASTO_FIJO,
            self::CATEGORIA_GASTO_INSTITUCIONAL,
            self::CATEGORIA_GASTO_VARIO,
        ],
    ];

    // ── Estados ────────────────────────────
    public const ESTADO_PENDIENTE = 'pendiente';
    public const ESTADO_PARCIAL   = 'parcial';
    public const ESTADO_PAGADO    = 'pagado';
    public const ESTADO_ANULADO   = 'anulado';

    /** Etiquetas legibles por categoría — única fuente (antes copiadas en cada vista). */
    public const ETIQUETAS_CATEGORIA = [
        self::CATEGORIA_INGRESO_TORNEO      => 'Ingreso por torneo',
        self::CATEGORIA_MENSUALIDAD         => 'Mensualidad',
        self::CATEGORIA_MULTA               => 'Multa',
        self::CATEGORIA_OTRO_INGRESO        => 'Otro ingreso',
        self::CATEGORIA_NOMINA_ARBITRO      => 'Nómina de árbitros',
        self::CATEGORIA_ARBITRO_EXTERNO     => 'Árbitro externo',
        self::CATEGORIA_GASTO_FIJO          => 'Gasto fijo',
        self::CATEGORIA_GASTO_INSTITUCIONAL => 'Gasto institucional',
        self::CATEGORIA_GASTO_VARIO         => 'Gasto vario',
    ];

    /** Etiqueta + color de badge por estado. */
    public const ETIQUETAS_ESTADO = [
        self::ESTADO_PENDIENTE => ['Pendiente', 'gray'],
        self::ESTADO_PARCIAL   => ['Parcial', 'amber'],
        self::ESTADO_PAGADO    => ['Pagado', 'green'],
        self::ESTADO_ANULADO   => ['Anulado', 'red'],
    ];

    // ── Orígenes de multa ──────────────────
    public const ORIGEN_MULTA_SANCION  = 'sancion';
    public const ORIGEN_MULTA_ACADEMICO = 'academico';
    public const ORIGEN_MULTA_MANUAL   = 'manual';

    protected $fillable = [
        'idColegio',
        'tipoMovimiento',
        'categoria',
        'concepto',
        'montoTotal',
        'estadoMovimiento',
        'fechaMovimiento',
        'idArbitro',
        'nombreArbitroExterno',
        'documentoArbitroExterno',
        'idTorneo',
        'idPartido',
        'idDesignacion',
        'tipoOrigenMulta',
        'idOrigenMulta',
        'idUsuarioRegistro',
        'observaciones',
    ];

    protected $casts = [
        'montoTotal'      => 'decimal:2',
        'fechaMovimiento' => 'date',
    ];

    // ── Inspectores de estado ─────────────

    public function esIngreso(): bool
    {
        return $this->tipoMovimiento === self::TIPO_INGRESO;
    }

    public function esEgreso(): bool
    {
        return $this->tipoMovimiento === self::TIPO_EGRESO;
    }

    public function estaAnulado(): bool
    {
        return $this->estadoMovimiento === self::ESTADO_ANULADO;
    }

    public function etiquetaCategoria(): string
    {
        return self::ETIQUETAS_CATEGORIA[$this->categoria] ?? $this->categoria;
    }

    /**
     * Saldo pendiente = monto total menos la suma de abonos no anulados.
     * No es un valor persistido (no arrastra inconsistencias si un abono se
     * anula después). Si el listado precargó el agregado `totalAbonado`
     * (scope conTotalAbonado), se usa ese valor — evita una query por fila.
     */
    public function saldoPendiente(): float
    {
        $abonado = array_key_exists('totalAbonado', $this->attributes)
            ? $this->attributes['totalAbonado']
            : $this->abonos()->where('anulado', false)->sum('monto');

        return (float) $this->montoTotal - (float) $abonado;
    }

    /**
     * Precarga la suma de abonos válidos como `totalAbonado` en una sola
     * query agregada — usar en cualquier listado que llame saldoPendiente().
     */
    public function scopeConTotalAbonado($query)
    {
        return $query->withSum('abonosValidos as totalAbonado', 'monto');
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

    public function torneo(): BelongsTo
    {
        return $this->belongsTo(Torneo::class, 'idTorneo', 'idTorneo');
    }

    public function partido(): BelongsTo
    {
        return $this->belongsTo(Partido::class, 'idPartido', 'idPartido');
    }

    public function designacion(): BelongsTo
    {
        return $this->belongsTo(Designacion::class, 'idDesignacion', 'idDesignacion');
    }

    public function usuarioRegistro(): BelongsTo
    {
        return $this->belongsTo(User::class, 'idUsuarioRegistro', 'idUsuario');
    }

    public function abonos(): HasMany
    {
        return $this->hasMany(AbonoMovimiento::class, 'idMovimiento', 'idMovimiento')
                    ->orderByDesc('fechaAbono');
    }

    /** Solo los abonos que cuentan contablemente (no anulados). */
    public function abonosValidos(): HasMany
    {
        return $this->hasMany(AbonoMovimiento::class, 'idMovimiento', 'idMovimiento')
                    ->where('anulado', false);
    }

    public function historial(): HasMany
    {
        return $this->hasMany(HistorialMovimientoFinanciero::class, 'idMovimiento', 'idMovimiento')
                    ->orderByDesc('created_at');
    }
}
