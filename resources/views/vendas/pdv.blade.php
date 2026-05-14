@extends('layouts.app')

@section('title', 'PDV')
@section('page-title', 'PDV')

@push('styles')
<style>
    .pdv-shell { display: grid; grid-template-columns: minmax(0, 1fr) 380px; gap: 16px; }
    .pdv-cart { max-height: calc(100vh - 330px); overflow-y: auto; }
    .pdv-total { font-size: 2.4rem; line-height: 1; }
    .pdv-scan-input { font-size: 1.2rem; height: 54px; }
    .pdv-item-active { background: #FFFBEA; }
    .pix-box img { max-width: 260px; width: 100%; }
    @media (max-width: 992px) { .pdv-shell { grid-template-columns: 1fr; } }
</style>
@endpush

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
    <div>
        <h4 class="mb-0 fw-bold">Venda rápida</h4>
        <p class="text-muted mb-0 small">Leitor de código, carrinho e pagamento em uma tela</p>
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

<div class="pdv-shell">
    <div class="d-flex flex-column gap-3">
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

    <div class="d-flex flex-column gap-3">
        <div class="card">
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

                <div class="mb-3">
                    <label class="form-label fw-semibold">E-mail para Pix</label>
                    <input type="email" id="payerEmail" class="form-control" placeholder="cliente@email.com">
                </div>

                <div class="mb-3">
                    <label class="form-label fw-semibold">Desconto (R$)</label>
                    <input type="number" id="desconto" class="form-control" min="0" step="0.01" value="0">
                </div>

                <div class="border-top pt-3">
                    <div class="d-flex justify-content-between text-muted"><span>Subtotal</span><span id="subtotalDisplay">R$ 0,00</span></div>
                    <div class="d-flex justify-content-between text-danger"><span>Desconto</span><span id="descontoDisplay">-R$ 0,00</span></div>
                    <div class="d-flex justify-content-between align-items-end mt-2">
                        <span class="fw-bold">Total</span>
                        <span class="fw-bold text-success pdv-total" id="totalDisplay">R$ 0,00</span>
                    </div>
                </div>

                <button type="button" id="btnFinalizar" class="btn btn-success btn-lg w-100 mt-3">
                    <i class="fa-solid fa-check me-2"></i>Finalizar venda
                </button>
            </div>
        </div>

        <div class="card pix-box" id="pixBox" style="display:none">
            <div class="card-header"><i class="fa-brands fa-pix me-2"></i>Pix Mercado Pago</div>
            <div class="card-body text-center">
                <div class="fw-semibold mb-2" id="pixStatus">Aguardando pagamento...</div>
                <img id="pixQrImage" alt="QR Code Pix" class="mx-auto mb-3">
                <textarea id="pixCopiaCola" class="form-control small" rows="4" readonly></textarea>
                <button type="button" class="btn btn-outline-primary w-100 mt-2" id="btnConsultarPix">
                    <i class="fa-solid fa-rotate me-1"></i>Consultar pagamento
                </button>
                <button type="button" class="btn btn-success w-100 mt-2" id="btnConfirmarPixManual" style="display:none">
                    <i class="fa-solid fa-check me-1"></i>Confirmar Pix manual
                </button>
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
                    Confirme somente depois de conferir no aplicativo do banco que o valor caiu na chave Pix cadastrada.
                </div>
                <div class="alert alert-danger small d-none" id="pixManualErro"></div>
                <div class="mb-3">
                    <label class="form-label fw-semibold">Nome de quem pagou</label>
                    <input type="text" class="form-control" id="pixConfirmacaoPagador" name="pix_confirmacao_pagador" maxlength="150" required>
                </div>
                <div class="mb-3">
                    <label class="form-label fw-semibold">Codigo/autenticacao do comprovante</label>
                    <input type="text" class="form-control" id="pixConfirmacaoReferencia" name="pix_confirmacao_referencia" minlength="4" maxlength="120" required>
                </div>
                <div class="mb-3">
                    <label class="form-label fw-semibold">Observacao</label>
                    <textarea class="form-control" id="pixConfirmacaoObservacao" name="pix_confirmacao_observacao" rows="2" maxlength="500" placeholder="Ex.: conferido no app do banco, valor e horario batem."></textarea>
                </div>
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" id="pixConfirmouExtrato" name="confirmou_extrato" value="1" required>
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
const formaPagamento = document.getElementById('formaPagamento');
const clienteId = document.getElementById('clienteId');
const payerEmail = document.getElementById('payerEmail');
const pixManualForm = document.getElementById('pixManualForm');
const pixManualModalEl = document.getElementById('pixManualModal');
const pixManualModal = pixManualModalEl ? new bootstrap.Modal(pixManualModalEl) : null;
const pixManualErro = document.getElementById('pixManualErro');

function dinheiro(valor) {
    return 'R$ ' + Number(valor || 0).toLocaleString('pt-BR', { minimumFractionDigits: 2 });
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
    const desconto = Number(descontoInput.value || 0);
    return { subtotal, desconto, total: Math.max(0, subtotal - desconto) };
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
    document.getElementById('totalDisplay').textContent = dinheiro(total.total);

    publicarCliente();
}

function publicarCliente(extra = {}) {
    const selectedCliente = clienteId.options[clienteId.selectedIndex];

    canalCliente?.postMessage({
        type: 'pdv-update',
        carrinho,
        totais: totais(),
        desconto: Number(descontoInput.value || 0),
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
    window.buscaTimer = setTimeout(() => buscarProdutos(q).then(renderResultados), 250);
});

scanInput.addEventListener('keydown', event => {
    if (event.key !== 'Enter') return;
    event.preventDefault();
    const q = normalizarLeitura(scanInput.value);
    if (!q) return;
    buscarProdutos(q, true)
        .then(produtos => produtos.length ? produtos : buscarProdutos(q))
        .then(produtos => {
            if (produtos.length === 1) adicionarProduto(produtos[0]);
            else renderResultados(produtos);
        });
});

document.getElementById('btnBuscarProduto').addEventListener('click', () => {
    buscarProdutos(scanInput.value).then(renderResultados);
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

[descontoInput, formaPagamento].forEach(el => el.addEventListener('input', renderCarrinho));
[descontoInput, formaPagamento].forEach(el => el.addEventListener('change', renderCarrinho));

document.getElementById('btnFinalizar').addEventListener('click', () => {
    if (!carrinho.length) {
        alert('Adicione pelo menos um produto.');
        return;
    }

    const payload = {
        cliente_id: clienteId.value || null,
        forma_pagamento: formaPagamento.value,
        desconto: Number(descontoInput.value || 0),
        payer_email: payerEmail.value || null,
        itens: carrinho.map(item => ({
            produto_id: item.produto_id,
            descricao: item.descricao,
            quantidade: item.quantidade,
            preco_unitario: item.preco_unitario,
        })),
    };

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

        if (vendaAtual.forma_pagamento === 'PIX') {
            iniciarConsultaPix();
        } else {
            alert(data.message);
            novaVenda();
        }
    })
    .catch(error => alert(error.message));
});

function mostrarPix(venda) {
    document.getElementById('pixBox').style.display = venda.forma_pagamento === 'PIX' ? 'block' : 'none';
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
            alert('Pagamento aprovado. Venda finalizada!');
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

    if (!pixManualForm.checkValidity()) {
        pixManualForm.reportValidity();
        return;
    }

    const payload = {
        pix_confirmacao_pagador: document.getElementById('pixConfirmacaoPagador').value.trim(),
        pix_confirmacao_referencia: document.getElementById('pixConfirmacaoReferencia').value.trim(),
        pix_confirmacao_observacao: document.getElementById('pixConfirmacaoObservacao').value.trim(),
        confirmou_extrato: document.getElementById('pixConfirmouExtrato').checked ? '1' : '',
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
        alert(data.message);
        novaVenda();
    })
    .catch(error => {
        if (pixManualErro) {
            pixManualErro.textContent = error.message;
            pixManualErro.classList.remove('d-none');
        } else {
            alert(error.message);
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
