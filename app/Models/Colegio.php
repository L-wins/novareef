<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Colegio extends Model
{
    protected $table      = 'colegios';
    protected $primaryKey = 'idColegio';
    protected $keyType    = 'int';
    public    $incrementing = true;

    protected $fillable = [
        'tenantId',
        'nombreColegio',
        'codigoColegio',
        'emailColegio',
        'telefonoColegio',
        'direccionColegio',
        'ciudadColegio',
        'departamentoColegio',
        'paisColegio',
        'logoColegio',
        'estadoColegio',
        'planColegio',
        'fechaSuscripcion',
        'fechaExpiracion',
    ];

    protected $casts = [
        'fechaSuscripcion' => 'date',
        'fechaExpiracion'  => 'date',
        'estadoColegio'    => 'string',
        'planColegio'      => 'string',
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(\Stancl\Tenancy\Database\Models\Tenant::class, 'tenantId');
    }
}
