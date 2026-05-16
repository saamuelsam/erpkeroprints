@extends('layouts.app')

@section('title', 'Categorias')
@section('page-title', 'Categorias')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="mb-0 fw-bold">Categorias</h4>
        <p class="text-muted mb-0 small">Organize seus produtos por categoria</p>
    </div>
    <a href="{{ route('categorias.create') }}" class="btn btn-primary">
        <i class="fa-solid fa-plus me-2"></i>Nova Categoria
    </a>
</div>

{{-- Filtro --}}
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
        @if($categorias->isEmpty())
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
                            <th>Descrição</th>
                            <th class="text-center">Produtos</th>
                            <th class="text-center">Status</th>
                            <th class="text-end">Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($categorias as $categoria)
                        <tr>
                            <td class="fw-semibold">
                                <button class="btn btn-sm btn-link text-decoration-none px-0" type="button"
                                        data-bs-toggle="collapse" data-bs-target="#subcategorias{{ $categoria->id }}"
                                        aria-expanded="false" aria-controls="subcategorias{{ $categoria->id }}">
                                    <i class="fa-solid fa-chevron-down me-2"></i>{{ $categoria->nome }}
                                </button>
                            </td>
                            <td class="text-muted">{{ $categoria->descricao ?: '—' }}</td>
                            <td class="text-center">
                                <span class="badge bg-primary-subtle text-primary rounded-pill">{{ $categoria->produtos_count }}</span>
                            </td>
                            <td class="text-center">
                                @if($categoria->ativo)
                                    <span class="badge bg-success-subtle text-success">Ativa</span>
                                @else
                                    <span class="badge bg-secondary-subtle text-secondary">Inativa</span>
                                @endif
                            </td>
                            <td class="text-end">
                                <div class="d-flex gap-1 justify-content-end">
                                    <a href="{{ route('categorias.edit', $categoria) }}" class="btn btn-sm btn-outline-secondary" title="Editar">
                                        <i class="fa-solid fa-pen"></i>
                                    </a>
                                    @if($categoria->produtos_count == 0)
                                    <form method="POST" action="{{ route('categorias.destroy', $categoria) }}"
                                          onsubmit="return confirm('Confirmar exclusão de {{ $categoria->nome }}?')">
                                        @csrf @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-outline-danger" title="Excluir">
                                            <i class="fa-solid fa-trash"></i>
                                        </button>
                                    </form>
                                    @endif
                                </div>
                            </td>
                        </tr>
                        <tr class="collapse bg-light" id="subcategorias{{ $categoria->id }}">
                            <td colspan="5" class="p-0">
                                <div class="p-3">
                                    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
                                        <div>
                                            <div class="fw-semibold">Subcategorias de {{ $categoria->nome }}</div>
                                            <div class="text-muted small">Organize linhas como grafica, papelaria, tecnologia ou designer grafico.</div>
                                        </div>
                                    </div>

                                    <form method="POST" action="{{ route('categorias.subcategorias.store', $categoria) }}" class="row g-2 align-items-end mb-3">
                                        @csrf
                                        <div class="col-12 col-md-4">
                                            <label class="form-label small fw-semibold">Nova subcategoria</label>
                                            <input type="text" name="nome" class="form-control" placeholder="Ex.: Papelaria" required>
                                        </div>
                                        <div class="col-12 col-md-5">
                                            <label class="form-label small fw-semibold">Descricao</label>
                                            <input type="text" name="descricao" class="form-control" placeholder="Opcional">
                                        </div>
                                        <div class="col-auto">
                                            <input type="hidden" name="ativo" value="1">
                                            <button class="btn btn-primary"><i class="fa-solid fa-plus me-1"></i>Adicionar</button>
                                        </div>
                                    </form>

                                    @if($categoria->subcategorias->isEmpty())
                                        <div class="text-muted small">Nenhuma subcategoria cadastrada.</div>
                                    @else
                                        <div class="table-responsive">
                                            <table class="table table-sm align-middle mb-0">
                                                <thead>
                                                    <tr>
                                                        <th>Nome</th>
                                                        <th>Descricao</th>
                                                        <th class="text-center">Produtos</th>
                                                        <th class="text-center">Status</th>
                                                        <th class="text-end">Acoes</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    @foreach($categoria->subcategorias as $subcategoria)
                                                    <tr>
                                                        <td>
                                                            <input type="text" name="nome" value="{{ $subcategoria->nome }}"
                                                                   form="subcategoriaForm{{ $subcategoria->id }}" class="form-control form-control-sm" required>
                                                        </td>
                                                        <td>
                                                            <input type="text" name="descricao" value="{{ $subcategoria->descricao }}"
                                                                   form="subcategoriaForm{{ $subcategoria->id }}" class="form-control form-control-sm">
                                                        </td>
                                                        <td class="text-center">
                                                            <span class="badge bg-primary-subtle text-primary rounded-pill">{{ $subcategoria->produtos_count }}</span>
                                                        </td>
                                                        <td class="text-center">
                                                            <div class="form-check form-switch d-inline-flex">
                                                                <input type="hidden" name="ativo" value="0" form="subcategoriaForm{{ $subcategoria->id }}">
                                                                <input class="form-check-input" type="checkbox" name="ativo" value="1"
                                                                       form="subcategoriaForm{{ $subcategoria->id }}" {{ $subcategoria->ativo ? 'checked' : '' }}>
                                                            </div>
                                                        </td>
                                                        <td class="text-end">
                                                            <div class="d-flex gap-1 justify-content-end">
                                                                <form id="subcategoriaForm{{ $subcategoria->id }}" method="POST" action="{{ route('categorias.subcategorias.update', [$categoria, $subcategoria]) }}">
                                                                    @csrf @method('PUT')
                                                                </form>
                                                                <button class="btn btn-sm btn-outline-secondary" title="Salvar" form="subcategoriaForm{{ $subcategoria->id }}">
                                                                    <i class="fa-solid fa-floppy-disk"></i>
                                                                </button>
                                                                @if($subcategoria->produtos_count === 0)
                                                                <form method="POST" action="{{ route('categorias.subcategorias.destroy', [$categoria, $subcategoria]) }}"
                                                                      onsubmit="return confirm('Confirmar exclusao de {{ $subcategoria->nome }}?')">
                                                                    @csrf @method('DELETE')
                                                                    <button class="btn btn-sm btn-outline-danger" title="Excluir">
                                                                        <i class="fa-solid fa-trash"></i>
                                                                    </button>
                                                                </form>
                                                                @endif
                                                            </div>
                                                        </td>
                                                    </tr>
                                                    @endforeach
                                                </tbody>
                                            </table>
                                        </div>
                                    @endif
                                </div>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="d-flex justify-content-between align-items-center p-3 border-top">
                <small class="text-muted">{{ $categorias->total() }} categorias</small>
                {{ $categorias->links() }}
            </div>
        @endif
    </div>
</div>
@endsection
