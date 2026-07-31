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
            $table->string('function')->nullable()->after('description');
            if (!Schema::hasColumn('op_material_reconciliations', 'required_qty')) {
                $table->decimal('required_qty', 12, 4)->default(0)->after('unit');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('op_material_reconciliations', function (Blueprint $table) {
            $table->dropColumn(['function']);
            // required_qty could be kept if it was already there, but for safety:
            // $table->dropColumn(['required_qty']);
        });
    }
};
