<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\MaquilaOrder;
use App\Models\MaquilaDelivery;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

class MaquilaTrackingController extends Controller
{
    /**
     * Render the 360° Search & Tracking View.
     */
    public function consultar(Request $request)
    {
        $totalOrders = MaquilaOrder::count();
        $maquiladoresCount = MaquilaOrder::distinct('maquilador')->count('maquilador');
        
        return view('maquila.consultar', compact('totalOrders', 'maquiladoresCount'));
    }

    /**
     * Search orders dynamically by query string (Lote, OP, Item code, Description, Maquilador).
     */
    public function buscar(Request $request)
    {
        $query = trim($request->query('q', ''));

        if (strlen($query) < 2) {
            return response()->json([
                'success' => true,
                'data' => [],
            ]);
        }

        $upperQuery = mb_strtoupper($query);

        $results = MaquilaOrder::whereRaw('UPPER(lote) LIKE ?', ["%{$upperQuery}%"])
            ->orWhereRaw('UPPER(op) LIKE ?', ["%{$upperQuery}%"])
            ->orWhereRaw('UPPER(descripcion) LIKE ?', ["%{$upperQuery}%"])
            ->orWhereRaw('UPPER(codigo_item) LIKE ?', ["%{$upperQuery}%"])
            ->orWhereRaw('UPPER(maquilador) LIKE ?', ["%{$upperQuery}%"])
            ->select([
                'id',
                'lote',
                'op',
                'descripcion',
                'codigo_item',
                'maquilador',
                'estatus',
                'balance',
                'cantidad_programada',
                'pendiente',
            ])
            ->limit(15)
            ->get();

        return response()->json([
            'success' => true,
            'data' => $results,
        ]);
    }

    /**
     * Get complete details of a specific batch (lote) including deliveries & metrics.
     */
    public function detalle($lote)
    {
        $order = MaquilaOrder::with(['deliveries'])->where('lote', $lote)->first();

        if (!$order) {
            return response()->json([
                'success' => false,
                'message' => "No se encontró ninguna orden con el lote: {$lote}",
            ], 404);
        }

        $totalEntregado = (float)$order->deliveries->sum('cantidad_entregada');
        $cantidadProgramada = (float)$order->cantidad_programada;
        
        // Calculate pending balance safely
        $saldoPendiente = (float)($order->pendiente > 0 ? $order->pendiente : max(0, $cantidadProgramada - $totalEntregado));
        
        // Calculate percentage of completion
        $cumplimientoPorcentaje = $cantidadProgramada > 0 
            ? round(($totalEntregado / $cantidadProgramada) * 100, 2) 
            : 0;

        // Map deliveries with contribution percentage
        $deliveries = $order->deliveries->map(function ($delivery) use ($cantidadProgramada) {
            $aporte = $cantidadProgramada > 0 
                ? round(((float)$delivery->cantidad_entregada / $cantidadProgramada) * 100, 2) 
                : 0;

            return [
                'id' => $delivery->id,
                'numero_entrega' => $delivery->numero_entrega,
                'documento_remision' => $delivery->documento_remision ?: 'N/A',
                'cantidad_entregada' => (float)$delivery->cantidad_entregada,
                'porcentaje_aporte' => $aporte,
                'created_at' => $delivery->created_at ? $delivery->created_at->format('Y-m-d H:i') : null,
            ];
        });

        return response()->json([
            'success' => true,
            'data' => [
                'order' => $order,
                'deliveries' => $deliveries,
                'metrics' => [
                    'cantidad_programada' => $cantidadProgramada,
                    'total_entregado' => $totalEntregado,
                    'saldo_pendiente' => $saldoPendiente,
                    'cumplimiento_porcentaje' => min($cumplimientoPorcentaje, 100),
                    'cumplimiento_real' => $cumplimientoPorcentaje,
                ],
            ],
        ]);
    }

    /**
     * Upload Excel file and trigger sync command.
     */
    public function subirExcel(Request $request)
    {
        $request->validate([
            'excel_file' => 'required|file|mimes:xlsx,xls|max:51200', // max 50MB
        ]);

        try {
            $file = $request->file('excel_file');
            $dirPath = storage_path('app/maquilas');

            if (!file_exists($dirPath)) {
                mkdir($dirPath, 0777, true);
            }

            $fileName = 'Control_de_Produccion_Aurofarma_2026.xlsx';
            $file->move($dirPath, $fileName);
            $fullPath = $dirPath . '/' . $fileName;

            // Execute Artisan sync command
            $exitCode = Artisan::call('maquilas:sync-excel', ['path' => $fullPath]);
            $output = Artisan::output();

            if ($exitCode === 0) {
                return response()->json([
                    'success' => true,
                    'message' => 'Archivo subido y sincronización ejecutada exitosamente.',
                    'output' => $output,
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'El archivo se subió pero ocurrió un error durante la sincronización.',
                    'output' => $output,
                ], 500);
            }
        } catch (\Throwable $e) {
            Log::error("Error en subirExcel: " . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Error al procesar el archivo: ' . $e->getMessage(),
            ], 500);
        }
    }
}
