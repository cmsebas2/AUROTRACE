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
        Schema::table('audit_logs', function (Blueprint $table) {
            $table->foreignId('signer_id')->nullable()->constrained('users');
            $table->foreignId('on_behalf_of_id')->nullable()->constrained('users');
            $table->text('justification')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('audit_logs', function (Blueprint $table) {
            $table->dropForeign(['signer_id']);
            $table->dropColumn('signer_id');
            $table->dropForeign(['on_behalf_of_id']);
            $table->dropColumn('on_behalf_of_id');
            $table->dropColumn('justification');
        });
    }
};
