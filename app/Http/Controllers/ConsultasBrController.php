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
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

/**
 * ============================================================================
 * CONTROLADOR DEL ARCHIVO FÍSICO 3D (CONSULTAS BR) - ConsultasBrController
 * ============================================================================
 * GUÍA RÁPIDA ("FOR DUMMIES"):
 * Administra el gemelo digital 3D del Rack de Archivo Físico de Aurofarma:
 * - El Rack 1 tiene 5 Niveles (baldas).
 * - Cada Nivel tiene 42 Archivadores AZ (21 impares al frente = cara VISIBLE,
 *   y 21 pares detrás = cara POSTERIOR), sumando 210 Archivadores en total.
 * - Cada Archivador tiene 4 Slots (capacidad para 4 Batch Records), para un
 *   total de 840 expedientes físicos.
 *
 * Métodos principales:
 * 1. `index()`: Renderiza el visor 3D interactivo.
 * 2. `getOccupiedSlots()`: Devuelve en JSON qué slots están ocupados.
 * 3. `apiGetArchivador($numero)`: Devuelve el detalle de los 4 slots de una carpeta AZ.
 * 4. `apiAssignSlot()`: Asigna un lote (de Planta o Maquila) a un slot físico.
 * 5. `apiSearch()`: Busca un lote/OP y devuelve sus coordenadas 3D (Nivel, Cara, Archivador, Slot).
 * ============================================================================
 */
class ConsultasBrController extends Controller
{
    /**
     * Garantiza que las ubicaciones físicas estén sincronizadas con las órdenes reales.
     * OPTIMIZACIÓN PILAR 4: Se ejecuta máximo una vez cada 5 minutos mediante caché
     * (salvo reconstrucción explícita) para evitar repetir decenas de `updateOrInsert` en cada clic.
     */
    protected function ensureSchema()
    {
        \Illuminate\Support\Facades\Cache::remember('aurotrace_archive_sync_v1', 300, function () {
            $this->syncRealMaquilaArchiveLocations();
            return true;
        });
    }

    /**
     * Reconstruye y sincroniza las ubicaciones físicas del archivo 3D de forma inmediata.
     */
    public function rebuildArchiveLocations()
    {
        \Illuminate\Support\Facades\Cache::forget('aurotrace_archive_sync_v1');
        $this->syncRealMaquilaArchiveLocations();
    }

    /**
     * Alias de compatibilidad para sincronización de ubicaciones.
     */
    protected function syncMissingMaquilaArchiveLocations()
    {
        $this->syncRealMaquilaArchiveLocations();
    }

    /**
     * Alias de compatibilidad para inicialización de ubicaciones.
     */
    protected function seedInitialArchiveLocations()
    {
        $this->syncRealMaquilaArchiveLocations();
    }

    /**
     * API: Retorna el mapa completo de slots ocupados en el Rack 1.
     */
    public function getOccupiedSlots()
    {
        $this->ensureSchema();

        $locations = DB::table('batch_record_archive_locations')
            ->select('archivador_numero', 'slot', 'lote', 'op_number', 'maquila_production_order_id')
            ->get();

        $map = [];
        foreach ($locations as $loc) {
            $key = "{$loc->archivador_numero}_{$loc->slot}";
            $map[$key] = [
                'lote'     => $loc->lote,
                'op'       => $loc->op_number,
                'order_id' => $loc->maquila_production_order_id,
                'num_arch' => (int) $loc->archivador_numero,
                'slot'     => (int) $loc->slot,
            ];
        }

        return response()->json([
            'success'  => true,
            'occupied' => $map,
        ]);
    }

    /**
     * Sincroniza las órdenes de Maquila que tienen `posicion_archivo_fisico`
     * hacia la tabla `batch_record_archive_locations`, soportando tanto el formato
     * largo ("ARCHIVADOR #5 ... SLOT 1") como el abreviado ("R 1 N 1 A 5 S 1").
     */
    protected function syncRealMaquilaArchiveLocations()
    {
        try {
            if (!Schema::hasTable('maquila_production_orders') || !Schema::hasTable('batch_record_archive_locations')) {
                return;
            }

            // 1. Obtener órdenes de maquila reales que tienen asignada ubicación física
            $validOrders = MaquilaProductionOrder::whereNotNull('posicion_archivo_fisico')
                ->where('posicion_archivo_fisico', '!=', '')
                ->get();

            $validOrderIds = $validOrders->pluck('id')->toArray();

            // 2. Limpiar únicamente registros de origen MAQUILA que hayan quedado huérfanos
            // (Conservando registros de PLANTA o asignados manualmente con orden válida)
            if (empty($validOrderIds)) {
                DB::table('batch_record_archive_locations')
                    ->where('tipo_origen', 'MAQUILA')
                    ->delete();
            } else {
                DB::table('batch_record_archive_locations')
                    ->where('tipo_origen', 'MAQUILA')
                    ->whereNotNull('maquila_production_order_id')
                    ->whereNotIn('maquila_production_order_id', $validOrderIds)
                    ->delete();
            }

            // 3. Sincronizar todas las órdenes con posición física válida
            $now = Carbon::now()->toDateTimeString();
            $today = Carbon::now()->toDateString();

            foreach ($validOrders as $m) {
                if (preg_match('/(?:ARCHIVADOR|A)\s*#?\s*(\d+)/i', $m->posicion_archivo_fisico, $matchArch)) {
                    $numArch = (int) $matchArch[1];
                    if ($numArch < 1 || $numArch > 210) {
                        continue;
                    }

                    $slot = 1;
                    if (preg_match('/(?:SLOT|S)\s*#?\s*([1-4])/i', $m->posicion_archivo_fisico, $matchSlot)) {
                        $slot = (int) $matchSlot[1];
                    }

                    $nivel = (int) ceil($numArch / 42);
                    $cara = ($numArch % 2 !== 0) ? 'VISIBLE' : 'POSTERIOR';

                    DB::table('batch_record_archive_locations')->updateOrInsert(
                        [
                            'rack'              => 'RACK 1',
                            'nivel'             => $nivel,
                            'archivador_numero' => $numArch,
                            'slot'              => $slot,
                        ],
                        [
                            'cara'                        => $cara,
                            'lote'                        => strtoupper(trim((string) $m->lote)),
                            'op_number'                   => $m->op ?? 'OP-EXT',
                            'producto_nombre'             => $m->producto_nombre ?? 'PRODUCTO MAQUILA',
                            'tipo_origen'                 => 'MAQUILA',
                            'maquila_production_order_id' => $m->id,
                            'fecha_archivo'               => $m->fecha_llegada_br ?? $today,
                            'updated_at'                  => $now,
                        ]
                    );
                }
            }
        } catch (\Throwable $e) {
            Log::warning('Error en syncRealMaquilaArchiveLocations: ' . $e->getMessage());
        }
    }

    /**
     * Vista Principal: Módulo Consultas BR con Archivo 3D Interactivo.
     */
    public function index(Request $request)
    {
        $this->ensureSchema();

        $search = trim($request->query('q', $request->query('buscar', '')));
        $resultadoBusqueda = null;

        if ($search !== '') {
            try {
                $searchReq = new Request(['q' => $search]);
                $resJson = $this->apiSearch($searchReq);
                $searchData = json_decode($resJson->getContent(), true);

                if ($searchData && !empty($searchData['found'])) {
                    $resultadoBusqueda = $searchData;
                    $request->query->set('nivel', (int) $searchData['nivel']);
                    $request->query->set('cara', $searchData['cara']);
                }
            } catch (\Throwable $e) {}
        }

        $rackSeleccionado = 'RACK 1';
        $nivelSeleccionado = $resultadoBusqueda ? (int) $resultadoBusqueda['nivel'] : (int) $request->query('nivel', 1);
        if ($nivelSeleccionado < 1 || $nivelSeleccionado > 5) {
            $nivelSeleccionado = 1;
        }

        $caraSeleccionada = $resultadoBusqueda ? strtoupper($resultadoBusqueda['cara']) : strtoupper($request->query('cara', 'VISIBLE'));
        if (!in_array($caraSeleccionada, ['VISIBLE', 'POSTERIOR'])) {
            $caraSeleccionada = 'VISIBLE';
        }

        $vistaModo = $request->query('vista', 'TODO');
        if (!in_array($vistaModo, ['TODO', 'BALDA'])) {
            $vistaModo = 'TODO';
        }

        // Pre-carga en memoria de ubicaciones y órdenes para evitar consultas repetidas en el bucle
        $allLocations = BatchRecordArchiveLocation::all()->groupBy('archivador_numero');
        $allMaquilaOrders = MaquilaProductionOrder::with(['maquilador', 'items'])->get()->keyBy(function ($m) {
            return strtoupper(trim((string) $m->lote));
        });

        // Construir la estructura de los 5 niveles del Rack (Nivel 1 superior -> Nivel 5 inferior)
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
                        $loteKey = strtoupper(trim((string) $rec->lote));
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
                            'slot'           => $s,
                            'ocupado'        => true,
                            'lote'           => $rec->lote,
                            'op_number'      => $maquila->op ?? $rec->op_number,
                            'producto'       => $maquila->producto_nombre ?? $rec->producto_nombre,
                            'maquilador'     => $maquiladorNombre,
                            'presentaciones' => array_values(array_unique($presentaciones)),
                            'tamano_lote'    => $tamanoLote,
                            'fecha_fab'      => $fechaFab,
                            'fecha_venc'     => $fechaVenc,
                            'tipo'           => $rec->tipo_origen,
                            'fecha_archivo'  => $rec->fecha_archivo ? (is_string($rec->fecha_archivo) ? $rec->fecha_archivo : Carbon::parse($rec->fecha_archivo)->format('Y-m-d')) : null,
                            'notas'          => $rec->notas,
                            'order_id'       => $orderId,
                            'radar_url'      => $orderId ? route('maquila.show', $orderId) : null,
                            'pdf_url'        => !empty($rec->lote) ? route('batch-records.pdf', ['lote' => $rec->lote]) : null,
                        ];
                    } else {
                        $slotsDetalle[] = [
                            'slot'           => $s,
                            'ocupado'        => false,
                            'lote'           => null,
                            'op_number'      => null,
                            'producto'       => null,
                            'maquilador'     => null,
                            'presentaciones' => [],
                            'tamano_lote'    => null,
                            'fecha_fab'      => null,
                            'fecha_venc'     => null,
                            'tipo'           => null,
                            'fecha_archivo'  => null,
                            'notas'          => null,
                            'order_id'       => null,
                            'radar_url'      => null,
                            'pdf_url'        => null,
                        ];
                    }
                }

                $archivadoresNivel[] = [
                    'posicion_en_hilera' => $i + 1,
                    'numero'             => $num,
                    'par_contraparte'    => $parDetras,
                    'cara'               => $caraSeleccionada,
                    'ocupacion_count'    => $count,
                    'slots_detalle'      => [
                        'archivador_numero' => $num,
                        'cara'              => $caraSeleccionada,
                        'total_ocupados'    => $count,
                        'slots'             => $slotsDetalle,
                    ],
                ];
            }

            $rackCompleto[$n] = [
                'nivel'        => $n,
                'etiqueta'     => "Nivel 0{$n}" . ($n === 1 ? ' (Superior)' : ($n === 5 ? ' (Inferior)' : '')),
                'archivadores' => $archivadoresNivel,
                'rango_texto'  => '#' . str_pad($archivadoresNivel[0]['numero'], 2, '0', STR_PAD_LEFT) . ' al #' . str_pad($archivadoresNivel[20]['numero'], 2, '0', STR_PAD_LEFT),
            ];
        }

        $archivadores = $rackCompleto[$nivelSeleccionado]['archivadores'] ?? [];

        // Estadísticas globales de capacidad del Rack
        $totalArchivadores = 5 * 42; // 210 archivadores físicos
        $capacidadTotalBatch = $totalArchivadores * 4; // 840 Batch Records
        $totalLotesArchivados = BatchRecordArchiveLocation::count();
        $espaciosDisponibles = max(0, $capacidadTotalBatch - $totalLotesArchivados);

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
     * API: Obtiene el estado detallado de los 4 slots de un archivador específico.
     */
    public function apiGetArchivador($numero)
    {
        $this->ensureSchema();

        $numero = (int) $numero;
        $cara = ($numero % 2 !== 0) ? 'VISIBLE' : 'POSTERIOR';

        $records = BatchRecordArchiveLocation::where('archivador_numero', $numero)
            ->get()
            ->keyBy(fn($item) => (int) $item->slot);

        $slots = [];
        for ($s = 1; $s <= 4; $s++) {
            if (isset($records[$s])) {
                $rec = $records[$s];

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
                    'slot'           => $s,
                    'ocupado'        => true,
                    'lote'           => $rec->lote,
                    'op_number'      => $maquila->op ?? $rec->op_number,
                    'producto'       => $maquila->producto_nombre ?? $rec->producto_nombre,
                    'maquilador'     => $maquiladorNombre,
                    'presentaciones' => array_values(array_unique($presentaciones)),
                    'tamano_lote'    => $tamanoLote,
                    'fecha_fab'      => $fechaFab,
                    'fecha_venc'     => $fechaVenc,
                    'tipo'           => $rec->tipo_origen,
                    'fecha_archivo'  => $rec->fecha_archivo ? (is_string($rec->fecha_archivo) ? $rec->fecha_archivo : Carbon::parse($rec->fecha_archivo)->format('Y-m-d')) : null,
                    'notas'          => $rec->notas,
                    'order_id'       => $orderId,
                    'radar_url'      => $orderId ? route('maquila.show', $orderId) : null,
                    'pdf_url'        => !empty($rec->lote) ? route('batch-records.pdf', ['lote' => $rec->lote]) : null,
                ];
            } else {
                $slots[] = [
                    'slot'           => $s,
                    'ocupado'        => false,
                    'lote'           => null,
                    'op_number'      => null,
                    'producto'       => null,
                    'maquilador'     => null,
                    'presentaciones' => [],
                    'tamano_lote'    => null,
                    'fecha_fab'      => null,
                    'fecha_venc'     => null,
                    'tipo'           => null,
                    'fecha_archivo'  => null,
                    'notas'          => null,
                    'order_id'       => null,
                    'radar_url'      => null,
                    'pdf_url'        => null,
                ];
            }
        }

        return response()->json([
            'archivador_numero' => $numero,
            'cara'              => $cara,
            'total_ocupados'    => $records->count(),
            'slots'             => $slots,
        ]);
    }

    /**
     * API: Asigna un Lote / Batch Record a un slot físico del archivador.
     */
    public function apiAssignSlot(Request $request)
    {
        $this->ensureSchema();

        if (Auth::check() && Auth::user()->isQualityUser()) {
            return response()->json([
                'success' => false,
                'message' => 'Acceso denegado: El perfil de Calidad tiene permisos de SOLO CONSULTA para el módulo de Archivo 3D.',
            ], 403);
        }

        $validated = $request->validate([
            'rack'              => 'required|string',
            'nivel'             => 'required|integer|min:1|max:5',
            'archivador_numero' => 'required|integer|min:1|max:210',
            'cara'              => 'required|in:VISIBLE,POSTERIOR',
            'slot'              => 'required|integer|min:1|max:4',
            'lote'              => 'required|string|max:50',
            'op_number'         => 'nullable|string|max:50',
            'producto_nombre'   => 'nullable|string|max:255',
            'tipo_origen'       => 'required|in:PLANTA,MAQUILA',
            'notas'             => 'nullable|string',
        ]);

        DB::beginTransaction();
        try {
            $loteUpper = strtoupper(trim($validated['lote']));
            $opUpper = strtoupper(trim($validated['op_number'] ?? ''));
            $prodUpper = strtoupper(trim($validated['producto_nombre'] ?? ''));

            // Buscar orden asociada en Maquila o Planta para vincular su ID y autocompletar datos
            $maquila = MaquilaProductionOrder::where('lote', $loteUpper)->first();
            $planta = ProductionOrder::with('product')->where('lote', $loteUpper)->first();

            if (empty($prodUpper)) {
                if ($maquila) {
                    $prodUpper = $maquila->producto_nombre;
                    if (empty($opUpper)) {
                        $opUpper = $maquila->op;
                    }
                } elseif ($planta) {
                    $prodUpper = $planta->product->name ?? 'PRODUCTO PLANTA';
                    if (empty($opUpper)) {
                        $opUpper = $planta->op_number;
                    }
                }
            }

            $location = BatchRecordArchiveLocation::updateOrCreate(
                [
                    'rack'              => $validated['rack'],
                    'nivel'             => $validated['nivel'],
                    'archivador_numero' => $validated['archivador_numero'],
                    'slot'              => $validated['slot'],
                ],
                [
                    'cara'                        => $validated['cara'],
                    'lote'                        => $loteUpper,
                    'op_number'                   => $opUpper,
                    'producto_nombre'             => $prodUpper ?: 'PRODUCTO FARMACÉUTICO',
                    'tipo_origen'                 => $validated['tipo_origen'],
                    'production_order_id'         => $planta?->id,
                    'maquila_production_order_id' => $maquila?->id,
                    'fecha_archivo'               => Carbon::today(),
                    'notas'                       => $validated['notas'] ?? null,
                ]
            );

            // Sincronizar etiqueta de posición física en MaquilaProductionOrder y ProductionOrder
            $posicionStr = "R 1 N {$location->nivel} A {$location->archivador_numero} S {$location->slot}";
            if ($maquila) {
                $maquila->update(['posicion_archivo_fisico' => $posicionStr]);
            }

            if ($planta && Schema::hasColumn('production_orders', 'posicion_archivo_fisico')) {
                $planta->update(['posicion_archivo_fisico' => $posicionStr]);
            }

            AuditLog::create([
                'user_id'    => Auth::id() ?? 1,
                'action'     => 'ASIGNAR_ARCHIVO_FISICO_BR',
                'model_type' => BatchRecordArchiveLocation::class,
                'model_id'   => $location->id,
                'reason'     => "Asignación física del Batch Record para el Lote {$loteUpper} en {$posicionStr} por usuario " . (Auth::user()->name ?? 'Sistema'),
                'new_values' => json_encode($location->toArray()),
                'ip_address' => $request->ip(),
            ]);

            DB::commit();
            \Illuminate\Support\Facades\Cache::forget('aurotrace_archive_sync_v1');

            return response()->json([
                'success'  => true,
                'message'  => "Lote {$loteUpper} archivado correctamente en {$posicionStr}.",
                'location' => $location,
            ]);
        } catch (\Throwable $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Error al asignar ubicación: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * API: Búsqueda rápida para ubicar espacialmente un lote u OP en el Rack 3D.
     */
    public function apiSearch(Request $request)
    {
        $this->ensureSchema();
        $q = strtoupper(trim($request->query('q', '')));
        if (empty($q)) {
            return response()->json(['found' => false]);
        }

        $likeOp = DB::connection()->getDriverName() === 'pgsql' ? 'ILIKE' : 'LIKE';

        // 1. Buscar directamente en batch_record_archive_locations
        $locQuery = BatchRecordArchiveLocation::where(function ($bQuery) use ($q, $likeOp) {
            $bQuery->where('lote', $likeOp, "%{$q}%")
                   ->orWhere('op_number', $likeOp, "%{$q}%")
                   ->orWhere('producto_nombre', $likeOp, "%{$q}%");

            if (is_numeric($q)) {
                $bQuery->orWhere('archivador_numero', (int) $q);
            }
        });
        $loc = $locQuery->first();

        if ($loc) {
            return response()->json([
                'found'               => true,
                'rack'                => $loc->rack,
                'nivel'               => (int) $loc->nivel,
                'cara'                => $loc->cara,
                'archivador_numero'   => (int) $loc->archivador_numero,
                'slot'                => (int) $loc->slot,
                'lote'                => $loc->lote,
                'op'                  => $loc->op_number,
                'producto'            => $loc->producto_nombre,
                'posicion_formateada' => $loc->ubicacion_completa ?: "R 1 N {$loc->nivel} A {$loc->archivador_numero} S {$loc->slot}",
            ]);
        }

        // 2. Buscar en MaquilaProductionOrder por lote, OP o nombre de producto
        $maquila = MaquilaProductionOrder::where(function ($mQuery) use ($q, $likeOp) {
            $mQuery->where('lote', $likeOp, "%{$q}%")
                   ->orWhere('op', $likeOp, "%{$q}%")
                   ->orWhere('producto_nombre', $likeOp, "%{$q}%");
        })->first();

        if ($maquila) {
            $posStr = $maquila->posicion_archivo_fisico;

            if ($posStr && preg_match('/(?:ARCHIVADOR|A)\s*#?\s*(\d+)/i', $posStr, $mA)) {
                $numArch = (int) $mA[1];
                $slot = 1;
                if (preg_match('/(?:SLOT|S)\s*#?\s*([1-4])/i', $posStr, $mS)) {
                    $slot = (int) $mS[1];
                }

                $nivel = (int) ceil($numArch / 42);
                $cara = ($numArch % 2 !== 0) ? 'VISIBLE' : 'POSTERIOR';
                $posFormateada = "R 1 N {$nivel} A {$numArch} S {$slot}";

                return response()->json([
                    'found'               => true,
                    'rack'                => 'RACK 1',
                    'nivel'               => $nivel,
                    'cara'                => $cara,
                    'archivador_numero'   => $numArch,
                    'slot'                => $slot,
                    'lote'                => $maquila->lote,
                    'op'                  => $maquila->op,
                    'producto'            => $maquila->producto_nombre,
                    'posicion_formateada' => $posFormateada,
                ]);
            }

            return response()->json([
                'found'   => false,
                'message' => "El lote '{$maquila->lote}' (OP {$maquila->op}) está registrado en Maquilas con estado '{$maquila->estado}', pero aún no tiene asignada una posición en el archivo físico central (esperando llegada de BR).",
            ]);
        }

        return response()->json([
            'found'   => false,
            'message' => "El lote '{$q}' no fue encontrado en la base de datos de producción ni archivo físico.",
        ]);
    }
}
