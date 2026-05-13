<!doctype html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Tela do Cliente | Kero Prints</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        :root {
            --yellow: #FFD000;
            --green: #22C55E;
            --cyan: #00AEEF;
            --ink: #0F172A;
            --panel: #111827;
            --soft: #1E293B;
            --muted: #94A3B8;
        }

        body {
            background: var(--ink);
            color: #F8FAFC;
            min-height: 100vh;
            overflow: hidden;
        }

        .brand-bar {
            border-bottom: 5px solid var(--yellow);
            background: #020617;
        }

        .total {
            color: var(--green);
            font-size: clamp(3rem, 7vw, 6rem);
            font-weight: 800;
            letter-spacing: 0;
            line-height: .95;
        }

        .muted { color: var(--muted); }
        .shell { height: calc(100vh - 122px); }
        .items-panel, .summary-panel {
            background: var(--panel);
            border: 1px solid rgba(255,255,255,.08);
            border-radius: 16px;
            height: 100%;
            overflow: hidden;
        }

        .items-list {
            max-height: calc(100vh - 230px);
            overflow-y: auto;
        }

        .item-row {
            border-bottom: 1px solid rgba(255,255,255,.08);
        }

        .item-row:last-child { border-bottom: 0; }

        .summary-line {
            align-items: center;
            border-bottom: 1px solid rgba(255,255,255,.08);
            display: flex;
            justify-content: space-between;
            padding: 14px 0;
        }

        .summary-line strong {
            font-size: 1.25rem;
        }

        .status-pill {
            border-radius: 999px;
            display: inline-flex;
            font-weight: 700;
            padding: 8px 14px;
        }

        .status-waiting { background: rgba(250, 204, 21, .14); color: #FACC15; }
        .status-paid { background: rgba(34, 197, 94, .14); color: #4ADE80; }
        .status-idle { background: rgba(148, 163, 184, .14); color: #CBD5E1; }

        .pix-card {
            background: #F8FAFC;
            border-radius: 14px;
            color: #111827;
            display: none;
            padding: 18px;
        }

        .pix-card img {
            background: white;
            border-radius: 12px;
            display: block;
            margin: 0 auto;
            max-width: 260px;
            padding: 8px;
            width: 100%;
        }

        .pix-code {
            background: #E2E8F0;
            border-radius: 8px;
            color: #0F172A;
            font-size: .8rem;
            max-height: 86px;
            overflow: hidden;
            padding: 10px;
            word-break: break-all;
        }
    </style>
</head>
<body>
    <header class="brand-bar p-4 d-flex justify-content-between align-items-center">
        <div>
            <h1 class="h2 mb-0 fw-bold">Kero Prints</h1>
            <div class="muted">Gráfica e Papelaria</div>
        </div>
        <div class="text-end">
            <div class="muted fs-5">Total da compra</div>
            <div class="total" id="total">R$ 0,00</div>
        </div>
    </header>

    <main class="container-fluid p-4 shell">
        <div class="row g-4 h-100">
            <div class="col-12 col-xl-8 h-100">
                <section class="items-panel">
                    <div class="p-4 border-bottom border-secondary-subtle d-flex justify-content-between align-items-center">
                        <div>
                            <h2 class="h4 mb-0 fw-bold">Itens da compra</h2>
                            <div class="muted" id="cliente">Consumidor final</div>
                        </div>
                        <span class="status-pill status-idle" id="statusVenda">Aguardando venda</span>
                    </div>
                    <div id="itens" class="items-list fs-4">
                        <div class="text-center muted py-5">Aguardando produtos...</div>
                    </div>
                </section>
            </div>

            <div class="col-12 col-xl-4 h-100">
                <section class="summary-panel p-4 d-flex flex-column">
                    <h2 class="h4 mb-3 fw-bold">Resumo</h2>

                    <div class="summary-line">
                        <span class="muted">Subtotal</span>
                        <strong id="subtotal">R$ 0,00</strong>
                    </div>
                    <div class="summary-line">
                        <span class="muted">Desconto</span>
                        <strong class="text-danger" id="desconto">-R$ 0,00</strong>
                    </div>
                    <div class="summary-line">
                        <span class="muted">Forma de pagamento</span>
                        <strong id="pagamento">-</strong>
                    </div>
                    <div class="summary-line">
                        <span class="muted">Status do pagamento</span>
                        <strong id="statusPagamento">Aguardando venda</strong>
                    </div>

                    <div class="pix-card mt-4" id="pix">
                        <div class="text-center">
                            <div class="fw-bold fs-5 mb-1">Pague com Pix</div>
                            <div class="small text-muted mb-3" id="pixStatus">Aguardando pagamento...</div>
                            <img id="pixImg" alt="QR Code Pix">
                        </div>
                        <div class="mt-3">
                            <div class="small fw-semibold mb-1">Pix copia e cola</div>
                            <div class="pix-code" id="pixCode"></div>
                        </div>
                    </div>

                    <div class="mt-auto pt-4 muted small">
                        Acompanhe os itens, descontos, pagamento e QR Pix em tempo real.
                    </div>
                </section>
            </div>
        </div>
    </main>

    <script>
    const canal = 'BroadcastChannel' in window ? new BroadcastChannel('kero-pdv-cliente') : null;
    const dinheiro = valor => 'R$ ' + Number(valor || 0).toLocaleString('pt-BR', { minimumFractionDigits: 2 });

    function atualizarStatus(venda) {
        const statusVenda = document.getElementById('statusVenda');
        const statusPagamento = document.getElementById('statusPagamento');

        if (!venda) {
            statusVenda.className = 'status-pill status-idle';
            statusVenda.textContent = 'Aguardando venda';
            statusPagamento.textContent = 'Aguardando venda';
            return;
        }

        const pago = venda.status === 'PAGA';
        statusVenda.className = 'status-pill ' + (pago ? 'status-paid' : 'status-waiting');
        statusVenda.textContent = venda.numero ? `${venda.numero} - ${venda.status_label}` : venda.status_label;
        statusPagamento.textContent = venda.status_label || 'Aguardando pagamento';
    }

    function atualizarPix(venda) {
        const pix = document.getElementById('pix');

        if (venda?.forma_pagamento === 'PIX' && venda?.pix_qr_code_base64) {
            pix.style.display = 'block';
            document.getElementById('pixImg').src = `data:image/png;base64,${venda.pix_qr_code_base64}`;
            document.getElementById('pixStatus').textContent = venda.status_label || 'Aguardando pagamento';
            document.getElementById('pixCode').textContent = venda.pix_qr_code || '';
            return;
        }

        pix.style.display = 'none';
        document.getElementById('pixImg').removeAttribute('src');
        document.getElementById('pixCode').textContent = '';
    }

    canal?.addEventListener('message', event => {
        const data = event.data || {};
        if (!data.carrinho) return;

        document.getElementById('itens').innerHTML = data.carrinho.length
            ? data.carrinho.map(item => `
                <div class="item-row p-4 d-flex justify-content-between gap-3">
                    <div>
                        <div class="fw-semibold">${item.descricao}</div>
                        <div class="muted fs-6">${item.quantidade} x ${dinheiro(item.preco_unitario)}</div>
                    </div>
                    <strong>${dinheiro(item.quantidade * item.preco_unitario)}</strong>
                </div>
            `).join('')
            : '<div class="text-center muted py-5">Aguardando produtos...</div>';

        document.getElementById('cliente').textContent = data.cliente || 'Consumidor final';
        document.getElementById('subtotal').textContent = dinheiro(data.totais?.subtotal || 0);
        document.getElementById('desconto').textContent = '-' + dinheiro(data.totais?.desconto || 0);
        document.getElementById('total').textContent = dinheiro(data.totais?.total || 0);
        document.getElementById('pagamento').textContent = data.formaPagamento || '-';

        atualizarStatus(data.venda);
        atualizarPix(data.venda);
    });
    </script>
</body>
</html>
