<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\AuditableTrait;

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

    public function productionOrder()
    {
        return $this->belongsTo(ProductionOrder::class);
    }

    public function signedByUser()
    {
        return $this->belongsTo(User::class, 'signed_by');
    }

    public function qaUser()
    {
        return $this->belongsTo(User::class, 'qa_user_id');
    }
}
