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
            $table->unsignedBigInteger('codificado_elaborado_id')->nullable();
            $table->string('codificado_elaborado_por')->nullable();
            $table->timestamp('codificado_elaborado_at')->nullable();
            
            $table->unsignedBigInteger('codificado_aprobado_id')->nullable();
            $table->string('codificado_aprobado_por')->nullable();
            $table->timestamp('codificado_aprobado_at')->nullable();
            
            $table->text('codificado_observaciones')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('production_orders', function (Blueprint $table) {
            $table->dropColumn([
                'codificado_elaborado_id', 'codificado_elaborado_por', 'codificado_elaborado_at',
                'codificado_aprobado_id', 'codificado_aprobado_por', 'codificado_aprobado_at',
                'codificado_observaciones'
            ]);
        });
    }
};
