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
    public const METODO_EFECTIVO            = 'efectivo';
    public const METODO_TRANSFERENCIA       = 'transferencia';
    public const METODO_CONSIGNACION        = 'consignacion';
    public const METODO_COMPENSACION_NOMINA = 'compensacion_nomina';
    public const METODO_OTRO                = 'otro';

    protected $fillable = [
        'idMovimiento',
        'idColegio',
        'monto',
        'fechaAbono',
        'metodoPago',
        'referencia',
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
