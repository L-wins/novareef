<?php

declare(strict_types=1);

namespace App\Exceptions;

/**
 * Se lanza cuando el optimistic locking detecta que el registro fue
 * modificado por otra transacción concurrente entre la lectura y la escritura.
 * El caller debe reintentar la operación o informar al usuario.
 */
class OptimisticLockException extends \RuntimeException
{
    public function __construct(string $mensaje = 'El registro fue modificado por otra operación. Recarga la página e inténtalo de nuevo.')
    {
        parent::__construct($mensaje);
    }
}
