<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\ReconditioningItem;
use App\Models\Item;
use Illuminate\Support\Facades\Storage;
use setasign\Fpdi\Fpdi;

class ReconditioningItemController extends Controller
{
    public function dashboard()
    {
        $allItems = ReconditioningItem::all();
        $activeItems = $allItems->where('is_released', false);
        
        // Volumen desglozado
        $totalKilos = $activeItems->where('uom', 'KIL')->sum('quantity');
        $totalUnits = $activeItems->where('uom', 'UND')->sum('quantity');

        $totalPallets = $activeItems->count();
        
        $risk1 = $activeItems->filter(fn($item) => $item->risk_level === 1)->count();
        $risk2 = $activeItems->filter(fn($item) => $item->risk_level === 2)->count();
        $risk3 = $activeItems->filter(fn($item) => $item->risk_level === 3)->count();

        // Calculate occupancy (assuming 100 pallets max for visual KPI)
        $occupancyPercentage = min(($totalPallets / 100) * 100, 100);

        // 1. Lead Time de Reacondicionamiento
        $releasedItems = $allItems->where('is_released', true);
        $totalLeadTimeDays = 0;
        foreach ($releasedItems as $rItem) {
            if ($rItem->released_at && $rItem->created_at) {
                $totalLeadTimeDays += \Carbon\Carbon::parse($rItem->created_at)->diffInDays(\Carbon\Carbon::parse($rItem->released_at));
            }
        }
        $avgLeadTime = $releasedItems->count() > 0 ? round($totalLeadTimeDays / $releasedItems->count(), 1) : 0;

        // 2. Tasa de Recuperación (%)
        $totalReleasedQty = $releasedItems->sum('quantity');
        $ptItems = $releasedItems->where('destination_warehouse', 'PT');
        $ptQty = $ptItems->sum('quantity');
        $ptQtyKilos = $ptItems->where('uom', 'KIL')->sum('quantity');
        $ptQtyUnits = $ptItems->where('uom', 'UND')->sum('quantity');
        $recoveryRate = $totalReleasedQty > 0 ? round(($ptQty / $totalReleasedQty) * 100, 1) : 0;

        // 3. Índice de Merma de Insumos
        $finishedItems = $allItems->whereIn('status', ['Terminado'])->merge($releasedItems);
        $totalReq = $finishedItems->sum('req_label') + $finishedItems->sum('req_box');
        $totalUsed = $finishedItems->sum('used_labels') + $finishedItems->sum('used_boxes');
        // Si no hay requerimientos, la merma es 0. Si hay, es la diferencia porcentual positiva.
        $wasteIndex = 0;
        if ($totalReq > 0 && $totalUsed > $totalReq) {
            $wasteIndex = round((($totalUsed - $totalReq) / $totalReq) * 100, 1);
        }

        // 4. Countdown de Vencimiento Próximo
        $activeItemsExp = $activeItems->whereNotNull('expiration_date');
        $closestExpirationDays = null;
        if ($activeItemsExp->count() > 0) {
            $closestDate = $activeItemsExp->min('expiration_date');
            $closestExpirationDays = now()->diffInDays(\Carbon\Carbon::parse($closestDate), false); // Puede ser negativo si ya venció
        }

        return view('reconditioning.dashboard', compact(
            'totalKilos', 'totalUnits', 'totalPallets', 'risk1', 'risk2', 'risk3', 'occupancyPercentage',
            'avgLeadTime', 'recoveryRate', 'ptQtyKilos', 'ptQtyUnits', 'wasteIndex', 'closestExpirationDays'
        ));
    }

    public function create()
    {
        return view('reconditioning.entrada');
    }

    public function store(Request $request)
    {
        $request->validate([
            'transfer_number' => 'required|string',
            'transfer_pdf' => 'nullable|file|mimes:pdf|max:5120',
            'items' => 'required|array|min:1',
            'items.*.item_code' => 'required|string',
            'items.*.lot_number' => 'required|string',
            'items.*.quantity' => 'required|numeric|min:0.01',
            'items.*.uom' => 'required|in:KIL,UND',
            'items.*.expiration_date' => 'required|date',
        ]);

        $pdfPath = null;
        if ($request->hasFile('transfer_pdf')) {
            $pdfPath = $request->file('transfer_pdf')->store('transfers', 'public');
        }

        foreach ($request->items as $row) {
            $item = Item::where('item_code', $row['item_code'])->first();
            $itemId = $item ? $item->id : null;

            // Nueva Validación: transfer_number + item_id + lot_number
            $exists = ReconditioningItem::where('transfer_number', $request->transfer_number)
                ->where('item_id', $itemId)
                ->where('lot_number', $row['lot_number'])
                ->exists();

            if ($exists) {
                return back()->withInput()->with('error', "El ítem {$row['item_code']} con lote {$row['lot_number']} ya fue ingresado para el traslado {$request->transfer_number}.");
            }

            ReconditioningItem::create([
                'item_id' => $itemId,
                'item_code' => $row['item_code'],
                'manufacturer' => $row['manufacturer'] ?? 'AUROFARMA S.A.S.',
                'is_external' => isset($row['is_external']) && ($row['is_external'] == '1' || $row['is_external'] == true),
                'lot_number' => $row['lot_number'],
                'expiration_date' => $row['expiration_date'],
                'quantity' => $row['quantity'],
                'uom' => $row['uom'],
                'transfer_number' => $request->transfer_number,
                'transfer_pdf_path' => $pdfPath,
                'location' => $row['location'] ?? null,
                'req_label' => $row['req_label'] ?? 0,
                'req_box' => $row['req_box'] ?? 0,
                'req_others' => $row['req_others'] ?? null,
                'observations' => $row['observations'] ?? null,
                'status' => 'Pendiente',
                'created_by_id' => auth()->id()
            ]);
        }

        return redirect()->route('reconditioning.inventory')->with('success', 'Ingreso multiproducto registrado correctamente en el sistema de Aurofarma S.A.S.');
    }

    public function checkTransferUniqueness(Request $request)
    {
        // Actualizado para validar la tríada
        $exists = ReconditioningItem::where('transfer_number', $request->transfer_number)
            ->where('item_code', $request->item_code)
            ->where('lot_number', $request->lot_number)
            ->exists();
            
        return response()->json(['exists' => $exists]);
    }

    public function inventory(Request $request)
    {
        $query = ReconditioningItem::with('item')->where('is_released', false);

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('item_code', 'like', "%{$search}%")
                  ->orWhere('lot_number', 'like', "%{$search}%")
                  ->orWhere('transfer_number', 'like', "%{$search}%");
            });
        }

        $items = $query->get();

        if ($request->filled('risk')) {
            $items = $items->filter(fn($item) => $item->risk_level == $request->risk);
        }

        $compactGroups = $items->groupBy(function($item) {
            return $item->item_code . '|' . $item->lot_number;
        })->map(function($group) {
            return [
                'item_code' => $group->first()->item_code,
                'description' => $group->first()->item ? $group->first()->item->description : 'N/A',
                'lot_number' => $group->first()->lot_number,
                'total_quantity' => $group->sum('quantity'),
                'uom' => $group->first()->uom,
                'records' => $group->count()
            ];
        });

        return view('reconditioning.inventory', compact('items', 'compactGroups'));
    }

    public function history(Request $request)
    {
        $query = ReconditioningItem::with('item')->where('is_released', true);

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('item_code', 'like', "%{$search}%")
                  ->orWhere('lot_number', 'like', "%{$search}%")
                  ->orWhere('transfer_number', 'like', "%{$search}%")
                  ->orWhere('exit_transfer_number', 'like', "%{$search}%");
            });
        }

        $items = $query->orderBy('released_at', 'desc')->get();

        if ($request->filled('risk')) {
            $items = $items->filter(fn($item) => $item->risk_level == $request->risk);
        }

        return view('reconditioning.history', compact('items'));
    }

    public function uploadTransferPdf(Request $request, $id)
    {
        $item = ReconditioningItem::findOrFail($id);
        
        $request->validate([
            'transfer_pdf' => 'required|file|mimes:pdf|max:5120',
            'apply_to_all' => 'nullable'
        ]);

        $pdfPath = $request->file('transfer_pdf')->store('transfers', 'public');
        
        if ($request->has('apply_to_all')) {
            ReconditioningItem::where('transfer_number', $item->transfer_number)
                ->update(['transfer_pdf_path' => $pdfPath]);
            $message = "Soporte actualizado para todos los ítems del traslado {$item->transfer_number}.";
        } else {
            $item->update(['transfer_pdf_path' => $pdfPath]);
            $message = 'Soporte documental actualizado correctamente.';
        }

        return back()->with('success', $message);
    }

    public function planner(Request $request)
    {
        $query = ReconditioningItem::with('item')
            ->where('is_released', false)
            ->whereIn('status', ['Pendiente', 'En Proceso']);

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('item_code', 'like', "%{$search}%")
                  ->orWhere('lot_number', 'like', "%{$search}%")
                  ->orWhere('transfer_number', 'like', "%{$search}%");
            });
        }

        $items = $query->get()->sortBy('risk_level');

        if ($request->filled('risk')) {
            $items = $items->filter(fn($item) => $item->risk_level == $request->risk);
        }
            
        return view('reconditioning.planner', compact('items'));
    }

    public function update(Request $request, $id)
    {
        $item = ReconditioningItem::findOrFail($id);
        
        if ($item->is_released) {
            return back()->with('error', 'No se puede editar un registro que ya ha sido liberado.');
        }

        $request->validate([
            'manufacturer' => 'nullable|string',
            'lot_number' => 'required|string',
            'location' => 'nullable|string',
            'req_label' => 'nullable|integer|min:0',
            'req_box' => 'nullable|integer|min:0',
            'req_others' => 'nullable|string',
        ]);

        // Explicitly updating ONLY allowed fields, ensuring created_at is never modified.
        $item->update([
            'manufacturer' => $request->manufacturer,
            'lot_number' => $request->lot_number,
            'location' => $request->location,
            'req_label' => $request->req_label,
            'req_box' => $request->req_box,
            'req_others' => $request->req_others,
        ]);

        return back()->with('success', 'Registro actualizado correctamente.');
    }

    public function complete(Request $request, $id)
    {
        $item = ReconditioningItem::findOrFail($id);
        
        $request->validate([
            'used_labels' => 'nullable|integer|min:0',
            'used_boxes' => 'nullable|integer|min:0',
        ]);

        $item->update([
            'status' => 'Terminado',
            'used_labels' => $request->used_labels,
            'used_boxes' => $request->used_boxes,
        ]);

        return back()->with('success', 'Proceso cerrado correctamente.');
    }

    public function release(Request $request, $id)
    {
        $item = ReconditioningItem::findOrFail($id);
        
        if ($item->status !== 'Terminado' || $item->is_released) {
            return back()->with('error', 'El ítem no está disponible para liberación.');
        }

        $request->validate([
            'quantity_to_release' => 'required|numeric|min:0.01|max:' . $item->quantity,
            'destination_warehouse' => 'required|in:PT,RZ',
            'exit_transfer_number' => 'required|string',
            'exit_transfer_pdf' => 'nullable|file|mimes:pdf|max:5120',
            'rejection_reason' => 'required_if:destination_warehouse,RZ',
            'rejection_photo' => 'required_if:destination_warehouse,RZ|image|mimes:jpeg,png,jpg|max:5120',
            'activity_performed' => 'nullable|string',
        ]);

        $photoPath = null;
        if ($request->hasFile('rejection_photo')) {
            $photoPath = $request->file('rejection_photo')->store('rejections', 'public');
        }

        $exitPdfPath = null;
        if ($request->hasFile('exit_transfer_pdf')) {
            $exitPdfPath = $request->file('exit_transfer_pdf')->store('exit_transfers', 'public');
        }

        $quantityToRelease = $request->quantity_to_release;
        $isPartial = $quantityToRelease < $item->quantity;
        $releasedById = $request->signature_user_id ?: auth()->id();

        if ($isPartial) {
            // Caso Salida Parcial: Dividir el registro
            $releasedItem = $item->replicate();
            $releasedItem->quantity = $quantityToRelease;
            $releasedItem->is_released = true;
            $releasedItem->released_at = now();
            $releasedItem->destination_warehouse = $request->destination_warehouse;
            $releasedItem->rejection_reason = $request->destination_warehouse === 'RZ' ? $request->rejection_reason : null;
            $releasedItem->rejection_photo_path = $photoPath;
            $releasedItem->exit_transfer_number = $request->exit_transfer_number;
            $releasedItem->exit_transfer_pdf_path = $exitPdfPath;
            $releasedItem->observations = ($item->observations ? $item->observations . "\n" : "") . "Salida Parcial. Actividad: " . $request->activity_performed;
            $releasedItem->released_by_id = $releasedById;
            $releasedItem->save();

            // Actualizar el original restando la cantidad
            $item->decrement('quantity', $quantityToRelease);
            
            // Para el PDF y el balance usamos el nuevo registro clonado
            $targetItem = $releasedItem;
        } else {
            // Caso Salida Total: Proceso estándar
            $item->update([
                'is_released' => true,
                'released_at' => now(),
                'destination_warehouse' => $request->destination_warehouse,
                'rejection_reason' => $request->destination_warehouse === 'RZ' ? $request->rejection_reason : null,
                'rejection_photo_path' => $photoPath,
                'exit_transfer_number' => $request->exit_transfer_number,
                'exit_transfer_pdf_path' => $exitPdfPath,
                'observations' => ($item->observations ? $item->observations . "\n" : "") . "Actividad: " . $request->activity_performed,
                'released_by_id' => $releasedById,
            ]);
            $targetItem = $item;
        }

        // Lógica de Saldos (Inventory Balances)
        $balance = \App\Models\InventoryBalance::firstOrCreate(
            [
                'item_id' => $targetItem->item_id,
                'lot_number' => $targetItem->lot_number,
                'warehouse' => $request->destination_warehouse,
            ],
            [
                'item_code' => $targetItem->item_code,
                'uom' => $targetItem->uom,
                'quantity' => 0
            ]
        );
        $balance->quantity += $quantityToRelease;
        $balance->save();

        // Construcción del Mega-Documento (Expediente Unificado)
        // Pasamos el item liberado (targetItem) al PDF
        $item = $targetItem;
        $actaPdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('reconditioning.pdf.acta_salida', compact('item'))->output();
        $tempActaPath = tempnam(sys_get_temp_dir(), 'acta');
        file_put_contents($tempActaPath, $actaPdf);

        $fpdi = new Fpdi();

        try {
            // 1. Añadir Acta Generada
            $pageCount = $fpdi->setSourceFile($tempActaPath);
            for ($pageNo = 1; $pageNo <= $pageCount; $pageNo++) {
                $templateId = $fpdi->importPage($pageNo);
                $size = $fpdi->getTemplateSize($templateId);
                $fpdi->AddPage($size['orientation'], [$size['width'], $size['height']]);
                $fpdi->useTemplate($templateId);
            }

            // 2. Añadir PDF de Entrada (Siesa)
            if ($item->transfer_pdf_path && Storage::disk('public')->exists($item->transfer_pdf_path)) {
                $tempInPath = Storage::disk('public')->path($item->transfer_pdf_path);
                $pageCount = $fpdi->setSourceFile($tempInPath);
                for ($pageNo = 1; $pageNo <= $pageCount; $pageNo++) {
                    $templateId = $fpdi->importPage($pageNo);
                    $size = $fpdi->getTemplateSize($templateId);
                    $fpdi->AddPage($size['orientation'], [$size['width'], $size['height']]);
                    $fpdi->useTemplate($templateId);
                }
            }

            // 3. Añadir PDF de Salida (Siesa)
            if ($exitPdfPath && Storage::disk('public')->exists($exitPdfPath)) {
                $tempOutPath = Storage::disk('public')->path($exitPdfPath);
                $pageCount = $fpdi->setSourceFile($tempOutPath);
                for ($pageNo = 1; $pageNo <= $pageCount; $pageNo++) {
                    $templateId = $fpdi->importPage($pageNo);
                    $size = $fpdi->getTemplateSize($templateId);
                    $fpdi->AddPage($size['orientation'], [$size['width'], $size['height']]);
                    $fpdi->useTemplate($templateId);
                }
            }

            // Generar archivo final en memoria
            $mergedPdfContent = $fpdi->Output('S');
        } catch (\Exception $e) {
            // Fallback: Si FPDI falla (ej. versión de PDF no soportada), guardamos solo el Acta.
            \Log::error("Error al fusionar PDFs con FPDI: " . $e->getMessage());
            $mergedPdfContent = $actaPdf;
        } finally {
            @unlink($tempActaPath);
        }
        
        // Guardar Expediente
        $fileName = 'EXPEDIENTE_' . $item->destination_warehouse . '_' . time() . '.pdf';
        $pdfPath = 'reconditioning_releases/' . $fileName;
        Storage::disk('public')->put($pdfPath, $mergedPdfContent);

        $item->update(['release_pdf_path' => $pdfPath]);

        return back()->with('success', 'Salida generada exitosamente. Expediente consolidado (Mega-Documento) y saldos actualizados.');
    }

    public function destroy($id)
    {
        $item = ReconditioningItem::findOrFail($id);

        // Opcional futuro: Restringir por permiso
        // if (!auth()->user()->can('super-admin')) { abort(403); }

        // 1. Revertir saldo de inventario si ya fue liberado
        if ($item->is_released && $item->destination_warehouse) {
            $balance = \App\Models\InventoryBalance::where('item_id', $item->item_id)
                ->where('lot_number', $item->lot_number)
                ->where('warehouse', $item->destination_warehouse)
                ->first();
                
            if ($balance) {
                $balance->quantity -= $item->quantity;
                if ($balance->quantity < 0) {
                    $balance->quantity = 0;
                }
                $balance->save();
            }
        }

        // 2. Eliminar archivos PDF físicos
        if ($item->transfer_pdf_path) {
            Storage::disk('public')->delete($item->transfer_pdf_path);
        }
        if ($item->exit_transfer_pdf_path) {
            Storage::disk('public')->delete($item->exit_transfer_pdf_path);
        }
        if ($item->release_pdf_path) {
            Storage::disk('public')->delete($item->release_pdf_path);
        }
        if ($item->rejection_photo_path) {
            Storage::disk('public')->delete($item->rejection_photo_path);
        }

        // 3. Eliminar registro
        $item->delete();

        return back()->with('success', 'Registro y evidencias eliminadas permanentemente. Saldos revertidos.');
    }
}
