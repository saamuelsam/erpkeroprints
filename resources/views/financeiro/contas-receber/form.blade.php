@extends('layouts.app')

@section('title', $conta->exists ? 'Editar Conta a Receber' : 'Nova Conta a Receber')
@section('page-title', $conta->exists ? 'Editar Conta a Receber' : 'Nova Conta a Receber')

@section('content')
<div class="d-flex align-items-center mb-4 gap-3">
    <a href="{{ route('financeiro.contas-receber.index') }}" class="btn btn-sm btn-outline-secondary">
        <i class="fa-solid fa-arrow-left me-1"></i>Voltar
    </a>
    <h4 class="mb-0 fw-bold">{{ $conta->exists ? 'Editar Conta a Receber' : 'Nova Conta a Receber' }}</h4>
</div>

<form method="POST" action="{{ $conta->exists ? route('financeiro.contas-receber.update', $conta) : route('financeiro.contas-receber.store') }}">
    @csrf
    @if($conta->exists) @method('PUT') @endif

    <div class="row g-4">
        <div class="col-12 col-lg-8">
            <div class="card">
                <div class="card-header"><i class="fa-solid fa-hand-holding-dollar me-2 text-info"></i>Dados da Conta</div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-12 col-md-6">
                            <label class="form-label fw-semibold">Cliente <span class="text-danger">*</span></label>
                            <select name="cliente_id" class="form-select @error('cliente_id') is-invalid @enderror" required>
                                <option value="">Selecione o cliente...</option>
                                @foreach($clientes as $c)
                                    <option value="{{ $c->id }}" {{ old('cliente_id', $conta->cliente_id) == $c->id ? 'selected' : '' }}>{{ $c->nome }}</option>
                                @endforeach
                            </select>
                            @error('cliente_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
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
                            <label class="form-label fw-semibold">Descrição <span class="text-danger">*</span></label>
                            <input type="text" name="descricao" value="{{ old('descricao', $conta->descricao) }}"
                                   class="form-control @error('descricao') is-invalid @enderror"
                                   placeholder="Ex: Pagamento da OS #2026-00001" required>
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
                <button type="submit" class="btn btn-primary btn-lg">
                    <i class="fa-solid fa-floppy-disk me-2"></i>
                    {{ $conta->exists ? 'Salvar' : 'Cadastrar Conta' }}
                </button>
                <a href="{{ route('financeiro.contas-receber.index') }}" class="btn btn-outline-secondary">Cancelar</a>
            </div>
        </div>
    </div>
</form>
@endsection
