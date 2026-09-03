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
        'pre_orden',
        'op',
        'producto_nombre',
        'producto_id',
        'forma_farmaceutica',
        'lote',
        'tamano_lote',
        'numero_sdm',
        'tipo_producto',
        'maquilador_id',
        'fecha_creacion',
        'fecha_fabricacion',
        'fecha_vencimiento',
        'fecha_envio_maquila',
        'fecha_llegada_br',
        'total_producto_terminado_fabricado',
        'rendimiento_real',
        'posicion_archivo_fisico',
        'estado_br_dt',
        'comentario_dt',
        'fecha_revision_dt',
        'usuario_dt_id',
        'certificado_fisicoquimico',
        'certificado_microbiologico',
        'certificado_endotoxinas',
        'liberar_br',
        'fecha_liberacion_br',
        'estado_br_calidad',
        'observaciones_calidad',
        'usuario_calidad_id',
        'estado',
        'usuario_creador_id',
        'observaciones'
    ];

    protected $casts = [
        'fecha_creacion' => 'date',
        'fecha_envio_maquila' => 'date',
        'fecha_llegada_br' => 'date',
        'fecha_liberacion_br' => 'date',
        'fecha_revision_dt' => 'datetime',
        'liberar_br' => 'boolean',
        'tamano_lote' => 'float',
        'total_producto_terminado_fabricado' => 'float',
        'rendimiento_real' => 'float',
    ];

    public function maquilador()
    {
        return $this->belongsTo(Maquilador::class, 'maquilador_id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'usuario_creador_id');
    }

    public function dtUser()
    {
        return $this->belongsTo(User::class, 'usuario_dt_id');
    }

    public function qaUser()
    {
        return $this->belongsTo(User::class, 'usuario_calidad_id');
    }

    public function product()
    {
        return $this->belongsTo(Product::class, 'producto_id');
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
        $base = $this->tamano_lote > 0 ? $this->tamano_lote : $this->total_programado;
        return max(0, round($base - $this->total_recibido, 3));
    }

    public function getPorcentajeAvanceGlobalAttribute(): float
    {
        $base = $this->tamano_lote > 0 ? $this->tamano_lote : $this->total_programado;
        if ($base <= 0) return 0.0;
        return round(($this->total_recibido / $base) * 100, 2);
    }

    public function getRendimientoCalculadoAttribute(): float
    {
        if (!is_null($this->rendimiento_real)) {
            return (float) $this->rendimiento_real;
        }

        $base = $this->tamano_lote > 0 ? $this->tamano_lote : $this->total_programado;
        if ($base <= 0) return 0.0;

        $fabricado = !is_null($this->total_producto_terminado_fabricado) && $this->total_producto_terminado_fabricado > 0
            ? $this->total_producto_terminado_fabricado
            : $this->total_recibido;

        return round(($fabricado / $base) * 100, 2);
    }

    public function getEstadoLabelAttribute(): string
    {
        $map = [
            'borrador' => 'OP CREADA',
            'OP CREADA' => 'OP CREADA',
            'enviada_a_maquila' => 'OP EN PRODUCCION',
            'en_proceso' => 'OP EN PRODUCCION',
            'OP EN PRODUCCION' => 'OP EN PRODUCCION',
            'entrega_parcial' => 'RECEPCIÓN PARCIAL',
            'OP TERMINADA - BR PENDIENTE' => 'OP TERMINADA - BR PENDIENTE',
            'completada_pendiente_liquidacion' => 'OP TERMINADA - BR PENDIENTE',
            'BR REVISION DT' => 'BR REVISION DT',
            'BR REVISION CALIDAD' => 'BR REVISION CALIDAD',
            'BR CERRADO' => 'BR CERRADO',
            'BR ABIERTO' => 'BR ABIERTO',
            'liquidada' => 'BR CERRADO',
            'cerrada_tecnicamente' => 'BR CERRADO',
            'anulada' => 'ANULADA',
        ];

        return $map[$this->estado] ?? strtoupper(str_replace('_', ' ', $this->estado));
    }

    public function getEstadoBadgeClassAttribute(): string
    {
        $st = $this->estado_label;
        if (str_contains($st, 'CREADA')) {
            return 'bg-slate-100 text-slate-800 border-slate-300';
        }
        if (str_contains($st, 'PRODUCCION')) {
            return 'bg-amber-50 text-amber-800 border-amber-300';
        }
        if (str_contains($st, 'PARCIAL')) {
            return 'bg-blue-50 text-blue-800 border-blue-300';
        }
        if (str_contains($st, 'PENDIENTE')) {
            return 'bg-purple-50 text-purple-800 border-purple-300';
        }
        if (str_contains($st, 'DT')) {
            return 'bg-indigo-50 text-indigo-800 border-indigo-300';
        }
        if (str_contains($st, 'CALIDAD')) {
            return 'bg-cyan-50 text-cyan-800 border-cyan-300';
        }
        if (str_contains($st, 'CERRADO')) {
            return 'bg-emerald-50 text-emerald-800 border-emerald-300';
        }
        if (str_contains($st, 'ABIERTO')) {
            return 'bg-red-50 text-red-800 border-red-300';
        }

        return 'bg-slate-100 text-slate-800 border-slate-300';
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
