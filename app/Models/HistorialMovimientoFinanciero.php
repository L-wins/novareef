<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HistorialMovimientoFinanciero extends Model
{
    protected $table        = 'historial_movimientos_financieros';
    protected $primaryKey   = 'idHistorial';
    protected $keyType      = 'int';
    public    $incrementing = true;
    public    $timestamps   = false;

    // ── Tipos de acción ───────────────────
    public const TIPO_CREADO     = 'creado';
    public const TIPO_ABONADO    = 'abonado';
    public const TIPO_ANULADO    = 'anulado';
    public const TIPO_COMPENSADO = 'compensado';

    protected $fillable = [
        'idMovimiento',
        'idColegio',
        'idUsuarioAccion',
        'tipoAccion',
        'estadoAnterior',
        'estadoNuevo',
        'detalle',
    ];

    protected $casts = [
        'created_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $historial): void {
            if (empty($historial->created_at)) {
                $historial->created_at = now();
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

    public function usuarioAccion(): BelongsTo
    {
        return $this->belongsTo(User::class, 'idUsuarioAccion', 'idUsuario');
    }
}
