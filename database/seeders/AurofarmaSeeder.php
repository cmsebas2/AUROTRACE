<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use App\Models\User;
use App\Models\Role;

class AurofarmaSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // 1. Jerarquía estricta BPM
        $jerarquia = [
            'ADMIN' => 'admin',
            'DIRECTOR TECNICO Y DE PRODUCCION' => 'director_tecnico',
            'ANALISTA DE PRODUCCION' => 'analista_produccion',
            'DIRECTOR DE ASEGURAMIENTO Y CONTROL DE CALIDAD' => 'director_calidad',
            'COORDINADOR DE ASEGURAMIENTO DE CALIDAD' => 'coordinador_calidad',
            'INSPECTOR DE CALIDAD' => 'inspector_calidad',
            'AUXILIAR DE CALIDAD' => 'auxiliar_calidad',
            'OPERARIO' => 'operario'
        ];

        foreach ($jerarquia as $roleName => $username) {
            $role = Role::firstOrCreate(['name' => $roleName]);
            $enumRole = str_contains(strtolower($roleName), 'calidad') ? 'calidad' : (str_contains(strtolower($roleName), 'operario') ? 'operario' : (str_contains(strtolower($roleName), 'tecnico') ? 'direccion_tecnica' : 'admin'));

            $user = User::updateOrCreate(
                ['email' => $username . '@temp.local'],
                [
                    'name' => $roleName,
                    'password' => Hash::make('admin'),
                    'pin_firma' => Hash::make('admin'),
                    'role' => $enumRole
                ]
            );

            if (method_exists($user, 'roles')) {
                $user->roles()->sync([$role->id]);
            }
        }

        // 2. Usuario específico de Calidad (calidad / calidad)
        $roleCalidad = Role::firstOrCreate(['name' => 'CALIDAD']);
        $userCalidad = User::updateOrCreate(
            ['email' => 'calidad@temp.local'],
            [
                'name' => 'Control de Calidad',
                'password' => Hash::make('calidad'),
                'pin_firma' => Hash::make('calidad'),
                'role' => 'calidad'
            ]
        );

        if (method_exists($userCalidad, 'roles')) {
            $userCalidad->roles()->sync([$roleCalidad->id]);
        }
    }
}
