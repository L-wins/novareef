<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AdminLoginLog extends Model
{
    protected $table      = 'admin_login_logs';
    protected $primaryKey = 'idLog';
    protected $keyType    = 'int';
    public    $incrementing = true;
    public    $timestamps   = false;

    protected $fillable = [
        'ip',
        'email',
        'exitoso',
        'user_agent',
    ];

    protected $casts = [
        'exitoso'    => 'boolean',
        'created_at' => 'datetime',
    ];
}
