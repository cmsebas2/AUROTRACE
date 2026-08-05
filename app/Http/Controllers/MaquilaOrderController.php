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
     * Display a listing of the resource (V3).
     */
    public function index(Request $request)
    {
        // 1. Obtener estadísticas globales V3
        $plantaEnMarcha = MaquilaOrder::count();
        $premezclaCount = MaquilaOrder::where('tipo_producto', 'PREMEZCLA')->count();
        $maquilaCount = MaquilaOrder::where('tipo_producto', 'MAQUILA')->count();
        
        // 2. Cargar órdenes con sus relaciones
        $query = MaquilaOrder::with(['items.product', 'items.catalogItem', 'creator'])->latest();
        
        // 3. Filtrado por tipo si viene en el request (V3)
        if ($request->filled('type') && in_array($request->type, ['PREMEZCLA', 'MAQUILA'])) {
            $query->where('tipo_producto', $request->type);
        }
        
        $orders = $query->paginate(15)->withQueryString();
        
        return view('maquila.index', compact('orders', 'plantaEnMarcha', 'premezclaCount', 'maquilaCount'));
    }

    /**
     * Show the form for creating a new resource (V3).
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
     * Endpoint API para buscar por referencia o código en el catálogo maestro (V3).
     */
    public function apiLookupReference(Request $request)
    {
        $ref = trim($request->query('reference', $request->query('ref', '')));

        if (empty($ref)) {
            return response()->json([
                'found' => false,
                'description' => '',
                'unidad_medida' => 'KG',
                'product_id' => null,
                'vigencia_meses' => null,
            ]);
        }

        $lowerRef = strtolower($ref);

        // 1. Buscar en el catálogo maestro de ítems (Item) por 'reference' o por 'item_code' (Case-Insensitive)
        $item = Item::whereRaw('LOWER(reference) = ?', [$lowerRef])
            ->orWhereRaw('LOWER(item_code) = ?', [$lowerRef])
            ->first();

        if (!$item) {
            // Buscar por coincidencia parcial en Item
            $item = Item::whereRaw('LOWER(reference) LIKE ?', ["%{$lowerRef}%"])
                ->orWhereRaw('LOWER(item_code) LIKE ?', ["%{$lowerRef}%"])
                ->orWhereRaw('LOWER(description) LIKE ?', ["%{$lowerRef}%"])
                ->first();
        }

        if ($item) {
            $uom = strtoupper($item->inventory_uom ?? 'KG');
            if (str_contains($uom, 'UND') || str_contains($uom, 'UNID') || str_contains($uom, 'PZA')) {
                $uom = 'UND';
            } else {
                $uom = 'KG';
            }

            // Intentar cruzar por descripción o referencia con algún producto registrado
            $lowerDesc = strtolower($item->description);
            $matchedProduct = Product::whereRaw('LOWER(name) LIKE ?', ["%{$lowerDesc}%"])
                ->orWhereRaw('LOWER(name) LIKE ?', ["%{$lowerRef}%"])
                ->first();

            return response()->json([
                'found' => true,
                'description' => $item->description,
                'unidad_medida' => $uom,
                'product_id' => $matchedProduct ? $matchedProduct->id : null,
                'vigencia_meses' => $matchedProduct ? $matchedProduct->vigencia_meses : null,
            ]);
        }

        // 2. Si no se encuentra en Item, buscar en Product por ID o nombre
        $product = Product::where('id', is_numeric($ref) ? $ref : 0)
            ->orWhereRaw('LOWER(name) LIKE ?', ["%{$lowerRef}%"])
            ->first();

        if ($product) {
            $uom = strtoupper($product->base_unit ?? 'KG');
            $uom = (str_contains($uom, 'UND') || str_contains($uom, 'UNID')) ? 'UND' : 'KG';

            return response()->json([
                'found' => true,
                'description' => $product->name,
                'unidad_medida' => $uom,
                'product_id' => $product->id,
                'vigencia_meses' => $product->vigencia_meses,
            ]);
        }

        return response()->json([
            'found' => false,
            'description' => 'Referencia no encontrada',
            'unidad_medida' => 'KG',
            'product_id' => null,
            'vigencia_meses' => null,
        ]);
    }

    /**
     * Store a newly created resource in storage (V3).
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
            'items.*.referencia' => 'required|string|max:255',
            'items.*.product_id' => 'nullable|exists:products,id',
            'items.*.lote_fisico' => 'required|string|max:255',
            'items.*.cantidad_programada' => 'required|numeric|min:0.01',
            'items.*.unidad_medida' => 'required|in:KG,UND',
            'items.*.fecha_fabricacion' => 'required|date',
            'items.*.fecha_vencimiento' => 'required|date|after_or_equal:items.*.fecha_fabricacion',
        ]);

        $order = DB::transaction(function () use ($validated, $request) {
            // 1. Crear Orden de Maquila V3
            $order = MaquilaOrder::create([
                'tipo_producto' => $validated['tipo_producto'],
                'odm' => strtoupper($validated['odm']),
                'sdm' => $request->filled('sdm') ? strtoupper($validated['sdm']) : null,
                'maquilador' => $validated['maquilador'],
                'fecha_creacion' => $validated['fecha_creacion'],
                'created_by' => Auth::id(),
            ]);

            // 2. Insertar Detalle de Ítems V3
            foreach ($validated['items'] as $itemData) {
                $productId = $itemData['product_id'] ?? null;
                if (empty($productId)) {
                    $matchedProduct = Product::where('name', 'LIKE', '%' . $itemData['referencia'] . '%')->first();
                    if ($matchedProduct) {
                        $productId = $matchedProduct->id;
                    }
                }

                MaquilaOrderItem::create([
                    'maquila_order_id' => $order->id,
                    'referencia' => strtoupper($itemData['referencia']),
                    'product_id' => $productId,
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
                'reason' => "Se creó la Orden de Maquila V3 ODM: {$order->odm} para el maquilador {$order->maquilador}",
            ]);

            return $order;
        });

        return redirect()->route('maquila.index')->with('success', "Orden de Maquila {$order->odm} guardada y auditada correctamente.");
    }

    /**
     * Remove the specified resource from storage (SoftDelete + AuditTrail).
     */
    public function destroy($id)
    {
        $order = MaquilaOrder::findOrFail($id);
        $odm = $order->odm;

        DB::transaction(function () use ($order, $odm) {
            $order->delete();

            AuditLog::create([
                'user_id' => Auth::id(),
                'action' => 'eliminar_orden_maquila',
                'model_type' => 'App\Models\MaquilaOrder',
                'model_id' => $order->id,
                'new_values' => json_encode(['odm' => $odm, 'status' => 'deleted']),
                'ip_address' => request()->ip(),
                'reason' => "Se eliminó la Orden de Maquila ODM: {$odm}",
            ]);
        });

        return redirect()->route('maquila.index')->with('success', "Orden de Maquila {$odm} eliminada correctamente.");
    }
}
