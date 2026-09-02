<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Eliminar tablas relacionadas a Maquilas de la Base de Datos
        Schema::dropIfExists('maquila_deliveries');
        Schema::dropIfExists('maquila_order_items');
        Schema::dropIfExists('maquila_orders');
        Schema::dropIfExists('maquila_tracking');
        Schema::dropIfExists('maquila_items');

        // 2. Eliminar permiso de gestionar_maquilas
        try {
            $permission = DB::table('permissions')->where('name', 'gestionar_maquilas')->first();
            if ($permission) {
                DB::table('permission_role')->where('permission_id', $permission->id)->delete();
                DB::table('permissions')->where('id', $permission->id)->delete();
            }
        } catch (\Throwable $e) {
            // Ignorar si la tabla de permisos no soporta o no existe en ciertos entornos
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Operación irreversible de eliminación permanente
    }
};
