@extends('layouts.app')

@section('title', 'OS #' . $os->numero_os)
@section('page-title', 'OS #' . $os->numero_os)

@section('content')
@php
    $proximosStatus = [
        'ABERTA' => 'AGUARDANDO_APROVACAO',
        'AGUARDANDO_APROVACAO' => 'PRODUCAO',
        'PRODUCAO' => 'FINALIZADA',
        'FINALIZADA' => 'ENTREGUE',
    ];
    $proximoStatus = $proximosStatus[$os->status] ?? null;
@endphp

<div class="d-flex align-items-center mb-4 gap-3 flex-wrap">
    <a href="{{ route('ordens-servico.index') }}" class="btn btn-sm btn-outline-secondary">
        <i class="fa-solid fa-arrow-left me-1"></i>Voltar
    </a>
    <h4 class="mb-0 fw-bold">OS #{{ $os->numero_os }}</h4>
    <span class="badge bg-{{ $os->status_badge }} fs-6">{{ $os->status_label }}</span>
    <div class="ms-auto d-flex gap-2 flex-wrap">
        <button type="button" class="btn btn-sm btn-outline-secondary no-print" onclick="window.print()">
            <i class="fa-solid fa-print me-1"></i>Imprimir comprovante
        </button>
        @if($proximoStatus)
            <form method="POST" action="{{ route('ordens-servico.status-rapido', $os) }}" class="no-print">
                @csrf
                @method('PATCH')
                <input type="hidden" name="status" value="{{ $proximoStatus }}">
                <button type="submit" class="btn btn-sm btn-primary">
                    <i class="fa-solid fa-forward-step me-1"></i>{{ \App\Models\OrdemServico::STATUS_LABELS[$proximoStatus]['label'] }}
                </button>
            </form>
        @endif
        <a href="{{ route('ordens-servico.edit', $os) }}" class="btn btn-sm btn-outline-primary no-print">
            <i class="fa-solid fa-pen me-1"></i>Editar
        </a>
    </div>
</div>

<div class="row g-4">
    {{-- Resumo Financeiro --}}
    <div class="col-12 col-md-3">
        <div class="card text-center">
            <div class="card-body">
                <div class="text-muted small">Valor Final</div>
                <div class="fw-bold fs-2 text-primary">R$ {{ number_format($os->valor_final, 2, ',', '.') }}</div>
                <div class="text-muted small">Custo: R$ {{ number_format($os->custo_total, 2, ',', '.') }}</div>
            </div>
        </div>
    </div>
    <div class="col-12 col-md-3">
        <div class="card text-center">
            <div class="card-body">
                <div class="text-muted small">Lucro</div>
                <div class="fw-bold fs-2 {{ $os->lucro >= 0 ? 'text-success' : 'text-danger' }}">
                    R$ {{ number_format($os->lucro, 2, ',', '.') }}
                </div>
                <div class="text-muted small">Margem: {{ number_format($os->margem, 1, ',', '.') }}%</div>
            </div>
        </div>
    </div>
    <div class="col-12 col-md-3">
        <div class="card text-center">
            <div class="card-body">
                <div class="text-muted small">Cliente</div>
                <div class="fw-semibold">{{ $os->cliente_exibicao }}</div>
                <div class="text-muted small">{{ $os->cliente?->telefone_formatado ?? 'Venda avulsa' }}</div>
                @if($os->cliente?->cpf_cnpj_formatado)
                    <div class="text-muted small">{{ $os->cliente->cpf_cnpj_formatado }}</div>
                @endif
            </div>
        </div>
    </div>
    <div class="col-12 col-md-3">
        <div class="card text-center">
            <div class="card-body">
                <div class="text-muted small">Pagamento</div>
                <span class="badge bg-{{ $os->status_pagamento_badge }} fs-6">{{ $os->status_pagamento_label }}</span>
                <div class="text-muted small mt-1">{{ $os->forma_pagamento ?: 'A definir' }}</div>
            </div>
        </div>
    </div>

    {{-- Descrição --}}
    <div class="col-12 col-lg-7">
        <div class="card h-100">
            <div class="card-header"><i class="fa-solid fa-file-alt me-2"></i>Detalhes do Serviço</div>
            <div class="card-body">
                <p class="mb-3">{{ $os->descricao_servico ?: 'Venda avulsa sem descrição detalhada.' }}</p>

                @if($os->observacoes_internas)
                    <div class="alert alert-warning py-2 small no-print">
                        <strong>Interno:</strong> {{ $os->observacoes_internas }}
                    </div>
                @endif
                @if($os->observacoes_cliente)
                    <div class="alert alert-info py-2 small">
                        <strong>Cliente:</strong> {{ $os->observacoes_cliente }}
                    </div>
                @endif

                <div class="row text-center mt-3">
                    <div class="col"><div class="text-muted small">Abertura</div><div class="fw-semibold">{{ $os->data_abertura->format('d/m/Y') }}</div></div>
                    <div class="col"><div class="text-muted small">Previsão</div><div class="fw-semibold">{{ $os->data_prevista_entrega?->format('d/m/Y') ?? '—' }}</div></div>
                    <div class="col"><div class="text-muted small">Responsável</div><div class="fw-semibold">{{ $os->responsavel->name }}</div></div>
                </div>
            </div>
        </div>
    </div>

    {{-- Dados do cliente para comprovante --}}
    <div class="col-12 col-lg-5">
        <div class="card h-100">
            <div class="card-header"><i class="fa-solid fa-user me-2"></i>Dados do Cliente</div>
            <div class="card-body">
                @if($os->cliente)
                    <dl class="row small mb-0">
                        <dt class="col-4 text-muted">Nome</dt>
                        <dd class="col-8 fw-semibold">{{ $os->cliente->nome }}</dd>

                        @if($os->cliente->cpf_cnpj_formatado)
                            <dt class="col-4 text-muted">CPF/CNPJ</dt>
                            <dd class="col-8">{{ $os->cliente->cpf_cnpj_formatado }}</dd>
                        @endif

                        @if($os->cliente->telefone_formatado)
                            <dt class="col-4 text-muted">Telefone</dt>
                            <dd class="col-8">{{ $os->cliente->telefone_formatado }}</dd>
                        @endif

                        @if($os->cliente->email)
                            <dt class="col-4 text-muted">E-mail</dt>
                            <dd class="col-8">{{ $os->cliente->email }}</dd>
                        @endif

                        @if($os->cliente->endereco)
                            <dt class="col-4 text-muted">Endereço</dt>
                            <dd class="col-8">
                                {{ $os->cliente->endereco }}{{ $os->cliente->numero ? ', ' . $os->cliente->numero : '' }}
                                @if($os->cliente->complemento) - {{ $os->cliente->complemento }} @endif
                                @if($os->cliente->bairro || $os->cliente->cidade)
                                    <br>{{ $os->cliente->bairro }}{{ $os->cliente->cidade ? ' - ' . $os->cliente->cidade . '/' . $os->cliente->estado : '' }}
                                @endif
                                @if($os->cliente->cep)
                                    <br>CEP: {{ $os->cliente->cep }}
                                @endif
                            </dd>
                        @endif
                    </dl>
                @elseif($os->cliente_nome)
                    <dl class="row small mb-0">
                        <dt class="col-4 text-muted">Nome</dt>
                        <dd class="col-8 fw-semibold">{{ $os->cliente_nome }}</dd>
                    </dl>
                @else
                    <div class="text-muted small">Consumidor final / venda avulsa.</div>
                @endif
            </div>
        </div>
    </div>

    <div class="col-12 col-lg-5 no-print">
        <div class="card h-100">
            <div class="card-header"><i class="fa-solid fa-clock-rotate-left me-2"></i>Histórico de Status</div>
            <div class="card-body p-0">
                <ul class="list-group list-group-flush">
                    @php
                        $statusLabels = \App\Models\OrdemServico::STATUS_LABELS;
                    @endphp
                    @forelse($os->historicos as $h)
                    <li class="list-group-item py-2">
                        <div class="d-flex justify-content-between">
                            <span class="small">
                                @if($h->status_anterior)
                                    <span class="text-muted">{{ $statusLabels[$h->status_anterior]['label'] ?? $h->status_anterior }}</span>
                                    <i class="fa-solid fa-arrow-right mx-1 text-muted" style="font-size:.7rem"></i>
                                @endif
                                <strong>{{ $statusLabels[$h->status_novo]['label'] ?? $h->status_novo }}</strong>
                            </span>
                            <small class="text-muted">{{ $h->created_at->format('d/m H:i') }}</small>
                        </div>
                        <small class="text-muted">por {{ $h->usuario->name }}</small>
                    </li>
                    @empty
                    <li class="list-group-item text-muted text-center py-3 small">Nenhum histórico.</li>
                    @endforelse
                </ul>
            </div>
        </div>
    </div>

    {{-- Itens --}}
    <div class="col-12">
        <div class="card">
            <div class="card-header"><i class="fa-solid fa-list me-2"></i>Itens / Materiais</div>
            <div class="card-body p-0">
                @if($os->itens->isEmpty())
                    <div class="text-center text-muted py-3 small">Nenhum item cadastrado.</div>
                @else
                    <div class="table-responsive">
                        <table class="table table-sm mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Item</th>
                                    <th class="text-end">Qtd</th>
                                    <th class="text-end">Custo Unit.</th>
                                    <th class="text-end">Preço Unit.</th>
                                    <th class="text-end">Total</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($os->itens as $item)
                                <tr>
                                    <td>{{ $item->descricao_item }}</td>
                                    <td class="text-end">{{ number_format($item->quantidade, 2, ',', '.') }}</td>
                                    <td class="text-end text-muted">R$ {{ number_format($item->custo_unitario, 2, ',', '.') }}</td>
                                    <td class="text-end">R$ {{ number_format($item->preco_unitario, 2, ',', '.') }}</td>
                                    <td class="text-end fw-semibold">R$ {{ number_format($item->total_item, 2, ',', '.') }}</td>
                                </tr>
                                @endforeach
                            </tbody>
                            <tfoot class="table-light">
                                <tr>
                                    <td colspan="4" class="text-end fw-bold">Total dos Itens:</td>
                                    <td class="text-end fw-bold">R$ {{ number_format($os->itens->sum('total_item'), 2, ',', '.') }}</td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection

@push('styles')
<style>
@media print {
    .no-print,
    .sidebar,
    .topbar {
        display: none !important;
    }

    body {
        background: #fff !important;
    }

    .content-wrapper,
    .main-content {
        margin: 0 !important;
        padding: 0 !important;
        width: 100% !important;
    }

    .card {
        border: 1px solid #d5dbe3 !important;
        box-shadow: none !important;
        break-inside: avoid;
    }
}
</style>
@endpush
