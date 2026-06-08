<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReglamentoTorneo extends Model
{
    use HasFactory;

    protected $table        = 'reglamentos_torneo';
    protected $primaryKey   = 'idReglamento';
    protected $keyType      = 'int';
    public    $incrementing = true;
    public    $timestamps   = false;

    protected $fillable = [
        'idTorneo',
        'nombreArchivo',
        'rutaArchivo',
        'tamanoBytes',
        'esActual',
        'idUsuarioSubida',
    ];

    protected $casts = [
        'tamanoBytes' => 'integer',
        'esActual'    => 'boolean',
        'created_at'  => 'datetime',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $r): void {
            $r->created_at ??= now();
        });
    }

    public function torneo(): BelongsTo
    {
        return $this->belongsTo(Torneo::class, 'idTorneo', 'idTorneo');
    }

    public function subidoPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'idUsuarioSubida', 'idUsuario');
    }

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
}
