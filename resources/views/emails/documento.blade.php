<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <style>
        body { font-family: Arial, sans-serif; background: #f5f5f5; margin: 0; padding: 20px; }
        .container { max-width: 600px; margin: 0 auto; background: white; border-radius: 8px; overflow: hidden; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        .header { background: #FFD000; color: #111; padding: 30px; text-align: center; }
        .header h1 { margin: 0; font-size: 24px; font-weight: 800; }
        .header h1 span { color: #EC008C; }
        .header p { margin: 5px 0 0; opacity: 0.7; font-size: 14px; }
        .body { padding: 30px; }
        .body h2 { color: #111827; margin-bottom: 15px; font-size: 20px; }
        .body p { color: #555; line-height: 1.6; margin-bottom: 15px; }
        .info-box { background: #f8f9fa; border-radius: 6px; padding: 20px; margin: 20px 0; border-left: 4px solid #FFD000; }
        .info-box p { margin: 5px 0; font-size: 14px; }
        .info-box strong { color: #111827; }
        .total { font-size: 24px; font-weight: bold; color: #111827; text-align: center; padding: 15px; background: #FFF9D6; border-radius: 6px; margin: 20px 0; border: 2px solid #FFD000; }
        .footer { background: #f8f9fa; padding: 20px; text-align: center; font-size: 12px; color: #999; border-top: 1px solid #eee; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1><span>Kero</span> Prints</h1>
            <p>Gráfica e Papelaria</p>
        </div>

        <div class="body">
            <h2>{{ $documento->tipo_label }} #{{ $documento->numero }}</h2>

            <p>Olá, <strong>{{ $documento->cliente->nome }}</strong>!</p>

            <p>Segue em anexo o {{ strtolower($documento->tipo_label) }} referente aos serviços/produtos listados abaixo.</p>

            <div class="info-box">
                <p><strong>Documento:</strong> {{ $documento->numero }}</p>
                <p><strong>Emissão:</strong> {{ $documento->data_emissao->format('d/m/Y') }}</p>
                @if($documento->data_vencimento)
                    <p><strong>Vencimento:</strong> {{ $documento->data_vencimento->format('d/m/Y') }}</p>
                @endif
                @if($documento->forma_pagamento)
                    <p><strong>Forma de Pagamento:</strong> {{ $documento->forma_pagamento }}</p>
                @endif
            </div>

            <div class="total">
                Valor Total: R$ {{ number_format($documento->valor_total, 2, ',', '.') }}
            </div>

            <p>O documento completo está disponível em formato PDF no anexo deste e-mail.</p>

            <p>Em caso de dúvidas, entre em contato conosco.</p>

            <p>Atenciosamente,<br><strong>Equipe Kero Prints</strong></p>
        </div>

        <div class="footer">
            Este e-mail foi enviado automaticamente pela <strong>Kero Prints Gráfica e Papelaria</strong>.<br>
            Por favor, não responda este e-mail.
        </div>
    </div>
</body>
</html>
