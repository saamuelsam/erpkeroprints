@extends('layouts.app')

@section('title', $conta->exists ? 'Editar Conta a Pagar' : 'Nova Conta a Pagar')
@section('page-title', $conta->exists ? 'Editar Conta a Pagar' : 'Nova Conta a Pagar')

@section('content')
<div class="d-flex align-items-center mb-4 gap-3">
    <a href="{{ route('financeiro.contas-pagar.index') }}" class="btn btn-sm btn-outline-secondary">
        <i class="fa-solid fa-arrow-left me-1"></i>Voltar
    </a>
    <h4 class="mb-0 fw-bold">{{ $conta->exists ? 'Editar Conta a Pagar' : 'Nova Conta a Pagar' }}</h4>
</div>

<form method="POST" action="{{ $conta->exists ? route('financeiro.contas-pagar.update', $conta) : route('financeiro.contas-pagar.store') }}">
    @csrf
    @if($conta->exists) @method('PUT') @endif

    <div class="row g-4">
        <div class="col-12 col-lg-8">
            <div class="card">
                <div class="card-header"><i class="fa-solid fa-file-invoice-dollar me-2 text-warning"></i>Dados da Conta</div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-12 col-md-6">
                            <label class="form-label fw-semibold">Fornecedor</label>
                            <select name="fornecedor_id" class="form-select">
                                <option value="">Nenhum cadastrado</option>
                                @foreach($fornecedores as $f)
                                    <option value="{{ $f->id }}" {{ old('fornecedor_id', $conta->fornecedor_id) == $f->id ? 'selected' : '' }}>{{ $f->nome }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="col-12 col-md-6">
                            <label class="form-label fw-semibold">Categoria</label>
                            <select name="categoria" class="form-select">
                                <option value="">Selecione...</option>
                                @foreach($categorias as $val => $label)
                                    <option value="{{ $val }}" {{ old('categoria', $conta->categoria) === $val ? 'selected' : '' }}>{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="col-12">
                            <label class="form-label fw-semibold">Descrição <span class="text-danger">*</span></label>
                            <input type="text" name="descricao" value="{{ old('descricao', $conta->descricao) }}"
                                   class="form-control @error('descricao') is-invalid @enderror"
                                   placeholder="Ex: Aluguel maio 2026" required>
                            @error('descricao') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

                        <div class="col-12 col-md-4">
                            <label class="form-label fw-semibold">Data Emissão <span class="text-danger">*</span></label>
                            <input type="date" name="data_emissao" value="{{ old('data_emissao', $conta->data_emissao?->format('Y-m-d') ?? now()->format('Y-m-d')) }}"
                                   class="form-control @error('data_emissao') is-invalid @enderror" required>
                            @error('data_emissao') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

                        <div class="col-12 col-md-4">
                            <label class="form-label fw-semibold">Vencimento <span class="text-danger">*</span></label>
                            <input type="date" name="data_vencimento" value="{{ old('data_vencimento', $conta->data_vencimento?->format('Y-m-d')) }}"
                                   class="form-control @error('data_vencimento') is-invalid @enderror" required>
                            @error('data_vencimento') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

                        <div class="col-12 col-md-4">
                            <label class="form-label fw-semibold">Valor (R$) <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <span class="input-group-text">R$</span>
                                <input type="number" name="valor" value="{{ old('valor', $conta->valor ?? '') }}"
                                       class="form-control @error('valor') is-invalid @enderror"
                                       step="0.01" min="0.01" required>
                            </div>
                            @error('valor') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                        </div>

                        <div class="col-12 col-md-6">
                            <label class="form-label fw-semibold">Forma de Pagamento</label>
                            <select name="forma_pagamento" class="form-select">
                                <option value="">Selecione...</option>
                                @foreach($formasPagamento as $fp)
                                    <option value="{{ $fp }}" {{ old('forma_pagamento', $conta->forma_pagamento) === $fp ? 'selected' : '' }}>{{ $fp }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="col-12">
                            <label class="form-label fw-semibold">Observações</label>
                            <textarea name="observacoes" rows="3" class="form-control">{{ old('observacoes', $conta->observacoes) }}</textarea>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-12 col-lg-4">
            <div class="d-grid gap-2">
                <button type="submit" class="btn btn-warning btn-lg text-dark">
                    <i class="fa-solid fa-floppy-disk me-2"></i>
                    {{ $conta->exists ? 'Salvar' : 'Cadastrar Conta' }}
                </button>
                <a href="{{ route('financeiro.contas-pagar.index') }}" class="btn btn-outline-secondary">Cancelar</a>
            </div>
        </div>
    </div>
</form>
@endsection
