@extends('layouts.app')

@section('title', 'Ordens de Serviço')
@section('page-title', 'Ordens de Serviço')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="mb-0 fw-bold">Ordens de Serviço</h4>
        <p class="text-muted mb-0 small">Gerencie todas as ordens de serviço</p>
    </div>
    <a href="{{ route('ordens-servico.create') }}" class="btn btn-primary">
        <i class="fa-solid fa-plus me-2"></i>Nova OS
    </a>
</div>

{{-- Filtros --}}
<div class="card mb-4">
    <div class="card-body py-3">
        <form method="GET" class="row g-2 align-items-end">
            <div class="col-12 col-md-4">
                <input type="text" name="busca" value="{{ request('busca') }}" class="form-control" placeholder="Nº OS ou nome do cliente...">
            </div>
            <div class="col-12 col-md-2">
                <select name="status" class="form-select">
                    <option value="">Todos os status</option>
                    @foreach($statusOpcoes as $val => $info)
                        <option value="{{ $val }}" {{ request('status') === $val ? 'selected' : '' }}>{{ $info['label'] }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-12 col-md-2">
                <input type="date" name="data_inicio" value="{{ request('data_inicio') }}" class="form-control" title="Data início">
            </div>
            <div class="col-12 col-md-2">
                <input type="date" name="data_fim" value="{{ request('data_fim') }}" class="form-control" title="Data fim">
            </div>
            <div class="col-auto d-flex gap-2">
                <button type="submit" class="btn btn-primary"><i class="fa-solid fa-search me-1"></i>Filtrar</button>
                <a href="{{ route('ordens-servico.index') }}" class="btn btn-outline-secondary">Limpar</a>
            </div>
        </form>
    </div>
</div>

<div class="card">
    <div class="card-body p-0">
        @if($ordens->isEmpty())
            <div class="text-center text-muted py-5">
                <i class="fa-solid fa-clipboard fa-2x mb-2"></i>
                <p>Nenhuma ordem de serviço encontrada.</p>
                <a href="{{ route('ordens-servico.create') }}" class="btn btn-primary btn-sm">Criar primeira OS</a>
            </div>
        @else
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Nº OS</th>
                            <th>Cliente</th>
                            <th>Abertura</th>
                            <th>Previsão</th>
                            <th>Status</th>
                            <th>Pagamento</th>
                            <th class="text-end">Valor</th>
                            <th class="text-end">Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($ordens as $os)
                        <tr>
                            <td>
                                <a href="{{ route('ordens-servico.show', $os) }}" class="fw-bold text-decoration-none">
                                    {{ $os->numero_os }}
                                </a>
                            </td>
                            <td>{{ $os->cliente->nome }}</td>
                            <td class="text-muted small">{{ $os->data_abertura->format('d/m/Y') }}</td>
                            <td class="small {{ $os->data_prevista_entrega && $os->data_prevista_entrega->isPast() && !in_array($os->status, ['ENTREGUE','CANCELADA']) ? 'text-danger fw-bold' : 'text-muted' }}">
                                {{ $os->data_prevista_entrega?->format('d/m/Y') ?? '—' }}
                            </td>
                            <td><span class="badge bg-{{ $os->status_badge }}">{{ $os->status_label }}</span></td>
                            <td>
                                <span class="badge bg-{{ $os->status_pagamento_badge }}-subtle text-{{ $os->status_pagamento_badge }}">
                                    {{ $os->status_pagamento_label }}
                                </span>
                            </td>
                            <td class="text-end fw-semibold">R$ {{ number_format($os->valor_final, 2, ',', '.') }}</td>
                            <td class="text-end">
                                <div class="d-flex gap-1 justify-content-end">
                                    <a href="{{ route('ordens-servico.show', $os) }}" class="btn btn-sm btn-outline-primary" title="Ver">
                                        <i class="fa-solid fa-eye"></i>
                                    </a>
                                    <a href="{{ route('ordens-servico.edit', $os) }}" class="btn btn-sm btn-outline-secondary" title="Editar">
                                        <i class="fa-solid fa-pen"></i>
                                    </a>
                                </div>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="d-flex justify-content-between align-items-center p-3 border-top">
                <small class="text-muted">{{ $ordens->total() }} ordens</small>
                {{ $ordens->links() }}
            </div>
        @endif
    </div>
</div>
@endsection
