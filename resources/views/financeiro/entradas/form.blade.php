@extends('layouts.app')

@section('title', $entrada->exists ? 'Editar Entrada' : 'Nova Entrada')
@section('page-title', $entrada->exists ? 'Editar Entrada' : 'Nova Entrada')

@section('content')
<div class="d-flex align-items-center mb-4 gap-3">
    <a href="{{ route('financeiro.entradas.index') }}" class="btn btn-sm btn-outline-secondary">
        <i class="fa-solid fa-arrow-left me-1"></i>Voltar
    </a>
    <h4 class="mb-0 fw-bold">{{ $entrada->exists ? 'Editar Entrada' : 'Nova Entrada Financeira' }}</h4>
</div>

<form method="POST" action="{{ $entrada->exists ? route('financeiro.entradas.update', $entrada) : route('financeiro.entradas.store') }}">
    @csrf
    @if($entrada->exists) @method('PUT') @endif

    <div class="row g-4">
        <div class="col-12 col-lg-8">
            <div class="card">
                <div class="card-header"><i class="fa-solid fa-arrow-trend-up me-2 text-success"></i>Dados da Entrada</div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-12 col-md-4">
                            <label class="form-label fw-semibold">Data <span class="text-danger">*</span></label>
                            <input type="date" name="data" value="{{ old('data', $entrada->data?->format('Y-m-d') ?? now()->format('Y-m-d')) }}"
                                   class="form-control @error('data') is-invalid @enderror" required>
                            @error('data') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

                        <div class="col-12 col-md-4">
                            <label class="form-label fw-semibold">Categoria <span class="text-danger">*</span></label>
                            <select name="categoria" class="form-select @error('categoria') is-invalid @enderror" required>
                                <option value="">Selecione...</option>
                                @foreach($categorias as $val => $label)
                                    <option value="{{ $val }}" {{ old('categoria', $entrada->categoria) === $val ? 'selected' : '' }}>{{ $label }}</option>
                                @endforeach
                            </select>
                            @error('categoria') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

                        <div class="col-12 col-md-4">
                            <label class="form-label fw-semibold">Forma de Pagamento</label>
                            <select name="forma_pagamento" class="form-select">
                                <option value="">Selecione...</option>
                                @foreach($formasPagamento as $fp)
                                    <option value="{{ $fp }}" {{ old('forma_pagamento', $entrada->forma_pagamento) === $fp ? 'selected' : '' }}>{{ $fp }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="col-12">
                            <label class="form-label fw-semibold">Descrição <span class="text-danger">*</span></label>
                            <input type="text" name="descricao" value="{{ old('descricao', $entrada->descricao) }}"
                                   class="form-control @error('descricao') is-invalid @enderror"
                                   placeholder="Ex: Pagamento da OS #2026-00001" required>
                            @error('descricao') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

                        <div class="col-12 col-md-6">
                            <label class="form-label fw-semibold">Cliente (opcional)</label>
                            <select name="cliente_id" class="form-select">
                                <option value="">Nenhum</option>
                                @foreach($clientes as $c)
                                    <option value="{{ $c->id }}" {{ old('cliente_id', $entrada->cliente_id) == $c->id ? 'selected' : '' }}>{{ $c->nome }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="col-12 col-md-6">
                            <label class="form-label fw-semibold">Status</label>
                            <select name="status" class="form-select">
                                @foreach(\App\Models\FinanceiroEntrada::STATUS_LABELS as $val => $info)
                                    <option value="{{ $val }}" {{ old('status', $entrada->status ?? 'CONFIRMADA') === $val ? 'selected' : '' }}>{{ $info['label'] }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="col-12">
                            <label class="form-label fw-semibold">Observações</label>
                            <textarea name="observacoes" rows="3" class="form-control"
                                      placeholder="Anotações adicionais...">{{ old('observacoes', $entrada->observacoes) }}</textarea>
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
                        <input type="number" name="valor" value="{{ old('valor', $entrada->valor ?? '') }}"
                               class="form-control @error('valor') is-invalid @enderror"
                               step="0.01" min="0.01" placeholder="0,00" required>
                    </div>
                    @error('valor') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                </div>
            </div>

            <div class="d-grid gap-2">
                <button type="submit" class="btn btn-success btn-lg">
                    <i class="fa-solid fa-floppy-disk me-2"></i>
                    {{ $entrada->exists ? 'Salvar alterações' : 'Registrar Entrada' }}
                </button>
                <a href="{{ route('financeiro.entradas.index') }}" class="btn btn-outline-secondary">Cancelar</a>
            </div>
        </div>
    </div>
</form>
@endsection
