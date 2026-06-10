@extends('layouts.app')

@section('title', 'Vendas')
@section('page-title', 'Vendas')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="mb-0 fw-bold">Vendas</h4>
        <p class="text-muted mb-0 small">Histórico das vendas do PDV</p>
    </div>
    <a href="{{ route('vendas.pdv') }}" class="btn btn-primary">
        <i class="fa-solid fa-cash-register me-2"></i>Abrir PDV
    </a>
</div>

<div class="card mb-4">
    <div class="card-body py-3">
        <form method="GET" class="row g-2 align-items-end">
            <div class="col-12 col-md-4">
                <input type="text" name="busca" value="{{ request('busca') }}" class="form-control" placeholder="Número ou cliente...">
            </div>
            <div class="col-12 col-md-3">
                <select name="status" class="form-select">
                    <option value="">Todos os status</option>
                    @foreach($statusOpcoes as $status => $info)
                        <option value="{{ $status }}" {{ request('status') === $status ? 'selected' : '' }}>{{ $info['label'] }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-auto d-flex gap-2">
                <button class="btn btn-primary" type="submit"><i class="fa-solid fa-search me-1"></i>Filtrar</button>
                <a href="{{ route('vendas.index') }}" class="btn btn-outline-secondary">Limpar</a>
            </div>
        </form>
    </div>
</div>

<div class="card">
    <div class="card-body p-0">
        @if($vendas->isEmpty())
            <div class="text-center text-muted py-5">
                <i class="fa-solid fa-cart-shopping fa-2x mb-2"></i>
                <p>Nenhuma venda encontrada.</p>
            </div>
        @else
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Venda</th>
                            <th>Data</th>
                            <th>Cliente</th>
                            <th>Pagamento</th>
                            <th>Status</th>
                            <th class="text-end">Total</th>
                            <th class="text-end">Troco</th>
                            <th class="text-end">Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($vendas as $venda)
                            <tr>
                                <td class="fw-bold">
                                    {{ $venda->numero }}
                                    @if($venda->ordemServico)
                                        <a href="{{ route('ordens-servico.show', $venda->ordemServico) }}" class="badge bg-info-subtle text-info text-decoration-none ms-1">
                                            {{ $venda->ordemServico->numero_os }}
                                        </a>
                                    @endif
                                </td>
                                <td class="text-muted small">{{ $venda->created_at->format('d/m/Y H:i') }}</td>
                                <td>{{ $venda->cliente_exibicao }}</td>
                                <td>{{ $venda->forma_pagamento_label }}</td>
                                <td><span class="badge bg-{{ $venda->status_badge }}">{{ $venda->status_label }}</span></td>
                                <td class="text-end fw-semibold">R$ {{ number_format($venda->valor_total, 2, ',', '.') }}</td>
                                <td class="text-end text-muted small">
                                    @if($venda->forma_pagamento === 'DINHEIRO')
                                        R$ {{ number_format($venda->troco, 2, ',', '.') }}
                                    @else
                                        —
                                    @endif
                                </td>
                                <td class="text-end">
                                    <a href="{{ route('vendas.comprovante', $venda) }}" class="btn btn-sm btn-outline-secondary" title="Imprimir comprovante">
                                        <i class="fa-solid fa-print"></i>
                                    </a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="d-flex justify-content-between align-items-center p-3 border-top">
                <small class="text-muted">{{ $vendas->total() }} vendas</small>
                {{ $vendas->links() }}
            </div>
        @endif
    </div>
</div>
@endsection
