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
     * Endpoint API para buscar por referencia o código en la tabla items de Supabase (V3).
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

        $upperRef = strtoupper($ref);
        $lowerRef = strtolower($ref);
        $paddedRef = str_pad($ref, 7, '0', STR_PAD_LEFT);
        $unpaddedRef = ltrim($ref, '0');

        // 1. Intentar consulta directa a Supabase DB en la tabla 'items'
        try {
            $item = DB::table('items')
                ->whereRaw('UPPER(TRIM(item_code)) = ?', [$upperRef])
                ->orWhereRaw('UPPER(TRIM(reference)) = ?', [$upperRef])
                ->orWhereRaw('UPPER(TRIM(item_code)) = ?', [strtoupper($paddedRef)])
                ->orWhereRaw('UPPER(TRIM(reference)) = ?', [strtoupper($unpaddedRef)])
                ->orWhereRaw('UPPER(TRIM(item_code)) = ?', [strtoupper($unpaddedRef)])
                ->first();

            if (!$item) {
                // Coincidencia parcial por LIKE
                $item = DB::table('items')
                    ->whereRaw('UPPER(item_code) LIKE ?', ["%{$upperRef}%"])
                    ->orWhereRaw('UPPER(reference) LIKE ?', ["%{$upperRef}%"])
                    ->orWhereRaw('UPPER(description) LIKE ?', ["%{$upperRef}%"])
                    ->first();
            }

            if ($item && !empty($item->description)) {
                $uom = strtoupper($item->inventory_uom ?? 'KG');
                $uom = (str_contains($uom, 'UND') || str_contains($uom, 'UNID') || str_contains($uom, 'PZA') || str_contains($uom, 'SOB')) ? 'UND' : 'KG';

                $matchedProduct = DB::table('products')
                    ->whereRaw('UPPER(name) LIKE ?', ["%" . strtoupper($item->description) . "%"])
                    ->orWhereRaw('UPPER(name) LIKE ?', ["%{$upperRef}%"])
                    ->first();

                return response()->json([
                    'found' => true,
                    'description' => $item->description,
                    'unidad_medida' => $uom,
                    'product_id' => $matchedProduct ? $matchedProduct->id : null,
                    'vigencia_meses' => $matchedProduct ? $matchedProduct->vigencia_meses : null,
                    'source' => 'supabase_items'
                ]);
            }
        } catch (\Throwable $e) {
            // Silenciar error de consulta DB
        }

        // 2. Diccionario Estático de Respaldo en servidor PHP
        $staticCatalog = [
            'A11119' => ['description' => 'CABATEL NF X 20ML', 'uom' => 'UND'],
            'a11119' => ['description' => 'CABATEL NF X 20ML', 'uom' => 'UND'],
            '0001309' => ['description' => 'CABATEL NF X 20ML', 'uom' => 'UND'],
            '1309' => ['description' => 'CABATEL NF X 20ML', 'uom' => 'UND'],
            '106' => ['description' => 'MOGOLLA DE TRIGO', 'uom' => 'KG'],
            '0000755' => ['description' => 'MOGOLLA DE TRIGO', 'uom' => 'KG'],
            '113' => ['description' => 'HARINA DE TRIGO DE 3a', 'uom' => 'KG'],
            '0000605' => ['description' => 'HARINA DE TRIGO DE 3a', 'uom' => 'KG'],
            '1346' => ['description' => 'VIT B3 NIACINAMIDE 98%', 'uom' => 'KG'],
            '1356' => ['description' => 'VIT B6 PIRIDOXINA HCL 99%', 'uom' => 'KG'],
            '1362' => ['description' => 'VIT B9 ACIDO FOLICO 80%', 'uom' => 'KG'],
            '1368' => ['description' => 'VIT B12 CIANOCOBALA 1%', 'uom' => 'KG'],
            '1380' => ['description' => 'VIT H BIOTIN 98%', 'uom' => 'KG'],
            '1391' => ['description' => 'INOSITOL', 'uom' => 'KG'],
            '1399' => ['description' => 'ZINC SULPHATE 1H2O Zn35%', 'uom' => 'KG'],
            '1403' => ['description' => 'SULFATO DE MAGNESIO 7H2O Mg 9.86%', 'uom' => 'KG'],
            '1408' => ['description' => 'SULFATO DE COBRE 5H20 Cu25%', 'uom' => 'KG'],
            '1415' => ['description' => 'SULFATO FERROSO HEPTAHIDRATADO Fe19.5%', 'uom' => 'KG'],
            '1430' => ['description' => 'PROPIONATO DE CROMO INOVEL Cr0.4%', 'uom' => 'KG'],
            '1434' => ['description' => 'CLORURO DE CALCIO CaCl2 94%', 'uom' => 'KG'],
            '1437' => ['description' => 'AZUFRE S99.95%', 'uom' => 'KG'],
            '1444' => ['description' => 'BENTONITA AURO ANTICOMPAC', 'uom' => 'KG'],
            '1464' => ['description' => 'ENMASCARANTE (MALTODEXTRINA) AURO', 'uom' => 'KG'],
            '1513' => ['description' => 'WISDEM GOLDEN-Y40 XANTOFILAS 4%', 'uom' => 'KG'],
            '152' => ['description' => 'HARINA DE YUCA', 'uom' => 'KG'],
            '154' => ['description' => 'ALMIDON DE YUCA', 'uom' => 'KG'],
            '1560' => ['description' => 'LEVUCELL SB10 SPIN PROB', 'uom' => 'KG'],
            '1589' => ['description' => 'PRO-HEALTH AURO PROB', 'uom' => 'KG'],
            '1625' => ['description' => 'LECITINA DE SOYA POLVO EMULS', 'uom' => 'KG'],
            '1647' => ['description' => 'CYNARA SCOLYMUS PROT HEPATICO', 'uom' => 'KG'],
            '1648' => ['description' => 'SILYBUM SILYMARIN 80% PROT HEPATICO', 'uom' => 'KG'],
            '1657' => ['description' => 'HEPAXIN AURO PROT HEPATICO', 'uom' => 'KG'],
            '1666' => ['description' => 'BIOPOWDER YUCCA SCHIDIGERA VAR PRO', 'uom' => 'KG'],
            '1674' => ['description' => 'YUCASHID POLVO YUCCASCHIDI 60% AURO', 'uom' => 'KG'],
            '1682' => ['description' => 'TM-700 PHIBRO OXITETRACICLINA77.8%', 'uom' => 'KG'],
            '1684' => ['description' => 'ECOMAX AURO CLORTETRACI20%', 'uom' => 'KG'],
            '1689' => ['description' => 'Q-MUTIN AURO TIAMULINA 10%', 'uom' => 'KG'],
            '1692' => ['description' => 'TIAMULINA FUMARATO HIDROGENADO98%', 'uom' => 'KG'],
            '1701' => ['description' => 'TILMICOSINA75%', 'uom' => 'KG'],
            '1714' => ['description' => 'Q-SULFATYL T AURO TILOSIN F10%', 'uom' => 'KG'],
            '1716' => ['description' => 'SULFAMETAZINA98%', 'uom' => 'KG'],
            '1720' => ['description' => 'TILOSINA FOSFATO90%', 'uom' => 'KG'],
            '1722' => ['description' => 'AUROQUINOL 60% AUROFA HALQUINOL 60%', 'uom' => 'KG'],
            '1728' => ['description' => 'Q-FLORFEN AURO FLORFENICOL20%', 'uom' => 'KG'],
            '1730' => ['description' => 'FLORFENICOL98%', 'uom' => 'KG'],
            '1741' => ['description' => 'CIPROFARM AURO CIPROFLOXA20%', 'uom' => 'KG'],
            '1744' => ['description' => 'Q-MICOSPECTIN AURO LINC4.4%ESPEC4.4%', 'uom' => 'KG'],
            '1745' => ['description' => 'LINCOMICINA HCL98%', 'uom' => 'KG'],
            '1750' => ['description' => 'AMOXAVET 50 GVM AMOXICILINA50%', 'uom' => 'KG'],
            '1751' => ['description' => 'ESPECTINOMICINA SULFATO', 'uom' => 'KG'],
            '1752' => ['description' => 'CIPROFLOXACINA HCL AURO CIPROFL98%', 'uom' => 'KG'],
        ];

        $staticMatch = $staticCatalog[$upperRef] ?? $staticCatalog[$ref] ?? $staticCatalog[$unpaddedRef] ?? null;
        if ($staticMatch) {
            return response()->json([
                'found' => true,
                'description' => $staticMatch['description'],
                'unidad_medida' => $staticMatch['uom'],
                'product_id' => null,
                'vigencia_meses' => null,
                'source' => 'static_catalog'
            ]);
        }

        // 3. Buscar en la tabla 'products' por ID o nombre
        try {
            $product = DB::table('products')
                ->where('id', is_numeric($ref) ? $ref : 0)
                ->orWhereRaw('UPPER(name) LIKE ?', ["%{$upperRef}%"])
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
                    'source' => 'products_table'
                ]);
            }
        } catch (\Throwable $e) {
            // Silenciar error
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
            'producto' => 'required|string|max:255',
            'odm' => 'required|string|unique:maquila_orders,odm|max:255',
            'maquilador' => 'required|string|max:255',
            'fecha_creacion' => 'required|date',
            'items' => 'required|array|min:1',
            'items.*.sdm' => 'nullable|string|max:255',
            'items.*.referencia' => 'required|string|max:255',
            'items.*.product_id' => 'nullable|exists:products,id',
            'items.*.lote_fisico' => 'required|string|max:255',
            'items.*.cantidad_programada' => 'required|numeric|min:0.01',
            'items.*.unidad_medida' => 'required|in:KG,UND',
            'items.*.fecha_fabricacion' => 'required|date',
            'items.*.fecha_vencimiento' => 'required|string',
        ]);

        $order = DB::transaction(function () use ($validated, $request) {
            // 1. Crear Orden de Maquila V3
            $order = MaquilaOrder::create([
                'tipo_producto' => $validated['tipo_producto'],
                'producto' => strtoupper($validated['producto']),
                'odm' => strtoupper($validated['odm']),
                'maquilador' => $validated['maquilador'],
                'fecha_creacion' => $validated['fecha_creacion'],
                'created_by' => Auth::id(),
            ]);

            // 2. Insertar Detalle por Presentación y Trazabilidad V3
            foreach ($validated['items'] as $itemData) {
                $productId = $itemData['product_id'] ?? null;
                if (empty($productId)) {
                    $matchedProduct = Product::where('name', 'LIKE', '%' . $itemData['referencia'] . '%')->first();
                    if ($matchedProduct) {
                        $productId = $matchedProduct->id;
                    }
                }

                // Dar formato YYYY-MM-01 a fecha_vencimiento si viene en formato MM-YYYY o YYYY-MM
                $venc = trim($itemData['fecha_vencimiento']);
                if (preg_match('/^(\d{2})[- \/](\d{4})$/', $venc, $matches)) {
                    $venc = $matches[2] . '-' . $matches[1] . '-01';
                } elseif (preg_match('/^(\d{4})[- \/](\d{2})$/', $venc, $matches)) {
                    $venc = $matches[1] . '-' . $matches[2] . '-01';
                }

                MaquilaOrderItem::create([
                    'maquila_order_id' => $order->id,
                    'sdm' => !empty($itemData['sdm']) ? strtoupper($itemData['sdm']) : null,
                    'referencia' => strtoupper($itemData['referencia']),
                    'product_id' => $productId,
                    'lote_fisico' => strtoupper($itemData['lote_fisico']),
                    'cantidad_programada' => $itemData['cantidad_programada'],
                    'unidad_medida' => $itemData['unidad_medida'],
                    'fecha_fabricacion' => $itemData['fecha_fabricacion'],
                    'fecha_vencimiento' => $venc,
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
                'reason' => "Se creó la Orden de Maquila V3 ODM: {$order->odm} para el producto {$order->producto}",
            ]);

            return $order;
        });

        return redirect()->route('maquila.index')->with('success', "Orden de Maquila {$order->odm} guardada correctamente.");
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
