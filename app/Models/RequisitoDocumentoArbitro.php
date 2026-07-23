<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class RequisitoDocumentoArbitro extends Model
{
    use HasFactory;

    protected $table = 'requisitos_documento_arbitro';

    protected $primaryKey = 'idRequisito';

    protected $keyType = 'int';

    public $incrementing = true;

    protected $fillable = [
        'idColegio',
        'idCategoria',
        'nombre',
        'descripcion',
        'obligatorio',
        'requiereRevision',
        'activo',
        'orden',
        'plantillaRuta',
        'plantillaNombreOriginal',
        'plantillaMime',
        'plantillaTamanoBytes',
    ];

    protected $casts = [
        'obligatorio' => 'boolean',
        'requiereRevision' => 'boolean',
        'activo' => 'boolean',
        'idCategoria' => 'integer',
        'orden' => 'integer',
        'plantillaTamanoBytes' => 'integer',
    ];

    public function colegio(): BelongsTo
    {
        return $this->belongsTo(Colegio::class, 'idColegio', 'idColegio');
    }

    public function categoria(): BelongsTo
    {
        return $this->belongsTo(CategoriaArbitro::class, 'idCategoria', 'idCategoria');
    }

    public function documentos(): HasMany
    {
        return $this->hasMany(DocumentoArbitro::class, 'idRequisito', 'idRequisito');
    }
}
