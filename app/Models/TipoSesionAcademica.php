<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TipoSesionAcademica extends Model
{
    protected $table        = 'tipos_sesion_academica';
    protected $primaryKey   = 'idTipoSesion';
    protected $keyType      = 'int';
    public    $incrementing = true;

    protected $fillable = [
        'idColegio',
        'etiqueta',
        'esOficial',
        'descripcion',
        'esActivo',
        'orden',
    ];

    protected $casts = [
        'esOficial' => 'boolean',
        'esActivo'  => 'boolean',
    ];

    public function colegio(): BelongsTo
    {
        return $this->belongsTo(Colegio::class, 'idColegio', 'idColegio');
    }

    public function sesiones(): HasMany
    {
        return $this->hasMany(SesionAcademica::class, 'idTipoSesion', 'idTipoSesion');
    }
}
