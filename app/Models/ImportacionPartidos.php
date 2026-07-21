<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ImportacionPartidos extends Model
{
    use HasFactory;

    protected $table        = 'importaciones_partidos';
    protected $primaryKey   = 'idImportacion';
    protected $keyType      = 'int';
    public    $incrementing = true;

    public const ESTADO_PROCESANDO = 'procesando';
    public const ESTADO_CONFIRMADA = 'confirmada';
    public const ESTADO_REVERTIDA  = 'revertida';
    public const ESTADO_CANCELADA  = 'cancelada';

    protected $fillable = [
        'idColegio',
        'idTorneo',
        'idUsuario',
        'nombreArchivoOriginal',
        'idFormatoDefault',
        'estado',
        'totalFilas',
        'totalCreados',
        'confirmadaEn',
        'revertidaEn',
        'idUsuarioReversion',
    ];

    protected $casts = [
        'confirmadaEn' => 'datetime',
        'revertidaEn'  => 'datetime',
    ];

    public function colegio(): BelongsTo
    {
        return $this->belongsTo(Colegio::class, 'idColegio', 'idColegio');
    }

    public function torneo(): BelongsTo
    {
        return $this->belongsTo(Torneo::class, 'idTorneo', 'idTorneo');
    }

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'idUsuario', 'idUsuario');
    }

    public function usuarioReversion(): BelongsTo
    {
        return $this->belongsTo(User::class, 'idUsuarioReversion', 'idUsuario');
    }

    public function formatoDefault(): BelongsTo
    {
        return $this->belongsTo(FormatoDesignacion::class, 'idFormatoDefault', 'idFormato');
    }

    public function filas(): HasMany
    {
        return $this->hasMany(ImportacionPartidoFila::class, 'idImportacion', 'idImportacion');
    }

    public function partidosCreados(): HasMany
    {
        return $this->hasMany(Partido::class, 'idImportacion', 'idImportacion');
    }

    public function estaEnRevision(): bool
    {
        return $this->estado === self::ESTADO_PROCESANDO;
    }

    public function puedeRevertirse(): bool
    {
        return $this->estado === self::ESTADO_CONFIRMADA;
    }
}
