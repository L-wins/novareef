<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verificación 2FA — NovaReef Admin</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700,800&display=swap" rel="stylesheet">
    <script src="https://unpkg.com/feather-icons" defer></script>
    @vite(['resources/css/admin/admin.css', 'resources/js/admin/admin.js'])
</head>
<body class="admin-body">

<div class="admin-2fa-wrap">
    <div class="admin-2fa-card">

        {{-- Logo --}}
        <div class="admin-2fa-logo">
            <div class="admin-2fa-logo__icon">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none"
                     stroke="currentColor" stroke-width="2">
                    <circle cx="12" cy="12" r="10"/>
                    <path d="M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z"/>
                    <path d="M2 12h20"/>
                </svg>
            </div>
            <span class="admin-2fa-logo__name">NovaReef</span>
        </div>

        {{-- Ícono --}}
        <div class="admin-2fa-shield">
            <i data-feather="smartphone"></i>
        </div>

        <h1 style="font-size:1.25rem;font-weight:800;color:var(--text-bright);text-align:center;margin:0 0 6px;letter-spacing:-0.3px;">
            Verificación en dos pasos
        </h1>
        <p style="font-size:0.875rem;color:var(--text);text-align:center;margin:0 0 2rem;line-height:1.6;">
            Abre <strong style="color:var(--text-bright);">Google Authenticator</strong>
            y escribe el código de 6 dígitos
        </p>

        {{-- Error --}}
        @if ($errors->any())
        <div class="a-alert a-alert--danger" style="margin-bottom:1.5rem;">
            <i data-feather="alert-circle"></i>
            {{ $errors->first() }}
        </div>
        @endif

        <form method="POST" action="{{ route('admin.2fa.post') }}" id="otp-form">
            @csrf
            <input type="hidden" name="code" id="otp-code">

            {{-- 6 dígitos individuales --}}
            <div class="otp-row {{ $errors->any() ? 'otp-has-error' : '' }}" id="otp-group">
                @for ($i = 0; $i < 6; $i++)
                    <input type="text"
                           class="otp-digit"
                           inputmode="numeric"
                           pattern="[0-9]"
                           maxlength="1"
                           autocomplete="{{ $i === 0 ? 'one-time-code' : 'off' }}"
                           aria-label="Dígito {{ $i + 1 }}">
                @endfor
            </div>

            <button type="submit" class="a-btn a-btn--primary a-btn--full" style="margin-bottom:1rem;">
                <i data-feather="check"></i>
                Verificar código
            </button>
        </form>

        <a href="{{ route('welcome') }}"
           style="display:flex;align-items:center;justify-content:center;gap:6px;
                  font-size:0.8125rem;color:var(--text);transition:color .2s;"
           onmouseover="this.style.color='var(--text-bright)'"
           onmouseout="this.style.color='var(--text)'">
            <i data-feather="arrow-left" style="width:14px;height:14px;"></i>
            Volver al inicio
        </a>

    </div>
</div>

</body>
</html>
