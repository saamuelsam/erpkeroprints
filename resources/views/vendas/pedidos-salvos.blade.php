@extends('layouts.app')

@section('title', 'Pedidos salvos')
@section('page-title', 'Pedidos salvos')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
    <div>
        <h4 class="mb-0 fw-bold">Pedidos salvos</h4>
        <p class="text-muted mb-0 small">Pedidos guardados para editar, receber ou finalizar depois</p>
    </div>
    <div class="d-flex gap-2 flex-wrap">
        <a href="{{ route('vendas.pdv') }}" class="btn btn-primary">
            <i class="fa-solid fa-cash-register me-2"></i>Abrir PDV
        </a>
        <a href="{{ route('vendas.index') }}" class="btn btn-outline-secondary">
            <i class="fa-solid fa-list me-2"></i>Historico
        </a>
    </div>
</div>

<div class="card mb-4">
    <div class="card-body py-3">
        <form method="GET" class="row g-2 align-items-end">
            <div class="col-12 col-md-5">
                <label class="form-label small text-muted mb-1">Buscar pedido</label>
                <input type="text" name="busca" value="{{ request('busca') }}" class="form-control" placeholder="Numero ou cliente...">
            </div>
            <div class="col-auto d-flex gap-2">
                <button class="btn btn-primary" type="submit"><i class="fa-solid fa-search me-1"></i>Filtrar</button>
                <a href="{{ route('vendas.pedidos-salvos') }}" class="btn btn-outline-secondary">Limpar</a>
            </div>
        </form>
    </div>
</div>

<div class="card">
    <div class="card-body p-0">
        @if($vendas->isEmpty())
            <div class="text-center text-muted py-5">
                <i class="fa-regular fa-bookmark fa-2x mb-2"></i>
                <p class="mb-1">Nenhum pedido salvo encontrado.</p>
                <a href="{{ route('vendas.pdv') }}" class="btn btn-sm btn-primary mt-2">Criar pedido no PDV</a>
            </div>
        @else
            <div class="table-responsive">
                <table class="table table-hover mb-0 align-middle">
                    <thead class="table-light">
                        <tr>
                            <th>Pedido</th>
                            <th>Data</th>
                            <th>Cliente</th>
                            <th>Itens</th>
                            <th>Pagamento</th>
                            <th class="text-end">Total</th>
                            <th class="text-end">Acoes</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($vendas as $venda)
                            <tr>
                                <td class="fw-bold">{{ $venda->numero }}</td>
                                <td class="text-muted small">{{ $venda->created_at->format('d/m/Y H:i') }}</td>
                                <td>{{ $venda->cliente_exibicao }}</td>
                                <td>
                                    <span class="fw-semibold">{{ $venda->itens->sum('quantidade') }}</span>
                                    <span class="text-muted small">itens</span>
                                </td>
                                <td>{{ $venda->forma_pagamento_label }}</td>
                                <td class="text-end fw-semibold">R$ {{ number_format($venda->valor_total, 2, ',', '.') }}</td>
                                <td class="text-end">
                                    <div class="d-flex gap-1 justify-content-end flex-wrap">
                                        <a href="{{ route('vendas.editar-pedido', $venda) }}" class="btn btn-sm btn-outline-primary" title="Editar pedido">
                                            <i class="fa-solid fa-pen-to-square"></i>
                                        </a>
                                        <form method="POST" action="{{ route('vendas.receber-pendente', $venda) }}">
                                            @csrf
                                            <button type="submit" class="btn btn-sm btn-outline-success" title="Receber pedido">
                                                <i class="fa-solid fa-check"></i>
                                            </button>
                                        </form>
                                        <a href="{{ route('vendas.comprovante', $venda) }}" class="btn btn-sm btn-outline-secondary" title="Imprimir comprovante">
                                            <i class="fa-solid fa-print"></i>
                                        </a>
                                        <form method="POST" action="{{ route('vendas.destroy', $venda) }}" onsubmit="return confirm('Excluir este pedido salvo?');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-outline-danger" title="Excluir pedido">
                                                <i class="fa-solid fa-trash"></i>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="d-flex justify-content-between align-items-center p-3 border-top">
                <small class="text-muted">{{ $vendas->total() }} pedidos salvos</small>
                {{ $vendas->links() }}
            </div>
        @endif
    </div>
</div>
@endsection
