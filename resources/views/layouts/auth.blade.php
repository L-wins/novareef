<!DOCTYPE html>
<html lang="es" class="scroll-smooth">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="@yield('descripcion', 'NovaReef — Inicia sesión en tu colegio de árbitros.')">
    <title>@yield('titulo', 'Iniciar sesión') — NovaReef</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700,800&display=swap" rel="stylesheet" />
    @vite(['resources/css/auth/login.css', 'resources/js/auth/login.js'])
</head>
<body class="@yield('body_class', 'bg-slate-950 text-white antialiased min-h-screen login-bg login-grid flex items-center justify-center px-4 py-12')">

    @yield('contenido')

</body>
</html>
