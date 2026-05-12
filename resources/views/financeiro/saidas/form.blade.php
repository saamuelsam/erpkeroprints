@extends('layouts.app')

@section('title', $saida->exists ? 'Editar Saída' : 'Nova Saída')
@section('page-title', $saida->exists ? 'Editar Saída' : 'Nova Saída')

@section('content')
<div class="d-flex align-items-center mb-4 gap-3">
    <a href="{{ route('financeiro.saidas.index') }}" class="btn btn-sm btn-outline-secondary">
        <i class="fa-solid fa-arrow-left me-1"></i>Voltar
    </a>
    <h4 class="mb-0 fw-bold">{{ $saida->exists ? 'Editar Saída' : 'Nova Saída Financeira' }}</h4>
</div>

<form method="POST" action="{{ $saida->exists ? route('financeiro.saidas.update', $saida) : route('financeiro.saidas.store') }}">
    @csrf
    @if($saida->exists) @method('PUT') @endif

    <div class="row g-4">
        <div class="col-12 col-lg-8">
            <div class="card">
                <div class="card-header"><i class="fa-solid fa-arrow-trend-down me-2 text-danger"></i>Dados da Saída</div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-12 col-md-4">
                            <label class="form-label fw-semibold">Data <span class="text-danger">*</span></label>
                            <input type="date" name="data" value="{{ old('data', $saida->data?->format('Y-m-d') ?? now()->format('Y-m-d')) }}"
                                   class="form-control @error('data') is-invalid @enderror" required>
                            @error('data') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

                        <div class="col-12 col-md-4">
                            <label class="form-label fw-semibold">Categoria <span class="text-danger">*</span></label>
                            <select name="categoria" class="form-select @error('categoria') is-invalid @enderror" required>
                                <option value="">Selecione...</option>
                                @foreach($categorias as $val => $label)
                                    <option value="{{ $val }}" {{ old('categoria', $saida->categoria) === $val ? 'selected' : '' }}>{{ $label }}</option>
                                @endforeach
                            </select>
                            @error('categoria') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

                        <div class="col-12 col-md-4">
                            <label class="form-label fw-semibold">Forma de Pagamento</label>
                            <select name="forma_pagamento" class="form-select">
                                <option value="">Selecione...</option>
                                @foreach($formasPagamento as $fp)
                                    <option value="{{ $fp }}" {{ old('forma_pagamento', $saida->forma_pagamento) === $fp ? 'selected' : '' }}>{{ $fp }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="col-12">
                            <label class="form-label fw-semibold">Descrição <span class="text-danger">*</span></label>
                            <input type="text" name="descricao" value="{{ old('descricao', $saida->descricao) }}"
                                   class="form-control @error('descricao') is-invalid @enderror"
                                   placeholder="Ex: Compra de papel A4 500 folhas" required>
                            @error('descricao') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

                        <div class="col-12 col-md-6">
                            <label class="form-label fw-semibold">Fornecedor (opcional)</label>
                            <select name="fornecedor_id" class="form-select" id="fornecedor-select">
                                <option value="">Nenhum</option>
                                @foreach($fornecedores as $f)
                                    <option value="{{ $f->id }}" {{ old('fornecedor_id', $saida->fornecedor_id) == $f->id ? 'selected' : '' }}>{{ $f->nome }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="col-12 col-md-6">
                            <label class="form-label fw-semibold">Nome do fornecedor (avulso)</label>
                            <input type="text" name="fornecedor_nome" value="{{ old('fornecedor_nome', $saida->fornecedor_nome) }}"
                                   class="form-control" placeholder="Se não estiver cadastrado...">
                        </div>

                        <div class="col-12 col-md-6">
                            <label class="form-label fw-semibold">Status</label>
                            <select name="status" class="form-select">
                                @foreach(\App\Models\FinanceiroSaida::STATUS_LABELS as $val => $info)
                                    <option value="{{ $val }}" {{ old('status', $saida->status ?? 'CONFIRMADA') === $val ? 'selected' : '' }}>{{ $info['label'] }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="col-12">
                            <label class="form-label fw-semibold">Observações</label>
                            <textarea name="observacoes" rows="3" class="form-control"
                                      placeholder="Anotações adicionais...">{{ old('observacoes', $saida->observacoes) }}</textarea>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-12 col-lg-4">
            <div class="card mb-4">
                <div class="card-header"><i class="fa-solid fa-dollar-sign me-2"></i>Valor</div>
                <div class="card-body">
                    <label class="form-label fw-semibold">Valor (R$) <span class="text-danger">*</span></label>
                    <div class="input-group input-group-lg">
                        <span class="input-group-text">R$</span>
                        <input type="number" name="valor" value="{{ old('valor', $saida->valor ?? '') }}"
                               class="form-control @error('valor') is-invalid @enderror"
                               step="0.01" min="0.01" placeholder="0,00" required>
                    </div>
                    @error('valor') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                </div>
            </div>

            <div class="d-grid gap-2">
                <button type="submit" class="btn btn-danger btn-lg">
                    <i class="fa-solid fa-floppy-disk me-2"></i>
                    {{ $saida->exists ? 'Salvar alterações' : 'Registrar Saída' }}
                </button>
                <a href="{{ route('financeiro.saidas.index') }}" class="btn btn-outline-secondary">Cancelar</a>
            </div>
        </div>
    </div>
</form>
@endsection
