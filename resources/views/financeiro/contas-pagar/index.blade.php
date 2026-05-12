@extends('layouts.app')

@section('title', 'Contas a Pagar')
@section('page-title', 'Contas a Pagar')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="mb-0 fw-bold">Contas a Pagar</h4>
        <p class="text-muted mb-0 small">Gerencie despesas e pagamentos pendentes</p>
    </div>
    <a href="{{ route('financeiro.contas-pagar.create') }}" class="btn btn-warning text-dark">
        <i class="fa-solid fa-plus me-2"></i>Nova Conta
    </a>
</div>

{{-- Filtros --}}
<div class="card mb-4">
    <div class="card-body py-3">
        <form method="GET" class="row g-2 align-items-end">
            <div class="col-12 col-md-3">
                <input type="text" name="busca" value="{{ request('busca') }}" class="form-control" placeholder="Buscar descrição...">
            </div>
            <div class="col-12 col-md-2">
                <select name="status" class="form-select">
                    <option value="">Todos status</option>
                    @foreach(\App\Models\ContaPagar::STATUS_LABELS as $val => $info)
                        <option value="{{ $val }}" {{ request('status') === $val ? 'selected' : '' }}>{{ $info['label'] }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-6 col-md-2">
                <input type="date" name="data_inicio" value="{{ request('data_inicio') }}" class="form-control" title="Vencimento de">
            </div>
            <div class="col-6 col-md-2">
                <input type="date" name="data_fim" value="{{ request('data_fim') }}" class="form-control" title="Vencimento até">
            </div>
            <div class="col-auto d-flex gap-2">
                <button type="submit" class="btn btn-primary"><i class="fa-solid fa-search me-1"></i>Filtrar</button>
                <a href="{{ route('financeiro.contas-pagar.index') }}" class="btn btn-outline-secondary">Limpar</a>
            </div>
        </form>
    </div>
</div>

{{-- Cards --}}
<div class="row g-3 mb-4">
    <div class="col-12 col-md-6">
        <div class="card border-start border-warning border-4">
            <div class="card-body py-3 d-flex align-items-center gap-3">
                <div class="rounded-circle d-flex align-items-center justify-content-center" style="width:48px;height:48px;background:rgba(245,158,11,0.1)">
                    <i class="fa-solid fa-clock text-warning fs-5"></i>
                </div>
                <div>
                    <div class="text-muted small">Em aberto</div>
                    <div class="fw-bold fs-4 text-warning">R$ {{ number_format($totalAberto, 2, ',', '.') }}</div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-12 col-md-6">
        <div class="card border-start border-danger border-4">
            <div class="card-body py-3 d-flex align-items-center gap-3">
                <div class="rounded-circle d-flex align-items-center justify-content-center" style="width:48px;height:48px;background:rgba(239,68,68,0.1)">
                    <i class="fa-solid fa-triangle-exclamation text-danger fs-5"></i>
                </div>
                <div>
                    <div class="text-muted small">Vencidas</div>
                    <div class="fw-bold fs-4 text-danger">R$ {{ number_format($totalVencido, 2, ',', '.') }}</div>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Tabela --}}
<div class="card">
    <div class="card-body p-0">
        @if($contas->isEmpty())
            <div class="text-center text-muted py-5">
                <i class="fa-solid fa-file-invoice-dollar fa-2x mb-2"></i>
                <p>Nenhuma conta a pagar encontrada.</p>
            </div>
        @else
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Fornecedor</th>
                            <th>Descrição</th>
                            <th>Categoria</th>
                            <th>Vencimento</th>
                            <th class="text-end">Valor</th>
                            <th>Status</th>
                            <th class="text-end">Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($contas as $conta)
                        <tr class="{{ $conta->is_vencida ? 'table-danger' : '' }}">
                            <td class="fw-semibold">{{ $conta->fornecedor->nome ?? '—' }}</td>
                            <td>{{ $conta->descricao }}</td>
                            <td class="small">{{ $conta->categoria_label }}</td>
                            <td class="small {{ $conta->is_vencida ? 'text-danger fw-bold' : 'text-muted' }}">
                                {{ $conta->data_vencimento->format('d/m/Y') }}
                                @if($conta->is_vencida)
                                    <i class="fa-solid fa-circle-exclamation ms-1"></i>
                                @endif
                            </td>
                            <td class="text-end fw-semibold text-danger">R$ {{ number_format($conta->valor, 2, ',', '.') }}</td>
                            <td><span class="badge bg-{{ $conta->status_badge }}">{{ $conta->status_label }}</span></td>
                            <td class="text-end">
                                <div class="d-flex gap-1 justify-content-end">
                                    @if($conta->status === 'ABERTA')
                                        <form method="POST" action="{{ route('financeiro.contas-pagar.baixar', $conta) }}"
                                              onsubmit="return confirm('Confirmar pagamento de R$ {{ number_format($conta->valor, 2, ',', '.') }}?')">
                                            @csrf
                                            <input type="hidden" name="forma_pagamento" value="Pix">
                                            <button class="btn btn-sm btn-success" title="Registrar pagamento">
                                                <i class="fa-solid fa-check me-1"></i>Pagar
                                            </button>
                                        </form>
                                        <a href="{{ route('financeiro.contas-pagar.edit', $conta) }}" class="btn btn-sm btn-outline-secondary" title="Editar">
                                            <i class="fa-solid fa-pen"></i>
                                        </a>
                                    @endif
                                    @if($conta->status !== 'PAGA')
                                        <form method="POST" action="{{ route('financeiro.contas-pagar.destroy', $conta) }}" onsubmit="return confirm('Cancelar esta conta?')">
                                            @csrf @method('DELETE')
                                            <button class="btn btn-sm btn-outline-danger" title="Cancelar"><i class="fa-solid fa-ban"></i></button>
                                        </form>
                                    @endif
                                </div>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="d-flex justify-content-between align-items-center p-3 border-top">
                <small class="text-muted">{{ $contas->total() }} contas</small>
                {{ $contas->links() }}
            </div>
        @endif
    </div>
</div>
@endsection
