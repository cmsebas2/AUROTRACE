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
        try {
            if (!Schema::hasTable('batch_record_archive_locations') || 
                (Schema::hasTable('maquila_production_orders') && !Schema::hasColumn('maquila_production_orders', 'lote'))) {
                \Illuminate\Support\Facades\Artisan::call('migrate', ['--force' => true]);
            }
        } catch (\Throwable $e) {}

        try {
            if (Schema::hasTable('batch_record_archive_locations') && BatchRecordArchiveLocation::count() === 0) {
                $this->seedInitialArchiveLocations();
            }
        } catch (\Throwable $e) {}
    }

    /**
     * Población inicial con lotes existentes para que el usuario visualice el archivador con datos reales
     */
    protected function seedInitialArchiveLocations()
    {
        try {
            if (Schema::hasTable('maquila_production_orders') && Schema::hasColumn('maquila_production_orders', 'lote')) {
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
                        'op_number' => $m->op ?? 'OP-EXT',
                        'producto_nombre' => $m->producto_nombre ?? 'PRODUCTO MAQUILA',
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
            }
        } catch (\Throwable $e) {}

        // También registrar lotes de muestra si no había órdenes para que el módulo sea 100% interactivo
        try {
            if (BatchRecordArchiveLocation::count() === 0) {
                BatchRecordArchiveLocation::create([
                    'rack' => 'RACK 1',
                    'nivel' => 1,
                    'archivador_numero' => 1,
                    'cara' => 'VISIBLE',
                    'slot' => 1,
                    'lote' => '604MT01',
                    'op_number' => 'OP-2026-001',
                    'producto_nombre' => 'AUROFLOXACINA 10%',
                    'tipo_origen' => 'PLANTA',
                    'fecha_archivo' => Carbon::today(),
                    'notas' => 'Expediente físico archivado en auditoría inicial.'
                ]);
            }

            BatchRecordArchiveLocation::firstOrCreate(
                [
                    'rack' => 'RACK 1',
                    'nivel' => 1,
                    'archivador_numero' => 2,
                    'slot' => 1,
                ],
                [
                    'cara' => 'POSTERIOR',
                    'lote' => 'LOT-EXT-8812',
                    'op_number' => 'OP-EXT-042',
                    'producto_nombre' => 'COMPLEJO B FORTE 100ML',
                    'tipo_origen' => 'MAQUILA',
                    'fecha_archivo' => Carbon::today(),
                    'notas' => 'Archivado en doble profundidad (cara posterior).'
                ]
            );
        } catch (\Throwable $e) {}
    }

    /**
     * Vista Principal: Módulo Consultas BR con Archivo 3D Interactivo
     * - 1 Solo Rack físico
     * - 5 Niveles de arriba (Nivel 1) hacia abajo (Nivel 5)
     * - 21 archivadores por nivel en cara visible (impares) y 21 en cara posterior (pares) = 42 por nivel
     * - Capacidad: 210 archivadores físicos y 840 Batch Records (4 batch por archivador)
     */
    public function index(Request $request)
    {
        $this->ensureSchema();

        $rackSeleccionado = 'RACK 1'; // 1 solo rack actualmente
        $nivelSeleccionado = (int) $request->query('nivel', 1);
        if ($nivelSeleccionado < 1 || $nivelSeleccionado > 5) $nivelSeleccionado = 1;

        $caraSeleccionada = strtoupper($request->query('cara', 'VISIBLE')); // VISIBLE o POSTERIOR
        if (!in_array($caraSeleccionada, ['VISIBLE', 'POSTERIOR'])) $caraSeleccionada = 'VISIBLE';

        $vistaModo = $request->query('vista', 'TODO'); // 'TODO' (Rack Completo) o 'BALDA' (Nivel individual)
        if (!in_array($vistaModo, ['TODO', 'BALDA'])) $vistaModo = 'TODO';

        $search = trim($request->query('buscar', ''));

        // Cargar todas las ubicaciones ocupadas en una sola consulta
        $allRecords = collect();
        try {
            if (Schema::hasTable('batch_record_archive_locations')) {
                $allRecords = BatchRecordArchiveLocation::all()->groupBy('archivador_numero');
            }
        } catch (\Throwable $e) {}

        // Generar la estructura de los 5 niveles (de arriba hacia abajo: 1 -> 5)
        $rackCompleto = [];
        for ($n = 1; $n <= 5; $n++) {
            $baseNivel = ($n - 1) * 42;
            $archivadoresNivel = [];

            for ($i = 0; $i < 21; $i++) {
                if ($caraSeleccionada === 'VISIBLE') {
                    // Impares: Base + (2*i + 1) -> N1: 1..41, N2: 43..83, N3: 85..125, N4: 127..167, N5: 169..209
                    $num = $baseNivel + (2 * $i + 1);
                    $parDetras = $num + 1;
                } else {
                    // Pares: Base + (2*i + 2) -> N1: 2..42, N2: 44..84, N3: 86..126, N4: 128..168, N5: 170..210
                    $num = $baseNivel + (2 * $i + 2);
                    $parDetras = $num - 1;
                }

                $slotsOcupados = $allRecords->get($num, collect());

                $archivadoresNivel[] = [
                    'posicion_en_hilera' => $i + 1,
                    'numero' => $num,
                    'par_contraparte' => $parDetras,
                    'cara' => $caraSeleccionada,
                    'ocupacion_count' => $slotsOcupados->count(),
                    'slots' => $slotsOcupados->keyBy('slot'),
                ];
            }

            $rackCompleto[$n] = [
                'nivel' => $n,
                'etiqueta' => "Nivel 0{$n}" . ($n === 1 ? ' (Superior)' : ($n === 5 ? ' (Inferior)' : '')),
                'archivadores' => $archivadoresNivel,
                'rango_texto' => '#' . str_pad($archivadoresNivel[0]['numero'], 2, '0', STR_PAD_LEFT) . ' al #' . str_pad($archivadoresNivel[20]['numero'], 2, '0', STR_PAD_LEFT),
            ];
        }

        // Archivadores de la balda enfocada actualmente
        $archivadores = $rackCompleto[$nivelSeleccionado]['archivadores'] ?? [];

        // Estadísticas de Capacidad de 1 Rack con 5 Niveles (42 archivadores por nivel)
        $totalArchivadores = 5 * 42; // 210 archivadores físicos
        $capacidadTotalBatch = $totalArchivadores * 4; // 840 Batch Records
        $totalLotesArchivados = 0;
        try {
            if (Schema::hasTable('batch_record_archive_locations')) {
                $totalLotesArchivados = BatchRecordArchiveLocation::count();
            }
        } catch (\Throwable $e) {}
        $espaciosDisponibles = max(0, $capacidadTotalBatch - $totalLotesArchivados);

        // Si viene búsqueda, ubicar inmediatamente
        $resultadoBusqueda = null;
        if ($search !== '') {
            try {
                $resultadoBusqueda = BatchRecordArchiveLocation::where('lote', 'LIKE', "%{$search}%")
                    ->orWhere('op_number', 'LIKE', "%{$search}%")
                    ->orWhere('producto_nombre', 'LIKE', "%{$search}%")
                    ->orWhere('archivador_numero', $search)
                    ->first();
            } catch (\Throwable $e) {}
        }

        return view('consultas-br.index', compact(
            'rackSeleccionado',
            'nivelSeleccionado',
            'caraSeleccionada',
            'vistaModo',
            'rackCompleto',
            'archivadores',
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
            'nivel' => 'required|integer|min:1|max:5',
            'archivador_numero' => 'required|integer|min:1|max:210',
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
