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
        // 1. Catálogo Maestro de Ítems / Presentaciones de Maquila
        if (!Schema::hasTable('maquila_catalog_items')) {
            Schema::create('maquila_catalog_items', function (Blueprint $table) {
                $table->id();
                $table->string('codigo_item', 50)->unique()->index();
                $table->string('producto_nombre', 255);
                $table->string('presentacion', 150);
                $table->string('forma_farmaceutica', 100)->default('POLVO ORAL');
                $table->string('unidad_medida', 20)->default('KG'); // KG, UND, L
                $table->unsignedInteger('vigencia_meses')->default(24); // Meses para autocalcular fecha vencimiento
                $table->string('registro_ica', 50)->nullable();
                $table->boolean('activo')->default(true);
                $table->timestamps();
            });
        }

        // 2. Columnas unidad_medida y vigencia_meses en maquila_production_orders si no existen
        if (Schema::hasTable('maquila_production_orders')) {
            Schema::table('maquila_production_orders', function (Blueprint $table) {
                if (!Schema::hasColumn('maquila_production_orders', 'unidad_medida')) {
                    $table->string('unidad_medida', 20)->default('KG')->nullable()->after('tamano_lote');
                }
                if (!Schema::hasColumn('maquila_production_orders', 'vigencia_meses')) {
                    $table->unsignedInteger('vigencia_meses')->default(24)->nullable()->after('fecha_vencimiento');
                }
            });
        }

        // 3. Pre-población de ítems iniciales de Aurofarma
        $initialItems = [
            [
                'codigo_item' => '331030',
                'producto_nombre' => 'QMUTIN 20%',
                'presentacion' => 'Bolsa x 25 Kg',
                'forma_farmaceutica' => 'POLVO ORAL',
                'unidad_medida' => 'KG',
                'vigencia_meses' => 24,
                'registro_ica' => '8920-MV',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'codigo_item' => '100021',
                'producto_nombre' => 'AUROFLOXACINA 10%',
                'presentacion' => 'Frasco x 1000 mL',
                'forma_farmaceutica' => 'SOLUCIÓN ORAL',
                'unidad_medida' => 'L',
                'vigencia_meses' => 24,
                'registro_ica' => '7654-MV',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'codigo_item' => '100045',
                'producto_nombre' => 'COMPLEJO B FORTE',
                'presentacion' => 'Frasco x 100 mL',
                'forma_farmaceutica' => 'SOLUCIÓN INYECTABLE',
                'unidad_medida' => 'UND',
                'vigencia_meses' => 36,
                'registro_ica' => '6543-MV',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'codigo_item' => '1692',
                'producto_nombre' => 'TIAMULINA FUMARATO 98%',
                'presentacion' => 'Saco x 25 Kg',
                'forma_farmaceutica' => 'PREMEZCLA',
                'unidad_medida' => 'KG',
                'vigencia_meses' => 24,
                'registro_ica' => '4321-MV',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ];

        foreach ($initialItems as $item) {
            DB::table('maquila_catalog_items')->updateOrInsert(
                ['codigo_item' => $item['codigo_item']],
                $item
            );
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('maquila_catalog_items');
    }
};
