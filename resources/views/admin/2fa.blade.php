<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verificación 2FA — NovaReef Admin</title>
    <link rel="icon" type="image/png" href="{{ asset('images/logo/novareef-nr-light.png') }}" media="(prefers-color-scheme: light)">
    <link rel="icon" type="image/png" href="{{ asset('images/logo/novareef-nr-dark.png') }}" media="(prefers-color-scheme: dark)">
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700,800&display=swap" rel="stylesheet">
    @vite(['resources/css/admin/admin.css', 'resources/js/admin/admin.js'])
</head>
<body class="admin-body">

<div class="admin-2fa-wrap">
    <div class="admin-2fa-card">

        {{-- Logo --}}
        <div class="admin-2fa-logo">
            <div class="admin-2fa-logo__icon">
                <img src="{{ asset('images/logo/novareef-logo-icontile.png') }}" alt="NovaReef">
            </div>
            <span class="admin-2fa-logo__name">NovaReef</span>
        </div>

        {{-- Ícono --}}
        <div class="admin-2fa-shield">
            <i class="fa-solid fa-mobile-screen"></i>
        </div>

        <h1 class="admin-2fa-title">Verificación en dos pasos</h1>
        <p class="admin-2fa-sub">
            Abre <strong>Google Authenticator</strong>
            y escribe el código de 6 dígitos
        </p>

        {{-- Error --}}
        @if ($errors->any())
        <div class="a-alert a-alert--danger mb-6">
            <i class="fa-solid fa-circle-exclamation"></i>
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

            <button type="submit" class="a-btn a-btn--primary a-btn--full mb-4">
                <i class="fa-solid fa-check"></i>
                Verificar código
            </button>
        </form>

        <a href="{{ route('welcome') }}" class="a-back-home a-back-home--center">
            <i class="fa-solid fa-arrow-left"></i>
            Volver al inicio
        </a>

    </div>
</div>

</body>
</html>
