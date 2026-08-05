<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class MaquilaOrder extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'tipo_producto',
        'producto',
        'odm',
        'sdm',
        'maquilador',
        'fecha_creacion',
        'created_by',
    ];

    /**
     * Get the items for the maquila order.
     */
    public function items()
    {
        return $this->hasMany(MaquilaOrderItem::class);
    }

    /**
     * Get the user who created the order.
     */
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
