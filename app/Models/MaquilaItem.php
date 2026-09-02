<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Carbon\Carbon;

class MaquilaItem extends Model
{
    use SoftDeletes;

    protected $table = 'maquila_items';

    protected $fillable = [
        'maquila_production_order_id',
        'codigo_item',
        'descripcion_producto',
        'lote_fisico',
        'presentacion',
        'cantidad_programada',
        'unidad_medida',
        'fecha_fabricacion',
        'fecha_vencimiento',
        'liquidado'
    ];

    protected $casts = [
        'cantidad_programada' => 'float',
        'fecha_fabricacion' => 'date',
        'fecha_vencimiento' => 'date',
        'liquidado' => 'boolean'
    ];

    public function order()
    {
        return $this->belongsTo(MaquilaProductionOrder::class, 'maquila_production_order_id');
    }

    public function deliveries()
    {
        return $this->hasMany(MaquilaDelivery::class, 'maquila_item_id')->orderBy('fecha_recepcion', 'asc');
    }

    /**
     * Total recibido de todas las entregas parciales del ítem
     */
    public function getCantidadRecibidaTotalAttribute(): float
    {
        return (float) $this->deliveries->sum('cantidad_recibida');
    }

    /**
     * Saldo pendiente por recibir
     */
    public function getSaldoPendienteAttribute(): float
    {
        $saldo = $this->cantidad_programada - $this->cantidad_recibida_total;
        return max(0, round($saldo, 3));
    }

    /**
     * Porcentaje de Avance (%)
     */
    public function getPorcentajeAvanceAttribute(): float
    {
        if ($this->cantidad_programada <= 0) return 0.0;
        return round(($this->cantidad_recibida_total / $this->cantidad_programada) * 100, 2);
    }

    /**
     * Rendimiento Operativo (Yield %)
     * Formula: (Σ cantidad_recibida / cantidad_programada) * 100
     */
    public function getRendimientoPctAttribute(): float
    {
        if ($this->cantidad_programada <= 0) return 0.0;
        return round(($this->cantidad_recibida_total / $this->cantidad_programada) * 100, 2);
    }

    /**
     * Desviación de Rendimiento (%): Yield % - 100
     */
    public function getDesviacionRendimientoAttribute(): float
    {
        return round($this->rendimiento_pct - 100, 2);
    }

    /**
     * Clasificación de Desviación (Merma, Exceso, Dentro de Rango)
     * Tolerancia: 5% (configurable según tipo_producto)
     */
    public function getClasificacionDesviacionAttribute(): string
    {
        $yield = $this->rendimiento_pct;
        $tolerancia = 5.0; // 5% tolerancia estándar BPM ICA

        if ($this->order && $this->order->tipo_producto === 'premezcla') {
            $tolerancia = 3.0; // Tolerancia más estricta para Premezclas
        }

        if ($yield < (100.0 - $tolerancia)) {
            return 'Merma';
        } elseif ($yield > (100.0 + $tolerancia)) {
            return 'Exceso';
        }

        return 'Dentro de Rango';
    }

    /**
     * Lead Time en días desde despacho a maquila hasta última entrega o el día de hoy
     */
    public function getLeadTimeDiasAttribute(): int
    {
        if (!$this->order || !$this->order->fecha_envio_maquila) {
            return 0;
        }

        $fechaEnvio = Carbon::parse($this->order->fecha_envio_maquila);
        $ultimaEntrega = $this->deliveries->last();

        if ($ultimaEntrega && $ultimaEntrega->fecha_recepcion) {
            $fechaFin = Carbon::parse($ultimaEntrega->fecha_recepcion);
        } else {
            $fechaFin = Carbon::today();
        }

        return (int) $fechaEnvio::diffInDays($fechaFin, false);
    }
}
