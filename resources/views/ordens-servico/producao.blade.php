@extends('layouts.app')

@section('title', 'Painel de Produção')
@section('page-title', 'Painel de Produção')

@section('content')
@php
    $proximosStatus = [
        'ABERTA' => 'AGUARDANDO_APROVACAO',
        'AGUARDANDO_APROVACAO' => 'PRODUCAO',
        'PRODUCAO' => 'FINALIZADA',
        'FINALIZADA' => 'ENTREGUE',
    ];
@endphp

<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
    <div>
        <h4 class="mb-0 fw-bold">Painel de Produção</h4>
        <p class="text-muted mb-0 small">Acompanhe os pedidos por etapa e prazo de entrega</p>
    </div>
    <a href="{{ route('ordens-servico.create') }}" class="btn btn-primary">
        <i class="fa-solid fa-plus me-2"></i>Nova OS
    </a>
</div>

<div class="row g-3 mb-4">
    <div class="col-6 col-lg-3">
        <div class="card border-start border-danger border-4">
            <div class="card-body py-3">
                <div class="text-muted small">Atrasadas</div>
                <div class="fs-3 fw-bold text-danger">{{ $indicadores['atrasadas'] }}</div>
            </div>
        </div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="card border-start border-warning border-4">
            <div class="card-body py-3">
                <div class="text-muted small">Entrega hoje</div>
                <div class="fs-3 fw-bold text-warning">{{ $indicadores['hoje'] }}</div>
            </div>
        </div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="card border-start border-info border-4">
            <div class="card-body py-3">
                <div class="text-muted small">Em produção</div>
                <div class="fs-3 fw-bold text-info">{{ $indicadores['producao'] }}</div>
            </div>
        </div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="card border-start border-success border-4">
            <div class="card-body py-3">
                <div class="text-muted small">Prontas</div>
                <div class="fs-3 fw-bold text-success">{{ $indicadores['prontas'] }}</div>
            </div>
        </div>
    </div>
</div>

<div class="row g-3">
    @foreach($statusFluxo as $status)
        @php
            $ordens = $ordensPorStatus->get($status, collect());
            $statusInfo = $statusOpcoes[$status];
        @endphp
        <div class="col-12 col-xl-3 col-lg-6">
            <div class="card h-100">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <span>
                        <span class="badge bg-{{ $statusInfo['badge'] }} me-2">&nbsp;</span>
                        {{ $statusInfo['label'] }}
                    </span>
                    <span class="badge bg-light text-dark">{{ $ordens->count() }}</span>
                </div>
                <div class="card-body p-2">
                    @forelse($ordens as $os)
                        @php
                            $atrasada = $os->data_prevista_entrega && $os->data_prevista_entrega->isPast() && !$os->data_prevista_entrega->isToday();
                            $hoje = $os->data_prevista_entrega && $os->data_prevista_entrega->isToday();
                            $proximo = $proximosStatus[$os->status] ?? null;
                        @endphp
                        <div class="border rounded p-3 mb-2 bg-white">
                            <div class="d-flex justify-content-between gap-2">
                                <a href="{{ route('ordens-servico.show', $os) }}" class="fw-bold text-decoration-none">
                                    {{ $os->numero_os }}
                                </a>
                                <span class="small fw-semibold">R$ {{ number_format($os->valor_final, 2, ',', '.') }}</span>
                            </div>

                            <div class="small text-muted mt-1">{{ $os->cliente->nome }}</div>
                            <div class="small mt-2">
                                @if($atrasada)
                                    <span class="badge bg-danger">Atrasada: {{ $os->data_prevista_entrega->format('d/m') }}</span>
                                @elseif($hoje)
                                    <span class="badge bg-warning text-dark">Hoje: {{ $os->data_prevista_entrega->format('d/m') }}</span>
                                @elseif($os->data_prevista_entrega)
                                    <span class="badge bg-light text-dark">Entrega: {{ $os->data_prevista_entrega->format('d/m') }}</span>
                                @else
                                    <span class="badge bg-light text-muted">Sem previsão</span>
                                @endif
                            </div>

                            <div class="text-muted small mt-2">
                                {{ \Illuminate\Support\Str::limit($os->descricao_servico, 90) }}
                            </div>

                            <div class="d-flex gap-1 mt-3">
                                <a href="{{ route('ordens-servico.edit', $os) }}" class="btn btn-sm btn-outline-secondary flex-fill">
                                    <i class="fa-solid fa-pen me-1"></i>Editar
                                </a>
                                @if($proximo)
                                    <form method="POST" action="{{ route('ordens-servico.status-rapido', $os) }}" class="flex-fill">
                                        @csrf
                                        @method('PATCH')
                                        <input type="hidden" name="status" value="{{ $proximo }}">
                                        <button type="submit" class="btn btn-sm btn-primary w-100">
                                            {{ $statusOpcoes[$proximo]['label'] }}
                                        </button>
                                    </form>
                                @endif
                            </div>
                        </div>
                    @empty
                        <div class="text-center text-muted small py-4">Nenhuma OS nesta etapa.</div>
                    @endforelse
                </div>
            </div>
        </div>
    @endforeach
</div>
@endsection
