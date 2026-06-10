@extends('layouts.app')

@section('title', 'Visão geral')
@section('page-title', 'Visão geral')

@push('styles')
<style>
    .dashboard-heading {
        align-items: end;
        display: flex;
        gap: 16px;
        justify-content: space-between;
        margin-bottom: 14px;
    }

    .dashboard-heading h1 {
        font-size: 1.18rem;
        font-weight: 800;
        margin: 0;
    }

    .dashboard-heading p {
        color: #667085;
        font-size: .78rem;
        margin: 3px 0 0;
    }

    .metric-grid {
        display: grid;
        gap: 10px;
        grid-template-columns: repeat(4, minmax(0, 1fr));
        margin-bottom: 16px;
    }

    .metric {
        align-items: center;
        background: #fff;
        border: 1px solid #e4e7ec;
        border-radius: 7px;
        color: inherit;
        display: flex;
        gap: 12px;
        min-height: 82px;
        padding: 13px;
        text-decoration: none;
    }

    .metric:hover {
        border-color: #cfd4dc;
        color: inherit;
        transform: translateY(-1px);
    }

    .metric-icon {
        align-items: center;
        background: #f2f4f7;
        border-radius: 6px;
        color: #344054;
        display: flex;
        flex: 0 0 auto;
        font-size: 1rem;
        height: 38px;
        justify-content: center;
        width: 38px;
    }

    .metric.is-yellow .metric-icon { background: #fff8d6; color: #7a6200; }
    .metric.is-cyan .metric-icon { background: #e6f8ff; color: #007ba8; }
    .metric.is-magenta .metric-icon { background: #ffedf7; color: #c60075; }
    .metric.is-green .metric-icon { background: #ecfdf3; color: #067647; }

    .metric-label {
        color: #667085;
        font-size: .68rem;
        font-weight: 700;
        text-transform: uppercase;
    }

    .metric-value {
        font-size: 1.45rem;
        font-weight: 800;
        line-height: 1.1;
        margin-top: 2px;
    }

    .quick-grid {
        display: grid;
        gap: 8px;
        grid-template-columns: repeat(5, minmax(0, 1fr));
    }

    .quick-tile {
        align-items: center;
        border: 1px solid #e4e7ec;
        border-radius: 6px;
        color: #344054;
        display: flex;
        font-size: .76rem;
        font-weight: 700;
        gap: 8px;
        min-height: 40px;
        padding: 0 10px;
        text-decoration: none;
    }

    .quick-tile:hover { background: #f8fafc; color: #101828; }
    .quick-tile i { color: #667085; width: 15px; }

    @media (max-width: 992px) {
        .metric-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); }
        .quick-grid { grid-template-columns: repeat(3, minmax(0, 1fr)); }
    }

    @media (max-width: 576px) {
        .dashboard-heading { align-items: stretch; flex-direction: column; }
        .metric-grid { grid-template-columns: 1fr; }
        .quick-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); }
    }
</style>
@endpush

@section('content')
<div class="dashboard-heading">
    <div>
        <h1>Resumo operacional</h1>
        <p>Acompanhe pedidos, produção e estoque em um só lugar.</p>
    </div>
    <a href="{{ route('ordens-servico.create') }}" class="btn btn-primary">
        <i class="fa-solid fa-plus me-2"></i>Nova ordem de serviço
    </a>
</div>

<div class="metric-grid">
    <a href="{{ route('ordens-servico.index', ['status' => 'ABERTA']) }}" class="metric is-yellow">
        <span class="metric-icon"><i class="fa-solid fa-clipboard-list"></i></span>
        <span><span class="metric-label">OS abertas</span><span class="metric-value d-block">{{ $indicadores['os_abertas'] }}</span></span>
    </a>
    <a href="{{ route('ordens-servico.producao') }}" class="metric is-cyan">
        <span class="metric-icon"><i class="fa-solid fa-gears"></i></span>
        <span><span class="metric-label">Em produção</span><span class="metric-value d-block">{{ $indicadores['os_producao'] }}</span></span>
    </a>
    <a href="{{ route('produtos.index', ['estoque_baixo' => 1]) }}" class="metric is-magenta">
        <span class="metric-icon"><i class="fa-solid fa-triangle-exclamation"></i></span>
        <span><span class="metric-label">Estoque baixo</span><span class="metric-value d-block">{{ $indicadores['estoque_baixo'] }}</span></span>
    </a>
    <a href="{{ route('ordens-servico.index') }}" class="metric is-green">
        <span class="metric-icon"><i class="fa-solid fa-truck"></i></span>
        <span><span class="metric-label">Entregas hoje</span><span class="metric-value d-block">{{ $indicadores['os_entrega_hoje'] }}</span></span>
    </a>
</div>

<div class="card mb-3">
    <div class="card-header">Acessos rápidos</div>
    <div class="card-body">
        <div class="quick-grid">
            <a href="{{ route('vendas.pdv') }}" class="quick-tile"><i class="fa-solid fa-cash-register"></i>Abrir PDV</a>
            <a href="{{ route('ordens-servico.producao') }}" class="quick-tile"><i class="fa-solid fa-industry"></i>Produção</a>
            <a href="{{ route('clientes.create') }}" class="quick-tile"><i class="fa-solid fa-user-plus"></i>Novo cliente</a>
            <a href="{{ route('produtos.create') }}" class="quick-tile"><i class="fa-solid fa-box-open"></i>Novo produto</a>
            <a href="{{ route('financeiro.fluxo-caixa') }}" class="quick-tile"><i class="fa-solid fa-chart-column"></i>Fluxo de caixa</a>
        </div>
    </div>
</div>

<div class="row g-3">
    <div class="col-12 col-xl-8">
        <div class="card h-100">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span>Ordens recentes</span>
                <a href="{{ route('ordens-servico.index') }}" class="btn btn-sm btn-outline-secondary">Ver todas</a>
            </div>
            <div class="card-body p-0">
                @if($ultimasOs->isEmpty())
                    <div class="empty-state"><i class="fa-regular fa-folder-open fa-xl mb-3 d-block"></i>Nenhuma ordem cadastrada.</div>
                @else
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr><th>OS</th><th>Cliente</th><th>Previsão</th><th>Status</th><th class="text-end">Valor</th></tr>
                            </thead>
                            <tbody>
                                @foreach($ultimasOs as $os)
                                <tr>
                                    <td><a href="{{ route('ordens-servico.show', $os) }}" class="fw-semibold text-decoration-none">{{ $os->numero_os }}</a></td>
                                    <td>{{ $os->cliente_exibicao }}</td>
                                    <td>{{ $os->data_prevista_entrega?->format('d/m/Y') ?? '-' }}</td>
                                    <td><span class="badge bg-{{ $os->status_badge }}">{{ $os->status_label }}</span></td>
                                    <td class="text-end fw-semibold">R$ {{ number_format($os->valor_final, 2, ',', '.') }}</td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>
        </div>
    </div>

    <div class="col-12 col-xl-4">
        <div class="card h-100">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span>Estoque baixo</span>
                <a href="{{ route('produtos.index', ['estoque_baixo' => 1]) }}" class="btn btn-sm btn-outline-secondary">Ver todos</a>
            </div>
            <div class="card-body p-0">
                @forelse($produtosEstoqueBaixo as $produto)
                    <a href="{{ route('produtos.show', $produto) }}" class="d-flex justify-content-between align-items-center gap-3 px-3 py-2 border-bottom text-decoration-none text-reset">
                        <span class="min-w-0">
                            <span class="fw-semibold d-block text-truncate">{{ $produto->nome }}</span>
                            <small class="text-muted">{{ $produto->categoria->nome }}</small>
                        </span>
                        <span class="text-end flex-shrink-0">
                            <strong class="text-danger d-block">{{ number_format($produto->quantidade_estoque, 2, ',', '.') }} {{ $produto->unidade_medida }}</strong>
                            <small class="text-muted">mín. {{ number_format($produto->estoque_minimo, 2, ',', '.') }}</small>
                        </span>
                    </a>
                @empty
                    <div class="empty-state"><i class="fa-solid fa-circle-check text-success fa-xl mb-3 d-block"></i>Estoque em ordem.</div>
                @endforelse
            </div>
        </div>
    </div>
</div>
@endsection
