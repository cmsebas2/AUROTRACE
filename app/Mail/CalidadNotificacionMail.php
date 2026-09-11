<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use App\Models\MaquilaProductionOrder;

class CalidadNotificacionMail extends Mailable
{
    use Queueable, SerializesModels;

    public $order;
    public $evento;
    public $usuarioNombre;

    public function __construct(MaquilaProductionOrder $order, string $evento = 'INGRESO_REVISION_CALIDAD', string $usuarioNombre = 'Sistema')
    {
        $this->order = $order;
        $this->evento = $evento;
        $this->usuarioNombre = $usuarioNombre;
    }

    public function envelope(): Envelope
    {
        $asunto = "🔔 [AUROTRACE QA] Batch Record Requerido para Revisión de Calidad — Lote {$this->order->lote} (OP #{$this->order->op})";
        if ($this->evento === 'PRUEBA_SISTEMA') {
            $asunto = "🧪 [PRUEBA AUROTRACE] Notificación de Revisión de Calidad — Lote {$this->order->lote}";
        }

        return new Envelope(
            subject: $asunto,
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.calidad_notificacion',
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
