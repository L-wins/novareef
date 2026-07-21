<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ImportacionPartidoFila extends Model
{
    use HasFactory;

    protected $table        = 'importacion_partidos_filas';
    protected $primaryKey   = 'idFila';
    protected $keyType      = 'int';
    public    $incrementing = true;

    protected $fillable = [
        'idImportacion',
        'clave',
        'grupoTexto',
        'categoriaTexto',
        'fechaTexto',
        'asociacionTexto',
        'nombreSedeTexto',
        'diaTexto',
        'ciudadTexto',
        'rolesTexto',
        'equipoLocal',
        'equipoVisitante',
        'fechaPartido',
        'horaPartido',
        'idDivisionMatch',
        'idSedeMatch',
        'idFormato',
        'designacionesMatch',
        'incluir',
        'esPosibleDuplicado',
        'errores',
        'advertencias',
        'idPartidoCreado',
    ];

    protected $casts = [
        'fechaPartido'        => 'date',
        'rolesTexto'          => 'array',
        'designacionesMatch'  => 'array',
        'errores'             => 'array',
        'advertencias'        => 'array',
        'incluir'             => 'boolean',
        'esPosibleDuplicado'  => 'boolean',
    ];

    public function importacion(): BelongsTo
    {
        return $this->belongsTo(ImportacionPartidos::class, 'idImportacion', 'idImportacion');
    }

    public function divisionMatch(): BelongsTo
    {
        return $this->belongsTo(DivisionTorneo::class, 'idDivisionMatch', 'idDivision');
    }

    public function sedeMatch(): BelongsTo
    {
        return $this->belongsTo(SedeTorneo::class, 'idSedeMatch', 'idSede');
    }

    public function formato(): BelongsTo
    {
        return $this->belongsTo(FormatoDesignacion::class, 'idFormato', 'idFormato');
    }

    public function partidoCreado(): BelongsTo
    {
        return $this->belongsTo(Partido::class, 'idPartidoCreado', 'idPartido');
    }

    public function tieneErrores(): bool
    {
        return ($this->errores ?? []) !== [];
    }
}
