<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * ============================================================================
 * RESULTADO DE ENVASADO DEL LOTE - BatchPackagingResult
 * ============================================================================
 * GUÍA RÁPIDA ("FOR DUMMIES"):
 * Según el Artículo 1 de GEMINI.md, esta tabla guarda el resultado consolidado
 * del módulo de envase (relación 1 a 1 con `ProductionOrder`), incluyendo:
 * - Controles organolépticos (color, olor, textura, ausencia de partículas).
 * - Los 10 pesos muestreados y el peso promedio (`average_weight`).
 * - La cantidad real de unidades obtenidas (`units_obtained`), usada para
 *   calcular el rendimiento final del lote.
 * ============================================================================
 */
class BatchPackagingResult extends Model
{
    use HasFactory;

    protected $fillable = [
        'production_order_id',
        'color_conforme',
        'odor_conforme',
        'texture_conforme',
        'particles_free',
        'weight_1', 'weight_2', 'weight_3', 'weight_4', 'weight_5',
        'weight_6', 'weight_7', 'weight_8', 'weight_9', 'weight_10',
        'average_weight',
        'units_obtained',
        'start_time',
        'end_time',
        'status',
        'user_id',
        'signed_at',
        'qa_user_id',
        'qa_verified_at',
    ];

    protected $casts = [
        'signed_at'       => 'datetime',
        'qa_verified_at'  => 'datetime',
        'start_time'      => 'datetime',
        'end_time'        => 'datetime',
        'color_conforme'  => 'boolean',
        'odor_conforme'   => 'boolean',
        'texture_conforme'=> 'boolean',
        'particles_free'  => 'boolean',
    ];

    /** Orden de Producción a la que pertenece este resultado de envasado. */
    public function productionOrder()
    {
        return $this->belongsTo(ProductionOrder::class);
    }

    /** Operario que registró y firmó el envasado. */
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /** Inspector de Calidad (QA) que verificó el envasado. */
    public function qaUser()
    {
        return $this->belongsTo(User::class, 'qa_user_id');
    }
}
