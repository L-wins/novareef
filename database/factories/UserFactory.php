<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\User>
 */
class UserFactory extends Factory
{
    public function definition(): array
    {
        return [
            'nombreUsuario'   => fake()->name(),
            'emailUsuario'    => fake()->unique()->safeEmail(),
            'passwordUsuario' => 'password', // cast 'hashed' lo encripta automáticamente
            'telefonoUsuario' => fake()->optional()->numerify('3#########'),
            'rolUsuario'      => 'arbitro',
            'estadoUsuario'   => 'activo',
            'temaPreferencia' => 'oscuro',
            'remember_token'  => Str::random(10),
        ];
    }

    public function superadmin(): static
    {
        return $this->state(fn () => ['rolUsuario' => 'superadmin']);
    }

    public function suspendido(): static
    {
        return $this->state(fn () => ['estadoUsuario' => 'suspendido']);
    }
}
