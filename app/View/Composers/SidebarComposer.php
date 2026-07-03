<?php

declare(strict_types=1);

namespace App\View\Composers;

use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class SidebarComposer
{
    public function compose(View $view): void
    {
        $view->with('modulosPlan', Auth::user()?->colegio?->plan?->modulosJSON ?? []);
    }
}
