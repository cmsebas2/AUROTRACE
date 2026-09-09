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
     * Auto-migración y sincronización automática de lotes de maquila hacia el archivo 3D
     */
    protected function ensureSchema()
    {
        try {
            if (Schema::hasTable('maquila_production_orders') && Schema::hasTable('batch_record_archive_locations')) {
                $totalOrders = MaquilaProductionOrder::whereNotNull('lote')->where('lote', '!=', '')->count();
                $totalLocations = BatchRecordArchiveLocation::count();

                if ($totalOrders > 0 && $totalLocations < $totalOrders) {
                    $this->syncMissingMaquilaArchiveLocations();
                }
            }
        } catch (\Throwable $e) {}
    }

    /**
     * Sincroniza y reconstruye secuencialmente las ubicaciones físicas del archivo 3D para todos los lotes
     */
    public function rebuildArchiveLocations()
    {
        $this->syncMissingMaquilaArchiveLocations();
    }

    public function getOccupiedSlots()
    {
        $locations = DB::table('batch_record_archive_locations')
            ->select('archivador_numero', 'slot', 'lote', 'op_number', 'maquila_production_order_id')
            ->get();

        $map = [];
        foreach ($locations as $loc) {
            $key = "{$loc->archivador_numero}_{$loc->slot}";
            $map[$key] = [
                'lote' => $loc->lote,
                'op' => $loc->op_number,
                'order_id' => $loc->maquila_production_order_id,
                'num_arch' => (int)$loc->archivador_numero,
                'slot' => (int)$loc->slot,
            ];
        }

        return response()->json([
            'success' => true,
            'occupied' => $map
        ]);
    }

    protected function syncMissingMaquilaArchiveLocations()
    {
        try {
            if (!Schema::hasTable('maquila_production_orders') || !Schema::hasTable('batch_record_archive_locations')) {
                return;
            }

            $orders = MaquilaProductionOrder::whereNotNull('lote')
                ->where('lote', '!=', '')
                ->orderBy('id', 'asc')
                ->get();

            if ($orders->isEmpty()) {
                return;
            }

            $existingLocs = DB::table('batch_record_archive_locations')->get();
            $existingByLote = [];
            $usedSlotsMap = [];

            foreach ($existingLocs as $loc) {
                $loteKey = strtoupper(trim($loc->lote));
                $existingByLote[$loteKey] = $loc;
                $usedSlotsMap["{$loc->archivador_numero}_{$loc->slot}"] = true;
            }

            $now = Carbon::now()->toDateTimeString();
            $today = Carbon::now()->toDateString();
            $currentArch = 1;
            $currentSlot = 1;

            foreach ($orders as $m) {
                $loteUpper = strtoupper(trim($m->lote));

                // Si la orden ya tiene ubicación asignada en posicion_archivo_fisico
                if (!empty($m->posicion_archivo_fisico) && preg_match('/ARCHIVADOR\s*#?\s*(\d+)/i', $m->posicion_archivo_fisico, $matchArch)) {
                    $numArch = (int)$matchArch[1];
                    $slot = 1;
                    if (preg_match('/SLOT\s*([1-4])/i', $m->posicion_archivo_fisico, $matchSlot)) {
                        $slot = (int)$matchSlot[1];
                    }
                    $nivel = (int)ceil($numArch / 42);
                    $cara = ($numArch % 2 !== 0) ? 'VISIBLE' : 'POSTERIOR';

                    DB::table('batch_record_archive_locations')->updateOrInsert(
                        ['lote' => $loteUpper],
                        [
                            'rack' => 'RACK 1',
                            'nivel' => $nivel,
                            'archivador_numero' => $numArch,
                            'slot' => $slot,
                            'cara' => $cara,
                            'op_number' => $m->op ?? 'OP-EXT',
                            'producto_nombre' => $m->producto_nombre ?? 'PRODUCTO MAQUILA',
                            'tipo_origen' => 'MAQUILA',
                            'maquila_production_order_id' => $m->id,
                            'fecha_archivo' => $m->fecha_llegada_br ?? $today,
                            'updated_at' => $now,
                        ]
                    );
                    $usedSlotsMap["{$numArch}_{$slot}"] = true;
                    continue;
                }

                // Si ya existe registro en batch_record_archive_locations para este lote
                if (isset($existingByLote[$loteUpper])) {
                    $loc = $existingByLote[$loteUpper];
                    $posicionStr = "RACK 1 · NIVEL 0{$loc->nivel} · ARCHIVADOR #{$loc->archivador_numero} · SLOT {$loc->slot}";
                    $m->update(['posicion_archivo_fisico' => $posicionStr]);
                    $usedSlotsMap["{$loc->archivador_numero}_{$loc->slot}"] = true;
                    continue;
                }

                // Si no tiene ubicación asignada, buscar el siguiente slot realmente libre
                while (isset($usedSlotsMap["{$currentArch}_{$currentSlot}"])) {
                    $currentSlot++;
                    if ($currentSlot > 4) {
                        $currentSlot = 1;
                        $currentArch++;
                        if ($currentArch > 210) $currentArch = 210;
                    }
                }

                $nivel = (int)ceil($currentArch / 42);
                $cara = ($currentArch % 2 !== 0) ? 'VISIBLE' : 'POSTERIOR';
                $posicionStr = "RACK 1 · NIVEL 0{$nivel} · ARCHIVADOR #{$currentArch} · SLOT {$currentSlot}";

                DB::table('batch_record_archive_locations')->insert([
                    'lote' => $loteUpper,
                    'rack' => 'RACK 1',
                    'nivel' => $nivel,
                    'archivador_numero' => $currentArch,
                    'slot' => $currentSlot,
                    'cara' => $cara,
                    'op_number' => $m->op ?? 'OP-EXT',
                    'producto_nombre' => $m->producto_nombre ?? 'PRODUCTO MAQUILA',
                    'tipo_origen' => 'MAQUILA',
                    'maquila_production_order_id' => $m->id,
                    'fecha_archivo' => $m->fecha_llegada_br ?? $today,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);

                $m->update(['posicion_archivo_fisico' => $posicionStr]);
                $usedSlotsMap["{$currentArch}_{$currentSlot}"] = true;
            }

            \Illuminate\Support\Facades\Cache::forget('schema_checked_consultas_br_v5');
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning('Error en syncMissingMaquilaArchiveLocations: ' . $e->getMessage());
        }
    }

    /**
     * Población inicial con lotes existentes para que el usuario visualice el archivador con datos reales
     */
    protected function seedInitialArchiveLocations()
    {
        $this->syncMissingMaquilaArchiveLocations();
    }

    /**
     * Vista Principal: Módulo Consultas BR con Archivo 3D Interactivo (Optimizado para Carga Ultrarrápida)
     */
    public function index(Request $request)
    {
        $this->ensureSchema();

        $rackSeleccionado = 'RACK 1';
        $nivelSeleccionado = (int) $request->query('nivel', 1);
        if ($nivelSeleccionado < 1 || $nivelSeleccionado > 5) $nivelSeleccionado = 1;

        $caraSeleccionada = strtoupper($request->query('cara', 'VISIBLE'));
        if (!in_array($caraSeleccionada, ['VISIBLE', 'POSTERIOR'])) $caraSeleccionada = 'VISIBLE';

        $vistaModo = $request->query('vista', 'TODO');
        if (!in_array($vistaModo, ['TODO', 'BALDA'])) $vistaModo = 'TODO';

        $search = trim($request->query('buscar', ''));

        // Carga ligera pre-compilada de ubicaciones y órdenes para respuesta instantánea sin latencia
        $allLocations = BatchRecordArchiveLocation::all()->groupBy('archivador_numero');
        $allMaquilaOrders = MaquilaProductionOrder::with(['maquilador', 'items'])->get()->keyBy(function($m) {
            return strtoupper(trim($m->lote));
        });

        // Generar la estructura de los 5 niveles (de arriba hacia abajo: 1 -> 5)
        $rackCompleto = [];
        for ($n = 1; $n <= 5; $n++) {
            $baseNivel = ($n - 1) * 42;
            $archivadoresNivel = [];

            for ($i = 0; $i < 21; $i++) {
                if ($caraSeleccionada === 'VISIBLE') {
                    $num = $baseNivel + (2 * $i + 1);
                    $parDetras = $num + 1;
                } else {
                    $num = $baseNivel + (2 * $i + 2);
                    $parDetras = $num - 1;
                }

                $records = $allLocations->get($num, collect())->keyBy('slot');
                $count = $records->count();

                $slotsDetalle = [];
                for ($s = 1; $s <= 4; $s++) {
                    if (isset($records[$s])) {
                        $rec = $records[$s];
                        $loteKey = strtoupper(trim($rec->lote));
                        $maquila = $allMaquilaOrders->get($loteKey);

                        $presentaciones = [];
                        $maquiladorNombre = 'AUROFARMA';
                        $tamanoLote = null;
                        $fechaFab = null;
                        $fechaVenc = null;
                        $orderId = $rec->maquila_production_order_id;

                        if ($maquila) {
                            $orderId = $maquila->id;
                            $maquiladorNombre = $maquila->maquilador->nombre ?? 'MAQUILA EXTERNA';
                            $tamanoLote = $maquila->tamano_lote ? ($maquila->tamano_lote . ' ' . ($maquila->unidad_medida ?? 'UND')) : null;
                            if ($maquila->fecha_fabricacion) {
                                $fechaFab = is_string($maquila->fecha_fabricacion) ? $maquila->fecha_fabricacion : Carbon::parse($maquila->fecha_fabricacion)->format('Y-m');
                            }
                            if ($maquila->fecha_vencimiento) {
                                $fechaVenc = is_string($maquila->fecha_vencimiento) ? $maquila->fecha_vencimiento : Carbon::parse($maquila->fecha_vencimiento)->format('Y-m');
                            }
                            if ($maquila->items && $maquila->items->count() > 0) {
                                $presentaciones = $maquila->items->pluck('presentacion')->filter(fn($p) => !empty($p))->values()->toArray();
                                if (empty($presentaciones)) {
                                    $presentaciones = $maquila->items->pluck('descripcion_producto')->filter(fn($d) => !empty($d))->values()->toArray();
                                }
                            }
                        }

                        $slotsDetalle[] = [
                            'slot' => $s,
                            'ocupado' => true,
                            'lote' => $rec->lote,
                            'op_number' => $maquila->op ?? $rec->op_number,
                            'producto' => $maquila->producto_nombre ?? $rec->producto_nombre,
                            'maquilador' => $maquiladorNombre,
                            'presentaciones' => array_values(array_unique($presentaciones)),
                            'tamano_lote' => $tamanoLote,
                            'fecha_fab' => $fechaFab,
                            'fecha_venc' => $fechaVenc,
                            'tipo' => $rec->tipo_origen,
                            'fecha_archivo' => $rec->fecha_archivo ? (is_string($rec->fecha_archivo) ? $rec->fecha_archivo : Carbon::parse($rec->fecha_archivo)->format('Y-m-d')) : null,
                            'notas' => $rec->notas,
                            'order_id' => $orderId,
                            'radar_url' => $orderId ? route('maquila.show', $orderId) : null,
                            'pdf_url' => route('batch-records.pdf', $rec->lote),
                        ];
                    } else {
                        $slotsDetalle[] = [
                            'slot' => $s,
                            'ocupado' => false,
                            'lote' => null,
                            'op_number' => null,
                            'producto' => null,
                            'maquilador' => null,
                            'presentaciones' => [],
                            'tamano_lote' => null,
                            'fecha_fab' => null,
                            'fecha_venc' => null,
                            'tipo' => null,
                            'fecha_archivo' => null,
                            'notas' => null,
                            'order_id' => null,
                            'radar_url' => null,
                            'pdf_url' => null,
                        ];
                    }
                }

                $archivadoresNivel[] = [
                    'posicion_en_hilera' => $i + 1,
                    'numero' => $num,
                    'par_contraparte' => $parDetras,
                    'cara' => $caraSeleccionada,
                    'ocupacion_count' => $count,
                    'slots_detalle' => [
                        'archivador_numero' => $num,
                        'cara' => $caraSeleccionada,
                        'total_ocupados' => $count,
                        'slots' => $slotsDetalle
                    ]
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
        $totalLotesArchivados = BatchRecordArchiveLocation::count();
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

        $records = BatchRecordArchiveLocation::where('archivador_numero', $numero)
            ->get()
            ->keyBy(function ($item) {
                return (int)$item->slot;
            });

        $slots = [];
        for ($s = 1; $s <= 4; $s++) {
            if (isset($records[$s])) {
                $rec = $records[$s];

                // Buscar orden de maquila para enriquecer los datos
                $maquila = null;
                if ($rec->maquila_production_order_id) {
                    $maquila = MaquilaProductionOrder::with(['maquilador', 'items'])->find($rec->maquila_production_order_id);
                }
                if (!$maquila) {
                    $maquila = MaquilaProductionOrder::with(['maquilador', 'items'])->where('lote', $rec->lote)->first();
                }

                $presentaciones = [];
                $maquiladorNombre = 'AUROFARMA';
                $tamanoLote = null;
                $fechaFab = null;
                $fechaVenc = null;
                $orderId = $rec->maquila_production_order_id;

                if ($maquila) {
                    $orderId = $maquila->id;
                    $maquiladorNombre = $maquila->maquilador->nombre ?? 'MAQUILA EXTERNA';
                    $tamanoLote = $maquila->tamano_lote ? ($maquila->tamano_lote . ' ' . ($maquila->unidad_medida ?? 'UND')) : null;
                    
                    if ($maquila->fecha_fabricacion) {
                        $fechaFab = is_string($maquila->fecha_fabricacion) ? $maquila->fecha_fabricacion : Carbon::parse($maquila->fecha_fabricacion)->format('Y-m');
                    }
                    if ($maquila->fecha_vencimiento) {
                        $fechaVenc = is_string($maquila->fecha_vencimiento) ? $maquila->fecha_vencimiento : Carbon::parse($maquila->fecha_vencimiento)->format('Y-m');
                    }
                    
                    if ($maquila->items && $maquila->items->count() > 0) {
                        $presentaciones = $maquila->items->pluck('presentacion')->filter(fn($p) => !empty($p))->values()->toArray();
                        if (empty($presentaciones)) {
                            $presentaciones = $maquila->items->pluck('descripcion_producto')->filter(fn($d) => !empty($d))->values()->toArray();
                        }
                    }
                }

                $slots[] = [
                    'slot' => $s,
                    'ocupado' => true,
                    'lote' => $rec->lote,
                    'op_number' => $maquila->op ?? $rec->op_number,
                    'producto' => $maquila->producto_nombre ?? $rec->producto_nombre,
                    'maquilador' => $maquiladorNombre,
                    'presentaciones' => array_values(array_unique($presentaciones)),
                    'tamano_lote' => $tamanoLote,
                    'fecha_fab' => $fechaFab,
                    'fecha_venc' => $fechaVenc,
                    'tipo' => $rec->tipo_origen,
                    'fecha_archivo' => $rec->fecha_archivo ? (is_string($rec->fecha_archivo) ? $rec->fecha_archivo : Carbon::parse($rec->fecha_archivo)->format('Y-m-d')) : null,
                    'notas' => $rec->notas,
                    'order_id' => $orderId,
                    'radar_url' => $orderId ? route('maquila.show', $orderId) : null,
                    'pdf_url' => route('batch-records.pdf', $rec->lote),
                ];
            } else {
                $slots[] = [
                    'slot' => $s,
                    'ocupado' => false,
                    'lote' => null,
                    'op_number' => null,
                    'producto' => null,
                    'maquilador' => null,
                    'presentaciones' => [],
                    'tamano_lote' => null,
                    'fecha_fab' => null,
                    'fecha_venc' => null,
                    'tipo' => null,
                    'fecha_archivo' => null,
                    'notas' => null,
                    'order_id' => null,
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
