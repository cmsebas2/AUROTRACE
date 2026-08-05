<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (Schema::hasTable('maquila_orders')) {
            Schema::table('maquila_orders', function (Blueprint $table) {
                if (!Schema::hasColumn('maquila_orders', 'producto')) {
                    $table->string('producto')->nullable()->after('tipo_producto');
                }
            });
        }

        if (Schema::hasTable('maquila_order_items')) {
            Schema::table('maquila_order_items', function (Blueprint $table) {
                if (!Schema::hasColumn('maquila_order_items', 'sdm')) {
                    $table->string('sdm')->nullable()->after('referencia');
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('maquila_orders')) {
            Schema::table('maquila_orders', function (Blueprint $table) {
                if (Schema::hasColumn('maquila_orders', 'producto')) {
                    $table->dropColumn('producto');
                }
            });
        }

        if (Schema::hasTable('maquila_order_items')) {
            Schema::table('maquila_order_items', function (Blueprint $table) {
                if (Schema::hasColumn('maquila_order_items', 'sdm')) {
                    $table->dropColumn('sdm');
                }
            });
        }
    }
};
