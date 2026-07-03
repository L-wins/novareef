<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('colegio.{idColegio}.partidos', function (User $user, int $idColegio) {
    return (int) $user->idColegio === $idColegio;
});

Broadcast::channel('colegio.{idColegio}.designaciones', function (User $user, int $idColegio) {
    return (int) $user->idColegio === $idColegio;
});
