<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\AuditableTrait;

/**
 * ============================================================================
 * EXPLOSIÓN Y CONCILIACIÓN DE MATERIALES DE LA OP - OpMaterialReconciliation
 * ============================================================================
 * GUÍA RÁPIDA ("FOR DUMMIES"):
 * Cada fila de esta tabla es un ingrediente (Materia Prima) o material de
 * envase/empaque que requiere una Orden de Producción específica.
 *
 * Se utiliza en 3 momentos clave del flujo:
 * 1. Al crear la OP y ajustar activos (guarda `required_qty`, `bh_valor`, `bs_valor`, `humedad_valor`).
 * 2. Al cargar los Certificados de Análisis - COAs (`lote`, `n_analisis`, `fecha_vencimiento_coa`, `coa_pdf_path`).
 * 3. Al final del lote en Conciliación (compara `received_qty`, `used_qty` y `returned_qty`).
 * ============================================================================
 */
class OpMaterialReconciliation extends Model
{
    use AuditableTrait;

    protected $fillable = [
        'production_order_id',
        'material_code',
        'type',
        'description',
        'function',
        'unit',
        'required_qty',
        'lote',
        'received_qty',
        'used_qty',
        'returned_qty',
        'date',
        'signed_by',
        'signed_at',
        'qa_user_id',
        'qa_verified_at',
        'observations',
        'bh_valor',
        'bs_valor',
        'humedad_valor',
        'ajuste_porcentaje',
        'n_analisis',
        'fecha_vencimiento_coa',
        'coa_pdf_path',
    ];

    protected $casts = [
        'date'                  => 'date',
        'signed_at'             => 'datetime',
        'qa_verified_at'        => 'datetime',
        'fecha_vencimiento_coa' => 'date',
    ];

    /** Orden de Producción a la que pertenece este material. */
    public function productionOrder()
    {
        return $this->belongsTo(ProductionOrder::class);
    }

    /** Usuario que firmó la conciliación de este material. */
    public function signedByUser()
    {
        return $this->belongsTo(User::class, 'signed_by');
    }

    /** Usuario de Calidad (QA) que verificó este material. */
    public function qaUser()
    {
        return $this->belongsTo(User::class, 'qa_user_id');
    }
}
