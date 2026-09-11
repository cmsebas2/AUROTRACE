<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use App\Models\MaquilaProductionOrder;

class CalidadNotificacionMail extends Mailable
{
    use Queueable, SerializesModels;

    public $order;
    public $evento;
    public $usuarioNombre;

    public function __construct(MaquilaProductionOrder $order, string $evento = 'REVISION_CALIDAD', string $usuarioNombre = 'Sistema AUROTRACE')
    {
        $this->order = $order;
        $this->evento = $evento;
        $this->usuarioNombre = $usuarioNombre;
    }

    public function build()
    {
        $lote = $this->order->lote ?? 'N/A';
        $op = $this->order->op ?? 'N/A';

        return $this->subject("🛡️ AUROTRACE QA: Lote {$lote} (OP {$op}) requiere Dictamen de Calidad")
                    ->view('emails.calidad_notificacion');
    }
}
