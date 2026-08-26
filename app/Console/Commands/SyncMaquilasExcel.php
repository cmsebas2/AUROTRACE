<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\MaquilaOrder;
use App\Models\MaquilaDelivery;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Shared\Date;
use Illuminate\Support\Facades\Log;

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
            $spreadsheet = IOFactory::load($path);
        } catch (\Throwable $e) {
            $this->error("Error al cargar el archivo Excel: " . $e->getMessage());
            return 1;
        }

        $totalOrders = 0;
        $totalDeliveries = 0;

        foreach ($spreadsheet->getAllSheets() as $sheet) {
            $sheetName = trim($sheet->getTitle());
            if (in_array(mb_strtolower($sheetName), $this->ignoredSheets)) {
                $this->line("Saltando pestaña ignorada: {$sheetName}");
                continue;
            }

            $this->info("Procesando pestaña: {$sheetName}");
            $highestRow = $sheet->getHighestRow();

            for ($row = 6; $row <= $highestRow; $row++) {
                $lote = $this->getCellValue($sheet, "H", $row);

                if (empty($lote) || strtolower($lote) === 'lote' || strtolower($lote) === 'none') {
                    continue;
                }

                $orderData = [
                    'maquilador' => $sheetName,
                    'fecha_creacion' => $this->getCellDateOrString($sheet, "A", $row),
                    'estatus' => $this->getCellValue($sheet, "B", $row),
                    'ubicacion' => $this->getCellValue($sheet, "C", $row),
                    'op' => $this->getCellValue($sheet, "D", $row),
                    'codigo_item' => $this->getCellValue($sheet, "E", $row),
                    'descripcion' => $this->getCellValue($sheet, "F", $row),
                    'fecha_fabricacion' => $this->getCellDateOrString($sheet, "I", $row),
                    'fecha_vencimiento' => $this->getCellDateOrString($sheet, "J", $row),
                    'cantidad_programada' => $this->getCellNumeric($sheet, "K", $row),
                    'adicional' => $this->getCellNumeric($sheet, "L", $row),
                    'devolucion' => $this->getCellNumeric($sheet, "M", $row),
                    'restante' => $this->getCellNumeric($sheet, "N", $row),
                    'balance' => $this->getCellValue($sheet, "O", $row),
                    'fecha_balance' => $this->getCellDateOrString($sheet, "P", $row),
                    'pendiente' => $this->getCellNumeric($sheet, "AA", $row),
                    'fecha_despacho_maquila' => $this->getCellDateOrString($sheet, "AB", $row),
                    'documento_traslado' => $this->getCellValue($sheet, "AC", $row),
                    'fecha_llegada_aurofarma' => $this->getCellDateOrString($sheet, "AD", $row),
                    'op_secundaria' => $this->getCellValue($sheet, "AE", $row),
                    'observaciones' => $this->getCellValue($sheet, "AF", $row),
                ];

                $order = MaquilaOrder::updateOrCreate(
                    ['lote' => $lote],
                    $orderData
                );

                $totalOrders++;

                // Clear previous deliveries for this order to avoid duplicates on re-sync
                MaquilaDelivery::where('maquila_order_id', $order->id)->delete();

                // Process deliveries columns: Q & R (1), S & T (2), U & V (3), W & X (4), Y & Z (5)
                $deliveryPairs = [
                    1 => ['doc' => 'Q', 'qty' => 'R'],
                    2 => ['doc' => 'S', 'qty' => 'T'],
                    3 => ['doc' => 'U', 'qty' => 'V'],
                    4 => ['doc' => 'W', 'qty' => 'X'],
                    5 => ['doc' => 'Y', 'qty' => 'Z'],
                ];

                foreach ($deliveryPairs as $num => $cols) {
                    $docRemision = $this->getCellValue($sheet, $cols['doc'], $row);
                    $qtyEntregada = $this->getCellNumeric($sheet, $cols['qty'], $row);

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

    /**
     * Get string value of cell cleaned.
     */
    private function getCellValue($sheet, string $col, int $row): ?string
    {
        $cell = $sheet->getCell("{$col}{$row}");
        $val = $cell->getFormattedValue();
        if ($val === null || $val === '') {
            $val = $cell->getValue();
        }
        $str = trim((string)$val);
        return $str === '' ? null : $str;
    }

    /**
     * Get numeric float value of cell.
     */
    private function getCellNumeric($sheet, string $col, int $row): float
    {
        $cell = $sheet->getCell("{$col}{$row}");
        $val = $cell->getValue();

        if (is_numeric($val)) {
            return (float)$val;
        }

        $formatted = $cell->getFormattedValue();
        $cleaned = preg_replace('/[^0-9\.-]/', '', (string)$formatted);
        return is_numeric($cleaned) ? (float)$cleaned : 0.0;
    }

    /**
     * Parse cell date value (Excel timestamp or string).
     */
    private function getCellDateOrString($sheet, string $col, int $row): ?string
    {
        $cell = $sheet->getCell("{$col}{$row}");
        $val = $cell->getValue();

        if (empty($val)) {
            return null;
        }

        if (is_numeric($val) && Date::isDateTime($cell)) {
            try {
                return Date::excelToDateTimeObject($val)->format('Y-m-d');
            } catch (\Throwable $e) {
                // Fallback to formatted string
            }
        }

        $str = trim((string)$cell->getFormattedValue());
        if ($str === '') {
            $str = trim((string)$val);
        }

        return $str === '' ? null : $str;
    }
}
