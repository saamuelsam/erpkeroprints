@extends('layouts.app')

@section('title', $produto->nome)
@section('page-title', 'Produto: ' . $produto->nome)

@section('content')
<div class="d-flex align-items-center mb-4 gap-3">
    <a href="{{ route('produtos.index') }}" class="btn btn-sm btn-outline-secondary">
        <i class="fa-solid fa-arrow-left me-1"></i>Voltar
    </a>
    <h4 class="mb-0 fw-bold">{{ $produto->nome }}</h4>
    @if($produto->isEstoqueBaixo())
        <span class="badge bg-danger">⚠ Estoque Baixo</span>
    @endif
    <div class="ms-auto d-flex gap-2">
        <a href="{{ route('produtos.edit', $produto) }}" class="btn btn-sm btn-outline-primary">
            <i class="fa-solid fa-pen me-1"></i>Editar
        </a>
        <button class="btn btn-sm btn-success" data-bs-toggle="modal" data-bs-target="#modalEntrada">
            <i class="fa-solid fa-arrow-up me-1"></i>Entrada de Estoque
        </button>
        <button class="btn btn-sm btn-outline-warning" data-bs-toggle="modal" data-bs-target="#modalAjusteEstoque">
            <i class="fa-solid fa-scale-balanced me-1"></i>Ajustar Estoque
        </button>
    </div>
</div>

<div class="row g-4 mb-4">
    {{-- Informações --}}
    <div class="col-12 col-md-6 col-xl-3">
        <div class="card text-center h-100">
            <div class="card-body">
                <div class="text-muted small mb-1">Estoque Atual</div>
                <div class="fw-bold fs-1 {{ $produto->isEstoqueBaixo() ? 'text-danger' : 'text-success' }}">
                    {{ number_format($produto->quantidade_estoque, 2, ',', '.') }}
                </div>
                <div class="text-muted">{{ $produto->unidade_medida }}</div>
                <div class="text-muted small mt-1">Mín: {{ number_format($produto->estoque_minimo, 2, ',', '.') }}</div>
            </div>
        </div>
    </div>

    <div class="col-12 col-md-6 col-xl-3">
        <div class="card text-center h-100">
            <div class="card-body">
                <div class="text-muted small mb-1">Preço de Venda</div>
                <div class="fw-bold fs-3 text-primary">R$ {{ number_format($produto->preco_venda, 2, ',', '.') }}</div>
                <div class="text-muted small">Custo: R$ {{ number_format($produto->custo_unitario, 2, ',', '.') }}</div>
            </div>
        </div>
    </div>

    <div class="col-12 col-md-6 col-xl-3">
        <div class="card text-center h-100">
            <div class="card-body">
                <div class="text-muted small mb-1">Lucro Unitário</div>
                <div class="fw-bold fs-3 {{ $produto->lucro_unitario >= 0 ? 'text-success' : 'text-danger' }}">
                    R$ {{ number_format($produto->lucro_unitario, 2, ',', '.') }}
                </div>
                <div class="text-muted small">Margem: {{ number_format($produto->margem_percentual, 1, ',', '.') }}%</div>
            </div>
        </div>
    </div>

    <div class="col-12 col-md-6 col-xl-3">
        <div class="card text-center h-100">
            <div class="card-body">
                <div class="text-muted small mb-1">Valor em Estoque</div>
                <div class="fw-bold fs-3">R$ {{ number_format($produto->valor_em_estoque, 2, ',', '.') }}</div>
                <div class="text-muted small">
                    {{ $produto->categoria->nome }}
                    @if($produto->subcategoria)
                        / {{ $produto->subcategoria->nome }}
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Histórico de Movimentações --}}
<div class="card">
    <div class="card-header"><i class="fa-solid fa-history me-2"></i>Histórico de Movimentações</div>
    <div class="card-body p-0">
        @if($movimentacoes->isEmpty())
            <div class="text-center text-muted py-4">Nenhuma movimentação registrada.</div>
        @else
            <div class="table-responsive">
                <table class="table table-sm mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Data</th>
                            <th>Tipo</th>
                            <th class="text-end">Qtd</th>
                            <th class="text-end">Custo</th>
                            <th class="text-end">Ant.</th>
                            <th class="text-end">Pós.</th>
                            <th>Usuário</th>
                            <th>Motivo</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($movimentacoes as $mov)
                        <tr>
                            <td class="text-muted small">{{ $mov->created_at->format('d/m/y H:i') }}</td>
                            <td>
                                <span class="badge {{ $mov->isEntrada() ? 'bg-success-subtle text-success' : 'bg-danger-subtle text-danger' }}">
                                    {{ $mov->tipo_label }}
                                </span>
                            </td>
                            <td class="text-end fw-semibold {{ $mov->isEntrada() ? 'text-success' : 'text-danger' }}">
                                {{ $mov->isEntrada() ? '+' : '-' }}{{ number_format($mov->quantidade, 2, ',', '.') }}
                            </td>
                            <td class="text-end text-muted small">R$ {{ number_format($mov->custo_unitario_momento, 2, ',', '.') }}</td>
                            <td class="text-end text-muted small">{{ number_format($mov->estoque_anterior, 2, ',', '.') }}</td>
                            <td class="text-end small fw-semibold">{{ number_format($mov->estoque_posterior, 2, ',', '.') }}</td>
                            <td class="text-muted small">{{ $mov->usuario->name }}</td>
                            <td class="text-muted small">{{ $mov->motivo ?: '—' }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="p-3 border-top">{{ $movimentacoes->links() }}</div>
        @endif
    </div>
</div>

{{-- Modal: Entrada de Estoque --}}
<div class="modal fade" id="modalEntrada" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fa-solid fa-arrow-up me-2 text-success"></i>Entrada de Estoque</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" action="{{ route('produtos.entrada-estoque', $produto) }}">
                @csrf
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Tipo de Entrada</label>
                        <select name="tipo" class="form-select">
                            <option value="ENTRADA_COMPRA">Entrada por Compra</option>
                            <option value="ENTRADA_AJUSTE">Ajuste de Estoque</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Quantidade ({{ $produto->unidade_medida }}) *</label>
                        <input type="number" name="quantidade" class="form-control" step="0.001" min="0.001" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Custo Unitário (R$) *</label>
                        <div class="input-group">
                            <span class="input-group-text">R$</span>
                            <input type="number" name="custo_unitario" class="form-control"
                                   value="{{ $produto->custo_unitario }}" step="0.01" min="0" required>
                        </div>
                    </div>
                    <div class="mb-0">
                        <label class="form-label fw-semibold">Motivo / NF</label>
                        <input type="text" name="motivo" class="form-control" placeholder="Ex: NF 12345, Compra fornecedor...">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-success"><i class="fa-solid fa-check me-1"></i>Registrar Entrada</button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- Modal: Ajuste exato de Estoque --}}
<div class="modal fade" id="modalAjusteEstoque" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fa-solid fa-scale-balanced me-2 text-warning"></i>Ajustar Estoque</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" action="{{ route('produtos.ajustar-estoque', $produto) }}">
                @csrf
                @method('PATCH')
                <div class="modal-body">
                    <div class="alert alert-warning small">
                        Use este ajuste apenas para corrigir erro de contagem. O sistema vai registrar a diferença no histórico.
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Estoque correto ({{ $produto->unidade_medida }}) *</label>
                        <input type="number" name="quantidade_estoque" class="form-control"
                               value="{{ $produto->quantidade_estoque }}" step="0.001" min="0" required>
                        <div class="form-text">Estoque atual: {{ number_format($produto->quantidade_estoque, 3, ',', '.') }} {{ $produto->unidade_medida }}</div>
                    </div>
                    <div class="mb-0">
                        <label class="form-label fw-semibold">Motivo</label>
                        <input type="text" name="motivo" class="form-control" placeholder="Ex: Contagem física, lançamento duplicado, erro de compra...">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-warning"><i class="fa-solid fa-check me-1"></i>Salvar Ajuste</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
