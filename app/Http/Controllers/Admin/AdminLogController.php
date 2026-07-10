<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AdminActionLog;
use App\Models\AdminLoginLog;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AdminLogController extends Controller
{
    public function index(Request $request): View
    {
        $query = AdminLoginLog::query()->orderByDesc('created_at');

        if ($email = trim((string) $request->input('email', ''))) {
            $query->where('email', 'like', "%{$email}%");
        }

        if ($request->filled('resultado')) {
            $query->where('exitoso', $request->input('resultado') === 'exitoso');
        }

        $logs = $query->paginate(25, ['*'], 'accesos')->withQueryString();

        $acciones = AdminActionLog::with('admin')
            ->orderByDesc('created_at')
            ->paginate(25, ['*'], 'acciones')
            ->withQueryString();

        return view('admin.logs.index', compact('logs', 'acciones'));
    }
}
