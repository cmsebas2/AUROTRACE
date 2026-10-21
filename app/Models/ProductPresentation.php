<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * ============================================================================
 * PRESENTACIÓN COMERCIAL DE PRODUCTO - ProductPresentation
 * ============================================================================
 */
class ProductPresentation extends Model
{
    protected $fillable = [
        'product_id',
        'presentation_code',
        'name',
        'quantity_value',
        'unit_measure',
        'container_type',
        'theoretical_weight',
    ];

    protected $casts = [
        'theoretical_weight' => 'decimal:2',
        'quantity_value'     => 'decimal:2',
    ];

    /** Producto maestro al que pertenece esta presentación. */
    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    /** Materiales de envase y empaque requeridos por unidad de esta presentación. */
    public function materials()
    {
        return $this->hasMany(FormulaIngredient::class, 'presentation_id');
    }

    /** Alias de compatibilidad para `materials()`. */
    public function packaging_materials()
    {
        return $this->materials();
    }
}
