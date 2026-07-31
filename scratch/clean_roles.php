<?php

use App\Models\Role;
use App\Models\User;

echo "Iniciando reasignación de usuarios...\n";
$userMap = [
    'admin@aurofarma.com' => 'ADMIN',
    'constanza@aurofarma.com' => 'DIRECTOR DE ASEGURAMIENTO Y CONTROL DE CALIDAD',
    'calidad@aurofarma.com' => 'COORDINADOR DE ASEGURAMIENTO DE CALIDAD',
    'inspector@aurofarma.com' => 'INSPECTOR DE CALIDAD',
    'dt@aurofarma.com' => 'DIRECTOR TECNICO Y DE PRODUCCION',
    'operario@aurofarma.com' => 'OPERARIO'
];

foreach($userMap as $email => $roleName) {
    echo "Procesando $email...\n";
    $user = User::where('email', $email)->first();
    $role = Role::where('name', $roleName)->first();
    if($user && $role) {
        if(method_exists($user, 'roles')) {
            $user->roles()->sync([$role->id]);
        }
        $user->role = $roleName;
        $user->save();
        echo "- Usuario $email actualizado a $roleName\n";
    } else {
        echo "- Usuario $email o Rol $roleName no encontrados\n";
    }
}

echo "Limpiando roles obsoletos...\n";
$officialRoles = [
    'ADMIN',
    'DIRECTOR TECNICO Y DE PRODUCCION',
    'ANALISTA DE PRODUCCION',
    'DIRECTOR DE ASEGURAMIENTO Y CONTROL DE CALIDAD',
    'COORDINADOR DE ASEGURAMIENTO DE CALIDAD',
    'INSPECTOR DE CALIDAD',
    'AUXILIAR DE CALIDAD',
    'OPERARIO'
];

$deletedCount = Role::whereNotIn('name', $officialRoles)->delete();
echo "- Roles eliminados: $deletedCount\n";

echo "Limpieza completada: Usuarios migrados y roles duplicados eliminados.\n";
