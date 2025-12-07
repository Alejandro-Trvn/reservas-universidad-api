<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Role;
use Illuminate\Support\Facades\Hash;

class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        $adminRole = Role::firstOrCreate(
            ['nombre' => 'admin'],
            ['descripcion' => 'Administrador del sistema', 'estado' => 1]
        );

        User::updateOrCreate(
            ['email' => 'admin@uni.com'],
            [
                'name'                 => 'Administrador',
                'password'             => Hash::make('admin123'),
                'role_id'              => $adminRole->id,
                'estado'               => 1,
                'must_change_password' => false, 
            ]
        );
    }
}
