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
        // 1. Drop check constraint on maquila_production_orders estado
        try {
            DB::statement('ALTER TABLE "maquila_production_orders" DROP CONSTRAINT IF EXISTS "maquila_production_orders_estado_check"');
            DB::statement('ALTER TABLE "maquila_production_orders" DROP CONSTRAINT IF EXISTS "maquila_production_orders_tipo_producto_check"');
            DB::statement('ALTER TABLE "maquila_production_orders" ALTER COLUMN "estado" TYPE VARCHAR(60)');
        } catch (\Throwable $e) {}

        // 2. Drop check constraint on maquila_items unidad_medida and ensure VARCHAR(30)
        try {
            DB::statement('ALTER TABLE "maquila_items" DROP CONSTRAINT IF EXISTS "maquila_items_unidad_medida_check"');
            DB::statement('ALTER TABLE "maquila_items" ALTER COLUMN "unidad_medida" TYPE VARCHAR(30) USING "unidad_medida"::text');
            DB::statement('ALTER TABLE "maquila_items" ADD COLUMN IF NOT EXISTS "forma_farmaceutica" VARCHAR(100)');
            DB::statement('ALTER TABLE "maquila_items" ADD COLUMN IF NOT EXISTS "esm" VARCHAR(100)');
        } catch (\Throwable $e) {}

        // 3. Drop check constraint on maquila_deliveries
        try {
            DB::statement('ALTER TABLE "maquila_deliveries" DROP CONSTRAINT IF EXISTS "maquila_deliveries_tipo_entrega_check"');
        } catch (\Throwable $e) {}

        // 4. Ensure extra columns on maquila_production_orders
        if (Schema::hasTable('maquila_production_orders')) {
            Schema::table('maquila_production_orders', function (Blueprint $table) {
                if (!Schema::hasColumn('maquila_production_orders', 'unidad_medida')) {
                    $table->string('unidad_medida', 20)->nullable()->default('KG');
                }
                if (!Schema::hasColumn('maquila_production_orders', 'vigencia_meses')) {
                    $table->integer('vigencia_meses')->nullable()->default(24);
                }
                if (!Schema::hasColumn('maquila_production_orders', 'fecha_destruccion_br')) {
                    $table->string('fecha_destruccion_br', 20)->nullable();
                }
                if (!Schema::hasColumn('maquila_production_orders', 'lead_time_dias')) {
                    $table->integer('lead_time_dias')->nullable()->default(0);
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
    }
};
