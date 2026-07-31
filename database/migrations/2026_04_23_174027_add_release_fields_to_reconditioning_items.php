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
        Schema::table('reconditioning_items', function (Blueprint $table) {
            $table->boolean('is_released')->default(false);
            $table->timestamp('released_at')->nullable();
            $table->string('release_pdf_path')->nullable();
            $table->string('destination_warehouse')->nullable(); // PT or RZ
            $table->text('rejection_reason')->nullable();
            $table->string('rejection_photo_path')->nullable(); // Evidencia para RZ
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('reconditioning_items', function (Blueprint $table) {
            $table->dropColumn([
                'is_released',
                'released_at',
                'release_pdf_path',
                'destination_warehouse',
                'rejection_reason',
                'rejection_photo_path'
            ]);
        });
    }
};
