<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SedeTorneo extends Model
{
    use HasFactory;

    protected $table        = 'sedes_torneo';
    protected $primaryKey   = 'idSede';
    protected $keyType      = 'int';
    public    $incrementing = true;

    protected $fillable = [
        'idTorneo',
        'nombreSede',
        'ciudad',
        'urlMaps',
        'observaciones',
    ];

    public function torneo(): BelongsTo
    {
        return $this->belongsTo(Torneo::class, 'idTorneo', 'idTorneo');
    }

    public function partidos(): HasMany
    {
        return $this->hasMany(Partido::class, 'idSede', 'idSede');
    }
}
