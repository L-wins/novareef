<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SolicitudArco extends Model
{
    protected $table      = 'solicitudes_arco';
    protected $primaryKey = 'idSolicitud';

    public const TIPOS = ['acceso', 'rectificacion', 'cancelacion', 'oposicion'];

    protected $fillable = [
        'idUsuario',
        'idColegio',
        'tipo',
        'mensaje',
        'estado',
    ];

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'idUsuario', 'idUsuario');
    }

    public function colegio(): BelongsTo
    {
        return $this->belongsTo(Colegio::class, 'idColegio', 'idColegio');
    }
}
