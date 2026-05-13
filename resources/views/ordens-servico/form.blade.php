@extends('layouts.app')

@section('title', $os->exists ? 'Editar OS ' . $os->numero_os : 'Nova Ordem de Serviço')
@section('page-title', $os->exists ? 'Editar OS' : 'Nova Ordem de Serviço')

@section('content')
<div class="d-flex align-items-center mb-4 gap-3">
    <a href="{{ route('ordens-servico.index') }}" class="btn btn-sm btn-outline-secondary">
        <i class="fa-solid fa-arrow-left me-1"></i>Voltar
    </a>
    <h4 class="mb-0 fw-bold">{{ $os->exists ? 'Editar OS #' . $os->numero_os : 'Nova Ordem de Serviço' }}</h4>
</div>

<form method="POST" action="{{ $os->exists ? route('ordens-servico.update', $os) : route('ordens-servico.store') }}" id="form-os">
    @csrf
    @if($os->exists) @method('PUT') @endif

    <div class="row g-4">
        {{-- Coluna Principal --}}
        <div class="col-12 col-lg-8">
            {{-- Dados da OS --}}
            <div class="card mb-4">
                <div class="card-header"><i class="fa-solid fa-clipboard me-2"></i>Dados da Ordem de Serviço</div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-12 col-md-8">
                            <label class="form-label fw-semibold">Cliente <span class="text-danger">*</span></label>
                            <select name="cliente_id" class="form-select @error('cliente_id') is-invalid @enderror">
                                <option value="">Selecione o cliente...</option>
                                @foreach($clientes as $c)
                                    <option value="{{ $c->id }}" {{ old('cliente_id', $os->cliente_id) == $c->id ? 'selected' : '' }}>
                                        {{ $c->nome }}
                                    </option>
                                @endforeach
                            </select>
                            @error('cliente_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

                        <div class="col-12 col-md-4">
                            <label class="form-label fw-semibold">Previsão de Entrega</label>
                            <input type="date" name="data_prevista_entrega"
                                   value="{{ old('data_prevista_entrega', $os->data_prevista_entrega?->format('Y-m-d')) }}"
                                   class="form-control @error('data_prevista_entrega') is-invalid @enderror">
                            @error('data_prevista_entrega') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

                        <div class="col-12">
                            <label class="form-label fw-semibold">Descrição do Serviço <span class="text-danger">*</span></label>
                            <textarea name="descricao_servico" rows="4"
                                      class="form-control @error('descricao_servico') is-invalid @enderror"
                                      placeholder="Descreva detalhadamente o serviço a ser realizado...">{{ old('descricao_servico', $os->descricao_servico) }}</textarea>
                            @error('descricao_servico') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

                        <div class="col-12 col-md-6">
                            <label class="form-label fw-semibold">Observações Internas</label>
                            <textarea name="observacoes_internas" rows="2" class="form-control"
                                      placeholder="Anotações internas (não visível ao cliente)">{{ old('observacoes_internas', $os->observacoes_internas) }}</textarea>
                        </div>

                        <div class="col-12 col-md-6">
                            <label class="form-label fw-semibold">Observações para o Cliente</label>
                            <textarea name="observacoes_cliente" rows="2" class="form-control"
                                      placeholder="Aparece no comprovante do cliente">{{ old('observacoes_cliente', $os->observacoes_cliente) }}</textarea>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Itens / Materiais --}}
            <div class="card mb-4">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <span><i class="fa-solid fa-list me-2"></i>Itens / Materiais</span>
                    <button type="button" class="btn btn-sm btn-outline-primary" id="btn-add-item">
                        <i class="fa-solid fa-plus me-1"></i>Adicionar Item
                    </button>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table mb-0" id="tabela-itens">
                            <thead class="table-light">
                                <tr>
                                    <th style="width:35%">Produto / Descrição</th>
                                    <th style="width:10%">Qtd</th>
                                    <th style="width:15%">Custo Unit.</th>
                                    <th style="width:15%">Preço Unit.</th>
                                    <th style="width:15%">Total</th>
                                    <th style="width:10%"></th>
                                </tr>
                            </thead>
                            <tbody id="itens-body">
                                {{-- Itens existentes na edição --}}
                                @foreach(old('itens', $os->exists ? $os->itens->toArray() : []) as $i => $item)
                                <tr class="item-row">
                                    <td>
                                        <input type="hidden" name="itens[{{ $i }}][produto_id]" value="{{ $item['produto_id'] ?? '' }}" class="item-produto-id">
                                        <div class="fw-semibold small">{{ $item['descricao_item'] ?? 'Item avulso' }}</div>
                                        <input type="text" name="itens[{{ $i }}][descricao_item]"
                                               value="{{ $item['descricao_item'] ?? '' }}"
                                               class="form-control form-control-sm mt-1"
                                               placeholder="Descrição...">
                                    </td>
                                    <td><input type="number" name="itens[{{ $i }}][quantidade]" value="{{ $item['quantidade'] ?? 1 }}"
                                               class="form-control form-control-sm item-qtd" step="0.001" min="0.001"></td>
                                    <td><input type="number" name="itens[{{ $i }}][custo_unitario]" value="{{ $item['custo_unitario'] ?? 0 }}"
                                               class="form-control form-control-sm item-custo" step="0.01" min="0" readonly></td>
                                    <td><input type="number" name="itens[{{ $i }}][preco_unitario]" value="{{ $item['preco_unitario'] ?? 0 }}"
                                               class="form-control form-control-sm item-preco" step="0.01" min="0"></td>
                                    <td><input type="text" class="form-control form-control-sm item-total" readonly
                                               value="{{ number_format(($item['quantidade'] ?? 1) * ($item['preco_unitario'] ?? 0), 2, '.', '') }}"></td>
                                    <td><button type="button" class="btn btn-sm btn-outline-danger btn-remove-item"><i class="fa-solid fa-trash"></i></button></td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>

                {{-- Busca de produto via API --}}
                <div class="card-footer bg-light">
                    <div class="row g-2 align-items-center">
                        <div class="col">
                            <div class="input-group input-group-sm">
                                <span class="input-group-text"><i class="fa-solid fa-barcode"></i></span>
                                <input type="text" id="busca-produto" class="form-control"
                                       placeholder="Digite, cole ou leia o QR/código do produto...">
                            </div>
                            <div id="resultado-busca" class="list-group position-absolute" style="z-index:1000;min-width:300px;display:none;"></div>
                        </div>
                        <div class="col-auto">
                            <button type="button" class="btn btn-sm btn-outline-secondary" id="btn-add-avulso">
                                <i class="fa-solid fa-plus me-1"></i>Item avulso
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Sidebar --}}
        <div class="col-12 col-lg-4">
            {{-- Financeiro --}}
            <div class="card mb-4">
                <div class="card-header"><i class="fa-solid fa-dollar-sign me-2"></i>Valores</div>
                <div class="card-body">
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Valor do Serviço (R$) <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <span class="input-group-text">R$</span>
                            <input type="number" name="valor_servico" id="valor_servico"
                                   value="{{ old('valor_servico', $os->valor_servico ?? 0) }}"
                                   class="form-control @error('valor_servico') is-invalid @enderror"
                                   step="0.01" min="0">
                        </div>
                        @error('valor_servico') <div class="text-danger small">{{ $message }}</div> @enderror
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Custos Adicionais (R$)</label>
                        <div class="input-group">
                            <span class="input-group-text">R$</span>
                            <input type="number" name="custos_adicionais" id="custos_adicionais"
                                   value="{{ old('custos_adicionais', $os->custos_adicionais ?? 0) }}"
                                   class="form-control" step="0.01" min="0">
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Desconto (R$)</label>
                        <div class="input-group">
                            <span class="input-group-text">R$</span>
                            <input type="number" name="desconto" id="desconto"
                                   value="{{ old('desconto', $os->desconto ?? 0) }}"
                                   class="form-control" step="0.01" min="0">
                        </div>
                    </div>

                    <div class="alert alert-primary py-2 px-3">
                        <div class="d-flex justify-content-between small"><span>Serviço:</span><span id="res-servico">R$ 0,00</span></div>
                        <div class="d-flex justify-content-between small"><span>Adicionais:</span><span id="res-adicionais">R$ 0,00</span></div>
                        <div class="d-flex justify-content-between small text-danger"><span>Desconto:</span><span id="res-desconto">-R$ 0,00</span></div>
                        <hr class="my-1">
                        <div class="d-flex justify-content-between fw-bold"><span>Total:</span><span id="res-total">R$ 0,00</span></div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Forma de Pagamento</label>
                        <select name="forma_pagamento" class="form-select">
                            <option value="">Selecione...</option>
                            @foreach($formasPagamento as $fp)
                                <option value="{{ $fp }}" {{ old('forma_pagamento', $os->forma_pagamento) === $fp ? 'selected' : '' }}>{{ $fp }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
            </div>

            {{-- Status (somente edição) --}}
            @if($os->exists)
            <div class="card mb-4">
                <div class="card-header"><i class="fa-solid fa-circle-dot me-2"></i>Status</div>
                <div class="card-body">
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Status da OS</label>
                        <select name="status" class="form-select">
                            @foreach($statusOpcoes as $val => $info)
                                <option value="{{ $val }}" {{ old('status', $os->status) === $val ? 'selected' : '' }}>{{ $info['label'] }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="form-label fw-semibold">Status de Pagamento</label>
                        <select name="status_pagamento" class="form-select">
                            @foreach(\App\Models\OrdemServico::STATUS_PAGAMENTO_LABELS as $val => $info)
                                <option value="{{ $val }}" {{ old('status_pagamento', $os->status_pagamento) === $val ? 'selected' : '' }}>{{ $info['label'] }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
            </div>
            @endif

            <div class="d-grid gap-2">
                <button type="submit" class="btn btn-primary btn-lg">
                    <i class="fa-solid fa-floppy-disk me-2"></i>
                    {{ $os->exists ? 'Salvar alterações' : 'Criar Ordem de Serviço' }}
                </button>
                <a href="{{ route('ordens-servico.index') }}" class="btn btn-outline-secondary">Cancelar</a>
            </div>
        </div>
    </div>
</form>
@endsection

@push('scripts')
<script>
// ─── Controle de índice e busca de produtos via API ─────────────────────────
let itemIdx = {{ $os->exists ? $os->itens->count() : 0 }};
const buscaUrl = '{{ route('api.produtos.buscar') }}';
let buscaTimer;

// ─── Linha de item avulso (sem produto) ──────────────────────────────────────
function novaLinhaAvulsa(idx, produto = null) {
    return `<tr class="item-row">
        <td>
            <input type="hidden" name="itens[${idx}][produto_id]" value="${produto ? produto.id : ''}" class="item-produto-id">
            <div class="fw-semibold small text-muted">${produto ? produto.nome : 'Item avulso'}</div>
            <input type="text" name="itens[${idx}][descricao_item]"
                   value="${produto ? produto.nome : ''}"
                   class="form-control form-control-sm mt-1" placeholder="Descrição...">
        </td>
        <td><input type="number" name="itens[${idx}][quantidade]" value="1"
                   class="form-control form-control-sm item-qtd" step="0.001" min="0.001"></td>
        <td><input type="number" name="itens[${idx}][custo_unitario]" value="${produto ? produto.custo_unitario : '0'}"
                   class="form-control form-control-sm item-custo" step="0.01" min="0" readonly></td>
        <td><input type="number" name="itens[${idx}][preco_unitario]" value="${produto ? produto.preco_venda : '0'}"
                   class="form-control form-control-sm item-preco" step="0.01" min="0"></td>
        <td><input type="text" class="form-control form-control-sm item-total" value="${produto ? parseFloat(produto.preco_venda).toFixed(2) : '0.00'}" readonly></td>
        <td><button type="button" class="btn btn-sm btn-outline-danger btn-remove-item"><i class="fa-solid fa-trash"></i></button></td>
    </tr>`;
}

function adicionarItem(produto = null) {
    document.getElementById('itens-body').insertAdjacentHTML('beforeend', novaLinhaAvulsa(itemIdx++, produto));
    bindItens();
    calcTotal();
}

// ─── Botão adicionar item avulso ──────────────────────────────────────────────
document.getElementById('btn-add-avulso').addEventListener('click', () => adicionarItem(null));
document.getElementById('btn-add-item').addEventListener('click', () => adicionarItem(null));

// ─── Busca de produto via API (debounce 300ms) ────────────────────────────────
const inputBusca   = document.getElementById('busca-produto');
const divResultado = document.getElementById('resultado-busca');

function normalizarLeituraProduto(valor) {
    const texto = (valor || '').trim();
    if (!texto) return '';

    try {
        const dados = JSON.parse(texto);
        return String(dados.codigo_barras || dados.codigo_interno || dados.codigo || dados.sku || texto).trim();
    } catch (e) {}

    try {
        const url = new URL(texto);
        return String(
            url.searchParams.get('codigo_barras') ||
            url.searchParams.get('codigo_interno') ||
            url.searchParams.get('codigo') ||
            url.searchParams.get('sku') ||
            url.pathname.split('/').filter(Boolean).pop() ||
            texto
        ).trim();
    } catch (e) {}

    return texto;
}

function buscarProdutos(q, exact = false) {
    const leitura = normalizarLeituraProduto(q);
    if (!leitura) return Promise.resolve([]);

    const params = new URLSearchParams({ q: leitura });
    if (exact) params.set('exact', '1');

    return fetch(`${buscaUrl}?${params.toString()}`, {
        headers: { 'X-Requested-With': 'XMLHttpRequest' }
    }).then(r => r.json());
}

function selecionarProduto(produto) {
    adicionarItem(produto);
    inputBusca.value = '';
    divResultado.style.display = 'none';
    inputBusca.focus();
}

inputBusca.addEventListener('input', () => {
    clearTimeout(buscaTimer);
    const q = normalizarLeituraProduto(inputBusca.value);
    if (q.length < 2) { divResultado.style.display = 'none'; return; }

    buscaTimer = setTimeout(() => {
        fetch(`${buscaUrl}?q=${encodeURIComponent(q)}`, {
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        })
        .then(r => r.json())
        .then(produtos => {
            divResultado.innerHTML = '';
            if (!produtos.length) {
                divResultado.innerHTML = '<a class="list-group-item list-group-item-action text-muted small">Nenhum produto encontrado.</a>';
            } else {
                produtos.forEach(p => {
                    const a = document.createElement('a');
                    a.className = 'list-group-item list-group-item-action';
                    a.innerHTML = `<div class="fw-semibold small">${p.nome}</div>
                        <small class="text-muted">Cód: ${p.codigo_interno || '—'} | Estoque: ${p.quantidade_estoque} ${p.unidade_medida} | R$ ${parseFloat(p.preco_venda).toFixed(2).replace('.',',')}</small>`;
                    a.addEventListener('click', () => {
                        adicionarItem(p);
                        inputBusca.value = '';
                        divResultado.style.display = 'none';
                    });
                    divResultado.appendChild(a);
                });
            }
            divResultado.style.display = 'block';
        })
        .catch(() => { divResultado.style.display = 'none'; });
    }, 300);
});

inputBusca.addEventListener('keydown', (e) => {
    if (e.key !== 'Enter') return;

    e.preventDefault();
    clearTimeout(buscaTimer);

    const q = normalizarLeituraProduto(inputBusca.value);
    if (!q) return;

    buscarProdutos(q, true)
        .then(produtos => produtos.length ? produtos : buscarProdutos(q))
        .then(produtos => {
            if (produtos.length === 1) {
                selecionarProduto(produtos[0]);
                return;
            }

            divResultado.innerHTML = '';
            if (!produtos.length) {
                divResultado.innerHTML = '<a class="list-group-item list-group-item-action text-muted small">Nenhum produto encontrado.</a>';
            } else {
                produtos.forEach(p => {
                    const a = document.createElement('a');
                    a.className = 'list-group-item list-group-item-action';
                    a.innerHTML = `<div class="fw-semibold small">${p.nome}</div>
                        <small class="text-muted">Cod: ${p.codigo_interno || '-'} | Estoque: ${p.quantidade_estoque} ${p.unidade_medida} | R$ ${parseFloat(p.preco_venda).toFixed(2).replace('.',',')}</small>`;
                    a.addEventListener('click', () => selecionarProduto(p));
                    divResultado.appendChild(a);
                });
            }
            divResultado.style.display = 'block';
        })
        .catch(() => { divResultado.style.display = 'none'; });
});

// Fecha resultado ao clicar fora
document.addEventListener('click', (e) => {
    if (!inputBusca.contains(e.target) && !divResultado.contains(e.target)) {
        divResultado.style.display = 'none';
    }
});

// ─── Eventos de cálculo nas linhas de item ────────────────────────────────────
function bindItens() {
    document.querySelectorAll('.item-qtd, .item-preco').forEach(inp => {
        inp.oninput = function() {
            calcLinhTotal(this.closest('tr'));
            calcTotal();
        };
    });

    document.querySelectorAll('.btn-remove-item').forEach(btn => {
        btn.onclick = function() {
            this.closest('tr').remove();
            calcTotal();
        };
    });
}

function calcLinhTotal(row) {
    const qtd   = parseFloat(row.querySelector('.item-qtd').value) || 0;
    const preco = parseFloat(row.querySelector('.item-preco').value) || 0;
    row.querySelector('.item-total').value = (qtd * preco).toFixed(2);
}

function fmt(val) {
    return 'R$ ' + parseFloat(val || 0).toLocaleString('pt-BR', { minimumFractionDigits: 2 });
}

function calcTotal() {
    const servico    = parseFloat(document.getElementById('valor_servico').value) || 0;
    const adicionais = parseFloat(document.getElementById('custos_adicionais').value) || 0;
    const desconto   = parseFloat(document.getElementById('desconto').value) || 0;
    const total      = Math.max(0, servico + adicionais - desconto);

    document.getElementById('res-servico').textContent    = fmt(servico);
    document.getElementById('res-adicionais').textContent = fmt(adicionais);
    document.getElementById('res-desconto').textContent   = '-' + fmt(desconto);
    document.getElementById('res-total').textContent      = fmt(total);
}

// ─── Inicialização ────────────────────────────────────────────────────────────
['valor_servico','custos_adicionais','desconto'].forEach(id => {
    document.getElementById(id)?.addEventListener('input', calcTotal);
});

bindItens();
calcTotal();
document.querySelectorAll('.item-row').forEach(calcLinhTotal);
</script>
@endpush
