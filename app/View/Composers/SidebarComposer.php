<?php

declare(strict_types=1);

namespace App\View\Composers;

use App\Services\LimiteService;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class SidebarComposer
{
    public function __construct(private readonly LimiteService $limites)
    {
    }

    public function compose(View $view): void
    {
        $idColegio = Auth::user()?->idColegio;

        $view->with('modulosPlan', $idColegio === null ? [] : $this->limites->modulosHabilitados($idColegio));
    }
}
