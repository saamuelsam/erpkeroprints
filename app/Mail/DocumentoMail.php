<?php

namespace App\Mail;

use App\Models\Documento;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class DocumentoMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Documento $documento,
        public string $pdfContent
    ) {}

    public function envelope(): Envelope
    {
        $tipo = $this->documento->tipo_label;
        return new Envelope(
            subject: "{$tipo} #{$this->documento->numero} — MasterPrint ERP",
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.documento',
        );
    }

    public function attachments(): array
    {
        return [
            \Illuminate\Mail\Mailables\Attachment::fromData(
                fn() => $this->pdfContent,
                "{$this->documento->numero}.pdf"
            )->withMime('application/pdf'),
        ];
    }
}
