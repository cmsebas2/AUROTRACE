<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

use App\Models\Role;
use App\Models\Permission;

class RolePermissionSeeder extends Seeder
{
    public function run(): void
    {
        $roles = ['admin', 'operario', 'calidad', 'direccion_tecnica'];
        foreach ($roles as $r) {
            Role::firstOrCreate(['name' => $r]);
        }

        $permissions = ['crear_op', 'aprobar_op', 'ver_auditoria', 'gestionar_inventario', 'firmar_liberacion'];
        foreach ($permissions as $p) {
            Permission::firstOrCreate(['name' => $p]);
        }
        
        $admin = Role::where('name', 'admin')->first();
        $admin->permissions()->sync(Permission::pluck('id'));
    }
}
