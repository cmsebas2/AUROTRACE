<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Carbon\Carbon;

class Maquilador extends Model
{
    use SoftDeletes;

    protected $table = 'maquiladores';

    protected $fillable = [
        'nombre',
        'nit',
        'activo',
        'certificado_bpm_ica_vigente_hasta'
    ];

    protected $casts = [
        'activo' => 'boolean',
        'certificado_bpm_ica_vigente_hasta' => 'date'
    ];

    public function orders()
    {
        return $this->hasMany(MaquilaProductionOrder::class, 'maquilador_id');
    }

    /**
     * Estado del certificado BPM-ICA
     * @return string ('vigente', 'proximo_a_vencer', 'vencido')
     */
    public function getEstadoCertificadoIcaAttribute(): string
    {
        if (!$this->certificado_bpm_ica_vigente_hasta) {
            return 'vencido';
        }

        $hoy = Carbon::today();
        $vencimiento = Carbon::parse($this->certificado_bpm_ica_vigente_hasta);

        if ($vencimiento->isPast()) {
            return 'vencido';
        }

        if ($vencimiento->diffInDays($hoy) <= 60) {
            return 'proximo_a_vencer';
        }

        return 'vigente';
    }

    public function getDiasVencimientoIcaAttribute(): int
    {
        if (!$this->certificado_bpm_ica_vigente_hasta) return -999;
        return (int) Carbon::today()->diffInDays(Carbon::parse($this->certificado_bpm_ica_vigente_hasta), false);
    }
}
