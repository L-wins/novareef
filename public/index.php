<?php

use Illuminate\Http\Request;

define('LARAVEL_START', microtime(true));

// display_errors=On (default de este php.ini local) imprime warnings/notices
// de PHP directo en el cuerpo de la respuesta — en un endpoint JSON eso
// corrompe la respuesta antes de llegar al navegador ("El servidor devolvió
// una respuesta inválida"). log_errors sigue activo, así que nada se pierde,
// solo deja de mezclarse con el output real.
ini_set('display_errors', '0');

// Determine if the application is in maintenance mode...
if (file_exists($maintenance = __DIR__.'/../storage/framework/maintenance.php')) {
    require $maintenance;
}

// Register the Composer autoloader...
require __DIR__.'/../vendor/autoload.php';

// Bootstrap Laravel and handle the request...
(require_once __DIR__.'/../bootstrap/app.php')
    ->handleRequest(Request::capture());
