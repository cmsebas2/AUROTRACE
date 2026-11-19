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
        try {
            DB::statement('ALTER TABLE "maquila_production_orders" DROP COLUMN IF EXISTS "pre_orden"');
        } catch (\Throwable $e) {}

        if (Schema::hasTable('maquila_production_orders')) {
            if (Schema::hasColumn('maquila_production_orders', 'pre_orden')) {
                Schema::table('maquila_production_orders', function (Blueprint $table) {
                    $table->dropColumn('pre_orden');
                });
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('maquila_production_orders')) {
            if (!Schema::hasColumn('maquila_production_orders', 'pre_orden')) {
                Schema::table('maquila_production_orders', function (Blueprint $table) {
                    $table->string('pre_orden')->nullable();
                });
            }
        }
    }
};