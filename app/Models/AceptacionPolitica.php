<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AceptacionPolitica extends Model
{
    protected $table      = 'aceptaciones_politica_privacidad';
    protected $primaryKey = 'idAceptacion';
    public    $timestamps = false;

    protected $fillable = [
        'idUsuario',
        'version',
        'tipo',
        'ip',
    ];

    protected $casts = [
        'created_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $aceptacion): void {
            if (empty($aceptacion->created_at)) {
                $aceptacion->created_at = now();
            }
        });
    }

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'idUsuario', 'idUsuario');
    }
}
