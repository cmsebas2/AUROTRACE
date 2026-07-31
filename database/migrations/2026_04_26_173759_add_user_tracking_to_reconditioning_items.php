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
            $table->foreignId('created_by_id')->nullable()->after('status')->constrained('users')->onDelete('set null');
            $table->foreignId('released_by_id')->nullable()->after('is_released')->constrained('users')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('reconditioning_items', function (Blueprint $table) {
            $table->dropForeign(['created_by_id']);
            $table->dropColumn('created_by_id');
            $table->dropForeign(['released_by_id']);
            $table->dropColumn('released_by_id');
        });
    }
};
