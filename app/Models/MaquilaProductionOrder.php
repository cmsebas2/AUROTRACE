<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Carbon\Carbon;

class MaquilaProductionOrder extends Model
{
    use SoftDeletes;

    protected $table = 'maquila_production_orders';

    protected $fillable = [
        'numero_odm',
        'numero_sdm',
        'tipo_producto',
        'maquilador_id',
        'fecha_creacion',
        'fecha_envio_maquila',
        'estado',
        'usuario_creador_id',
        'observaciones'
    ];

    protected $casts = [
        'fecha_creacion' => 'date',
        'fecha_envio_maquila' => 'date'
    ];

    public function maquilador()
    {
        return $this->belongsTo(Maquilador::class, 'maquilador_id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'usuario_creador_id');
    }

    public function items()
    {
        return $this->hasMany(MaquilaItem::class, 'maquila_production_order_id');
    }

    public function deliveries()
    {
        return $this->hasManyThrough(MaquilaDelivery::class, MaquilaItem::class, 'maquila_production_order_id', 'maquila_item_id');
    }

    public function getTotalProgramadoAttribute(): float
    {
        return (float) $this->items->sum('cantidad_programada');
    }

    public function getTotalRecibidoAttribute(): float
    {
        return (float) $this->items->reduce(function ($carry, $item) {
            return $carry + $item->cantidad_recibida_total;
        }, 0);
    }

    public function getSaldoTotalAttribute(): float
    {
        return max(0, round($this->total_programado - $this->total_recibido, 3));
    }

    public function getPorcentajeAvanceGlobalAttribute(): float
    {
        if ($this->total_programado <= 0) return 0.0;
        return round(($this->total_recibido / $this->total_programado) * 100, 2);
    }

    public function getLeadTimeDiasAttribute(): int
    {
        if (!$this->fecha_envio_maquila) return 0;
        $envio = Carbon::parse($this->fecha_envio_maquila);
        
        $ultimoDelivery = MaquilaDelivery::whereIn('maquila_item_id', $this->items->pluck('id'))
            ->latest('fecha_recepcion')
            ->first();

        $fin = $ultimoDelivery ? Carbon::parse($ultimoDelivery->fecha_recepcion) : Carbon::today();
        return (int) $envio->diffInDays($fin, false);
    }
}
