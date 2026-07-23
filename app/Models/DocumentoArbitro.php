<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DocumentoArbitro extends Model
{
    use HasFactory;

    public const ESTADO_PENDIENTE = 'pendiente';

    public const ESTADO_EN_REVISION = 'en_revision';

    public const ESTADO_APROBADO = 'aprobado';

    public const ESTADO_DEVUELTO = 'devuelto';

    public const ESTADOS = [
        self::ESTADO_PENDIENTE,
        self::ESTADO_EN_REVISION,
        self::ESTADO_APROBADO,
        self::ESTADO_DEVUELTO,
    ];

    protected $table = 'documentos_arbitro';

    protected $primaryKey = 'idDocumento';

    protected $keyType = 'int';

    public $incrementing = true;

    protected $fillable = [
        'idArbitro',
        'idRequisito',
        'nombreDocumento',
        'nombreOriginal',
        'archivoRuta',
        'tipoMime',
        'tamanoBytes',
        'obligatorio',
        'fechaSubida',
        'estadoRevision',
        'comentarioRevision',
        'fechaRevision',
        'idUsuarioRevision',
        'version',
    ];

    protected $casts = [
        'obligatorio' => 'boolean',
        'tamanoBytes' => 'integer',
        'fechaSubida' => 'datetime',
        'fechaRevision' => 'datetime',
        'version' => 'integer',
    ];

    protected $appends = ['estadoRevisionLabel', 'estadoRevisionColor', 'tamanoLegible'];

    public function getEstadoRevisionLabelAttribute(): string
    {
        return match ($this->estadoRevision) {
            self::ESTADO_EN_REVISION => 'En revision',
            self::ESTADO_APROBADO => 'Aprobado',
            self::ESTADO_DEVUELTO => 'Devuelto',
            default => 'Pendiente',
        };
    }

    public function getEstadoRevisionColorAttribute(): string
    {
        return match ($this->estadoRevision) {
            self::ESTADO_EN_REVISION => 'blue',
            self::ESTADO_APROBADO => 'green',
            self::ESTADO_DEVUELTO => 'red',
            default => 'gray',
        };
    }

    public function getTamanoLegibleAttribute(): string
    {
        if (! $this->tamanoBytes) {
            return 'Tamano no disponible';
        }

        if ($this->tamanoBytes < 1024 * 1024) {
            return number_format($this->tamanoBytes / 1024, 1, ',', '.').' KB';
        }

        return number_format($this->tamanoBytes / (1024 * 1024), 1, ',', '.').' MB';
    }

    public function arbitro(): BelongsTo
    {
        return $this->belongsTo(Arbitro::class, 'idArbitro', 'idArbitro');
    }

    public function requisito(): BelongsTo
    {
        return $this->belongsTo(RequisitoDocumentoArbitro::class, 'idRequisito', 'idRequisito');
    }

    public function revisor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'idUsuarioRevision', 'idUsuario');
    }
}
