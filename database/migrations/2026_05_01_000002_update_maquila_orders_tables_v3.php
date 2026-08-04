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
        // 1. Eliminar tablas previas si existieran
        Schema::dropIfExists('maquila_order_items');
        Schema::dropIfExists('maquila_orders');

        // 2. Crear tabla maquila_orders (V3)
        Schema::create('maquila_orders', function (Blueprint $table) {
            $table->id();
            $table->enum('tipo_producto', ['PREMEZCLA', 'MAQUILA']);
            $table->string('odm')->unique()->index();
            $table->string('sdm')->nullable()->index();
            $table->string('maquilador');
            $table->date('fecha_creacion');
            $table->foreignId('created_by')->constrained('users');
            $table->timestamps();
            $table->softDeletes();
        });

        // 3. Crear tabla maquila_order_items (V3)
        Schema::create('maquila_order_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('maquila_order_id')->constrained('maquila_orders')->onDelete('cascade');
            $table->string('referencia')->index();
            $table->foreignId('product_id')->nullable()->constrained('products');
            $table->string('lote_fisico');
            $table->decimal('cantidad_programada', 12, 2);
            $table->enum('unidad_medida', ['KG', 'UND']);
            $table->date('fecha_fabricacion');
            $table->date('fecha_vencimiento');
            $table->timestamps();
        });

        // 4. Asegurar creación y asignación del permiso 'gestionar_maquilas' al rol admin
        try {
            DB::table('permissions')->insertOrIgnore([
                'name' => 'gestionar_maquilas',
                'created_at' => now(),
                'updated_at' => now()
            ]);
            
            $adminRole = DB::table('roles')->where('name', 'admin')->first();
            $perm = DB::table('permissions')->where('name', 'gestionar_maquilas')->first();
            
            if ($adminRole && $perm) {
                DB::table('permission_role')->insertOrIgnore([
                    'permission_id' => $perm->id,
                    'role_id' => $adminRole->id
                ]);
            }
        } catch (\Throwable $e) {
            // Ignorar excepciones si las tablas no están completas
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('maquila_order_items');
        Schema::dropIfExists('maquila_orders');
    }
};
