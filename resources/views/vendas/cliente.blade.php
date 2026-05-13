<!doctype html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Tela do Cliente | Kero Prints</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background:#0F172A; color:#F8FAFC; min-height:100vh; }
        .brand-bar { border-bottom:4px solid #FFD000; }
        .total { font-size:4rem; line-height:1; color:#22C55E; }
        .item-row { border-bottom:1px solid rgba(255,255,255,.08); }
        .muted { color:#94A3B8; }
        .pix img { max-width:300px; width:100%; background:white; border-radius:12px; padding:8px; }
    </style>
</head>
<body>
    <div class="brand-bar p-4 d-flex justify-content-between align-items-center">
        <div>
            <h1 class="h3 mb-0">Kero Prints</h1>
            <div class="muted">Gráfica e Papelaria</div>
        </div>
        <div class="text-end">
            <div class="muted">Total</div>
            <div class="total" id="total">R$ 0,00</div>
        </div>
    </div>

    <main class="container-fluid p-4">
        <div class="row g-4">
            <div class="col-12 col-lg-8">
                <h2 class="h5 muted mb-3">Itens</h2>
                <div id="itens" class="fs-4">
                    <div class="text-center muted py-5">Aguardando produtos...</div>
                </div>
            </div>
            <div class="col-12 col-lg-4">
                <div class="bg-dark rounded p-4">
                    <div class="d-flex justify-content-between mb-2"><span class="muted">Subtotal</span><strong id="subtotal">R$ 0,00</strong></div>
                    <div class="d-flex justify-content-between mb-2"><span class="muted">Desconto</span><strong id="desconto">-R$ 0,00</strong></div>
                    <div class="d-flex justify-content-between mb-2"><span class="muted">Pagamento</span><strong id="pagamento">-</strong></div>
                    <div class="pix text-center mt-4" id="pix" style="display:none">
                        <div class="fw-bold mb-2">Pague com Pix</div>
                        <img id="pixImg" alt="QR Code Pix">
                        <div class="muted small mt-2" id="pixStatus">Aguardando pagamento...</div>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <script>
    const canal = 'BroadcastChannel' in window ? new BroadcastChannel('kero-pdv-cliente') : null;
    const dinheiro = valor => 'R$ ' + Number(valor || 0).toLocaleString('pt-BR', { minimumFractionDigits: 2 });

    canal?.addEventListener('message', event => {
        const data = event.data || {};
        if (!data.carrinho) return;

        document.getElementById('itens').innerHTML = data.carrinho.length
            ? data.carrinho.map(item => `
                <div class="item-row py-3 d-flex justify-content-between gap-3">
                    <div>
                        <div class="fw-semibold">${item.descricao}</div>
                        <div class="muted fs-6">${item.quantidade} x ${dinheiro(item.preco_unitario)}</div>
                    </div>
                    <strong>${dinheiro(item.quantidade * item.preco_unitario)}</strong>
                </div>
            `).join('')
            : '<div class="text-center muted py-5">Aguardando produtos...</div>';

        document.getElementById('subtotal').textContent = dinheiro(data.totais?.subtotal || 0);
        document.getElementById('desconto').textContent = '-' + dinheiro(data.totais?.desconto || 0);
        document.getElementById('total').textContent = dinheiro(data.totais?.total || 0);
        document.getElementById('pagamento').textContent = data.formaPagamento || '-';

        const pix = document.getElementById('pix');
        if (data.venda?.forma_pagamento === 'PIX' && data.venda?.pix_qr_code_base64) {
            pix.style.display = 'block';
            document.getElementById('pixImg').src = `data:image/png;base64,${data.venda.pix_qr_code_base64}`;
            document.getElementById('pixStatus').textContent = data.venda.status_label;
        } else {
            pix.style.display = 'none';
        }
    });
    </script>
</body>
</html>
