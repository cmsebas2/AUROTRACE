<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\BatchRecordArchiveLocation;
use App\Models\ProductionOrder;
use App\Models\MaquilaProductionOrder;
use App\Models\AuditLog;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class ConsultasBrController extends Controller
{
    /**
     * Auto-migración y precarga de datos para el módulo de Archivo 3D
     */
    protected function ensureSchema()
    {
        if (!Schema::hasTable('batch_record_archive_locations')) {
            try {
                \Illuminate\Support\Facades\Artisan::call('migrate', ['--force' => true]);
            } catch (\Throwable $e) {}
        }

        // Si la tabla está vacía, sincronizar automáticamente los lotes existentes que tengan posicion_archivo_fisico
        if (Schema::hasTable('batch_record_archive_locations') && BatchRecordArchiveLocation::count() === 0) {
            $this->seedInitialArchiveLocations();
        }
    }

    /**
     * Población inicial con lotes existentes para que el usuario visualice el archivador con datos reales
     */
    protected function seedInitialArchiveLocations()
    {
        $maquilaOrders = MaquilaProductionOrder::whereNotNull('lote')->take(12)->get();
        $slotIndex = 1;
        $archivadorCounter = 1; // 1, 3, 5...

        foreach ($maquilaOrders as $m) {
            BatchRecordArchiveLocation::create([
                'rack' => 'RACK 1',
                'nivel' => 1,
                'archivador_numero' => $archivadorCounter,
                'cara' => 'VISIBLE',
                'slot' => $slotIndex,
                'lote' => $m->lote,
                'op_number' => $m->op,
                'producto_nombre' => $m->producto_nombre,
                'tipo_origen' => 'MAQUILA',
                'maquila_production_order_id' => $m->id,
                'fecha_archivo' => $m->fecha_llegada_br ?? Carbon::today(),
                'notas' => 'Expediente físico archivado en auditoría inicial.'
            ]);

            $slotIndex++;
            if ($slotIndex > 4) {
                $slotIndex = 1;
                $archivadorCounter += 2; // siguiente impar
            }
        }

        // También registrar un lote en la cara posterior (pares) para demostrar la doble profundidad
        BatchRecordArchiveLocation::create([
            'rack' => 'RACK 1',
            'nivel' => 1,
            'archivador_numero' => 2,
            'cara' => 'POSTERIOR',
            'slot' => 1,
            'lote' => 'LOT-EXT-8812',
            'op_number' => 'OP-EXT-042',
            'producto_nombre' => 'COMPLEJO B FORTE 100ML',
            'tipo_origen' => 'MAQUILA',
            'fecha_archivo' => Carbon::today(),
            'notas' => 'Archivado en doble profundidad (cara posterior).'
        ]);
    }

    /**
     * Vista Principal: Módulo Consultas BR con Archivo 3D Interactivo
     */
    public function index(Request $request)
    {
        $this->ensureSchema();

        $rackSeleccionado = $request->query('rack', 'RACK 1');
        $nivelSeleccionado = (int) $request->query('nivel', 1);
        if ($nivelSeleccionado < 1 || $nivelSeleccionado > 4) $nivelSeleccionado = 1;

        $caraSeleccionada = strtoupper($request->query('cara', 'VISIBLE')); // VISIBLE o POSTERIOR
        if (!in_array($caraSeleccionada, ['VISIBLE', 'POSTERIOR'])) $caraSeleccionada = 'VISIBLE';

        $search = trim($request->query('buscar', ''));

        // Calcular el rango de numeración para este Rack y Nivel:
        // Cada nivel tiene 21 archivadores en el frente (impares) y 21 en el fondo (pares) = 42 archivadores por nivel.
        // Nivel 1: Base 0  -> Frente: 1..41 (impares), Detrás: 2..42 (pares)
        // Nivel 2: Base 42 -> Frente: 43..83 (impares), Detrás: 44..84 (pares)
        // Nivel 3: Base 84 -> Frente: 85..125 (impares), Detrás: 86..126 (pares)
        // Nivel 4: Base 126-> Frente: 127..167 (impares), Detrás: 128..168 (pares)
        // Si cambia el Rack (Rack 2), suma un offset de 168 por cada rack:
        $rackOffsets = [
            'RACK 1' => 0,
            'RACK 2' => 168,
            'RACK 3' => 336,
            'RACK 4' => 504,
        ];
        $rackOffset = $rackOffsets[$rackSeleccionado] ?? 0;
        $nivelOffset = ($nivelSeleccionado - 1) * 42;
        $baseNumero = $rackOffset + $nivelOffset;

        // Generar los 21 archivadores para la vista actual (izquierda a derecha)
        $archivadores = [];
        for ($i = 0; $i < 21; $i++) {
            if ($caraSeleccionada === 'VISIBLE') {
                // Impares: Base + (2*i + 1)
                $num = $baseNumero + (2 * $i + 1);
                $parDetras = $num + 1;
            } else {
                // Pares: Base + (2*i + 2)
                $num = $baseNumero + (2 * $i + 2);
                $parDetras = $num - 1;
            }

            // Consultar cuántos slots (de los 4) están ocupados para este archivador
            $slotsOcupados = BatchRecordArchiveLocation::where('archivador_numero', $num)->get();

            $archivadores[] = [
                'posicion_en_hilera' => $i + 1,
                'numero' => $num,
                'par_contraparte' => $parDetras,
                'cara' => $caraSeleccionada,
                'ocupacion_count' => $slotsOcupados->count(), // 0 a 4
                'slots' => $slotsOcupados->keyBy('slot'),
            ];
        }

        // Estadísticas de Capacidad del Archivo Central
        $totalArchivadores = 4 * 4 * 42; // 4 Racks * 4 Niveles * 42 = 672 archivadores
        $capacidadTotalBatch = $totalArchivadores * 4; // 2,688 Batch Records
        $totalLotesArchivados = BatchRecordArchiveLocation::count();
        $espaciosDisponibles = max(0, $capacidadTotalBatch - $totalLotesArchivados);

        // Si viene un parámetro de búsqueda, encontrar la ubicación exacta
        $resultadoBusqueda = null;
        if ($search !== '') {
            $resultadoBusqueda = BatchRecordArchiveLocation::where('lote', 'LIKE', "%{$search}%")
                ->orWhere('op_number', 'LIKE', "%{$search}%")
                ->orWhere('producto_nombre', 'LIKE', "%{$search}%")
                ->orWhere('archivador_numero', $search)
                ->first();
        }

        return view('consultas-br.index', compact(
            'rackSeleccionado',
            'nivelSeleccionado',
            'caraSeleccionada',
            'archivadores',
            'baseNumero',
            'totalArchivadores',
            'capacidadTotalBatch',
            'totalLotesArchivados',
            'espaciosDisponibles',
            'search',
            'resultadoBusqueda'
        ));
    }

    /**
     * API: Obtener el estado detallado de los 4 slots de un archivador específico
     */
    public function apiGetArchivador($numero)
    {
        $this->ensureSchema();

        $numero = (int) $numero;
        $cara = ($numero % 2 !== 0) ? 'VISIBLE' : 'POSTERIOR';

        $records = BatchRecordArchiveLocation::where('archivador_numero', $numero)->get()->keyBy('slot');

        $slots = [];
        for ($s = 1; $s <= 4; $s++) {
            if (isset($records[$s])) {
                $rec = $records[$s];
                $slots[] = [
                    'slot' => $s,
                    'ocupado' => true,
                    'lote' => $rec->lote,
                    'op_number' => $rec->op_number,
                    'producto' => $rec->producto_nombre,
                    'tipo' => $rec->tipo_origen,
                    'fecha_archivo' => $rec->fecha_archivo ? $rec->fecha_archivo->format('Y-m-d') : null,
                    'notas' => $rec->notas,
                    'radar_url' => $rec->maquila_production_order_id ? route('maquila.show', $rec->maquila_production_order_id) : null,
                    'pdf_url' => route('batch-records.pdf', $rec->lote),
                ];
            } else {
                $slots[] = [
                    'slot' => $s,
                    'ocupado' => false,
                    'lote' => null,
                    'op_number' => null,
                    'producto' => null,
                    'tipo' => null,
                    'fecha_archivo' => null,
                    'notas' => null,
                    'radar_url' => null,
                    'pdf_url' => null,
                ];
            }
        }

        return response()->json([
            'archivador_numero' => $numero,
            'cara' => $cara,
            'total_ocupados' => $records->count(),
            'slots' => $slots
        ]);
    }

    /**
     * API: Asignar un Lote / Batch Record a un slot físico del archivador
     */
    public function apiAssignSlot(Request $request)
    {
        $this->ensureSchema();

        $validated = $request->validate([
            'rack' => 'required|string',
            'nivel' => 'required|integer|min:1|max:4',
            'archivador_numero' => 'required|integer|min:1',
            'cara' => 'required|in:VISIBLE,POSTERIOR',
            'slot' => 'required|integer|min:1|max:4',
            'lote' => 'required|string|max:50',
            'op_number' => 'nullable|string|max:50',
            'producto_nombre' => 'nullable|string|max:255',
            'tipo_origen' => 'required|in:PLANTA,MAQUILA',
            'notas' => 'nullable|string'
        ]);

        DB::beginTransaction();
        try {
            $loteUpper = strtoupper(trim($validated['lote']));
            $opUpper = strtoupper(trim($validated['op_number'] ?? ''));
            $prodUpper = strtoupper(trim($validated['producto_nombre'] ?? ''));

            // Si no viene producto, intentar autocompletar desde las tablas maestras
            if (empty($prodUpper)) {
                $maquila = MaquilaProductionOrder::where('lote', $loteUpper)->first();
                if ($maquila) {
                    $prodUpper = $maquila->producto_nombre;
                    if (empty($opUpper)) $opUpper = $maquila->op;
                } else {
                    $planta = ProductionOrder::with('product')->where('lote', $loteUpper)->first();
                    if ($planta) {
                        $prodUpper = $planta->product->name ?? 'PRODUCTO PLANTA';
                        if (empty($opUpper)) $opUpper = $planta->op_number;
                    }
                }
            }

            $location = BatchRecordArchiveLocation::updateOrCreate(
                [
                    'rack' => $validated['rack'],
                    'nivel' => $validated['nivel'],
                    'archivador_numero' => $validated['archivador_numero'],
                    'slot' => $validated['slot'],
                ],
                [
                    'cara' => $validated['cara'],
                    'lote' => $loteUpper,
                    'op_number' => $opUpper,
                    'producto_nombre' => $prodUpper ?: 'PRODUCTO FARMACÉUTICO',
                    'tipo_origen' => $validated['tipo_origen'],
                    'fecha_archivo' => Carbon::today(),
                    'notas' => $validated['notas'] ?? null
                ]
            );

            // Sincronizar en MaquilaProductionOrder si existe
            $posicionStr = "{$location->rack} · NIVEL 0{$location->nivel} · ARCHIVADOR #{$location->archivador_numero} · SLOT {$location->slot}";
            MaquilaProductionOrder::where('lote', $loteUpper)->update([
                'posicion_archivo_fisico' => $posicionStr
            ]);

            // Sincronizar en ProductionOrder si existe
            if (Schema::hasColumn('production_orders', 'posicion_archivo_fisico')) {
                ProductionOrder::where('lote', $loteUpper)->update([
                    'posicion_archivo_fisico' => $posicionStr
                ]);
            }

            // Audit Trail
            AuditLog::create([
                'user_id' => Auth::id() ?? 1,
                'action' => 'ASIGNAR_ARCHIVO_FISICO_BR',
                'model_type' => 'App\Models\BatchRecordArchiveLocation',
                'model_id' => $location->id,
                'reason' => "Asignación física del Batch Record para el Lote {$loteUpper} en {$posicionStr} por usuario " . (Auth::user()->name ?? 'Sistema'),
                'new_values' => json_encode($location->toArray()),
                'ip_address' => $request->ip()
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => "Lote {$loteUpper} archivado correctamente en {$posicionStr}.",
                'location' => $location
            ]);

        } catch (\Throwable $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Error al asignar ubicación: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * API: Búsqueda rápida para ubicar espacialmente en 3D
     */
    public function apiSearch(Request $request)
    {
        $this->ensureSchema();
        $q = trim($request->query('q', ''));
        if (empty($q)) {
            return response()->json(['found' => false]);
        }

        $loc = BatchRecordArchiveLocation::where('lote', 'LIKE', "%{$q}%")
            ->orWhere('op_number', 'LIKE', "%{$q}%")
            ->orWhere('producto_nombre', 'LIKE', "%{$q}%")
            ->orWhere('archivador_numero', $q)
            ->first();

        if ($loc) {
            return response()->json([
                'found' => true,
                'rack' => $loc->rack,
                'nivel' => $loc->nivel,
                'cara' => $loc->cara,
                'archivador_numero' => $loc->archivador_numero,
                'slot' => $loc->slot,
                'lote' => $loc->lote,
                'op' => $loc->op_number,
                'producto' => $loc->producto_nombre,
                'posicion_formateada' => $loc->ubicacion_completa
            ]);
        }

        return response()->json(['found' => false, 'message' => 'Lote no encontrado en el archivo físico.']);
    }
}
