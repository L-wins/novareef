<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AdminActionLog extends Model
{
    protected $table      = 'admin_action_logs';
    protected $primaryKey = 'idLog';
    protected $keyType    = 'int';
    public    $incrementing = true;
    public    $timestamps   = false;

    protected $fillable = [
        'idAdmin',
        'accion',
        'entidad',
        'entidadId',
        'detalle',
    ];

    protected $casts = [
        'created_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $log): void {
            if (empty($log->created_at)) {
                $log->created_at = now();
            }
        });
    }

    public function admin(): BelongsTo
    {
        return $this->belongsTo(Admin::class, 'idAdmin', 'idAdmin');
    }
}
