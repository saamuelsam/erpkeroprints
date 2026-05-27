@extends('layouts.app')

@section('title', 'PDV')
@section('page-title', 'PDV')

@push('styles')
<style>
    .pdv-shell { align-items: start; display: grid; grid-template-columns: minmax(0, 1fr) 390px; gap: 16px; }
    .pdv-main { min-width: 0; }
    .pdv-side { position: sticky; top: 82px; }
    .pdv-payment-card .card-body { max-height: calc(100vh - 142px); overflow-y: auto; }
    .pdv-cart { max-height: calc(100vh - 315px); overflow-y: auto; }
    .pdv-total { font-size: 2.4rem; line-height: 1; }
    .pdv-scan-input { font-size: 1.2rem; height: 54px; }
    .pdv-item-active { background: #FFFBEA; }
    .pix-box {
        background: #F8FAFC;
        border: 1px solid #D9E2EC;
        border-radius: 8px;
        display: none;
        padding: 12px;
    }
    .pix-box img { max-width: 210px; width: 100%; }
    .pix-box textarea { font-size: .78rem; max-height: 84px; resize: none; }
    .pdv-brand-logo { max-height: 56px; width: auto; }
    .cash-panel {
        background: #F8FAFC;
        border: 1px solid #D9E2EC;
        border-radius: 8px;
        padding: 12px;
    }
    .change-display {
        color: #047857;
        font-size: 1.4rem;
        font-weight: 800;
        line-height: 1;
    }
    .change-display.is-missing { color: #DC2626; }
    .mixed-payment-row {
        align-items: end;
        display: grid;
        gap: 8px;
        grid-template-columns: minmax(0, 1.2fr) minmax(90px, .8fr) minmax(90px, .8fr);
    }
    .pdv-summary {
        background: #FFFFFF;
        border-top: 1px solid #E2E8F0;
        bottom: 0;
        padding-top: 12px;
        position: sticky;
    }
    .pdv-actions {
        background: #FFFFFF;
        bottom: 0;
        padding-bottom: 2px;
        position: sticky;
    }
    .pdv-toast-stack {
        position: fixed;
        right: 22px;
        top: 22px;
        z-index: 1080;
        display: grid;
        gap: 10px;
        width: min(360px, calc(100vw - 32px));
    }
    .pdv-toast {
        background: #111827;
        border-left: 5px solid #FFD000;
        border-radius: 10px;
        box-shadow: 0 18px 45px rgba(15, 23, 42, .24);
        color: #fff;
        padding: 14px 16px;
        animation: toastIn .18s ease-out;
    }
    .pdv-toast.success { border-left-color: #22C55E; }
    .pdv-toast.error { border-left-color: #EF4444; }
    .pdv-toast.warning { border-left-color: #F59E0B; }
    .pdv-toast-title { font-weight: 800; margin-bottom: 2px; }
    .pdv-toast-message { color: #CBD5E1; font-size: .9rem; white-space: pre-line; }
    @keyframes toastIn { from { opacity: 0; transform: translateY(-8px); } to { opacity: 1; transform: translateY(0); } }
    @media (max-width: 992px) {
        .pdv-shell { grid-template-columns: 1fr; }
        .pdv-side { position: static; }
        .pdv-payment-card .card-body { max-height: none; }
    }
    @media (max-width: 576px) { .mixed-payment-row { grid-template-columns: 1fr; } }
</style>
@endpush

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
    <div class="d-flex align-items-center gap-3">
        <img src="{{ asset('images/logo-color.png') }}" alt="Kero Prints Gráfica e Papelaria" class="pdv-brand-logo">
        <div>
            <h4 class="mb-0 fw-bold">Venda rápida</h4>
            <p class="text-muted mb-0 small">Leitor de código, carrinho e pagamento em uma tela</p>
        </div>
    </div>
    <div class="d-flex gap-2">
        <a href="{{ route('vendas.index') }}" class="btn btn-outline-secondary">
            <i class="fa-solid fa-list me-1"></i>Histórico
        </a>
        <a href="{{ route('vendas.cliente') }}" target="_blank" class="btn btn-outline-primary" id="btnTelaCliente">
            <i class="fa-solid fa-display me-1"></i>Tela do cliente
        </a>
    </div>
</div>

<div class="pdv-toast-stack" id="pdvToastStack" aria-live="polite" aria-atomic="true"></div>

<div class="pdv-shell">
    <div class="d-flex flex-column gap-3 pdv-main">
        <div class="card">
            <div class="card-body">
                <label class="form-label fw-semibold">Leitura do produto</label>
                <div class="input-group">
                    <span class="input-group-text"><i class="fa-solid fa-barcode"></i></span>
                    <input type="text" id="scanInput" class="form-control pdv-scan-input"
                           placeholder="Passe o leitor, digite código ou busque por nome..." autofocus autocomplete="off">
                    <button class="btn btn-primary" type="button" id="btnBuscarProduto">
                        <i class="fa-solid fa-search"></i>
                    </button>
                </div>
                <div id="resultadoBusca" class="list-group mt-2" style="display:none"></div>
            </div>
        </div>

        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span><i class="fa-solid fa-cart-shopping me-2"></i>Itens da venda</span>
                <button class="btn btn-sm btn-outline-danger" type="button" id="btnLimpar">
                    <i class="fa-solid fa-trash me-1"></i>Limpar
                </button>
            </div>
            <div class="card-body p-0 pdv-cart">
                <table class="table table-hover mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Produto</th>
                            <th class="text-end">Qtd</th>
                            <th class="text-end">Preço</th>
                            <th class="text-end">Total</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody id="cartBody">
                        <tr id="emptyCart">
                            <td colspan="5" class="text-center text-muted py-5">Passe o primeiro produto no leitor.</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="d-flex flex-column gap-3 pdv-side">
        <div class="card pdv-payment-card">
            <div class="card-header"><i class="fa-solid fa-cash-register me-2"></i>Pagamento</div>
            <div class="card-body">
                <div class="mb-3">
                    <label class="form-label fw-semibold">Cliente</label>
                    <select id="clienteId" class="form-select">
                        <option value="">Consumidor final</option>
                        @foreach($clientes as $cliente)
                            <option value="{{ $cliente->id }}" data-email="{{ $cliente->email }}">{{ $cliente->nome }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="mb-3">
                    <label class="form-label fw-semibold">Forma de pagamento</label>
                    <select id="formaPagamento" class="form-select">
                        @foreach($formasPagamento as $value => $label)
                            <option value="{{ $value }}">{{ $label }}</option>
                        @endforeach
                    </select>
                    @unless($mercadoPagoConfigurado)
                        <div class="form-text text-warning">Pix automatico precisa de MERCADO_PAGO_ACCESS_TOKEN no .env.</div>
                    @endunless
                </div>

                <div class="mb-3" id="payerEmailBox">
                    <label class="form-label fw-semibold">E-mail para Pix</label>
                    <input type="email" id="payerEmail" class="form-control" placeholder="cliente@email.com">
                </div>

                <div class="pix-box mb-3" id="pixBox">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <div>
                            <div class="fw-bold"><i class="fa-brands fa-pix me-1"></i>Pix</div>
                            <div class="small text-muted" id="pixStatus">Aguardando pagamento...</div>
                        </div>
                        <button type="button" class="btn btn-sm btn-outline-primary" id="btnConsultarPix">
                            <i class="fa-solid fa-rotate me-1"></i>Consultar
                        </button>
                    </div>
                    <div class="text-center">
                        <img id="pixQrImage" alt="QR Code Pix" class="mx-auto mb-2">
                    </div>
                    <textarea id="pixCopiaCola" class="form-control small" rows="3" readonly></textarea>
                    <button type="button" class="btn btn-success w-100 mt-2" id="btnConfirmarPixManual" style="display:none">
                        <i class="fa-solid fa-check me-1"></i>Confirmar Pix manual
                    </button>
                </div>

                <div class="mb-3 cash-panel" id="cashBox" style="display:none">
                    <label class="form-label fw-semibold">Valor recebido em dinheiro</label>
                    <div class="input-group mb-2">
                        <span class="input-group-text">R$</span>
                        <input type="number" id="valorRecebido" class="form-control form-control-lg" min="0" step="0.01" inputmode="decimal" placeholder="0,00">
                    </div>
                    <div class="d-flex justify-content-between align-items-center">
                        <span class="text-muted small" id="trocoLabel">Troco</span>
                        <span class="change-display" id="trocoDisplay">R$ 0,00</span>
                    </div>
                    <div class="d-flex gap-2 mt-2 flex-wrap">
                        <button type="button" class="btn btn-sm btn-outline-secondary cash-fast" data-cash="exact">Exato</button>
                        <button type="button" class="btn btn-sm btn-outline-secondary cash-fast" data-cash="10">R$ 10</button>
                        <button type="button" class="btn btn-sm btn-outline-secondary cash-fast" data-cash="20">R$ 20</button>
                        <button type="button" class="btn btn-sm btn-outline-secondary cash-fast" data-cash="50">R$ 50</button>
                        <button type="button" class="btn btn-sm btn-outline-secondary cash-fast" data-cash="100">R$ 100</button>
                    </div>
                </div>

                <div class="mb-3 cash-panel" id="mixedBox" style="display:none">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <label class="form-label fw-semibold mb-0">Pagamento misto</label>
                        <button type="button" class="btn btn-sm btn-outline-primary" id="btnAddPagamento">
                            <i class="fa-solid fa-plus me-1"></i>Adicionar
                        </button>
                    </div>
                    <div class="d-grid gap-2" id="pagamentosMistos"></div>
                    <div class="border-top mt-2 pt-2 small">
                        <div class="d-flex justify-content-between"><span class="text-muted">Distribuido</span><strong id="mistoDistribuido">R$ 0,00</strong></div>
                        <div class="d-flex justify-content-between"><span class="text-muted" id="mistoSaldoLabel">Falta distribuir</span><strong id="mistoSaldo">R$ 0,00</strong></div>
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label fw-semibold">Desconto (R$)</label>
                    <input type="number" id="desconto" class="form-control" min="0" step="0.01" value="0">
                </div>

                <div class="pdv-summary">
                    <div class="d-flex justify-content-between text-muted"><span>Subtotal</span><span id="subtotalDisplay">R$ 0,00</span></div>
                    <div class="d-flex justify-content-between text-danger"><span>Desconto</span><span id="descontoDisplay">-R$ 0,00</span></div>
                    <div class="d-flex justify-content-between text-muted"><span>Itens</span><span id="itensDisplay">0</span></div>
                    <div class="d-flex justify-content-between align-items-end mt-2">
                        <span class="fw-bold">Total</span>
                        <span class="fw-bold text-success pdv-total" id="totalDisplay">R$ 0,00</span>
                    </div>
                </div>

                <div class="pdv-actions mt-3">
                    <button type="button" id="btnFinalizar" class="btn btn-success btn-lg w-100">
                        <i class="fa-solid fa-check me-2"></i>Finalizar venda
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="pixManualModal" tabindex="-1" aria-labelledby="pixManualModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <form class="modal-content" id="pixManualForm">
            <div class="modal-header">
                <h5 class="modal-title" id="pixManualModalLabel">Confirmar Pix manual</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
            </div>
            <div class="modal-body">
                <div class="alert alert-warning small">
                    Confirme somente depois de conferir no aplicativo do banco que o valor caiu na chave Pix cadastrada. Os campos abaixo são opcionais.
                </div>
                <div class="alert alert-danger small d-none" id="pixManualErro"></div>
                <div class="mb-3">
                    <label class="form-label fw-semibold">Nome de quem pagou</label>
                    <input type="text" class="form-control" id="pixConfirmacaoPagador" name="pix_confirmacao_pagador" maxlength="150">
                </div>
                <div class="mb-3">
                    <label class="form-label fw-semibold">Codigo/autenticacao do comprovante</label>
                    <input type="text" class="form-control" id="pixConfirmacaoReferencia" name="pix_confirmacao_referencia" minlength="4" maxlength="120">
                </div>
                <div class="mb-3">
                    <label class="form-label fw-semibold">Observacao</label>
                    <textarea class="form-control" id="pixConfirmacaoObservacao" name="pix_confirmacao_observacao" rows="2" maxlength="500" placeholder="Ex.: conferido no app do banco, valor e horario batem."></textarea>
                </div>
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" id="pixConfirmouExtrato" name="confirmou_extrato" value="1">
                    <label class="form-check-label" for="pixConfirmouExtrato">
                        Conferi no extrato/app bancario que o Pix entrou e o valor bate com a venda.
                    </label>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="submit" class="btn btn-success" id="btnSalvarConfirmacaoPix">
                    <i class="fa-solid fa-check me-1"></i>Confirmar pagamento
                </button>
            </div>
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script>
const buscaUrl = '{{ route('api.produtos.buscar') }}';
const vendaStoreUrl = '{{ route('vendas.store') }}';
const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
const canalCliente = 'BroadcastChannel' in window ? new BroadcastChannel('kero-pdv-cliente') : null;
let carrinho = [];
let vendaAtual = null;
let consultaTimer = null;

const scanInput = document.getElementById('scanInput');
const resultadoBusca = document.getElementById('resultadoBusca');
const cartBody = document.getElementById('cartBody');
const descontoInput = document.getElementById('desconto');
const valorRecebidoInput = document.getElementById('valorRecebido');
const cashBox = document.getElementById('cashBox');
const mixedBox = document.getElementById('mixedBox');
const pagamentosMistosEl = document.getElementById('pagamentosMistos');
const payerEmailBox = document.getElementById('payerEmailBox');
const formaPagamento = document.getElementById('formaPagamento');
const clienteId = document.getElementById('clienteId');
const payerEmail = document.getElementById('payerEmail');
const pixManualForm = document.getElementById('pixManualForm');
const pixManualModalEl = document.getElementById('pixManualModal');
const pixManualModal = pixManualModalEl ? new bootstrap.Modal(pixManualModalEl) : null;
const pixManualErro = document.getElementById('pixManualErro');
const pdvToastStack = document.getElementById('pdvToastStack');
let leituraTimer = null;
let leituraEmAndamento = false;
let ultimaLeituraProcessada = { codigo: '', momento: 0 };

function dinheiro(valor) {
    return 'R$ ' + Number(valor || 0).toLocaleString('pt-BR', { minimumFractionDigits: 2 });
}

function notificar(tipo, titulo, mensagem) {
    const toast = document.createElement('div');
    toast.className = `pdv-toast ${tipo || ''}`;
    toast.innerHTML = `<div class="pdv-toast-title">${titulo}</div><div class="pdv-toast-message">${mensagem || ''}</div>`;
    pdvToastStack.appendChild(toast);
    setTimeout(() => {
        toast.style.opacity = '0';
        toast.style.transform = 'translateY(-8px)';
        setTimeout(() => toast.remove(), 220);
    }, 4200);
}

function normalizarLeitura(valor) {
    const texto = (valor || '').trim();
    if (!texto) return '';

    try {
        const dados = JSON.parse(texto);
        return String(dados.codigo_barras || dados.codigo_interno || dados.codigo || dados.sku || texto).trim();
    } catch (e) {}

    try {
        const url = new URL(texto);
        return String(url.searchParams.get('codigo_barras') || url.searchParams.get('codigo') || url.pathname.split('/').filter(Boolean).pop() || texto).trim();
    } catch (e) {}

    return texto;
}

function buscarProdutos(q, exact = false) {
    const leitura = normalizarLeitura(q);
    if (!leitura) return Promise.resolve([]);
    const params = new URLSearchParams({ q: leitura });
    if (exact) params.set('exact', '1');
    return fetch(`${buscaUrl}?${params.toString()}`, { headers: { 'X-Requested-With': 'XMLHttpRequest' } }).then(r => r.json());
}

function adicionarProduto(produto) {
    const existente = carrinho.find(item => item.produto_id === produto.id);
    if (existente) {
        existente.quantidade += 1;
    } else {
        carrinho.push({
            produto_id: produto.id,
            descricao: produto.nome,
            quantidade: 1,
            preco_unitario: Number(produto.preco_venda),
            estoque: Number(produto.quantidade_estoque),
            unidade: produto.unidade_medida,
        });
    }
    scanInput.value = '';
    resultadoBusca.style.display = 'none';
    renderCarrinho();
    scanInput.focus();
    scanInput.select();
    notificar('success', 'Produto adicionado', `${produto.nome} entrou no carrinho.`);
}

function renderResultados(produtos) {
    resultadoBusca.innerHTML = '';
    if (!produtos.length) {
        resultadoBusca.innerHTML = '<button class="list-group-item list-group-item-action text-muted" type="button">Nenhum produto encontrado.</button>';
    } else {
        produtos.forEach(produto => {
            const btn = document.createElement('button');
            btn.type = 'button';
            btn.className = 'list-group-item list-group-item-action';
            btn.innerHTML = `<div class="fw-semibold">${produto.nome}</div>
                <small class="text-muted">Cod: ${produto.codigo_barras || produto.codigo_interno || '-'} | Estoque: ${produto.quantidade_estoque} ${produto.unidade_medida} | ${dinheiro(produto.preco_venda)}</small>`;
            btn.addEventListener('click', () => adicionarProduto(produto));
            resultadoBusca.appendChild(btn);
        });
    }
    resultadoBusca.style.display = 'block';
}

function totais() {
    const subtotal = carrinho.reduce((sum, item) => sum + item.quantidade * item.preco_unitario, 0);
    const desconto = Math.min(Number(descontoInput.value || 0), subtotal);
    const total = Math.max(0, subtotal - desconto);
    const recebido = Number(valorRecebidoInput?.value || 0);
    return {
        subtotal,
        desconto,
        total,
        valor_recebido: formaPagamento.value === 'DINHEIRO' ? recebido : null,
        troco: formaPagamento.value === 'DINHEIRO' ? Math.max(0, recebido - total) : 0,
        falta: formaPagamento.value === 'DINHEIRO' ? Math.max(0, total - recebido) : 0,
    };
}

function pagamentosMistos() {
    return [...pagamentosMistosEl.querySelectorAll('.mixed-payment-row')]
        .map(row => {
            const forma = row.querySelector('.misto-forma').value;
            const valor = Number(row.querySelector('.misto-valor').value || 0);
            const valorRecebido = Number(row.querySelector('.misto-recebido')?.value || valor);

            return {
                forma,
                valor,
                valor_recebido: forma === 'DINHEIRO' ? valorRecebido : null,
            };
        })
        .filter(pagamento => pagamento.forma && pagamento.valor > 0);
}

function totalMisto() {
    return pagamentosMistos().reduce((soma, pagamento) => soma + pagamento.valor, 0);
}

function mistoTemPix() {
    return formaPagamento.value === 'MISTO' && pagamentosMistos().some(pagamento => pagamento.forma === 'PIX');
}

function addPagamentoMisto(forma = 'PIX', valor = 0) {
    const row = document.createElement('div');
    row.className = 'mixed-payment-row';
    row.innerHTML = `
        <div>
            <label class="form-label small text-muted mb-1">Forma</label>
            <select class="form-select form-select-sm misto-forma">
                <option value="PIX">Pix</option>
                <option value="CARTAO_DEBITO">Cartao de Debito</option>
                <option value="CARTAO_CREDITO">Cartao de Credito</option>
                <option value="DINHEIRO">Dinheiro</option>
                <option value="OUTROS">Outros</option>
            </select>
        </div>
        <div>
            <label class="form-label small text-muted mb-1">Valor</label>
            <input type="number" class="form-control form-control-sm misto-valor" min="0" step="0.01" value="${Number(valor || 0).toFixed(2)}">
        </div>
        <div>
            <label class="form-label small text-muted mb-1">Recebido</label>
            <div class="input-group input-group-sm">
                <input type="number" class="form-control misto-recebido" min="0" step="0.01" value="${Number(valor || 0).toFixed(2)}">
                <button class="btn btn-outline-danger btn-remove-misto" type="button" title="Remover"><i class="fa-solid fa-xmark"></i></button>
            </div>
        </div>
    `;
    row.querySelector('.misto-forma').value = forma;
    row.querySelectorAll('input, select').forEach(el => {
        el.addEventListener('input', renderCarrinho);
        el.addEventListener('change', renderCarrinho);
    });
    row.querySelector('.btn-remove-misto').addEventListener('click', () => {
        row.remove();
        renderCarrinho();
    });
    pagamentosMistosEl.appendChild(row);
    atualizarLinhaMista(row);
}

function atualizarLinhaMista(row) {
    const forma = row.querySelector('.misto-forma').value;
    const recebidoInput = row.querySelector('.misto-recebido');
    recebidoInput.disabled = forma !== 'DINHEIRO';
    if (forma !== 'DINHEIRO') {
        recebidoInput.value = row.querySelector('.misto-valor').value || '0.00';
    }
}

function atualizarPagamentoUi() {
    const dinheiroSelecionado = formaPagamento.value === 'DINHEIRO';
    const mistoSelecionado = formaPagamento.value === 'MISTO';
    const pixSelecionado = formaPagamento.value === 'PIX' || mistoTemPix();

    cashBox.style.display = dinheiroSelecionado ? 'block' : 'none';
    mixedBox.style.display = mistoSelecionado ? 'block' : 'none';
    payerEmailBox.style.display = pixSelecionado ? 'block' : 'none';

    const total = totais();
    pagamentosMistosEl.querySelectorAll('.mixed-payment-row').forEach(atualizarLinhaMista);
    const trocoDisplay = document.getElementById('trocoDisplay');
    const trocoLabel = document.getElementById('trocoLabel');
    const falta = total.falta > 0;

    trocoDisplay.classList.toggle('is-missing', falta);
    trocoLabel.textContent = falta ? 'Falta receber' : 'Troco';
    trocoDisplay.textContent = dinheiro(falta ? total.falta : total.troco);

    const distribuido = totalMisto();
    const saldo = total.total - distribuido;
    document.getElementById('mistoDistribuido').textContent = dinheiro(distribuido);
    document.getElementById('mistoSaldoLabel').textContent = saldo >= 0 ? 'Falta distribuir' : 'Excedente';
    document.getElementById('mistoSaldo').textContent = dinheiro(Math.abs(saldo));
    document.getElementById('mistoSaldo').className = Math.abs(saldo) < 0.01 ? 'text-success' : 'text-danger';
}

function renderCarrinho() {
    cartBody.innerHTML = '';
    if (!carrinho.length) {
        cartBody.innerHTML = '<tr id="emptyCart"><td colspan="5" class="text-center text-muted py-5">Passe o primeiro produto no leitor.</td></tr>';
    } else {
        carrinho.forEach((item, index) => {
            const tr = document.createElement('tr');
            tr.className = index === carrinho.length - 1 ? 'pdv-item-active' : '';
            tr.innerHTML = `
                <td>
                    <div class="fw-semibold">${item.descricao}</div>
                    <small class="text-muted">Estoque: ${item.estoque} ${item.unidade}</small>
                </td>
                <td class="text-end" style="width:120px">
                    <input type="number" class="form-control form-control-sm text-end" min="0.001" step="0.001" value="${item.quantidade}">
                </td>
                <td class="text-end">${dinheiro(item.preco_unitario)}</td>
                <td class="text-end fw-semibold">${dinheiro(item.quantidade * item.preco_unitario)}</td>
                <td class="text-end"><button class="btn btn-sm btn-outline-danger" type="button"><i class="fa-solid fa-xmark"></i></button></td>
            `;
            tr.querySelector('input').addEventListener('input', e => {
                item.quantidade = Number(e.target.value || 0);
                renderCarrinho();
            });
            tr.querySelector('button').addEventListener('click', () => {
                carrinho.splice(index, 1);
                renderCarrinho();
            });
            cartBody.appendChild(tr);
        });
    }

    const total = totais();
    document.getElementById('subtotalDisplay').textContent = dinheiro(total.subtotal);
    document.getElementById('descontoDisplay').textContent = '-' + dinheiro(total.desconto);
    document.getElementById('itensDisplay').textContent = carrinho.reduce((sum, item) => sum + Number(item.quantidade || 0), 0).toLocaleString('pt-BR');
    document.getElementById('totalDisplay').textContent = dinheiro(total.total);
    atualizarPagamentoUi();

    publicarCliente();
}

function publicarCliente(extra = {}) {
    const selectedCliente = clienteId.options[clienteId.selectedIndex];
    const totaisAtuais = totais();

    canalCliente?.postMessage({
        type: 'pdv-update',
        carrinho,
        totais: totaisAtuais,
        desconto: Number(descontoInput.value || 0),
        pagamentoDinheiro: {
            valor_recebido: totaisAtuais.valor_recebido,
            troco: totaisAtuais.troco,
            falta: totaisAtuais.falta,
        },
        pagamentosMistos: pagamentosMistos(),
        formaPagamento: formaPagamento.options[formaPagamento.selectedIndex]?.text || '',
        formaPagamentoCodigo: formaPagamento.value,
        cliente: selectedCliente?.value ? selectedCliente.text : 'Consumidor final',
        venda: vendaAtual,
        ...extra,
    });
}

scanInput.addEventListener('input', () => {
    const q = normalizarLeitura(scanInput.value);
    if (q.length < 2) { resultadoBusca.style.display = 'none'; return; }
    clearTimeout(window.buscaTimer);
    clearTimeout(leituraTimer);
    leituraTimer = setTimeout(() => processarLeituraOuBusca(q), 220);
});

scanInput.addEventListener('keydown', event => {
    if (event.key !== 'Enter') return;
    event.preventDefault();
    clearTimeout(leituraTimer);
    processarLeituraOuBusca(scanInput.value, true);
});

document.getElementById('btnBuscarProduto').addEventListener('click', () => {
    clearTimeout(leituraTimer);
    processarLeituraOuBusca(scanInput.value, true);
});

function limparLeitura() {
    scanInput.value = '';
    resultadoBusca.style.display = 'none';
    scanInput.focus();
}

function leituraDuplicada(codigo) {
    const agora = Date.now();
    return ultimaLeituraProcessada.codigo === codigo && agora - ultimaLeituraProcessada.momento < 900;
}

function marcarLeitura(codigo) {
    ultimaLeituraProcessada = { codigo, momento: Date.now() };
}

function processarLeituraOuBusca(valor, acionadoManualmente = false) {
    const q = normalizarLeitura(valor);
    if (!q || leituraEmAndamento || leituraDuplicada(q)) return;

    leituraEmAndamento = true;

    buscarProdutos(q, true)
        .then(produtos => {
            if (produtos.length === 1) {
                marcarLeitura(q);
                adicionarProduto(produtos[0]);
                return null;
            }

            return buscarProdutos(q);
        })
        .then(produtos => {
            if (!produtos) return;

            if (produtos.length === 1 && acionadoManualmente) {
                marcarLeitura(q);
                adicionarProduto(produtos[0]);
                return;
            }

            if (!produtos.length && acionadoManualmente) {
                limparLeitura();
                notificar('warning', 'Produto nao encontrado', `Nenhum produto encontrado para ${q}.`);
                return;
            }

            renderResultados(produtos);
        })
        .catch(() => notificar('error', 'Falha na leitura', 'Não foi possível buscar o produto agora.'))
        .finally(() => {
            leituraEmAndamento = false;
        });
}

canalCliente?.addEventListener('message', event => {
    const data = event.data || {};
    if (data.type !== 'barcode-scan' || !data.codigo) return;
    processarLeituraOuBusca(data.codigo, true);
});

document.getElementById('btnLimpar').addEventListener('click', () => {
    carrinho = [];
    vendaAtual = null;
    document.getElementById('pixBox').style.display = 'none';
    renderCarrinho();
    scanInput.focus();
});

clienteId.addEventListener('change', () => {
    const selected = clienteId.options[clienteId.selectedIndex];
    if (selected?.dataset.email) payerEmail.value = selected.dataset.email;
    publicarCliente();
});

[descontoInput, valorRecebidoInput, formaPagamento].forEach(el => el?.addEventListener('input', renderCarrinho));
[descontoInput, valorRecebidoInput, formaPagamento].forEach(el => el?.addEventListener('change', renderCarrinho));

document.getElementById('btnAddPagamento').addEventListener('click', () => {
    const restante = Math.max(0, totais().total - totalMisto());
    addPagamentoMisto('OUTROS', restante);
    renderCarrinho();
});

formaPagamento.addEventListener('change', () => {
    if (formaPagamento.value === 'MISTO' && pagamentosMistosEl.children.length === 0) {
        const total = totais().total;
        addPagamentoMisto('PIX', total);
        addPagamentoMisto('CARTAO_CREDITO', 0);
        renderCarrinho();
    }
});

document.querySelectorAll('.cash-fast').forEach(botao => {
    botao.addEventListener('click', () => {
        const total = totais().total;
        valorRecebidoInput.value = botao.dataset.cash === 'exact'
            ? total.toFixed(2)
            : Number(botao.dataset.cash).toFixed(2);
        renderCarrinho();
        valorRecebidoInput.focus();
    });
});

document.getElementById('btnFinalizar').addEventListener('click', () => {
    if (!carrinho.length) {
        notificar('warning', 'Carrinho vazio', 'Adicione pelo menos um produto para finalizar.');
        return;
    }

    const totalAtual = totais();

    if (formaPagamento.value === 'DINHEIRO' && totalAtual.falta > 0) {
        valorRecebidoInput.focus();
        notificar('warning', 'Dinheiro insuficiente', `Ainda falta ${dinheiro(totalAtual.falta)} para fechar a venda.`);
        return;
    }

    if (formaPagamento.value === 'MISTO') {
        if (pagamentosMistos().length < 2) {
            notificar('warning', 'Pagamento misto incompleto', 'Informe pelo menos duas formas de pagamento.');
            return;
        }

        if (Math.abs(totalAtual.total - totalMisto()) > 0.009) {
            notificar('warning', 'Valores nao fecham', 'A soma das formas de pagamento precisa bater com o total.');
            return;
        }
    }

    const payload = {
        cliente_id: clienteId.value || null,
        forma_pagamento: formaPagamento.value,
        desconto: Number(descontoInput.value || 0),
        valor_recebido: formaPagamento.value === 'DINHEIRO' ? Number(valorRecebidoInput.value || 0) : null,
        payer_email: payerEmail.value || null,
        itens: carrinho.map(item => ({
            produto_id: item.produto_id,
            descricao: item.descricao,
            quantidade: item.quantidade,
            preco_unitario: item.preco_unitario,
        })),
    };

    if (formaPagamento.value === 'MISTO') {
        payload.pagamentos = pagamentosMistos();
    }

    fetch(vendaStoreUrl, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'Accept': 'application/json',
            'X-CSRF-TOKEN': csrfToken,
        },
        body: JSON.stringify(payload),
    })
    .then(async response => {
        const data = await response.json();
        if (!response.ok) throw new Error(data.message || 'Erro ao finalizar venda.');
        vendaAtual = data.venda;
        mostrarPix(vendaAtual);
        publicarCliente({ type: 'pdv-sale-created' });

        if (vendaAtual.forma_pagamento === 'PIX' || Number(vendaAtual.valor_pix || 0) > 0) {
            iniciarConsultaPix();
        } else {
            notificar('success', 'Venda finalizada', data.message);
            novaVenda();
        }
    })
    .catch(error => notificar('error', 'Erro ao finalizar', error.message));
});

function mostrarPix(venda) {
    document.getElementById('pixBox').style.display = (venda.forma_pagamento === 'PIX' || Number(venda.valor_pix || 0) > 0) ? 'block' : 'none';
    document.getElementById('pixStatus').textContent = venda.status_label;
    document.getElementById('pixCopiaCola').value = venda.pix_qr_code || '';
    document.getElementById('pixQrImage').src = venda.pix_qr_code_base64
        ? `data:image/png;base64,${venda.pix_qr_code_base64}`
        : (venda.pix_qr_code_image_url || '');
    document.getElementById('btnConsultarPix').style.display = venda.pix_manual ? 'none' : 'block';
    document.getElementById('btnConfirmarPixManual').style.display = venda.pix_manual ? 'block' : 'none';
    publicarCliente();
}

function iniciarConsultaPix() {
    clearInterval(consultaTimer);
    consultaTimer = setInterval(consultarPix, 4000);
}

function consultarPix() {
    if (!vendaAtual?.id) return;
    fetch(`/vendas/${vendaAtual.id}/consultar-pagamento`, {
        method: 'POST',
        headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': csrfToken },
    })
    .then(r => r.json())
    .then(data => {
        if (!data.venda) return;
        vendaAtual = data.venda;
        mostrarPix(vendaAtual);
        publicarCliente();
        if (vendaAtual.status === 'PAGA') {
            clearInterval(consultaTimer);
            notificar('success', 'Pagamento aprovado', 'Venda finalizada com sucesso.');
            novaVenda();
        }
    });
}

document.getElementById('btnConsultarPix').addEventListener('click', consultarPix);
document.getElementById('btnConfirmarPixManual').addEventListener('click', () => {
    if (!vendaAtual?.id) return;
    pixManualForm?.reset();
    pixManualErro?.classList.add('d-none');
    pixManualModal?.show();
});

pixManualForm?.addEventListener('submit', event => {
    event.preventDefault();
    if (!vendaAtual?.id) return;

    pixManualErro?.classList.add('d-none');

    const payload = {
        pix_confirmacao_pagador: document.getElementById('pixConfirmacaoPagador').value.trim(),
        pix_confirmacao_referencia: document.getElementById('pixConfirmacaoReferencia').value.trim(),
        pix_confirmacao_observacao: document.getElementById('pixConfirmacaoObservacao').value.trim(),
        confirmou_extrato: document.getElementById('pixConfirmouExtrato').checked ? '1' : '0',
    };

    const btn = document.getElementById('btnSalvarConfirmacaoPix');
    btn.disabled = true;
    btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin me-1"></i>Confirmando...';

    fetch(`/vendas/${vendaAtual.id}/confirmar-manual`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'Accept': 'application/json',
            'X-CSRF-TOKEN': csrfToken,
        },
        body: JSON.stringify(payload),
    })
    .then(async response => {
        const data = await response.json();
        if (!response.ok) {
            const erros = data.errors ? Object.values(data.errors).flat() : [];
            throw new Error(erros.join('\n') || data.message || 'Erro ao confirmar Pix.');
        }
        vendaAtual = data.venda;
        pixManualModal?.hide();
        mostrarPix(vendaAtual);
        publicarCliente();
        notificar('success', 'Pix confirmado', data.message);
        novaVenda();
    })
    .catch(error => {
        if (pixManualErro) {
            pixManualErro.textContent = error.message;
            pixManualErro.classList.remove('d-none');
        } else {
            notificar('error', 'Erro ao confirmar Pix', error.message);
        }
    })
    .finally(() => {
        btn.disabled = false;
        btn.innerHTML = '<i class="fa-solid fa-check me-1"></i>Confirmar pagamento';
    });
});

function novaVenda() {
    carrinho = [];
    vendaAtual = null;
    descontoInput.value = 0;
    valorRecebidoInput.value = '';
    pagamentosMistosEl.innerHTML = '';
    document.getElementById('pixBox').style.display = 'none';
    renderCarrinho();
    scanInput.focus();
}

window.addEventListener('load', () => {
    scanInput.focus();
    renderCarrinho();
    setInterval(() => publicarCliente(), 1000);
});
</script>
@endpush
