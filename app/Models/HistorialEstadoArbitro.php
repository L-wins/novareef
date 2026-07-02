<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HistorialEstadoArbitro extends Model
{
    use HasFactory;

    protected $table        = 'historial_estados_arbitro';
    protected $primaryKey   = 'idHistorial';
    protected $keyType      = 'int';
    public    $incrementing = true;
    public    $timestamps   = false;

    protected $fillable = [
        'idArbitro',
        'idUsuarioCambio',
        'estadoAnterior',
        'estadoNuevo',
        'motivo',
        'fechaInicio',
        'fechaFin',
    ];

    protected $casts = [
        'fechaInicio' => 'date',
        'fechaFin'    => 'date',
        'created_at'  => 'datetime',
    ];

    protected static function booted(): void
    {
        static::creating(function (HistorialEstadoArbitro $historial): void {
            if (empty($historial->created_at)) {
                $historial->created_at = now();
            }
        });
    }

    public function arbitro(): BelongsTo
    {
        return $this->belongsTo(Arbitro::class, 'idArbitro', 'idArbitro');
    }

    public function usuarioCambio(): BelongsTo
    {
        return $this->belongsTo(User::class, 'idUsuarioCambio', 'idUsuario');
    }

    public function estadoNuevoModel(): BelongsTo
    {
        return $this->belongsTo(EstadoArbitro::class, 'estadoNuevo', 'nombre');
    }

    public function estadoAnteriorModel(): BelongsTo
    {
        return $this->belongsTo(EstadoArbitro::class, 'estadoAnterior', 'nombre');
    }
}
