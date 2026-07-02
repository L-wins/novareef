<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TarifaTorneo extends Model
{
    use HasFactory;

    protected $table        = 'tarifas_torneo';
    protected $primaryKey   = 'idTarifa';
    protected $keyType      = 'int';
    public    $incrementing = true;

    protected $fillable = [
        'idDivision',
        'idRol',
        'idFormato',
        'valorPago',
    ];

    protected $casts = [
        'valorPago' => 'decimal:2',
    ];

    public function division(): BelongsTo
    {
        return $this->belongsTo(DivisionTorneo::class, 'idDivision', 'idDivision');
    }

    public function rol(): BelongsTo
    {
        return $this->belongsTo(RolPartido::class, 'idRol', 'idRol');
    }

    public function formato(): BelongsTo
    {
        return $this->belongsTo(FormatoDesignacion::class, 'idFormato', 'idFormato');
    }
}
