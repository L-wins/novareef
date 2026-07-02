<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class Admin extends Authenticatable
{
    use Notifiable;

    protected $table      = 'admins';
    protected $primaryKey = 'idAdmin';
    protected $guard      = 'admin';

    protected $fillable = [
        'nombre',
        'email',
        'password',
        'google2fa_secret',
        'two_factor_enabled',
        'activo',
        'ultimo_acceso',
    ];

    protected $hidden = [
        'password',
        'google2fa_secret',
        'remember_token',
    ];

    protected $casts = [
        'two_factor_enabled' => 'boolean',
        'activo'             => 'boolean',
        'ultimo_acceso'      => 'datetime',
        'password'           => 'hashed',
    ];
}
