@extends('layouts.app')

@section('title', "Documento {$documento->numero}")
@section('page-title', "Documento {$documento->numero}")

@section('content')
<div class="d-flex align-items-center justify-content-between mb-3 flex-wrap gap-3">
    <img src="{{ asset('images/logo-color.png') }}" alt="Kero Prints Gráfica e Papelaria" style="max-height:54px;width:auto">
</div>

<div class="d-flex align-items-center mb-4 gap-3 flex-wrap">
    <a href="{{ route('financeiro.documentos.index') }}" class="btn btn-sm btn-outline-secondary">
        <i class="fa-solid fa-arrow-left me-1"></i>Voltar
    </a>
    <h4 class="mb-0 fw-bold">{{ $documento->numero }}</h4>
    <span class="badge bg-dark-subtle text-dark">{{ $documento->tipo_label }}</span>
    <span class="badge bg-{{ $documento->status_badge }} fs-6">{{ $documento->status_label }}</span>

    <div class="ms-auto d-flex gap-2 flex-wrap">
        {{-- Ações --}}
        @if($documento->status === 'RASCUNHO')
            <form method="POST" action="{{ route('financeiro.documentos.emitir', $documento) }}">
                @csrf
                <button class="btn btn-sm btn-primary"><i class="fa-solid fa-stamp me-1"></i>Emitir</button>
            </form>
        @endif

        <a href="{{ route('financeiro.documentos.pdf', $documento) }}" class="btn btn-sm btn-outline-danger" target="_blank">
            <i class="fa-solid fa-file-pdf me-1"></i>PDF
        </a>

        @if($documento->cliente->email)
            <form method="POST" action="{{ route('financeiro.documentos.enviar-email', $documento) }}">
                @csrf
                <button class="btn btn-sm btn-outline-info" onclick="return confirm('Enviar por e-mail para {{ $documento->cliente->email }}?')">
                    <i class="fa-solid fa-envelope me-1"></i>E-mail
                </button>
            </form>
        @endif

        @if($documento->cliente->telefone)
            <form method="POST" action="{{ route('financeiro.documentos.enviar-whatsapp', $documento) }}">
                @csrf
                <button class="btn btn-sm btn-success">
                    <i class="fa-brands fa-whatsapp me-1"></i>WhatsApp
                </button>
            </form>
        @endif

        @if(in_array($documento->status, ['RASCUNHO', 'EMITIDO']))
            <a href="{{ route('financeiro.documentos.edit', $documento) }}" class="btn btn-sm btn-outline-secondary">
                <i class="fa-solid fa-pen me-1"></i>Editar
            </a>
        @endif
    </div>
</div>

<div class="row g-4">
    {{-- Dados do documento --}}
    <div class="col-12 col-lg-8">
        <div class="card mb-4">
            <div class="card-header"><i class="fa-solid fa-file-lines me-2"></i>Dados do Documento</div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-6 col-md-3">
                        <div class="text-muted small">Tipo</div>
                        <div class="fw-semibold">{{ $documento->tipo_label }}</div>
                    </div>
                    <div class="col-6 col-md-3">
                        <div class="text-muted small">Emissão</div>
                        <div class="fw-semibold">{{ $documento->data_emissao->format('d/m/Y') }}</div>
                    </div>
                    <div class="col-6 col-md-3">
                        <div class="text-muted small">Vencimento</div>
                        <div class="fw-semibold">{{ $documento->data_vencimento?->format('d/m/Y') ?? '—' }}</div>
                    </div>
                    <div class="col-6 col-md-3">
                        <div class="text-muted small">Pagamento</div>
                        <div class="fw-semibold">{{ $documento->forma_pagamento ?? '—' }}</div>
                    </div>
                </div>
            </div>
        </div>

        <div class="card mb-4">
            <div class="card-header"><i class="fa-solid fa-user me-2"></i>Cliente</div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-12 col-md-4">
                        <div class="text-muted small">Nome</div>
                        <div class="fw-semibold">{{ $documento->cliente->nome }}</div>
                    </div>
                    <div class="col-12 col-md-4">
                        <div class="text-muted small">E-mail</div>
                        <div>{{ $documento->cliente->email ?? '—' }}</div>
                    </div>
                    <div class="col-12 col-md-4">
                        <div class="text-muted small">Telefone</div>
                        <div>{{ $documento->cliente->telefone ?? '—' }}</div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Itens --}}
        <div class="card">
            <div class="card-header"><i class="fa-solid fa-list me-2"></i>Itens</div>
            <div class="card-body p-0">
                <table class="table table-hover mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Descrição</th>
                            <th class="text-end">Qtd</th>
                            <th class="text-end">Valor Unit.</th>
                            <th class="text-end">Desc.</th>
                            <th class="text-end">Total</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($documento->itens as $item)
                        <tr>
                            <td>{{ $item->descricao }}</td>
                            <td class="text-end">{{ number_format($item->quantidade, $item->quantidade == (int)$item->quantidade ? 0 : 3, ',', '.') }}</td>
                            <td class="text-end">R$ {{ number_format($item->valor_unitario, 2, ',', '.') }}</td>
                            <td class="text-end text-muted">{{ $item->desconto_item > 0 ? 'R$ ' . number_format($item->desconto_item, 2, ',', '.') : '—' }}</td>
                            <td class="text-end fw-semibold">R$ {{ number_format($item->total_item, 2, ',', '.') }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                    <tfoot>
                        <tr class="table-light">
                            <td colspan="4" class="text-end fw-semibold">Subtotal:</td>
                            <td class="text-end fw-semibold">R$ {{ number_format($documento->subtotal, 2, ',', '.') }}</td>
                        </tr>
                        @if($documento->desconto > 0)
                        <tr class="table-light">
                            <td colspan="4" class="text-end text-danger">Desconto:</td>
                            <td class="text-end text-danger">- R$ {{ number_format($documento->desconto, 2, ',', '.') }}</td>
                        </tr>
                        @endif
                        <tr class="table-light">
                            <td colspan="4" class="text-end fw-bold fs-5">Total:</td>
                            <td class="text-end fw-bold fs-5 text-success">R$ {{ number_format($documento->valor_total, 2, ',', '.') }}</td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </div>

    {{-- Sidebar --}}
    <div class="col-12 col-lg-4">
        @if($documento->observacoes)
        <div class="card mb-4">
            <div class="card-header"><i class="fa-solid fa-sticky-note me-2"></i>Observações</div>
            <div class="card-body">{{ $documento->observacoes }}</div>
        </div>
        @endif

        @if($documento->condicoes_pagamento)
        <div class="card mb-4">
            <div class="card-header"><i class="fa-solid fa-handshake me-2"></i>Condições de Pagamento</div>
            <div class="card-body">{{ $documento->condicoes_pagamento }}</div>
        </div>
        @endif

        {{-- Histórico de envios --}}
        <div class="card">
            <div class="card-header"><i class="fa-solid fa-paper-plane me-2"></i>Histórico de Envios</div>
            <div class="card-body">
                @if($documento->envios->isEmpty())
                    <p class="text-muted small mb-0">Nenhum envio realizado ainda.</p>
                @else
                    <ul class="list-unstyled mb-0">
                        @foreach($documento->envios as $envio)
                        <li class="d-flex align-items-center gap-2 mb-2 pb-2 {{ !$loop->last ? 'border-bottom' : '' }}">
                            @if($envio->tipo === 'EMAIL')
                                <i class="fa-solid fa-envelope text-info"></i>
                            @else
                                <i class="fa-brands fa-whatsapp text-success"></i>
                            @endif
                            <div>
                                <div class="small fw-semibold">{{ $envio->destinatario }}</div>
                                <div class="text-muted" style="font-size:.7rem">
                                    {{ $envio->created_at->format('d/m/Y H:i') }} por {{ $envio->responsavel->name ?? '—' }}
                                </div>
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
