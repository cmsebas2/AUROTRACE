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
        $roles = [
            'admin',
            'ADMIN',
            'operario',
            'OPERARIO',
            'calidad',
            'CALIDAD',
            'INSPECTOR DE CALIDAD',
            'DIRECTOR DE ASEGURAMIENTO Y CONTROL DE CALIDAD',
            'direccion_tecnica',
            'DIRECCION TECNICA',
            'DIRECCIÓN TÉCNICA',
            'Analista de Producción'
        ];
        foreach ($roles as $r) {
            Role::firstOrCreate(['name' => $r]);
        }

        $permissions = [
            'ver_dashboard',
            'ver_productos',
            'crear_op',
            'aprobar_op',
            'ver_monitoreo_ops',
            'ejecutar_manufactura',
            'ver_aseguramiento_calidad',
            'ver_genealogia',
            'liberacion_final_lote',
            'ver_expedientes_batch_records',
            'ver_modulo_archivo_3d',
            'acceso_maquilas_externas',
            'gestionar_usuarios_roles',
            'gestionar_ajustes_sistema',
            'ver_auditoria',
            'ver_audit_trail',
            'gestionar_inventario',
            'firmar_liberacion',
            'verificacion_controles_en_proceso',
            'realizar_despeje_y_muestreo',
            'registrar_manufactura_y_pesaje',
            'registrar_ajuste_activos',
            'verificar_ajuste_activos',
            'solicitar_aprobacion_codificado',
            'registrar_coas'
        ];
        foreach ($permissions as $p) {
            Permission::firstOrCreate(['name' => $p]);
        }
        
        $allPermissionIds = Permission::pluck('id');
        $adminRoles = Role::whereIn('name', ['admin', 'ADMIN', 'direccion_tecnica', 'DIRECCION TECNICA', 'DIRECCIÓN TÉCNICA'])->get();
        foreach ($adminRoles as $adminRole) {
            $adminRole->permissions()->sync($allPermissionIds);
        }

        // Vincular permisos específicos para QA y Operarios
        $qaDir = Role::whereIn('name', ['DIRECTOR DE ASEGURAMIENTO Y CONTROL DE CALIDAD', 'calidad', 'CALIDAD'])->get();
        $qaPerms = Permission::whereIn('name', [
            'ver_dashboard', 'ver_productos', 'ver_monitoreo_ops', 'ver_aseguramiento_calidad',
            'ver_genealogia', 'liberacion_final_lote', 'ver_expedientes_batch_records',
            'ver_modulo_archivo_3d', 'acceso_maquilas_externas', 'verificacion_controles_en_proceso',
            'realizar_despeje_y_muestreo', 'ver_audit_trail', 'verificar_ajuste_activos'
        ])->pluck('id');
        foreach ($qaDir as $qr) {
            $qr->permissions()->sync($qaPerms);
        }

        $operarios = Role::whereIn('name', ['operario', 'OPERARIO'])->get();
        $opPerms = Permission::whereIn('name', [
            'ver_dashboard', 'ejecutar_manufactura', 'registrar_manufactura_y_pesaje',
            'registrar_ajuste_activos', 'solicitar_aprobacion_codificado'
        ])->pluck('id');
        foreach ($operarios as $opr) {
            $opr->permissions()->sync($opPerms);
        }
    }
}
