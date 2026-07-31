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
            $table->decimal('bh_valor', 16, 4)->nullable()->after('required_qty');
            $table->decimal('bs_valor', 16, 4)->nullable()->after('bh_valor');
            $table->decimal('humedad_valor', 16, 4)->nullable()->after('bs_valor');
            $table->decimal('ajuste_porcentaje', 16, 4)->nullable()->after('humedad_valor');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('op_material_reconciliations', function (Blueprint $table) {
            $table->dropColumn(['bh_valor', 'bs_valor', 'humedad_valor', 'ajuste_porcentaje']);
        });
    }
};
