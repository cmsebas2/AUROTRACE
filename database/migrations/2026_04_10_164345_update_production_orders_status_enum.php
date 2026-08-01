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
            DB::statement('ALTER TABLE production_orders DROP CONSTRAINT IF EXISTS production_orders_status_check');
            DB::statement("ALTER TABLE production_orders ALTER COLUMN status TYPE VARCHAR(50)");
            DB::statement("ALTER TABLE production_orders ALTER COLUMN status SET DEFAULT 'PLANEADO'");
        } else {
            DB::statement("ALTER TABLE production_orders MODIFY COLUMN status ENUM('PLANEADO','LIBERADO','PESAJE','MANUFACTURA','ACONDICIONAMIENTO','COMPLETADO','CUARENTENA','ANULADO') NOT NULL DEFAULT 'PLANEADO'");
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (DB::getDriverName() === 'pgsql') {
            DB::statement("ALTER TABLE production_orders ALTER COLUMN status TYPE VARCHAR(50)");
            DB::statement("ALTER TABLE production_orders ALTER COLUMN status SET DEFAULT 'PLANEADO'");
        } else {
            DB::statement("ALTER TABLE production_orders MODIFY COLUMN status ENUM('PLANEADO','LIBERADO','EN_PROCESO','ACONDICIONAMIENTO','COMPLETADO','CUARENTENA','ANULADO') NOT NULL DEFAULT 'PLANEADO'");
        }
    }
};
