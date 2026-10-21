<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('products')) {
            Schema::table('products', function (Blueprint $table) {
                if (!Schema::hasColumn('products', 'vida_util_unidad')) {
                    $table->string('vida_util_unidad', 30)->nullable()->default('MESES');
                }
                if (!Schema::hasColumn('products', 'active_ingredients_json')) {
                    $table->text('active_ingredients_json')->nullable();
                }
                if (!Schema::hasColumn('products', 'maquiladores_json')) {
                    $table->text('maquiladores_json')->nullable();
                }
                if (!Schema::hasColumn('products', 'presentations_json')) {
                    $table->text('presentations_json')->nullable();
                }
            });
        }

        if (Schema::hasTable('product_presentations')) {
            Schema::table('product_presentations', function (Blueprint $table) {
                if (!Schema::hasColumn('product_presentations', 'quantity_value')) {
                    $table->decimal('quantity_value', 12, 2)->nullable();
                }
                if (!Schema::hasColumn('product_presentations', 'unit_measure')) {
                    $table->string('unit_measure', 20)->nullable();
                }
                if (!Schema::hasColumn('product_presentations', 'container_type')) {
                    $table->string('container_type', 255)->nullable();
                }
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('products')) {
            Schema::table('products', function (Blueprint $table) {
                $cols = [];
                foreach (['vida_util_unidad', 'active_ingredients_json', 'maquiladores_json', 'presentations_json'] as $c) {
                    if (Schema::hasColumn('products', $c)) {
                        $cols[] = $c;
                    }
                }
                if (!empty($cols)) {
                    $table->dropColumn($cols);
                }
            });
        }

        if (Schema::hasTable('product_presentations')) {
            Schema::table('product_presentations', function (Blueprint $table) {
                $cols = [];
                foreach (['quantity_value', 'unit_measure', 'container_type'] as $c) {
                    if (Schema::hasColumn('product_presentations', $c)) {
                        $cols[] = $c;
                    }
                }
                if (!empty($cols)) {
                    $table->dropColumn($cols);
                }
            });
        }
    }
};
