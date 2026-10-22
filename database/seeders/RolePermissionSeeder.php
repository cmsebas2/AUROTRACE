<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Role;
use App\Models\Permission;
use App\Models\User;

class RolePermissionSeeder extends Seeder
{
    public function run(): void
    {
        $roles = [
            'ADMIN',
            'DIRECTOR TECNICO Y DE PRODUCCION',
            'DIRECCION TECNICA',
            'ANALISTA DE PRODUCCION',
            'Analista de Producción',
            'DIRECTOR DE ASEGURAMIENTO Y CONTROL DE CALIDAD',
            'COORDINADOR DE ASEGURAMIENTO DE CALIDAD',
            'INSPECTOR DE CALIDAD',
            'AUXILIAR DE CALIDAD',
            'CALIDAD',
            'OPERARIO',
        ];

        foreach ($roles as $r) {
            Role::firstOrCreate(['name' => $r]);
        }

        // Sincronizar todos los permisos del catálogo maestro de AuroTrace
        $catalog = User::flatPermissionCatalog();
        foreach (array_keys($catalog) as $permName) {
            Permission::firstOrCreate(['name' => $permName]);
        }

        $allPermissionIds = Permission::pluck('id');

        // 1. Administradores y Dirección Técnica (Acceso Maestro)
        $adminRoles = Role::whereIn('name', [
            'admin', 'ADMIN',
            'direccion_tecnica', 'DIRECCION TECNICA', 'DIRECCIÓN TÉCNICA',
            'DIRECTOR TECNICO Y DE PRODUCCION',
        ])->get();

        foreach ($adminRoles as $adminRole) {
            $adminRole->permissions()->sync($allPermissionIds);
        }

        // 2. Dirección / Coordinación de Aseguramiento de Calidad (QA Senior)
        $qaDirectors = Role::whereIn('name', [
            'DIRECTOR DE ASEGURAMIENTO Y CONTROL DE CALIDAD',
            'COORDINADOR DE ASEGURAMIENTO DE CALIDAD',
            'CALIDAD',
            'calidad',
        ])->get();

        $qaDirectorPerms = Permission::whereIn('name', [
            'ver_dashboard',
            'ver_productos',
            'imprimir_ficha_tecnica',
            'aprobar_op',
            'ver_monitoreo_ops',
            'verificar_ajuste_activos',
            'aprobar_codificado',
            'verificacion_controles_en_proceso',
            'realizar_despeje_y_muestreo',
            'ver_aseguramiento_calidad',
            'registrar_coas',
            'aprobar_coas',
            'dictaminar_calidad_qa',
            'ver_expedientes_batch_records',
            'ver_genealogia',
            'liberacion_final_lote',
            'firmar_liberacion',
            'ver_modulo_archivo_3d',
            'gestionar_archivo_fisico_3d',
            'acceso_maquilas_externas',
            'revision_calidad_maquila',
            'ver_audit_trail',
            'ver_auditoria',
        ])->pluck('id');

        foreach ($qaDirectors as $qr) {
            $qr->permissions()->sync($qaDirectorPerms);
        }

        // 3. Inspectores y Auxiliares de Calidad (QA Planta)
        $qaInspectors = Role::whereIn('name', [
            'INSPECTOR DE CALIDAD',
            'AUXILIAR DE CALIDAD',
        ])->get();

        $qaInspectorPerms = Permission::whereIn('name', [
            'ver_productos',
            'ver_monitoreo_ops',
            'verificar_ajuste_activos',
            'aprobar_codificado',
            'realizar_despeje_y_muestreo',
            'verificacion_controles_en_proceso',
            'ver_aseguramiento_calidad',
            'registrar_coas',
            'dictaminar_calidad_qa',
            'ver_expedientes_batch_records',
            'ver_modulo_archivo_3d',
            'gestionar_archivo_fisico_3d',
            'acceso_maquilas_externas',
            'revision_calidad_maquila',
        ])->pluck('id');

        foreach ($qaInspectors as $qi) {
            $qi->permissions()->sync($qaInspectorPerms);
        }

        // 4. Analistas de Producción (Planeación, OPs, Maquilas y Archivo)
        $analistas = Role::whereIn('name', [
            'ANALISTA DE PRODUCCION',
            'Analista de Producción',
        ])->get();

        $analistaPerms = Permission::whereIn('name', [
            'ver_dashboard',
            'ver_productos',
            'crear_ficha_inicial_producto',
            'configurar_presentaciones_envases',
            'configurar_maquiladores_lotes_producto',
            'editar_ficha_producto',
            'gestionar_productos_formulas',
            'configurar_instructivos_ebr',
            'imprimir_ficha_tecnica',
            'crear_op',
            'ver_monitoreo_ops',
            'registrar_ajuste_activos',
            'solicitar_aprobacion_codificado',
            'ejecutar_manufactura',
            'ver_expedientes_batch_records',
            'ver_modulo_archivo_3d',
            'gestionar_archivo_fisico_3d',
            'acceso_maquilas_externas',
            'gestionar_ordenes_maquila',
            'registrar_recepcion_maquila',
            'acceder_reacondicionamiento',
            'gestionar_inventario',
        ])->pluck('id');

        foreach ($analistas as $an) {
            $an->permissions()->sync($analistaPerms);
        }

        // 5. Operarios de Planta EBR (Maker)
        $operarios = Role::whereIn('name', ['operario', 'OPERARIO'])->get();
        $opPerms = Permission::whereIn('name', [
            'ver_dashboard',
            'ver_productos',
            'ejecutar_manufactura',
            'registrar_conciliacion_materiales',
            'realizar_despeje_y_muestreo',
            'registrar_manufactura_y_pesaje',
            'registrar_envasado_acondicionamiento',
            'registrar_ajuste_activos',
            'solicitar_aprobacion_codificado',
        ])->pluck('id');

        foreach ($operarios as $opr) {
            $opr->permissions()->sync($opPerms);
        }
    }
}
