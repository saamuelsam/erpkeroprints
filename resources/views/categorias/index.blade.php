@extends('layouts.app')

@section('title', 'Categorias')
@section('page-title', 'Categorias')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="mb-0 fw-bold">Categorias</h4>
        <p class="text-muted mb-0 small">Monte uma arvore livre para organizar produtos em qualquer profundidade</p>
    </div>
    <a href="{{ route('categorias.create') }}" class="btn btn-primary">
        <i class="fa-solid fa-plus me-2"></i>Nova Categoria
    </a>
</div>

<div class="card mb-4">
    <div class="card-body py-3">
        <form method="GET" class="row g-2 align-items-end">
            <div class="col-12 col-md-6">
                <input type="text" name="busca" value="{{ request('busca') }}" class="form-control" placeholder="Buscar categoria...">
            </div>
            <div class="col-auto d-flex gap-2">
                <button type="submit" class="btn btn-primary"><i class="fa-solid fa-search me-1"></i>Buscar</button>
                <a href="{{ route('categorias.index') }}" class="btn btn-outline-secondary">Limpar</a>
            </div>
        </form>
    </div>
</div>

<div class="card">
    <div class="card-body p-0">
        @if($categoriasArvore->isEmpty())
            <div class="text-center text-muted py-5">
                <i class="fa-solid fa-tags fa-2x mb-2"></i>
                <p>Nenhuma categoria encontrada.</p>
            </div>
        @else
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Nome</th>
                            <th>Descricao</th>
                            <th class="text-center">Produtos</th>
                            <th class="text-center">Status</th>
                            <th class="text-end">Acoes</th>
                        </tr>
                    </thead>
                    <tbody>
                        @include('categorias._tree_rows', ['categorias' => $categoriasArvore, 'nivel' => 0])
                    </tbody>
                </table>
            </div>
            <div class="p-3 border-top">
                <small class="text-muted">{{ $categorias->count() }} categorias</small>
            </div>
        @endif
    </div>
</div>

<div class="modal fade" id="modalExcluirCategoria" tabindex="-1" aria-labelledby="modalExcluirCategoriaLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header border-0 pb-0">
                <div>
                    <h5 class="modal-title fw-bold" id="modalExcluirCategoriaLabel">Excluir categoria</h5>
                    <div class="text-muted small">Essa acao reorganiza os itens vinculados automaticamente.</div>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
            </div>
            <div class="modal-body">
                <div class="d-flex gap-3 align-items-start">
                    <div class="rounded-circle d-flex align-items-center justify-content-center flex-shrink-0"
                         style="width:42px;height:42px;background:rgba(220,53,69,.12);color:#dc3545;">
                        <i class="fa-solid fa-trash"></i>
                    </div>
                    <div>
                        <div class="fw-semibold mb-1">Deseja excluir <span id="categoriaExcluirNome"></span>?</div>
                        <div class="text-muted small">
                            Produtos dessa categoria serao movidos para a categoria pai ou para <strong>Sem categoria</strong>.
                            Categorias internas sobem um nivel na arvore.
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer border-0 pt-0">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
                <form method="POST" id="formExcluirCategoria">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-danger">
                        <i class="fa-solid fa-trash me-1"></i>Excluir categoria
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
const modalExcluirCategoria = document.getElementById('modalExcluirCategoria');

modalExcluirCategoria?.addEventListener('show.bs.modal', event => {
    const botao = event.relatedTarget;
    document.getElementById('categoriaExcluirNome').textContent = botao.dataset.categoria;
    document.getElementById('formExcluirCategoria').action = botao.dataset.action;
});
</script>
@endpush
