<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        try {
            DB::statement('ALTER TABLE "maquila_production_orders" DROP CONSTRAINT IF EXISTS "maquila_production_orders_numero_odm_unique"');
            DB::statement('DROP INDEX IF EXISTS "maquila_production_orders_numero_odm_unique"');
        } catch (\Throwable $e) {}

        if (Schema::hasTable('maquila_production_orders') && Schema::hasColumn('maquila_production_orders', 'numero_odm')) {
            try {
                Schema::table('maquila_production_orders', function (Blueprint $table) {
                    $table->dropUnique('maquila_production_orders_numero_odm_unique');
                });
            } catch (\Throwable $e) {}
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('maquila_production_orders') && Schema::hasColumn('maquila_production_orders', 'numero_odm')) {
            try {
                Schema::table('maquila_production_orders', function (Blueprint $table) {
                    $table->unique('numero_odm');
                });
            } catch (\Throwable $e) {}
        }
    }
};
