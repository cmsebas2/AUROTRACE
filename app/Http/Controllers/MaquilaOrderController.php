<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\MaquilaOrder;
use App\Models\MaquilaOrderItem;
use App\Models\Product;
use App\Models\AuditLog;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class MaquilaOrderController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        // 1. Obtener estadísticas globales
        $plantaEnMarcha = MaquilaOrder::count();
        $premezclaCount = MaquilaOrder::where('tipo_producto', 'PREMEZCLA')->count();
        $productoTerminadoCount = MaquilaOrder::where('tipo_producto', 'PRODUCTO_TERMINADO')->count();
        
        // 2. Cargar órdenes con sus relaciones
        $query = MaquilaOrder::with(['items.product', 'creator'])->latest();
        
        // 3. Filtrado por tipo si viene en el request
        if ($request->filled('type') && in_array($request->type, ['PREMEZCLA', 'PRODUCTO_TERMINADO'])) {
            $query->where('tipo_producto', $request->type);
        }
        
        $orders = $query->paginate(15)->withQueryString();
        
        return view('maquila.index', compact('orders', 'plantaEnMarcha', 'premezclaCount', 'productoTerminadoCount'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $products = Product::where('status', 'ACTIVO')->orderBy('name')->get();
        $maquiladores = [
            'QOPPA PHARMA',
            'INSEC',
            'RATAR',
            'DECNO',
            'ITALCOL S.A. FUNZA',
            'ITALCOL S.A. CARTAGENA',
            'PROQUIVET',
            'PRONATUCOL',
            'GDI',
            'LIXMAR',
            'ARJONA',
            'FARMANDINA',
            'FARMATEC',
            'SFC'
        ];
        
        return view('maquila.create', compact('products', 'maquiladores'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'tipo_producto' => 'required|in:PREMEZCLA,PRODUCTO_TERMINADO',
            'odm' => 'required|string|unique:maquila_orders,odm|max:255',
            'sdm' => 'nullable|string|max:255',
            'maquilador' => 'required|string|max:255',
            'fecha_creacion' => 'required|date',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.cantidad' => 'required|numeric|min:0.01',
            'items.*.lote' => 'required|string|max:255',
            'items.*.cantidad_programada' => 'required|numeric|min:0.01',
            'items.*.fecha_fabricacion' => 'required|date',
            'items.*.fecha_vencimiento' => 'required|date|after_or_equal:items.*.fecha_fabricacion',
        ]);

        $order = DB::transaction(function () use ($validated, $request) {
            // 1. Crear Orden de Maquila
            $order = MaquilaOrder::create([
                'tipo_producto' => $validated['tipo_producto'],
                'odm' => strtoupper($validated['odm']),
                'sdm' => $request->filled('sdm') ? strtoupper($validated['sdm']) : null,
                'maquilador' => $validated['maquilador'],
                'fecha_creacion' => $validated['fecha_creacion'],
                'created_by' => Auth::id(),
            ]);

            // 2. Insertar Detalle de Ítems
            foreach ($validated['items'] as $itemData) {
                MaquilaOrderItem::create([
                    'maquila_order_id' => $order->id,
                    'product_id' => $itemData['product_id'],
                    'cantidad' => $itemData['cantidad'],
                    'lote' => strtoupper($itemData['lote']),
                    'cantidad_programada' => $itemData['cantidad_programada'],
                    'fecha_fabricacion' => $itemData['fecha_fabricacion'],
                    'fecha_vencimiento' => $itemData['fecha_vencimiento'],
                ]);
            }

            // 3. Crear Registro en el Audit Trail (CFR 21 Compliant)
            AuditLog::create([
                'user_id' => Auth::id(),
                'action' => 'crear_orden_maquila',
                'model_type' => 'App\Models\MaquilaOrder',
                'model_id' => $order->id,
                'new_values' => json_encode($order->load('items')->toArray()),
                'ip_address' => $request->ip(),
                'reason' => "Se creó la Orden de Maquila ODM: {$order->odm} para el maquilador {$order->maquilador}",
            ]);

            return $order;
        });

        return redirect()->route('maquila.index')->with('success', "Orden de Maquila {$order->odm} guardada y auditada correctamente.");
    }
}
