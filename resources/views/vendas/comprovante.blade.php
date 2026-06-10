<!doctype html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Comprovante {{ $venda->numero }}</title>
    <style>
        * { box-sizing: border-box; }
        body {
            background: #f3f4f6;
            color: #111827;
            font-family: Arial, Helvetica, sans-serif;
            margin: 0;
            padding: 20px;
        }
        .toolbar {
            display: flex;
            gap: 8px;
            justify-content: space-between;
            margin: 0 auto 14px;
            max-width: 80mm;
        }
        .toolbar a,
        .toolbar button {
            background: #fff;
            border: 1px solid #9ca3af;
            border-radius: 4px;
            color: #111827;
            cursor: pointer;
            font-size: 12px;
            padding: 7px 10px;
            text-decoration: none;
        }
        .receipt {
            background: #fff;
            border: 1px solid #d1d5db;
            margin: 0 auto;
            padding: 7mm;
            width: 80mm;
        }
        .center { text-align: center; }
        .brand { font-size: 16px; font-weight: 800; }
        .muted { color: #4b5563; font-size: 11px; }
        .divider { border-top: 1px dashed #6b7280; margin: 8px 0; }
        .client { font-size: 14px; font-weight: 700; margin-top: 3px; overflow-wrap: anywhere; }
        .item { display: grid; font-size: 11px; gap: 3px; grid-template-columns: 1fr auto; margin-bottom: 7px; }
        .item-name { font-weight: 700; overflow-wrap: anywhere; }
        .line { display: flex; font-size: 11px; justify-content: space-between; margin: 4px 0; }
        .total { font-size: 15px; font-weight: 800; }
        @media print {
            @page { margin: 0; size: 80mm auto; }
            body { background: #fff; padding: 0; }
            .no-print { display: none !important; }
            .receipt { border: 0; width: 80mm; }
        }
    </style>
</head>
<body>
<div class="toolbar no-print">
    <a href="{{ route('vendas.index') }}">Voltar</a>
    <button type="button" onclick="window.print()">Imprimir</button>
</div>

<main class="receipt">
    <div class="center">
        <div class="brand">Kero Prints</div>
        <div class="muted">Comprovante de venda</div>
    </div>

    <div class="divider"></div>
    <div class="muted">Cliente</div>
    <div class="client">{{ $venda->cliente_exibicao }}</div>
    <div class="muted">{{ $venda->numero }} · {{ $venda->created_at->format('d/m/Y H:i') }}</div>

    <div class="divider"></div>
    @foreach($venda->itens as $item)
        <div class="item">
            <div>
                <div class="item-name">{{ $item->descricao }}</div>
                <div class="muted">{{ number_format($item->quantidade, 2, ',', '.') }} x R$ {{ number_format($item->preco_unitario, 2, ',', '.') }}</div>
            </div>
            <strong>R$ {{ number_format($item->total_item, 2, ',', '.') }}</strong>
        </div>
    @endforeach

    <div class="divider"></div>
    <div class="line"><span>Subtotal</span><span>R$ {{ number_format($venda->subtotal, 2, ',', '.') }}</span></div>
    @if((float) $venda->desconto > 0)
        <div class="line"><span>Desconto</span><span>-R$ {{ number_format($venda->desconto, 2, ',', '.') }}</span></div>
    @endif
    <div class="line total"><span>Total</span><span>R$ {{ number_format($venda->valor_total, 2, ',', '.') }}</span></div>
    <div class="line"><span>Pagamento</span><span>{{ $venda->forma_pagamento_label }}</span></div>
    @if($venda->forma_pagamento === 'DINHEIRO')
        <div class="line"><span>Recebido</span><span>R$ {{ number_format($venda->valor_recebido, 2, ',', '.') }}</span></div>
        <div class="line"><span>Troco</span><span>R$ {{ number_format($venda->troco, 2, ',', '.') }}</span></div>
    @endif

    <div class="divider"></div>
    <div class="center muted">Obrigado pela preferência.</div>
</main>
<script>
    window.addEventListener('load', () => {
        setTimeout(() => window.print(), 300);
    });
</script>
</body>
</html>
