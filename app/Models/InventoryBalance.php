<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InventoryBalance extends Model
{
    protected $fillable = [
        'item_id',
        'item_code',
        'lot_number',
        'warehouse',
        'quantity',
        'uom',
    ];

    public function item()
    {
        return $this->belongsTo(Item::class, 'item_id');
    }
}
