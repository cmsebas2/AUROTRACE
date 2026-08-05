<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        try {
            // Asegurar políticas públicas de lectura o deshabilitar RLS para lectura en PostgreSQL / Supabase
            DB::statement("
                DO $$ 
                BEGIN
                    IF EXISTS (SELECT 1 FROM pg_tables WHERE schemaname = 'public' AND tablename = 'items') THEN
                        ALTER TABLE public.items DISABLE ROW LEVEL SECURITY;
                    END IF;
                    IF EXISTS (SELECT 1 FROM pg_tables WHERE schemaname = 'public' AND tablename = 'products') THEN
                        ALTER TABLE public.products DISABLE ROW LEVEL SECURITY;
                    END IF;
                    IF EXISTS (SELECT 1 FROM pg_tables WHERE schemaname = 'public' AND tablename = 'product_presentations') THEN
                        ALTER TABLE public.product_presentations DISABLE ROW LEVEL SECURITY;
                    END IF;
                END $$;
            ");
        } catch (\Throwable $e) {
            // Silenciar si no es PostgreSQL
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // No destructivo
    }
};
