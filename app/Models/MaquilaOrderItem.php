<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class MaquilaOrderItem extends Model
{
    protected $fillable = [
        'maquila_order_id',
        'sdm',
        'referencia',
        'product_id',
        'lote_fisico',
        'cantidad_programada',
        'unidad_medida',
        'fecha_fabricacion',
        'fecha_vencimiento',
    ];

    protected $casts = [
        'fecha_fabricacion' => 'date',
        'fecha_vencimiento' => 'date',
    ];

    /**
     * Get the maquila order that owns this item.
     */
    public function order()
    {
        return $this->belongsTo(MaquilaOrder::class, 'maquila_order_id');
    }

    /**
     * Get the product details (optional relation).
     */
    public function product()
    {
        return $this->belongsTo(Product::class, 'product_id');
    }

    /**
     * Get the item catalog reference.
     */
    public function catalogItem()
    {
        return $this->belongsTo(Item::class, 'referencia', 'reference');
    }

    /**
     * Get description dynamically from items or products catalog.
     */
    public function getDescription()
    {
        if ($this->catalogItem) {
            return $this->catalogItem->description;
        }

        $lowerRef = strtolower($this->referencia);
        $itemByCode = Item::whereRaw('LOWER(item_code) = ?', [$lowerRef])
            ->orWhereRaw('LOWER(reference) = ?', [$lowerRef])
            ->first();

        if ($itemByCode) {
            return $itemByCode->description;
        }

        if ($this->product) {
            return $this->product->name;
        }

        $matchedProduct = Product::whereRaw('LOWER(name) LIKE ?', ["%{$lowerRef}%"])->first();
        if ($matchedProduct) {
            return $matchedProduct->name;
        }

        return 'Ítem sin descripción';
    }

    /**
     * Check if the item is near its expiration date (less than 3 months).
     */
    public function isNearExpiry()
    {
        if (!$this->fecha_vencimiento) {
            return false;
        }

        $vencimiento = Carbon::parse($this->fecha_vencimiento);
        $hoy = Carbon::now();

        // Si ya está vencido o vence en menos de 90 días (3 meses)
        return $vencimiento->isPast() || $hoy->diffInDays($vencimiento, false) < 90;
    }
}
