<!DOCTYPE html>
<html lang="es" class="scroll-smooth">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="@yield('descripcion', 'NovaReef — La plataforma digital para la gestión integral de colegios de árbitros de fútbol en Colombia.')">
    <title>@yield('titulo', 'NovaReef — Gestión de Árbitros de Fútbol')</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700,800,900&display=swap" rel="stylesheet" />
    @vite(['resources/css/welcome.css', 'resources/js/welcome.js'])
</head>
<body class="bg-slate-950 text-white antialiased">

    @yield('contenido')

</body>
</html>
