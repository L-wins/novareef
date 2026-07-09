<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HistorialSancion extends Model
{
    protected $table        = 'historial_sanciones';
    protected $primaryKey   = 'idHistorial';
    protected $keyType      = 'int';
    public    $incrementing = true;
    public    $timestamps   = false;

    // ── Tipos de acción ───────────────────
    public const TIPO_IMPUESTA           = 'impuesta';
    public const TIPO_CUMPLIDA           = 'cumplida';
    public const TIPO_ANULADA            = 'anulada';
    public const TIPO_APELADA            = 'apelada';
    public const TIPO_APELACION_RESUELTA = 'apelacion_resuelta';

    protected $fillable = [
        'idSancion',
        'idColegio',
        'idArbitro',
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

    public function sancion(): BelongsTo
    {
        return $this->belongsTo(Sancion::class, 'idSancion', 'idSancion');
    }

    public function colegio(): BelongsTo
    {
        return $this->belongsTo(Colegio::class, 'idColegio', 'idColegio');
    }

    public function arbitro(): BelongsTo
    {
        return $this->belongsTo(Arbitro::class, 'idArbitro', 'idArbitro');
    }

    public function usuarioAccion(): BelongsTo
    {
        return $this->belongsTo(User::class, 'idUsuarioAccion', 'idUsuario');
    }
}
