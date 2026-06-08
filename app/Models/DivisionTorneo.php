<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DivisionTorneo extends Model
{
    use HasFactory;

    protected $table        = 'divisiones_torneo';
    protected $primaryKey   = 'idDivision';
    protected $keyType      = 'int';
    public    $incrementing = true;

    protected $fillable = [
        'idTorneo',
        'nombreDivision',
        'descripcion',
    ];

    public function torneo(): BelongsTo
    {
        return $this->belongsTo(Torneo::class, 'idTorneo', 'idTorneo');
    }

    public function tarifas(): HasMany
    {
        return $this->hasMany(TarifaTorneo::class, 'idDivision', 'idDivision');
    }

    public function partidos(): HasMany
    {
        return $this->hasMany(Partido::class, 'idDivision', 'idDivision');
    }
}
