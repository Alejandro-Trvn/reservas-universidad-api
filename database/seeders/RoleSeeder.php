<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Role;

class RoleSeeder extends Seeder
{
    public function run(): void
    {
        // Admin
        Role::updateOrCreate(
            ['nombre' => 'admin'], // clave de búsqueda
            [
                'descripcion' => 'Administrador del sistema',
                'estado' => 1,
            ]
        );

        // Usuario normal
        Role::updateOrCreate(
            ['nombre' => 'usuario'],
            [
                'descripcion' => 'Usuario estándar',
                'estado' => 1,
            ]
        );
    }
}
