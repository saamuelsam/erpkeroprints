@extends('layouts.app')

@section('title', 'Produtos')
@section('page-title', 'Produtos')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="mb-0 fw-bold">Produtos</h4>
        <p class="text-muted mb-0 small">
            Catálogo completo
            @if($totalEstoqueBaixo > 0)
                — <span class="text-danger fw-semibold">⚠ {{ $totalEstoqueBaixo }} com estoque baixo</span>
            @endif
        </p>
    </div>
    <a href="{{ route('produtos.create') }}" class="btn btn-primary">
        <i class="fa-solid fa-plus me-2"></i>Novo Produto
    </a>
</div>

{{-- Filtros --}}
<div class="card mb-4">
    <div class="card-body py-3">
        <form method="GET" class="row g-2 align-items-end" id="produtoFiltroForm">
            <div class="col-12 col-md-4">
                <input type="text" name="busca" value="{{ request('busca') }}" class="form-control" id="produtoBuscaInput" placeholder="Nome, código, código de barras...">
            </div>
            <div class="col-12 col-md-3">
                <select name="categoria_id" class="form-select">
                    <option value="">Todas as categorias</option>
                    @foreach($categorias as $cat)
                        <option value="{{ $cat->id }}" {{ request('categoria_id') == $cat->id ? 'selected' : '' }}>
                            {{ $cat->nome }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="col-auto">
                <select name="apenas_ativos" class="form-select">
                    <option value="1" {{ request('apenas_ativos', '1') === '1' ? 'selected' : '' }}>Somente ativos</option>
                    <option value="0" {{ request('apenas_ativos') === '0' ? 'selected' : '' }}>Todos</option>
                </select>
            </div>
            <div class="col-auto">
                <div class="form-check mt-1">
                    <input class="form-check-input" type="checkbox" name="estoque_baixo" value="1" id="estBaixo"
                           {{ request('estoque_baixo') ? 'checked' : '' }}>
                    <label class="form-check-label text-danger fw-semibold small" for="estBaixo">
                        ⚠ Estoque baixo
                    </label>
                </div>
            </div>
            <div class="col-auto d-flex gap-2">
                <button type="submit" class="btn btn-primary"><i class="fa-solid fa-search me-1"></i>Filtrar</button>
                <a href="{{ route('produtos.index') }}" class="btn btn-outline-secondary">Limpar</a>
            </div>
        </form>
    </div>
</div>

<div id="produtosResultado">
<div class="card">
    <div class="card-body p-0">
        @if($produtos->isEmpty())
            <div class="text-center text-muted py-5">
                <i class="fa-solid fa-box-open fa-2x mb-2"></i>
                <p>Nenhum produto encontrado.</p>
                <a href="{{ route('produtos.create') }}" class="btn btn-primary btn-sm">Cadastrar primeiro produto</a>
            </div>
        @else
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Produto</th>
                            <th>Categoria</th>
                            <th class="text-end">Estoque</th>
                            <th class="text-end">Custo</th>
                            <th class="text-end">Venda</th>
                            <th class="text-end">Margem</th>
                            <th class="text-center">Status</th>
                            <th class="text-end">Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($produtos as $produto)
                        <tr class="{{ $produto->isEstoqueBaixo() ? 'table-warning' : '' }}">
                            <td>
                                <div class="fw-semibold">
                                    {{ $produto->isEstoqueBaixo() ? '⚠ ' : '' }}
                                    <a href="{{ route('produtos.show', $produto) }}" class="text-decoration-none text-dark">
                                        {{ $produto->nome }}
                                    </a>
                                </div>
                                @if($produto->codigo_interno)
                                    <small class="text-muted">Cód: {{ $produto->codigo_interno }}</small>
                                @endif
                            </td>
                                <td class="text-muted small">
                                    <div>{{ $produto->categoria->nome }}</div>
                                    @if($produto->subcategoria)
                                        <div class="text-body-secondary">{{ $produto->subcategoria->nome }}</div>
                                    @endif
                                </td>
                            <td class="text-end fw-semibold {{ $produto->isEstoqueBaixo() ? 'text-danger' : '' }}">
                                {{ number_format($produto->quantidade_estoque, 2, ',', '.') }}
                                <small class="text-muted">{{ $produto->unidade_medida }}</small>
                            </td>
                            <td class="text-end text-muted">R$ {{ number_format($produto->custo_unitario, 2, ',', '.') }}</td>
                            <td class="text-end fw-semibold">R$ {{ number_format($produto->preco_venda, 2, ',', '.') }}</td>
                            <td class="text-end">
                                <span class="badge {{ $produto->margem_percentual >= 20 ? 'bg-success-subtle text-success' : 'bg-warning-subtle text-warning' }}">
                                    {{ number_format($produto->margem_percentual, 1, ',', '.') }}%
                                </span>
                            </td>
                            <td class="text-center">
                                @if($produto->ativo)
                                    <span class="badge bg-success-subtle text-success">Ativo</span>
                                @else
                                    <span class="badge bg-secondary-subtle text-secondary">Inativo</span>
                                @endif
                            </td>
                            <td class="text-end">
                                <div class="d-flex gap-1 justify-content-end">
                                    <a href="{{ route('produtos.show', $produto) }}" class="btn btn-sm btn-outline-primary" title="Detalhes">
                                        <i class="fa-solid fa-eye"></i>
                                    </a>
                                    <a href="{{ route('produtos.edit', $produto) }}" class="btn btn-sm btn-outline-secondary" title="Editar">
                                        <i class="fa-solid fa-pen"></i>
                                    </a>
                                </div>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="d-flex justify-content-between align-items-center p-3 border-top">
                <small class="text-muted">Exibindo {{ $produtos->firstItem() }}–{{ $produtos->lastItem() }} de {{ $produtos->total() }} produtos</small>
                {{ $produtos->links() }}
            </div>
        @endif
    </div>
</div>
</div>
@endsection

@push('scripts')
<script>
const produtoFiltroForm = document.getElementById('produtoFiltroForm');
const produtoBuscaInput = document.getElementById('produtoBuscaInput');
const produtosResultado = document.getElementById('produtosResultado');
let produtoFiltroTimer;
let produtoBuscaAbortController;

function montarUrlProdutoFiltro(urlDestino = null) {
    const url = urlDestino ? new URL(urlDestino, window.location.origin) : new URL(window.location.href);

    if (!urlDestino) {
        const params = new URLSearchParams(new FormData(produtoFiltroForm));
        url.search = params.toString();
    }

    return url;
}

async function buscarProdutosAoVivo(urlDestino = null) {
    if (!produtoFiltroForm || !produtosResultado) return;

    produtoBuscaAbortController?.abort();
    produtoBuscaAbortController = new AbortController();
    const url = montarUrlProdutoFiltro(urlDestino);

    produtosResultado.classList.add('opacity-50');

    try {
        const response = await fetch(url, {
            headers: { 'X-Requested-With': 'XMLHttpRequest' },
            signal: produtoBuscaAbortController.signal,
        });
        const html = await response.text();
        const doc = new DOMParser().parseFromString(html, 'text/html');
        const novoResultado = doc.getElementById('produtosResultado');

        if (!response.ok || !novoResultado) {
            throw new Error('Nao foi possivel atualizar os produtos.');
        }

        produtosResultado.innerHTML = novoResultado.innerHTML;
        window.history.replaceState({}, '', url);

        if (/^\d{6,}$/.test(produtoBuscaInput.value.trim())) {
            produtoBuscaInput.select();
        }
    } catch (error) {
        if (error.name !== 'AbortError') {
            console.error(error);
        }
    } finally {
        produtosResultado.classList.remove('opacity-50');
    }
}

produtoBuscaInput?.addEventListener('input', () => {
    clearTimeout(produtoFiltroTimer);
    produtoFiltroTimer = setTimeout(() => buscarProdutosAoVivo(), 350);
});

produtoBuscaInput?.addEventListener('keydown', event => {
    if (event.key !== 'Enter') return;
    event.preventDefault();
    clearTimeout(produtoFiltroTimer);
    buscarProdutosAoVivo().then(() => {
        produtoBuscaInput.select();
    });
});

produtoBuscaInput?.addEventListener('focus', () => {
    if (produtoBuscaInput.value.trim()) {
        produtoBuscaInput.select();
    }
});

produtoFiltroForm?.addEventListener('submit', event => {
    event.preventDefault();
    buscarProdutosAoVivo();
});

produtoFiltroForm?.querySelectorAll('select, input[type="checkbox"]').forEach(campo => {
    campo.addEventListener('change', () => buscarProdutosAoVivo());
});

produtosResultado?.addEventListener('click', event => {
    const link = event.target.closest('.pagination a');
    if (!link) return;

    event.preventDefault();
    buscarProdutosAoVivo(link.href);
});
</script>
@endpush
