<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Maquilador;
use Carbon\Carbon;

class MaquiladorSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $maquiladores = [
            ['nombre' => 'QOPPA PHARMA', 'nit' => '900.123.456-1', 'vigencia_meses' => 12],
            ['nombre' => 'INSEC', 'nit' => '800.987.654-2', 'vigencia_meses' => 18],
            ['nombre' => 'RATAR', 'nit' => '890.321.654-3', 'vigencia_meses' => 6],
            ['nombre' => 'DECNO', 'nit' => '901.456.789-4', 'vigencia_meses' => 24],
            ['nombre' => 'ITALCOL S.A. FUNZA', 'nit' => '860.001.122-5', 'vigencia_meses' => 15],
            ['nombre' => 'ITALCOL S.A. CARTAGENA', 'nit' => '860.001.122-6', 'vigencia_meses' => 9],
            ['nombre' => 'PROQUIVET', 'nit' => '800.112.233-7', 'vigencia_meses' => -1], // Expirado para pruebas de alerta ICA
            ['nombre' => 'PRONATUCOL', 'nit' => '900.554.332-8', 'vigencia_meses' => 1], // Próximo a vencer para pruebas de alerta
            ['nombre' => 'GDI', 'nit' => '901.223.344-9', 'vigencia_meses' => 12],
            ['nombre' => 'LIXMAR', 'nit' => '800.776.554-0', 'vigencia_meses' => 14],
            ['nombre' => 'ARJONA', 'nit' => '890.443.221-1', 'vigencia_meses' => 20],
            ['nombre' => 'FARMANDINA', 'nit' => '860.554.667-2', 'vigencia_meses' => 11],
            ['nombre' => 'FARMATEC', 'nit' => '800.998.877-3', 'vigencia_meses' => 8],
            ['nombre' => 'SFC', 'nit' => '900.887.766-4', 'vigencia_meses' => 16],
        ];

        foreach ($maquiladores as $m) {
            Maquilador::updateOrCreate(
                ['nombre' => $m['nombre']],
                [
                    'nit' => $m['nit'],
                    'activo' => true,
                    'certificado_bpm_ica_vigente_hasta' => Carbon::now()->addMonths($m['vigencia_meses'])->toDateString(),
                ]
            );
        }
    }
}
