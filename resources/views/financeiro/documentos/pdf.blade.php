<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>{{ $documento->numero }}</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Helvetica', 'Arial', sans-serif; font-size: 12px; color: #333; line-height: 1.5; }

        .header { background: #FFD000; color: #111; padding: 30px 40px; display: table; width: 100%; }
        .header-left { display: table-cell; vertical-align: middle; width: 60%; }
        .header-right { display: table-cell; vertical-align: middle; width: 40%; text-align: right; }
        .header h1 { font-size: 22px; margin-bottom: 5px; font-weight: 800; }
        .header h1 .kero { color: #EC008C; }
        .header p { font-size: 11px; opacity: 0.7; }
        .doc-number { font-size: 18px; font-weight: bold; color: #111; }
        .doc-tipo { font-size: 14px; text-transform: uppercase; letter-spacing: 2px; opacity: 0.8; color: #111; }

        .body { padding: 30px 40px; }

        .info-grid { display: table; width: 100%; margin-bottom: 25px; }
        .info-col { display: table-cell; vertical-align: top; width: 50%; }
        .info-col h3 { font-size: 12px; text-transform: uppercase; letter-spacing: 1px; color: #111827; margin-bottom: 8px; border-bottom: 2px solid #FFD000; padding-bottom: 4px; }
        .info-col p { margin-bottom: 3px; font-size: 11px; }
        .info-col strong { color: #111; }

        table.items { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
        table.items thead th { background: #111827; color: white; padding: 10px 12px; text-align: left; font-size: 11px; text-transform: uppercase; }
        table.items tbody td { padding: 10px 12px; border-bottom: 1px solid #e5e5e5; font-size: 11px; }
        table.items tbody tr:nth-child(even) { background: #FFFEF0; }
        table.items .text-right { text-align: right; }

        .totais { margin-left: auto; width: 280px; }
        .totais table { width: 100%; border-collapse: collapse; }
        .totais td { padding: 6px 12px; font-size: 12px; }
        .totais .total-row { border-top: 3px solid #FFD000; }
        .totais .total-row td { font-size: 16px; font-weight: bold; color: #111827; padding-top: 10px; }

        .observacoes { margin-top: 25px; padding: 15px; background: #FFFEF0; border-radius: 6px; border-left: 4px solid #FFD000; }
        .observacoes h4 { font-size: 11px; text-transform: uppercase; color: #111827; margin-bottom: 5px; }
        .observacoes p { font-size: 11px; }

        .footer { position: fixed; bottom: 0; left: 0; right: 0; background: #f1f3f5; padding: 15px 40px; text-align: center; font-size: 10px; color: #666; border-top: 2px solid #FFD000; }
    </style>
</head>
<body>
    {{-- Cabeçalho --}}
    <div class="header">
        <div class="header-left">
            <h1><span class="kero">Kero</span> Prints</h1>
            <p>Gráfica e Papelaria</p>
        </div>
        <div class="header-right">
            <div class="doc-tipo">{{ $documento->tipo_label }}</div>
            <div class="doc-number">{{ $documento->numero }}</div>
        </div>
    </div>

    <div class="body">
        {{-- Info --}}
        <div class="info-grid">
            <div class="info-col" style="padding-right: 20px;">
                <h3>Cliente</h3>
                <p><strong>{{ $documento->cliente->nome }}</strong></p>
                @if($documento->cliente->cpf_cnpj)
                    <p>CPF/CNPJ: {{ $documento->cliente->cpf_cnpj }}</p>
                @endif
                @if($documento->cliente->email)
                    <p>E-mail: {{ $documento->cliente->email }}</p>
                @endif
                @if($documento->cliente->telefone)
                    <p>Telefone: {{ $documento->cliente->telefone }}</p>
                @endif
            </div>
            <div class="info-col">
                <h3>Documento</h3>
                <p>Emissão: <strong>{{ $documento->data_emissao->format('d/m/Y') }}</strong></p>
                @if($documento->data_vencimento)
                    <p>Vencimento: <strong>{{ $documento->data_vencimento->format('d/m/Y') }}</strong></p>
                @endif
                @if($documento->forma_pagamento)
                    <p>Pagamento: <strong>{{ $documento->forma_pagamento }}</strong></p>
                @endif
                @if($documento->condicoes_pagamento)
                    <p>Condições: {{ $documento->condicoes_pagamento }}</p>
                @endif
            </div>
        </div>

        {{-- Itens --}}
        <table class="items">
            <thead>
                <tr>
                    <th style="width:5%">#</th>
                    <th style="width:45%">Descrição</th>
                    <th class="text-right" style="width:12%">Quantidade</th>
                    <th class="text-right" style="width:15%">Valor Unitário</th>
                    <th class="text-right" style="width:10%">Desconto</th>
                    <th class="text-right" style="width:13%">Total</th>
                </tr>
            </thead>
            <tbody>
                @foreach($documento->itens as $i => $item)
                <tr>
                    <td>{{ $i + 1 }}</td>
                    <td>{{ $item->descricao }}</td>
                    <td class="text-right">{{ number_format($item->quantidade, $item->quantidade == (int)$item->quantidade ? 0 : 3, ',', '.') }}</td>
                    <td class="text-right">R$ {{ number_format($item->valor_unitario, 2, ',', '.') }}</td>
                    <td class="text-right">{{ $item->desconto_item > 0 ? 'R$ ' . number_format($item->desconto_item, 2, ',', '.') : '—' }}</td>
                    <td class="text-right"><strong>R$ {{ number_format($item->total_item, 2, ',', '.') }}</strong></td>
                </tr>
                @endforeach
            </tbody>
        </table>

        {{-- Totais --}}
        <div class="totais">
            <table>
                <tr>
                    <td>Subtotal:</td>
                    <td class="text-right">R$ {{ number_format($documento->subtotal, 2, ',', '.') }}</td>
                </tr>
                @if($documento->desconto > 0)
                <tr>
                    <td style="color: #dc3545;">Desconto:</td>
                    <td class="text-right" style="color: #dc3545;">- R$ {{ number_format($documento->desconto, 2, ',', '.') }}</td>
                </tr>
                @endif
                <tr class="total-row">
                    <td>Total:</td>
                    <td class="text-right">R$ {{ number_format($documento->valor_total, 2, ',', '.') }}</td>
                </tr>
            </table>
        </div>

        {{-- Observações --}}
        @if($documento->observacoes)
        <div class="observacoes">
            <h4>Observações</h4>
            <p>{{ $documento->observacoes }}</p>
        </div>
        @endif
    </div>

    <div class="footer">
        Documento gerado por Kero Prints Gráfica e Papelaria — {{ now()->format('d/m/Y H:i') }}
    </div>
</body>
</html>
