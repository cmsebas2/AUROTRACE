<?php

use App\Models\Role;
use App\Models\Permission;
use Illuminate\Support\Facades\Artisan;

echo "Vinculando permisos oficiales...\n";

$admin = Role::where('name', 'ADMIN')->first();
if($admin) {
    $admin->permissions()->sync(Permission::all());
    echo "- ADMIN vinculado a todos los permisos.\n";
}

$qaDir = Role::where('name', 'DIRECTOR DE ASEGURAMIENTO Y CONTROL DE CALIDAD')->first();
if($qaDir) {
    $p = Permission::whereIn('name', ['liberacion_final_lote', 'ver_audit_trail'])->pluck('id');
    $qaDir->permissions()->sync($p);
    echo "- Director QA vinculado.\n";
}

$inspector = Role::where('name', 'INSPECTOR DE CALIDAD')->first();
if($inspector) {
    $p = Permission::whereIn('name', ['verificacion_controles_en_proceso', 'realizar_despeje_y_muestreo'])->pluck('id');
    $inspector->permissions()->sync($p);
    echo "- Inspector vinculado.\n";
}

$operario = Role::where('name', 'OPERARIO')->first();
if($operario) {
    $p = Permission::whereIn('name', ['registrar_manufactura_y_pesaje'])->pluck('id');
    $operario->permissions()->sync($p);
    echo "- Operario vinculado.\n";
}

echo "Limpiando cache...\n";
Artisan::call('cache:clear');
echo "Cache limpia.\n";
