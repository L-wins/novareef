<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;


class EmergenteTorneo extends Model
{
    use HasFactory;

    protected $table        = 'emergentes_torneo';
    protected $primaryKey   = 'idEmergente';
    protected $keyType      = 'int';
    public    $incrementing = true;

    protected $fillable = [
        'idTorneo',
        'idArbitro',
        'idSede',
        'idColegio',
        'fechaEmergente',
        'notas',
        'idUsuarioAsignador',
    ];

    protected $casts = [
        'fechaEmergente' => 'date',
    ];

    public function torneo(): BelongsTo
    {
        return $this->belongsTo(Torneo::class, 'idTorneo', 'idTorneo');
    }

    public function arbitro(): BelongsTo
    {
        return $this->belongsTo(Arbitro::class, 'idArbitro', 'idArbitro');
    }

    public function sede(): BelongsTo
    {
        return $this->belongsTo(SedeTorneo::class, 'idSede', 'idSede');
    }

    public function colegio(): BelongsTo
    {
        return $this->belongsTo(Colegio::class, 'idColegio', 'idColegio');
    }

    public function asignador(): BelongsTo
    {
        return $this->belongsTo(User::class, 'idUsuarioAsignador', 'idUsuario');
    }
}
