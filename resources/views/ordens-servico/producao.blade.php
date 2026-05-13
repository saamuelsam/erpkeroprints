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

@push('styles')
<style>
    .kanban-column {
        min-height: 220px;
        transition: background-color .15s ease, outline-color .15s ease;
    }

    .kanban-column.drag-over {
        background: #FFFBEA;
        outline: 2px dashed #E6BB00;
        outline-offset: -6px;
    }

    .kanban-card {
        cursor: grab;
        transition: opacity .15s ease, transform .15s ease, box-shadow .15s ease;
    }

    .kanban-card:active {
        cursor: grabbing;
    }

    .kanban-card.dragging {
        opacity: .55;
        transform: scale(.98);
        box-shadow: 0 10px 20px rgba(15, 23, 42, .16);
    }

    .drop-hint {
        border: 1px dashed #CBD5E1;
        border-radius: 8px;
        color: #64748B;
        display: none;
        font-size: .8rem;
        padding: 10px;
        text-align: center;
    }

    .kanban-column.drag-over .drop-hint {
        display: block;
    }
</style>
@endpush

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
                <div class="card-body p-2 kanban-column" data-status="{{ $status }}">
                    <div class="drop-hint mb-2">Solte aqui para mover para {{ $statusInfo['label'] }}</div>
                    @forelse($ordens as $os)
                        @php
                            $atrasada = $os->data_prevista_entrega && $os->data_prevista_entrega->isPast() && !$os->data_prevista_entrega->isToday();
                            $hoje = $os->data_prevista_entrega && $os->data_prevista_entrega->isToday();
                            $proximo = $proximosStatus[$os->status] ?? null;
                        @endphp
                        <div class="border rounded p-3 mb-2 bg-white kanban-card"
                             draggable="true"
                             data-os-id="{{ $os->id }}"
                             data-status="{{ $os->status }}"
                             data-update-url="{{ route('ordens-servico.status-rapido', $os) }}">
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

@push('scripts')
<script>
const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
let draggedCard = null;

document.querySelectorAll('.kanban-card').forEach(card => {
    card.addEventListener('dragstart', event => {
        draggedCard = card;
        card.classList.add('dragging');
        event.dataTransfer.effectAllowed = 'move';
        event.dataTransfer.setData('text/plain', card.dataset.osId);
    });

    card.addEventListener('dragend', () => {
        card.classList.remove('dragging');
        document.querySelectorAll('.kanban-column.drag-over').forEach(column => {
            column.classList.remove('drag-over');
        });
        draggedCard = null;
    });
});

document.querySelectorAll('.kanban-column').forEach(column => {
    column.addEventListener('dragover', event => {
        if (!draggedCard) return;

        event.preventDefault();
        event.dataTransfer.dropEffect = 'move';
        column.classList.add('drag-over');
    });

    column.addEventListener('dragleave', event => {
        if (!column.contains(event.relatedTarget)) {
            column.classList.remove('drag-over');
        }
    });

    column.addEventListener('drop', event => {
        event.preventDefault();
        column.classList.remove('drag-over');

        if (!draggedCard) return;

        const novoStatus = column.dataset.status;
        const statusAtual = draggedCard.dataset.status;

        if (!novoStatus || novoStatus === statusAtual) return;

        moverOs(draggedCard, column, novoStatus);
    });
});

function moverOs(card, column, novoStatus) {
    const originalParent = card.parentElement;
    const originalNext = card.nextElementSibling;
    const statusAtual = card.dataset.status;

    column.appendChild(card);
    card.dataset.status = novoStatus;

    fetch(card.dataset.updateUrl, {
        method: 'PATCH',
        headers: {
            'Content-Type': 'application/json',
            'Accept': 'application/json',
            'X-CSRF-TOKEN': csrfToken,
            'X-Requested-With': 'XMLHttpRequest',
        },
        body: JSON.stringify({ status: novoStatus }),
    })
    .then(async response => {
        if (!response.ok) {
            const data = await response.json().catch(() => ({}));
            throw new Error(data.message || 'Não foi possível mover a OS.');
        }

        window.location.reload();
    })
    .catch(error => {
        card.dataset.status = statusAtual;

        if (originalNext) {
            originalParent.insertBefore(card, originalNext);
        } else {
            originalParent.appendChild(card);
        }

        alert(error.message);
    });
}
</script>
@endpush
