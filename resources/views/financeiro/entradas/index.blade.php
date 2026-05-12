@extends('layouts.app')

@section('title', 'Entradas Financeiras')
@section('page-title', 'Entradas Financeiras')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="mb-0 fw-bold">Entradas Financeiras</h4>
        <p class="text-muted mb-0 small">Receitas e recebimentos do negócio</p>
    </div>
    <a href="{{ route('financeiro.entradas.create') }}" class="btn btn-success">
        <i class="fa-solid fa-plus me-2"></i>Nova Entrada
    </a>
</div>

{{-- Filtros --}}
<div class="card mb-4">
    <div class="card-body py-3">
        <form method="GET" class="row g-2 align-items-end">
            <div class="col-12 col-md-3">
                <input type="text" name="busca" value="{{ request('busca') }}" class="form-control" placeholder="Buscar descrição ou cliente...">
            </div>
            <div class="col-12 col-md-2">
                <select name="categoria" class="form-select">
                    <option value="">Todas categorias</option>
                    @foreach($categorias as $val => $label)
                        <option value="{{ $val }}" {{ request('categoria') === $val ? 'selected' : '' }}>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-6 col-md-2">
                <input type="date" name="data_inicio" value="{{ request('data_inicio') }}" class="form-control" title="Data início">
            </div>
            <div class="col-6 col-md-2">
                <input type="date" name="data_fim" value="{{ request('data_fim') }}" class="form-control" title="Data fim">
            </div>
            <div class="col-auto d-flex gap-2">
                <button type="submit" class="btn btn-primary"><i class="fa-solid fa-search me-1"></i>Filtrar</button>
                <a href="{{ route('financeiro.entradas.index') }}" class="btn btn-outline-secondary">Limpar</a>
            </div>
        </form>
    </div>
</div>

{{-- Card de total --}}
<div class="row mb-4">
    <div class="col-12 col-md-4">
        <div class="card border-start border-success border-4">
            <div class="card-body py-3 d-flex align-items-center gap-3">
                <div class="rounded-circle d-flex align-items-center justify-content-center" style="width:48px;height:48px;background:rgba(34,197,94,0.1)">
                    <i class="fa-solid fa-arrow-trend-up text-success fs-5"></i>
                </div>
                <div>
                    <div class="text-muted small">Total filtrado</div>
                    <div class="fw-bold fs-4 text-success">R$ {{ number_format($totalFiltro, 2, ',', '.') }}</div>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Tabela --}}
<div class="card">
    <div class="card-body p-0">
        @if($entradas->isEmpty())
            <div class="text-center text-muted py-5">
                <i class="fa-solid fa-arrow-trend-up fa-2x mb-2"></i>
                <p>Nenhuma entrada encontrada.</p>
                <a href="{{ route('financeiro.entradas.create') }}" class="btn btn-success btn-sm">Registrar primeira entrada</a>
            </div>
        @else
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Data</th>
                            <th>Descrição</th>
                            <th>Categoria</th>
                            <th>Pagamento</th>
                            <th>Cliente</th>
                            <th class="text-end">Valor</th>
                            <th>Status</th>
                            <th class="text-end">Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($entradas as $entrada)
                        <tr>
                            <td class="text-muted small">{{ $entrada->data->format('d/m/Y') }}</td>
                            <td>
                                {{ $entrada->descricao }}
                                @if($entrada->origem_tipo)
                                    <span class="badge bg-info-subtle text-info ms-1" style="font-size:.65rem">Auto</span>
                                @endif
                            </td>
                            <td class="small">{{ $entrada->categoria_label }}</td>
                            <td class="small text-muted">{{ $entrada->forma_pagamento ?? '—' }}</td>
                            <td class="small">{{ $entrada->cliente->nome ?? '—' }}</td>
                            <td class="text-end fw-semibold text-success">R$ {{ number_format($entrada->valor, 2, ',', '.') }}</td>
                            <td><span class="badge bg-{{ $entrada->status_badge }}">{{ $entrada->status_label }}</span></td>
                            <td class="text-end">
                                @if(!$entrada->origem_tipo)
                                <div class="d-flex gap-1 justify-content-end">
                                    <a href="{{ route('financeiro.entradas.edit', $entrada) }}" class="btn btn-sm btn-outline-secondary" title="Editar">
                                        <i class="fa-solid fa-pen"></i>
                                    </a>
                                    <form method="POST" action="{{ route('financeiro.entradas.destroy', $entrada) }}" onsubmit="return confirm('Tem certeza que deseja excluir esta entrada?')">
                                        @csrf @method('DELETE')
                                        <button class="btn btn-sm btn-outline-danger" title="Excluir"><i class="fa-solid fa-trash"></i></button>
                                    </form>
                                </div>
                                @else
                                    <span class="text-muted small">—</span>
                                @endif
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="d-flex justify-content-between align-items-center p-3 border-top">
                <small class="text-muted">{{ $entradas->total() }} entradas</small>
                {{ $entradas->links() }}
            </div>
        @endif
    </div>
</div>
@endsection
