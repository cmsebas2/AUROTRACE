<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        try {
            DB::statement("ALTER TABLE users DROP CONSTRAINT IF EXISTS users_role_check;");
        } catch (\Throwable $e) {}
    }

    public function down(): void
    {
    }
};
