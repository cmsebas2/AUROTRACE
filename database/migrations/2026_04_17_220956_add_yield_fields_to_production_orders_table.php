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
            $table->decimal('theoretical_kg', 12, 3)->nullable();
            $table->integer('theoretical_units')->nullable();
            $table->decimal('actual_kg', 12, 3)->nullable();
            $table->integer('actual_units')->nullable();
            $table->decimal('yield_percentage', 8, 2)->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('production_orders', function (Blueprint $table) {
            $table->dropColumn(['theoretical_kg', 'theoretical_units', 'actual_kg', 'actual_units', 'yield_percentage']);
        });
    }
};
