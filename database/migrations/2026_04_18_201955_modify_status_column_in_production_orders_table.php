<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (DB::getDriverName() === 'pgsql') {
            DB::statement('ALTER TABLE production_orders ALTER COLUMN status TYPE VARCHAR(50)');
        } else {
            DB::statement('ALTER TABLE production_orders MODIFY COLUMN status VARCHAR(50)');
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (DB::getDriverName() === 'pgsql') {
            DB::statement('ALTER TABLE production_orders ALTER COLUMN status TYPE VARCHAR(20)');
        } else {
            DB::statement('ALTER TABLE production_orders MODIFY COLUMN status VARCHAR(20)');
        }
    }
};
