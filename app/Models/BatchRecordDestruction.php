<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BatchRecordDestruction extends Model
{
    protected $table = 'batch_record_destructions';

    protected $fillable = [
        'acta_numero',
        'fecha_destruccion',
        'metodo_destruccion',
        'motivo',
        'observaciones',
        'cantidad_lotes',
        'lotes_detalle',
        'usuario_id',
        'usuario_nombre',
        'ip_address',
    ];

    protected $casts = [
        'fecha_destruccion' => 'date',
        'cantidad_lotes' => 'integer',
        'lotes_detalle' => 'array',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'usuario_id');
    }

    public function orders()
    {
        return $this->hasMany(MaquilaProductionOrder::class, 'acta_destruccion_id');
    }
}
