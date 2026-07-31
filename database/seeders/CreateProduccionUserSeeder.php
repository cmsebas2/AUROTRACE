<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Role;
use App\Models\Permission;
use Illuminate\Support\Facades\Hash;

class CreateProduccionUserSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Crear el Rol
        $roleName = 'Analista de Producción';
        $role = Role::firstOrCreate(['name' => $roleName]);

        // 2. Crear Permiso específico (opcional pero recomendado)
        $permission = Permission::firstOrCreate(['name' => 'acceder_reacondicionamiento']);
        
        // Sincronizar permiso con el rol
        $role->permissions()->syncWithoutDetaching([$permission->id]);

        // 3. Crear el Usuario
        $user = User::updateOrCreate(
            ['email' => 'produccion@temp.local'],
            [
                'name' => 'Analista de Producción',
                'password' => Hash::make('AuroTrace2026*'),
                'role' => $roleName,
                'pin_firma' => Hash::make('AuroTrace2026*'), // Por si se usa para firmas
            ]
        );

        $this->command->info("Usuario 'produccion' creado exitosamente con el rol '{$roleName}'.");
    }
}
