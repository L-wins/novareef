<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MaterialAcademico extends Model
{
    use HasFactory;

    protected $table        = 'materiales_academicos';
    protected $primaryKey   = 'idMaterial';
    protected $keyType      = 'int';
    public    $incrementing = true;

    protected $fillable = [
        'idColegio',
        'idSesion',
        'titulo',
        'archivo',
        'extension',
        'tamanoBytes',
        'idUsuarioSubio',
    ];

    protected $casts = [
        'tamanoBytes' => 'integer',
    ];

    public function getTamanoLegibleAttribute(): string
    {
        $bytes = (int) $this->tamanoBytes;

        if ($bytes >= 1_048_576) {
            return number_format($bytes / 1_048_576, 1, ',', '.') . ' MB';
        }
        if ($bytes >= 1024) {
            return number_format($bytes / 1024, 1, ',', '.') . ' KB';
        }
        return $bytes . ' B';
    }

    /** Ícono Font Awesome según extensión, para no repetirlo en cada vista. */
    public function getIconoAttribute(): string
    {
        return match (strtolower($this->extension)) {
            'pdf'                => 'fa-file-pdf',
            'doc', 'docx'        => 'fa-file-word',
            'ppt', 'pptx'        => 'fa-file-powerpoint',
            'xls', 'xlsx'        => 'fa-file-excel',
            'jpg', 'jpeg', 'png' => 'fa-file-image',
            default              => 'fa-file',
        };
    }

    public function colegio(): BelongsTo
    {
        return $this->belongsTo(Colegio::class, 'idColegio', 'idColegio');
    }

    public function sesion(): BelongsTo
    {
        return $this->belongsTo(SesionAcademica::class, 'idSesion', 'idSesion');
    }

    public function usuarioSubio(): BelongsTo
    {
        return $this->belongsTo(User::class, 'idUsuarioSubio', 'idUsuario');
    }
}
