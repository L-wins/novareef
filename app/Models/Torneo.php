<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class Torneo extends Model
{
    use HasFactory, SoftDeletes;

    protected $table        = 'torneos';
    protected $primaryKey   = 'idTorneo';
    protected $keyType      = 'int';
    public    $incrementing = true;

    protected $fillable = [
        'idColegio',
        'nombreTorneo',
        'tipoTorneo',
        'modalidadPago',
        'estadoTorneo',
        'organizadorNombre',
        'organizadorTelefono',
        'organizadorEmail',
        'temporada',
        'fechaInicio',
        'fechaFin',
        'idUsuarioCreador',
        'valorEmergente',
    ];

    protected $casts = [
        'fechaInicio'    => 'date',
        'fechaFin'       => 'date',
        'temporada'      => 'integer',
        'valorEmergente' => 'decimal:2',
        'deleted_at'     => 'datetime',
    ];

    public function colegio(): BelongsTo
    {
        return $this->belongsTo(Colegio::class, 'idColegio', 'idColegio');
    }

    public function creador(): BelongsTo
    {
        return $this->belongsTo(User::class, 'idUsuarioCreador', 'idUsuario');
    }

    public function divisiones(): HasMany
    {
        return $this->hasMany(DivisionTorneo::class, 'idTorneo', 'idTorneo');
    }

    public function sedes(): HasMany
    {
        return $this->hasMany(SedeTorneo::class, 'idTorneo', 'idTorneo');
    }

    public function partidos(): HasMany
    {
        return $this->hasMany(Partido::class, 'idTorneo', 'idTorneo');
    }

    public function reglamentos(): HasMany
    {
        return $this->hasMany(ReglamentoTorneo::class, 'idTorneo', 'idTorneo');
    }

    public function reglamentoActual(): HasOne
    {
        return $this->hasOne(ReglamentoTorneo::class, 'idTorneo', 'idTorneo')
            ->where('esActual', true)
            ->latest('created_at');
    }

    public function emergentes(): HasMany
    {
        return $this->hasMany(EmergenteTorneo::class, 'idTorneo', 'idTorneo');
    }

    public function movimientosFinancieros(): HasMany
    {
        return $this->hasMany(MovimientoFinanciero::class, 'idTorneo', 'idTorneo');
    }
}
