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
@endsection
