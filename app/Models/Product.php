<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    protected $fillable = [
        'name',
        'presentation',
        'pharmaceutical_form',
        'active_ingredient_concentration',
        'ica_license',
        'formula_maestra',
        'image',
        'vigencia_meses',
        'vida_util_unidad',
        'active_ingredients_json',
        'maquiladores_json',
        'presentations_json',
        'base_batch_size',
        'base_unit',
        'status',
    ];

    protected $casts = [
        'active_ingredients_json' => 'array',
        'maquiladores_json'       => 'array',
        'presentations_json'      => 'array',
    ];

    /**
     * Lista normalizada de principios activos y sus concentraciones.
     */
    public function getActiveIngredientsListAttribute(): array
    {
        $raw = $this->active_ingredients_json;
        if (is_string($raw)) {
            $raw = json_decode($raw, true);
        }
        if (is_array($raw) && count($raw) > 0) {
            return $raw;
        }
        if ($this->active_ingredient_concentration !== null && $this->active_ingredient_concentration > 0) {
            return [
                [
                    'name'          => 'Principio Activo Principal',
                    'concentration' => $this->active_ingredient_concentration,
                    'unit'          => '%',
                ],
            ];
        }
        return [];
    }

    /**
     * Lista normalizada de maquiladores autorizados y sus rangos de lote mínimo/máximo.
     */
    public function getMaquiladoresListAttribute(): array
    {
        $raw = $this->maquiladores_json;
        if (is_string($raw)) {
            $raw = json_decode($raw, true);
        }
        return is_array($raw) ? $raw : [];
    }

    public function ingredients()
    {
        return $this->hasMany(FormulaIngredient::class);
    }

    public function presentations()
    {
        return $this->hasMany(ProductPresentation::class);
    }

    public function manufacturingPlans()
    {
        return $this->hasMany(ProductManufacturingPlan::class);
    }

    public function activePlan()
    {
        return $this->hasOne(ProductManufacturingPlan::class)->whereRaw('"active" IS NOT FALSE');
    }

    public function steps()
    {
        return $this->hasMany(ProductStep::class)->orderBy('step_number');
    }

    public function productionOrders()
    {
        return $this->hasMany(ProductionOrder::class);
    }
}
