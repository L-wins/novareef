<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class RolPartido extends Model
{
    use HasFactory;

    protected $table        = 'roles_partido';
    protected $primaryKey   = 'idRol';
    protected $keyType      = 'int';
    public    $incrementing = true;

    protected $fillable = [
        'nombre',
        'descripcion',
        'esActivo',
        'orden',
    ];

    protected $casts = [
        'esActivo' => 'boolean',
        'orden'    => 'integer',
    ];

    public function tarifas(): HasMany
    {
        return $this->hasMany(TarifaTorneo::class, 'idRol', 'idRol');
    }
}
