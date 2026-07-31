<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FormulaIngredient extends Model
{
    protected $fillable = [
        'product_id', 'presentation_id', 'material_code', 'material_name', 'material_type', 'specific_material_type', 'function', 'unit', 'percentage', 'quantity'
    ];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function presentation()
    {
        return $this->belongsTo(ProductPresentation::class, 'presentation_id');
    }

    public function item()
    {
        return $this->belongsTo(Item::class, 'material_code', 'item_code');
    }
}
