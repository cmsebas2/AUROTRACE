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
            $table->string('exit_transfer_number')->nullable();
            $table->string('exit_transfer_pdf_path')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('reconditioning_items', function (Blueprint $table) {
            $table->dropColumn(['exit_transfer_number', 'exit_transfer_pdf_path']);
        });
    }
};
