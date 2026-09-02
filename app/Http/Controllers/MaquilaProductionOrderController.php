<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\MaquilaProductionOrder;
use App\Models\MaquilaItem;
use App\Models\MaquilaDelivery;
use App\Models\Maquilador;
use App\Models\ElectronicSignature;
use App\Models\Product;
use App\Models\Item;
use App\Models\AuditLog;
use App\Services\Cfr21SignatureService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class MaquilaProductionOrderController extends Controller
{
    protected $cfr21Service;

    public function __construct(Cfr21SignatureService $cfr21Service)
    {
        $this->cfr21Service = $cfr21Service;
    }

    /**
     * 3.2 Dashboard Analítico 360° (Torre de Control)
     */
    public function dashboard(Request $request)
    {
        // Auto-migrar si las tablas no existen aún en la base de datos Supabase/PostgreSQL
        if (!\Illuminate\Support\Facades\Schema::hasTable('maquila_production_orders')) {
            try {
                \Illuminate\Support\Facades\Artisan::call('migrate', ['--force' => true]);
                \Illuminate\Support\Facades\Artisan::call('db:seed', ['--class' => 'MaquiladorSeeder', '--force' => true]);
            } catch (\Throwable $e) {}
        }

        if (\Illuminate\Support\Facades\Schema::hasTable('maquiladores') && Maquilador::count() === 0) {
            try {
                \Illuminate\Support\Facades\Artisan::call('db:seed', ['--class' => 'MaquiladorSeeder', '--force' => true]);
            } catch (\Throwable $e) {}
        }

        $tipoFilter = $request->query('tipo_producto');
        $maquiladorFilter = $request->query('maquilador_id');

        // Query base de OPs
        $query = MaquilaProductionOrder::with(['maquilador', 'items.deliveries', 'creator']);

        if ($tipoFilter && in_array($tipoFilter, ['premezcla', 'producto_terminado'])) {
            $query->where('tipo_producto', $tipoFilter);
        }

        if ($maquiladorFilter) {
            $query->where('maquilador_id', $maquiladorFilter);
        }

        $orders = $query->latest()->get();

        // 1. KPIs principales
        $opsActivasCount = MaquilaProductionOrder::whereIn('estado', ['enviada_a_maquila', 'en_proceso', 'entrega_parcial'])->count();

        // Rendimientos promedio diferenciados por tipo (Regla 3.2)
        $itemsLiquidadosPremezcla = MaquilaItem::whereHas('order', function($q) {
            $q->where('tipo_producto', 'premezcla');
        })->get();
        
        $itemsLiquidadosTerminado = MaquilaItem::whereHas('order', function($q) {
            $q->where('tipo_producto', 'producto_terminado');
        })->get();

        $rendimientoPromedioPremezcla = $itemsLiquidadosPremezcla->count() > 0 
            ? round($itemsLiquidadosPremezcla->avg('rendimiento_pct'), 2) 
            : 100.0;

        $rendimientoPromedioTerminado = $itemsLiquidadosTerminado->count() > 0 
            ? round($itemsLiquidadosTerminado->avg('rendimiento_pct'), 2) 
            : 100.0;

        $allItems = MaquilaItem::all();
        $rendimientoPromedioGlobal = $allItems->count() > 0 
            ? round($allItems->avg('rendimiento_pct'), 2) 
            : 100.0;

        $leadTimePromedio = round($orders->avg('lead_time_dias'), 1);

        // 2. Alertas de Vencimiento BPM ICA Maquiladores
        $alertasBpmIca = Maquilador::where('activo', true)
            ->get()
            ->filter(function($m) {
                return in_array($m->estado_certificado_ica, ['vencido', 'proximo_a_vencer']);
            });

        // 3. Alertas de Vencimiento de Productos (Lotes)
        $alertasVencimientoProducto = MaquilaItem::whereNotNull('fecha_vencimiento')
            ->whereDate('fecha_vencimiento', '<=', Carbon::today()->addDays(90))
            ->with(['order.maquilador'])
            ->get();

        $maquiladores = Maquilador::where('activo', true)->orderBy('nombre')->get();

        return view('maquila.dashboard', compact(
            'orders',
            'opsActivasCount',
            'rendimientoPromedioGlobal',
            'rendimientoPromedioPremezcla',
            'rendimientoPromedioTerminado',
            'leadTimePromedio',
            'alertasBpmIca',
            'alertasVencimientoProducto',
            'maquiladores',
            'tipoFilter',
            'maquiladorFilter'
        ));
    }

    /**
     * 3.1 Wizard de creación de ODM / SDM
     */
    public function create()
    {
        if (!\Illuminate\Support\Facades\Schema::hasTable('maquila_production_orders')) {
            try {
                \Illuminate\Support\Facades\Artisan::call('migrate', ['--force' => true]);
                \Illuminate\Support\Facades\Artisan::call('db:seed', ['--class' => 'MaquiladorSeeder', '--force' => true]);
            } catch (\Throwable $e) {}
        }

        if (\Illuminate\Support\Facades\Schema::hasTable('maquiladores') && Maquilador::count() === 0) {
            try {
                \Illuminate\Support\Facades\Artisan::call('db:seed', ['--class' => 'MaquiladorSeeder', '--force' => true]);
            } catch (\Throwable $e) {}
        }

        $maquiladores = Maquilador::where('activo', true)->orderBy('nombre')->get();
        $nextOdm = 'ODM-';

        return view('maquila.create', compact('maquiladores', 'nextOdm'));
    }

    /**
     * Guarda la nueva Orden de Maquila (ODM / SDM)
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'numero_odm' => 'required|string|unique:maquila_production_orders,numero_odm',
            'op' => 'nullable|string',
            'lote' => 'nullable|string',
            'tipo_producto' => 'required|in:premezcla,producto_terminado',
            'maquilador_id' => 'required|exists:maquiladores,id',
            'observaciones' => 'nullable|string',
            'items' => 'required|array|min:1',
            'items.*.sdm' => 'nullable|string',
            'items.*.codigo_item' => 'required|string',
            'items.*.descripcion_producto' => 'required|string',
            'items.*.cantidad_programada' => 'required|numeric|min:0.001',
            'items.*.unidad_medida' => 'required|in:KG,UND',
        ]);

        DB::beginTransaction();
        try {
            $maquilador = Maquilador::findOrFail($validated['maquilador_id']);

            $order = MaquilaProductionOrder::create([
                'numero_odm' => $validated['numero_odm'],
                'op' => $validated['op'] ?? null,
                'lote' => $validated['lote'] ?? null,
                'tipo_producto' => $validated['tipo_producto'],
                'maquilador_id' => $validated['maquilador_id'],
                'fecha_creacion' => Carbon::today(),
                'fecha_envio_maquila' => Carbon::today(),
                'estado' => 'enviada_a_maquila',
                'usuario_creador_id' => Auth::id(),
                'observaciones' => $validated['observaciones'] ?? null,
            ]);

            foreach ($validated['items'] as $itemData) {
                MaquilaItem::create([
                    'maquila_production_order_id' => $order->id,
                    'sdm' => $itemData['sdm'] ?? null,
                    'codigo_item' => $itemData['codigo_item'],
                    'descripcion_producto' => $itemData['descripcion_producto'],
                    'lote_fisico' => $validated['lote'] ?? '',
                    'presentacion' => 'UNIDAD',
                    'cantidad_programada' => $itemData['cantidad_programada'],
                    'unidad_medida' => $itemData['unidad_medida'],
                ]);
            }

            // Audit Trail
            AuditLog::create([
                'user_id' => Auth::id(),
                'action' => 'CREAR_ORDEN_MAQUILA',
                'model_type' => 'App\Models\MaquilaProductionOrder',
                'model_id' => $order->id,
                'reason' => "Creación de Orden de Maquila ODM: {$order->numero_odm} (OP: {$order->op}, Lote: {$order->lote}) para {$maquilador->nombre}",
                'new_values' => json_encode($order->toArray()),
                'ip_address' => $request->ip()
            ]);

            DB::commit();

            return redirect()->route('maquila.show', $order->id)
                ->with('success', "Orden de Maquila {$order->numero_odm} guardada y emitida correctamente.");

        } catch (\Throwable $e) {
            DB::rollBack();
            return back()->withInput()->with('error', 'Error al guardar la orden: ' . $e->getMessage());
        }
    }

    /**
     * 3.3 Radar de Trazabilidad por Lote (Vista Detallada 360°)
     */
    public function show($id)
    {
        $order = MaquilaProductionOrder::with([
            'maquilador',
            'creator',
            'items.deliveries.user',
            'items.deliveries.signature.user',
            'items.deliveries.signature.secondUser'
        ])->findOrFail($id);

        return view('maquila.radar', compact('order'));
    }

    /**
     * Registro de Entrega Parcial Directo (Sin requerir contraseña/firma CFR21)
     */
    public function registerDelivery(Request $request, $itemId)
    {
        $validated = $request->validate([
            'fecha_recepcion' => 'required|date',
            'numero_remision_factura' => 'required|string|max:255',
            'cantidad_recibida' => 'required|numeric|min:0.001',
            'excedente_autorizado' => 'nullable|boolean'
        ]);

        $item = MaquilaItem::with('order')->findOrFail($itemId);

        // Validar que no exceda el saldo salvo confirmación de excedente
        if ($validated['cantidad_recibida'] > $item->saldo_pendiente && empty($validated['excedente_autorizado'])) {
            return back()->with('warning_excedente', [
                'item_id' => $item->id,
                'cantidad' => $validated['cantidad_recibida'],
                'saldo' => $item->saldo_pendiente,
                'mensaje' => "La cantidad a recibir ({$validated['cantidad_recibida']}) excede el saldo pendiente ({$item->saldo_pendiente}). ¿Desea registrar una recepción con excedente de merma?"
            ]);
        }

        DB::beginTransaction();
        try {
            $payload = [
                'item_id' => $item->id,
                'fecha' => $validated['fecha_recepcion'],
                'remision' => $validated['numero_remision_factura'],
                'cantidad' => $validated['cantidad_recibida'],
                'user_id' => Auth::id(),
                'timestamp' => now()->toIso8601String()
            ];

            $hashIntegridad = hash('sha256', json_encode($payload));

            // Crear la entrega directa
            $delivery = MaquilaDelivery::create([
                'maquila_item_id' => $item->id,
                'fecha_recepcion' => $validated['fecha_recepcion'],
                'numero_remision_factura' => $validated['numero_remision_factura'],
                'cantidad_recibida' => $validated['cantidad_recibida'],
                'usuario_registro_id' => Auth::id(),
                'hash_integridad' => $hashIntegridad
            ]);

            // Actualizar estado de la orden
            $order = $item->order;
            if (in_array($order->estado, ['enviada_a_maquila', 'en_proceso', 'borrador'])) {
                $order->update(['estado' => 'entrega_parcial']);
            }

            // Audit Trail
            AuditLog::create([
                'user_id' => Auth::id(),
                'action' => 'REGISTRO_ENTREGA_MAQUILA',
                'model_type' => 'App\Models\MaquilaDelivery',
                'model_id' => $delivery->id,
                'reason' => "Recepción de entrega parcial de {$delivery->cantidad_recibida} {$item->unidad_medida} para el ítem {$item->descripcion_producto} (Remisión: {$delivery->numero_remision_factura})",
                'new_values' => json_encode($delivery->toArray()),
                'ip_address' => $request->ip()
            ]);

            DB::commit();

            return redirect()->route('maquila.show', $order->id)
                ->with('success', "Entrega de {$delivery->cantidad_recibida} {$item->unidad_medida} registrada correctamente.");

        } catch (\Throwable $e) {
            DB::rollBack();
            return back()->with('error', 'Error al registrar entrega: ' . $e->getMessage());
        }
    }

    /**
     * Cierre Técnico y Liquidación Directo (Sin requerir contraseña/firma doble)
     */
    public function closeOrder(Request $request, $id)
    {
        $validated = $request->validate([
            'justificacion' => 'required|string|min:5'
        ]);

        $order = MaquilaProductionOrder::with('items.deliveries')->findOrFail($id);

        DB::beginTransaction();
        try {
            $payload = [
                'odm' => $order->numero_odm,
                'yield_global' => $order->porcentaje_avance_global,
                'user_id' => Auth::id(),
                'justificacion' => $validated['justificacion'],
                'timestamp' => now()->toIso8601String()
            ];

            $hashIntegridad = hash('sha256', json_encode($payload));

            // Liquidar orden directamente
            $order->update(['estado' => 'liquidada']);

            foreach ($order->items as $item) {
                $item->update(['liquidado' => true]);
            }

            AuditLog::create([
                'user_id' => Auth::id(),
                'action' => 'LIQUIDACION_CIERRE_MAQUILA',
                'model_type' => 'App\Models\MaquilaProductionOrder',
                'model_id' => $order->id,
                'reason' => "Cierre y Liquidación Final por usuario " . Auth::user()->name . ". Justificación: {$validated['justificacion']}. Yield Global: {$order->porcentaje_avance_global}%",
                'new_values' => json_encode(['estado' => 'liquidada', 'hash' => $hashIntegridad]),
                'ip_address' => $request->ip()
            ]);

            DB::commit();

            return redirect()->route('maquila.show', $order->id)
                ->with('success', "Orden de Maquila {$order->numero_odm} liquidada y cerrada técnicamente.");

        } catch (\Throwable $e) {
            DB::rollBack();
            return back()->with('error', 'Error durante la liquidación: ' . $e->getMessage());
        }
    }

    /**
     * API Fetch Autocompletado de ítems por código (Paso 2 Wizard)
     */
    public function apiGetItem($codigo)
    {
        $item = Item::where('codigo', $codigo)->first();
        if (!$item) {
            $product = Product::where('code', $codigo)->first();
            if ($product) {
                return response()->json([
                    'found' => true,
                    'codigo' => $product->code,
                    'descripcion' => $product->name,
                    'presentacion' => 'UNIDAD',
                    'unidad' => 'KG'
                ]);
            }

            return response()->json(['found' => false, 'message' => 'Código no encontrado en el maestro de ítems.']);
        }

        return response()->json([
            'found' => true,
            'codigo' => $item->codigo,
            'descripcion' => $item->descripcion,
            'presentacion' => $item->unidad_medida ?? 'Bolsa 25kg',
            'unidad' => in_array(strtoupper($item->unidad_medida ?? ''), ['UND', 'UNIDAD']) ? 'UND' : 'KG'
        ]);
    }
}
