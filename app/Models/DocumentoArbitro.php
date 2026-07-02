<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DocumentoArbitro extends Model
{
    use HasFactory;

    protected $table      = 'documentos_arbitro';
    protected $primaryKey = 'idDocumento';
    protected $keyType    = 'int';
    public    $incrementing = true;

    protected $fillable = [
        'idArbitro',
        'nombreDocumento',
        'archivoRuta',
        'tipoMime',
        'tamanoBytes',
        'obligatorio',
        'fechaSubida',
    ];

    protected $casts = [
        'obligatorio' => 'boolean',
        'tamanoBytes' => 'integer',
        'fechaSubida' => 'datetime',
    ];

    public function arbitro(): BelongsTo
    {
        return $this->belongsTo(Arbitro::class, 'idArbitro', 'idArbitro');
    }
}
