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
            $table->foreignId('realizado_id')->nullable()->constrained('users');
            $table->timestamp('realizado_at')->nullable();
            $table->foreignId('verificado_id')->nullable()->constrained('users');
            $table->timestamp('verificado_at')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('production_orders', function (Blueprint $table) {
            $table->dropForeign(['realizado_id']);
            $table->dropForeign(['verificado_id']);
            $table->dropColumn(['realizado_id', 'realizado_at', 'verificado_id', 'verificado_at']);
        });
    }
};
