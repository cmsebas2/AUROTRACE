<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\MaquilaOrder;
use App\Models\MaquilaOrderItem;
use App\Models\Product;
use App\Models\Item;
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
        // 1. Obtener estadísticas globales V2
        $plantaEnMarcha = MaquilaOrder::count();
        $premezclaCount = MaquilaOrder::where('tipo_producto', 'PREMEZCLA')->count();
        $maquilaCount = MaquilaOrder::where('tipo_producto', 'MAQUILA')->count();
        
        // 2. Cargar órdenes con sus relaciones
        $query = MaquilaOrder::with(['items.product', 'items.catalogItem', 'creator'])->latest();
        
        // 3. Filtrado por tipo si viene en el request (V2)
        if ($request->filled('type') && in_array($request->type, ['PREMEZCLA', 'MAQUILA'])) {
            $query->where('tipo_producto', $request->type);
        }
        
        $orders = $query->paginate(15)->withQueryString();
        
        return view('maquila.index', compact('orders', 'plantaEnMarcha', 'premezclaCount', 'maquilaCount'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $products = Product::where('status', 'ACTIVO')->orderBy('name')->get();
        // Cargar todo el catálogo maestro de ítems para el autocompletado en el frontend
        $items = Item::orderBy('item_code')->get(['item_code', 'description', 'inventory_uom']);
        
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
        
        return view('maquila.create', compact('products', 'items', 'maquiladores'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'tipo_producto' => 'required|in:PREMEZCLA,MAQUILA',
            'odm' => 'required|string|unique:maquila_orders,odm|max:255',
            'sdm' => 'nullable|string|max:255',
            'maquilador' => 'required|string|max:255',
            'fecha_creacion' => 'required|date',
            'items' => 'required|array|min:1',
            'items.*.item_code' => 'required|string|max:255',
            'items.*.product_id' => 'nullable|exists:products,id',
            'items.*.lote_fisico' => 'required|string|max:255',
            'items.*.cantidad_programada' => 'required|numeric|min:0.01',
            'items.*.unidad_medida' => 'required|in:KG,UND',
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

            // 2. Insertar Detalle de Ítems V2
            foreach ($validated['items'] as $itemData) {
                // Intentar emparejar automáticamente con un producto del catálogo si existe coincidencia de nombre/código
                $productId = $itemData['product_id'];
                if (empty($productId)) {
                    // Buscar si hay algún producto que coincida con el código de ítem
                    $matchedProduct = Product::where('name', 'LIKE', '%' . $itemData['item_code'] . '%')->first();
                    if ($matchedProduct) {
                        $productId = $matchedProduct->id;
                    }
                }

                MaquilaOrderItem::create([
                    'maquila_order_id' => $order->id,
                    'item_code' => $itemData['item_code'],
                    'product_id' => $productId ?: null,
                    'lote_fisico' => strtoupper($itemData['lote_fisico']),
                    'cantidad_programada' => $itemData['cantidad_programada'],
                    'unidad_medida' => $itemData['unidad_medida'],
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
                'reason' => "Se creó la Orden de Maquila V2 ODM: {$order->odm} para el maquilador {$order->maquilador}",
            ]);

            return $order;
        });

        return redirect()->route('maquila.index')->with('success', "Orden de Maquila {$order->odm} guardada y auditada correctamente.");
    }
}
