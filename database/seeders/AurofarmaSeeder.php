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
        // 1. Limpiar para evitar duplicados
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        DB::table('users')->truncate();
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');

        // 2. Jerarquía estricta BPM
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

            // Se utiliza forceCreate para evitar restricciones de $fillable en campos como pin_firma
            $user = User::forceCreate([
                'name' => $roleName,
                'email' => $username . '@temp.local',
                'password' => Hash::make('admin'),
                'pin_firma' => Hash::make('admin'),
                'role' => $roleName
            ]);

            if (method_exists($user, 'roles')) {
                $user->roles()->sync([$role->id]);
            }
        }
    }
}
