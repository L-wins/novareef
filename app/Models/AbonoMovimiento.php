<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AbonoMovimiento extends Model
{
    protected $table        = 'abonos_movimiento';
    protected $primaryKey   = 'idAbono';
    protected $keyType      = 'int';
    public    $incrementing = true;
    public    $timestamps   = false;

    // ── Métodos de pago ────────────────────
    public const METODO_EFECTIVO      = 'efectivo';
    public const METODO_PAGO_DIGITAL  = 'pago_digital';
    public const METODO_NOMINA        = 'nomina';

    /**
     * Métodos que un usuario puede elegir a mano en un formulario — excluye
     * nomina, que solo lo asigna internamente
     * FinanzasService::compensarDeudaConNomina(), nunca una selección de
     * usuario. Única fuente de verdad para la validación Rule::in() de los
     * Requests de abono.
     */
    public const METODOS_MANUALES = [
        self::METODO_EFECTIVO,
        self::METODO_PAGO_DIGITAL,
    ];

    protected $fillable = [
        'idMovimiento',
        'idColegio',
        'monto',
        'fechaAbono',
        'metodoPago',
        'idLotePago',
        'anulado',
        'idUsuarioRegistro',
        'idUsuarioAnulacion',
        'fechaAnulacion',
        'observaciones',
    ];

    protected $casts = [
        'monto'          => 'decimal:2',
        'fechaAbono'     => 'date',
        'anulado'        => 'boolean',
        'fechaAnulacion' => 'datetime',
        'created_at'     => 'datetime',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $abono): void {
            if (empty($abono->created_at)) {
                $abono->created_at = now();
            }
        });
    }

    // ── Relaciones ──

    public function movimiento(): BelongsTo
    {
        return $this->belongsTo(MovimientoFinanciero::class, 'idMovimiento', 'idMovimiento');
    }

    public function colegio(): BelongsTo
    {
        return $this->belongsTo(Colegio::class, 'idColegio', 'idColegio');
    }

    public function usuarioRegistro(): BelongsTo
    {
        return $this->belongsTo(User::class, 'idUsuarioRegistro', 'idUsuario');
    }

    public function usuarioAnulacion(): BelongsTo
    {
        return $this->belongsTo(User::class, 'idUsuarioAnulacion', 'idUsuario');
    }
}
