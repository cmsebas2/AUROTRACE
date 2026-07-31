<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ReconditioningItem extends Model
{
    protected $fillable = [
        'item_id', 'item_code', 'manufacturer', 'is_external', 'lot_number', 
        'expiration_date', 'quantity', 'uom', 'transfer_number', 'transfer_pdf_path',
        'location', 'req_label', 'req_box', 'req_others', 'observations', 'status',
        'used_labels', 'used_boxes',
        'is_released', 'released_at', 'release_pdf_path', 'destination_warehouse',
        'rejection_reason', 'rejection_photo_path',
        'exit_transfer_number', 'exit_transfer_pdf_path'
    ];

    protected $casts = [
        'is_external' => 'boolean',
        'is_released' => 'boolean',
        'released_at' => 'datetime',
        'expiration_date' => 'date',
    ];

    public function item()
    {
        return $this->belongsTo(Item::class, 'item_id');
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by_id');
    }

    public function releasedBy()
    {
        return $this->belongsTo(User::class, 'released_by_id');
    }

    public function getRiskLevelAttribute()
    {
        if ($this->is_external) {
            return 1; // Nivel 1 (Rojo) por ser externo
        }

        if (!$this->expiration_date) {
            return 3; // Default verde si no hay fecha, aunque es obligatoria
        }

        $months = now()->diffInMonths($this->expiration_date, false);
        
        if ($months < 3) {
            return 1; // Nivel 1 (Rojo)
        } elseif ($months >= 3 && $months <= 6) {
            return 2; // Nivel 2 (Amarillo)
        } else {
            return 3; // Nivel 3 (Verde)
        }
    }
}
