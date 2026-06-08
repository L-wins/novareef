<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class IndisponibilidadExtraordinaria extends Model
{
    use HasFactory;

    protected $table        = 'indisponibilidades_extraordinarias';
    protected $primaryKey   = 'idIndisponibilidad';
    protected $keyType      = 'int';
    public    $incrementing = true;

    protected $fillable = [
        'idArbitro',
        'idColegio',
        'fechaAfectada',
        'franjaAfectada',
        'motivo',
        'idUsuarioRegistro',
    ];

    protected $casts = [
        'fechaAfectada' => 'date',
    ];

    // ── Relaciones ────────────────────────────────────────────────────────────

    public function arbitro(): BelongsTo
    {
        return $this->belongsTo(Arbitro::class, 'idArbitro', 'idArbitro');
    }

    public function colegio(): BelongsTo
    {
        return $this->belongsTo(Colegio::class, 'idColegio', 'idColegio');
    }

    public function registradoPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'idUsuarioRegistro', 'idUsuario');
    }
}
