<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MaquilaCatalogItem extends Model
{
    protected $table = 'maquila_catalog_items';

    protected $fillable = [
        'codigo_item',
        'producto_nombre',
        'presentacion',
        'forma_farmaceutica',
        'unidad_medida',
        'vigencia_meses',
        'registro_ica',
        'activo'
    ];

    protected $casts = [
        'vigencia_meses' => 'integer',
        'activo' => 'boolean'
    ];
}
