<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CategoriaArbitro extends Model
{
    use HasFactory;

    protected $table      = 'categorias_arbitro';
    protected $primaryKey = 'idCategoria';
    protected $keyType    = 'int';
    public    $incrementing = true;

    protected $fillable = [
        'idColegio',
        'nombreCategoria',
        'descripcion',
        'esPorDefecto',
        'activa',
    ];

    protected $casts = [
        'esPorDefecto' => 'boolean',
        'activa'       => 'boolean',
    ];

    public function colegio(): BelongsTo
    {
        return $this->belongsTo(Colegio::class, 'idColegio', 'idColegio');
    }

    public function arbitros(): HasMany
    {
        return $this->hasMany(Arbitro::class, 'idCategoria', 'idCategoria');
    }
}
