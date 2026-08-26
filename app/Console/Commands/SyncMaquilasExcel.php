<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\MaquilaOrder;
use App\Models\MaquilaDelivery;
use App\Helpers\SimpleXLSXReader;

class SyncMaquilasExcel extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'maquilas:sync-excel {path?}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sincroniza las órdenes de maquila desde el archivo Excel consolidado de Aurofarma';

    /**
     * Sheets to ignore during sync.
     *
     * @var array
     */
    protected $ignoredSheets = [
        'estabilidades',
        'liq p',
        'listado pt',
        'formato',
        'estadísticas del libro',
        'estadisticas del libro',
    ];

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $path = $this->argument('path') ?: storage_path('app/maquilas/Control_de_Produccion_Aurofarma_2026.xlsx');

        if (!file_exists($path)) {
            $this->error("El archivo Excel no existe en la ruta: {$path}");
            return 1;
        }

        $this->info("Iniciando lectura del archivo: {$path}");

        try {
            $parsedSheets = SimpleXLSXReader::parse($path);
        } catch (\Throwable $e) {
            $this->error("Error al procesar el archivo Excel: " . $e->getMessage());
            return 1;
        }

        $totalOrders = 0;
        $totalDeliveries = 0;

        foreach ($parsedSheets as $sheetName => $rows) {
            $cleanSheetName = trim((string)$sheetName);
            if (in_array(mb_strtolower($cleanSheetName), $this->ignoredSheets)) {
                $this->line("Saltando pestaña ignorada: {$cleanSheetName}");
                continue;
            }

            if (empty($rows)) {
                continue;
            }

            $this->info("Procesando pestaña: {$cleanSheetName}");

            foreach ($rows as $rowIndex => $rowCells) {
                if ($rowIndex < 6) { // Start reading from line 6 (index 6)
                    continue;
                }

                $lote = $this->parseString($rowCells[8] ?? null); // Column H = index 8

                if (empty($lote) || strtolower($lote) === 'lote' || strtolower($lote) === 'none') {
                    continue;
                }

                $orderData = [
                    'maquilador' => $cleanSheetName,
                    'fecha_creacion' => $this->parseString($rowCells[1] ?? null),
                    'estatus' => $this->parseString($rowCells[2] ?? null),
                    'ubicacion' => $this->parseString($rowCells[3] ?? null),
                    'op' => $this->parseString($rowCells[4] ?? null),
                    'codigo_item' => $this->parseString($rowCells[5] ?? null),
                    'descripcion' => $this->parseString($rowCells[6] ?? null),
                    'fecha_fabricacion' => $this->parseString($rowCells[9] ?? null),
                    'fecha_vencimiento' => $this->parseString($rowCells[10] ?? null),
                    'cantidad_programada' => $this->parseNumeric($rowCells[11] ?? null),
                    'adicional' => $this->parseNumeric($rowCells[12] ?? null),
                    'devolucion' => $this->parseNumeric($rowCells[13] ?? null),
                    'restante' => $this->parseNumeric($rowCells[14] ?? null),
                    'balance' => $this->parseString($rowCells[15] ?? null),
                    'fecha_balance' => $this->parseString($rowCells[16] ?? null),
                    'pendiente' => $this->parseNumeric($rowCells[27] ?? null),
                    'fecha_despacho_maquila' => $this->parseString($rowCells[28] ?? null),
                    'documento_traslado' => $this->parseString($rowCells[29] ?? null),
                    'fecha_llegada_aurofarma' => $this->parseString($rowCells[30] ?? null),
                    'op_secundaria' => $this->parseString($rowCells[31] ?? null),
                    'observaciones' => $this->parseString($rowCells[32] ?? null),
                ];

                $order = MaquilaOrder::updateOrCreate(
                    ['lote' => $lote],
                    $orderData
                );

                $totalOrders++;

                // Clear previous deliveries for this order
                MaquilaDelivery::where('maquila_order_id', $order->id)->delete();

                // Process deliveries pairs (Q/R, S/T, U/V, W/X, Y/Z) -> indexes 17 to 26
                $deliveryPairs = [
                    1 => ['doc' => 17, 'qty' => 18],
                    2 => ['doc' => 19, 'qty' => 20],
                    3 => ['doc' => 21, 'qty' => 22],
                    4 => ['doc' => 23, 'qty' => 24],
                    5 => ['doc' => 25, 'qty' => 26],
                ];

                foreach ($deliveryPairs as $num => $cols) {
                    $docRemision = $this->parseString($rowCells[$cols['doc']] ?? null);
                    $qtyEntregada = $this->parseNumeric($rowCells[$cols['qty']] ?? null);

                    if (!empty($docRemision) || $qtyEntregada > 0) {
                        MaquilaDelivery::create([
                            'maquila_order_id' => $order->id,
                            'lote' => $lote,
                            'numero_entrega' => $num,
                            'documento_remision' => $docRemision,
                            'cantidad_entregada' => $qtyEntregada,
                        ]);
                        $totalDeliveries++;
                    }
                }
            }
        }

        $this->info("Sincronización completada exitosamente.");
        $this->info("Órdenes procesadas/actualizadas: {$totalOrders}");
        $this->info("Entregas registradas: {$totalDeliveries}");

        return 0;
    }

    private function parseString($val): ?string
    {
        if ($val === null || $val === '') {
            return null;
        }
        $str = trim((string)$val);
        return $str === '' ? null : $str;
    }

    private function parseNumeric($val): float
    {
        if ($val === null || $val === '') {
            return 0.0;
        }

        if (is_numeric($val)) {
            return (float)$val;
        }

        $cleaned = preg_replace('/[^0-9\.-]/', '', (string)$val);
        return is_numeric($cleaned) ? (float)$cleaned : 0.0;
    }
}
