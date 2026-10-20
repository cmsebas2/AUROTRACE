<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * ============================================================================
 * PRESENTACIÓN COMERCIAL DE PRODUCTO - ProductPresentation
 * ============================================================================
 * GUÍA RÁPIDA ("FOR DUMMIES"):
 * Un mismo producto (granel) puede envasarse en distintas presentaciones
 * comerciales (ej: Frasco x 50 mL, Frasco x 250 mL, Jeringa x 10 g).
 * Cada presentación tiene su propio código, peso teórico (`theoretical_weight`)
 * y su lista de materiales de envase y empaque asociados.
 * ============================================================================
 */
class ProductPresentation extends Model
{
    protected $fillable = [
        'product_id',
        'presentation_code',
        'name',
        'theoretical_weight',
    ];

    protected $casts = [
        'theoretical_weight' => 'decimal:2',
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
