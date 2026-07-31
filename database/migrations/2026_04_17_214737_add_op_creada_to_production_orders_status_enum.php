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
            DB::statement("ALTER TABLE production_orders ALTER COLUMN status TYPE VARCHAR(50)");
            DB::statement("ALTER TABLE production_orders ALTER COLUMN status SET DEFAULT 'OP CREADA'");
        } else {
            DB::statement("ALTER TABLE production_orders MODIFY COLUMN status ENUM('OP CREADA','PLANEADO','LIBERADO','PESAJE','MANUFACTURA','ACONDICIONAMIENTO','COMPLETADO','CUARENTENA','ANULADO') NOT NULL DEFAULT 'OP CREADA'");
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
            DB::statement("ALTER TABLE production_orders MODIFY COLUMN status ENUM('PLANEADO','LIBERADO','PESAJE','MANUFACTURA','ACONDICIONAMIENTO','COMPLETADO','CUARENTENA','ANULADO') NOT NULL DEFAULT 'PLANEADO'");
        }
    }
};
