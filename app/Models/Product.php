<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    protected $fillable = [
        'name', 'presentation', 'pharmaceutical_form', 'active_ingredient_concentration', 'ica_license', 'formula_maestra', 'image', 'vigencia_meses', 'base_batch_size', 'base_unit', 'status'
    ];

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
        return $this->hasOne(ProductManufacturingPlan::class)->where('active', true);
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
