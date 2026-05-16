@extends('layouts.app')

@section('title', $categoria->exists ? 'Editar Categoria' : 'Nova Categoria')
@section('page-title', $categoria->exists ? 'Editar Categoria' : 'Nova Categoria')

@section('content')
<div class="d-flex align-items-center mb-4 gap-3">
    <a href="{{ route('categorias.index') }}" class="btn btn-sm btn-outline-secondary">
        <i class="fa-solid fa-arrow-left me-1"></i>Voltar
    </a>
    <h4 class="mb-0 fw-bold">{{ $categoria->exists ? 'Editar Categoria' : 'Nova Categoria' }}</h4>
</div>

<div class="row justify-content-center">
    <div class="col-12 col-lg-6">
        <div class="card">
            <div class="card-header"><i class="fa-solid fa-tag me-2"></i>Dados da Categoria</div>
            <form method="POST" action="{{ $categoria->exists ? route('categorias.update', $categoria) : route('categorias.store') }}">
                @csrf
                @if($categoria->exists) @method('PUT') @endif

                <div class="card-body">
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Nome <span class="text-danger">*</span></label>
                        <input type="text" name="nome" value="{{ old('nome', $categoria->nome) }}"
                               class="form-control @error('nome') is-invalid @enderror"
                               placeholder="Ex: Papelaria, Gráfica, Lonas...">
                        @error('nome') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Categoria pai</label>
                        <select name="parent_id" class="form-select">
                            <option value="">Nenhuma, categoria principal</option>
                            @foreach($categoriasPai as $categoriaPai)
                                <option value="{{ $categoriaPai->id }}"
                                    {{ (string) old('parent_id', $categoria->parent_id ?? request('parent_id')) === (string) $categoriaPai->id ? 'selected' : '' }}>
                                    {{ $categoriaPai->nome_completo }}
                                </option>
                            @endforeach
                        </select>
                        <div class="form-text">Escolha uma categoria pai para criar quantos niveis internos quiser.</div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Descrição</label>
                        <textarea name="descricao" class="form-control" rows="3"
                                  placeholder="Descrição opcional da categoria...">{{ old('descricao', $categoria->descricao) }}</textarea>
                    </div>

                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" name="ativo" value="1" id="ativo"
                               {{ old('ativo', $categoria->ativo ?? true) ? 'checked' : '' }}>
                        <label class="form-check-label" for="ativo">Categoria ativa</label>
                    </div>
                </div>
                <div class="card-footer d-flex gap-2">
                    <button type="submit" class="btn btn-primary">
                        <i class="fa-solid fa-floppy-disk me-2"></i>
                        {{ $categoria->exists ? 'Salvar alterações' : 'Criar categoria' }}
                    </button>
                    <a href="{{ route('categorias.index') }}" class="btn btn-outline-secondary">Cancelar</a>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
