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
        Schema::table('op_material_reconciliations', function (Blueprint $table) {
            $table->string('material_code')->nullable()->after('production_order_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('op_material_reconciliations', function (Blueprint $table) {
            $table->dropColumn('material_code');
        });
    }
};
