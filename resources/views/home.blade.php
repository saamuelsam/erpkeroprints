@extends('layouts.app')

@section('title', 'Dashboard')
@section('page-title', 'Dashboard')

@section('content')
<div class="row g-4 mb-4">
    {{-- Card: OS Abertas --}}
    <div class="col-12 col-sm-6 col-xl-3">
        <div class="card h-100" style="border-left: 4px solid #FFD000;">
            <div class="card-body d-flex align-items-center gap-3">
                <div class="rounded-3 d-flex align-items-center justify-content-center"
                     style="width:52px;height:52px;background:#FFF9D6;color:#111827;font-size:1.4rem;flex-shrink:0;">
                    <i class="fa-solid fa-clipboard-list"></i>
                </div>
                <div>
                    <div class="text-muted" style="font-size:0.75rem;font-weight:600;text-transform:uppercase;letter-spacing:.5px">OS Abertas</div>
                    <div class="fw-bold" style="font-size:1.8rem;line-height:1.2">{{ $indicadores['os_abertas'] }}</div>
                </div>
            </div>
        </div>
    </div>

    {{-- Card: Em Produção --}}
    <div class="col-12 col-sm-6 col-xl-3">
        <div class="card h-100" style="border-left: 4px solid #00AEEF;">
            <div class="card-body d-flex align-items-center gap-3">
                <div class="rounded-3 d-flex align-items-center justify-content-center"
                     style="width:52px;height:52px;background:#E0F7FF;color:#0087C0;font-size:1.4rem;flex-shrink:0;">
                    <i class="fa-solid fa-gears"></i>
                </div>
                <div>
                    <div class="text-muted" style="font-size:0.75rem;font-weight:600;text-transform:uppercase;letter-spacing:.5px">Em Produção</div>
                    <div class="fw-bold" style="font-size:1.8rem;line-height:1.2">{{ $indicadores['os_producao'] }}</div>
                </div>
            </div>
        </div>
    </div>

    {{-- Card: Estoque Baixo --}}
    <div class="col-12 col-sm-6 col-xl-3">
        <div class="card h-100" style="border-left: 4px solid #EC008C;">
            <div class="card-body d-flex align-items-center gap-3">
                <div class="rounded-3 d-flex align-items-center justify-content-center"
                     style="width:52px;height:52px;background:#FFE8F5;color:#EC008C;font-size:1.4rem;flex-shrink:0;">
                    <i class="fa-solid fa-triangle-exclamation"></i>
                </div>
                <div>
                    <div class="text-muted" style="font-size:0.75rem;font-weight:600;text-transform:uppercase;letter-spacing:.5px">Estoque Baixo</div>
                    <div class="fw-bold" style="font-size:1.8rem;line-height:1.2">{{ $indicadores['estoque_baixo'] }}</div>
                </div>
            </div>
        </div>
    </div>

    {{-- Card: Entregas Hoje --}}
    <div class="col-12 col-sm-6 col-xl-3">
        <div class="card h-100" style="border-left: 4px solid #16A34A;">
            <div class="card-body d-flex align-items-center gap-3">
                <div class="rounded-3 d-flex align-items-center justify-content-center"
                     style="width:52px;height:52px;background:#F0FDF4;color:#16A34A;font-size:1.4rem;flex-shrink:0;">
                    <i class="fa-solid fa-truck"></i>
                </div>
                <div>
                    <div class="text-muted" style="font-size:0.75rem;font-weight:600;text-transform:uppercase;letter-spacing:.5px">Entregas Hoje</div>
                    <div class="fw-bold" style="font-size:1.8rem;line-height:1.2">{{ $indicadores['os_entrega_hoje'] }}</div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row g-4">
    {{-- Últimas Ordens de Serviço --}}
    <div class="col-12 col-xl-7">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span><i class="fa-solid fa-clipboard-list me-2 text-primary"></i>Últimas Ordens de Serviço</span>
                <a href="{{ route('ordens-servico.create') }}" class="btn btn-sm btn-primary">
                    <i class="fa-solid fa-plus me-1"></i>Nova OS
                </a>
            </div>
            <div class="card-body p-0">
                @if($ultimasOs->isEmpty())
                    <div class="text-center text-muted py-5">
                        <i class="fa-regular fa-folder-open fa-2x mb-2"></i>
                        <p class="mb-0">Nenhuma ordem de serviço cadastrada.</p>
                    </div>
                @else
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Nº OS</th>
                                    <th>Cliente</th>
                                    <th>Previsão</th>
                                    <th>Status</th>
                                    <th class="text-end">Valor</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($ultimasOs as $os)
                                <tr>
                                    <td>
                                        <a href="{{ route('ordens-servico.show', $os) }}" class="fw-semibold text-decoration-none">
                                            {{ $os->numero_os }}
                                        </a>
                                    </td>
                                    <td>{{ $os->cliente->nome }}</td>
                                    <td>{{ $os->data_prevista_entrega?->format('d/m/Y') ?? '—' }}</td>
                                    <td>
                                        <span class="badge bg-{{ $os->status_badge }}">{{ $os->status_label }}</span>
                                    </td>
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

    {{-- Produtos com Estoque Baixo --}}
    <div class="col-12 col-xl-5">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span><i class="fa-solid fa-triangle-exclamation me-2 text-warning"></i>Estoque Baixo</span>
                <a href="{{ route('produtos.index', ['estoque_baixo' => 1]) }}" class="btn btn-sm btn-outline-warning">
                    Ver todos
                </a>
            </div>
            <div class="card-body p-0">
                @if($produtosEstoqueBaixo->isEmpty())
                    <div class="text-center text-muted py-5">
                        <i class="fa-solid fa-circle-check fa-2x mb-2 text-success"></i>
                        <p class="mb-0">Todos os produtos estão com estoque adequado!</p>
                    </div>
                @else
                    <ul class="list-group list-group-flush">
                        @foreach($produtosEstoqueBaixo as $produto)
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                            <div>
                                <div class="fw-semibold text-sm">{{ $produto->nome }}</div>
                                <small class="text-muted">{{ $produto->categoria->nome }}</small>
                            </div>
                            <div class="text-end">
                                <span class="badge bg-danger rounded-pill">
                                    {{ number_format($produto->quantidade_estoque, 2, ',', '.') }} {{ $produto->unidade_medida }}
                                </span>
                                <div class="text-muted" style="font-size:.7rem">Mín: {{ number_format($produto->estoque_minimo, 2, ',', '.') }}</div>
                            </div>
                        </li>
                        @endforeach
                    </ul>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection
