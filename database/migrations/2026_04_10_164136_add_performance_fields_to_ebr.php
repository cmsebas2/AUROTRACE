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
        Schema::table('production_orders', function (Blueprint $table) {
            $table->decimal('final_yield_percentage', 5, 2)->nullable()->after('status');
        });

        Schema::table('batch_packaging_results', function (Blueprint $table) {
            $table->integer('units_obtained')->nullable()->after('particles_free');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('production_orders', function (Blueprint $table) {
            $table->dropColumn('final_yield_percentage');
        });

        Schema::table('batch_packaging_results', function (Blueprint $table) {
            $table->dropColumn('units_obtained');
        });
    }
};
