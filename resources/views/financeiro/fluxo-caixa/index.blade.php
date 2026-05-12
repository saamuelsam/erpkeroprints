@extends('layouts.app')

@section('title', 'Fluxo de Caixa')
@section('page-title', 'Fluxo de Caixa')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="mb-0 fw-bold">Fluxo de Caixa</h4>
        <p class="text-muted mb-0 small">Acompanhe todas as movimentações financeiras</p>
    </div>
</div>

{{-- Filtros --}}
<div class="card mb-4">
    <div class="card-body py-3">
        <form method="GET" class="row g-2 align-items-end">
            <div class="col-6 col-md-2">
                <label class="form-label small mb-1">Data início</label>
                <input type="date" name="data_inicio" value="{{ $dataInicio->format('Y-m-d') }}" class="form-control">
            </div>
            <div class="col-6 col-md-2">
                <label class="form-label small mb-1">Data fim</label>
                <input type="date" name="data_fim" value="{{ $dataFim->format('Y-m-d') }}" class="form-control">
            </div>
            <div class="col-12 col-md-2">
                <label class="form-label small mb-1">Tipo</label>
                <select name="tipo" class="form-select">
                    <option value="">Todos</option>
                    <option value="ENTRADA" {{ request('tipo') === 'ENTRADA' ? 'selected' : '' }}>Entradas</option>
                    <option value="SAIDA" {{ request('tipo') === 'SAIDA' ? 'selected' : '' }}>Saídas</option>
                </select>
            </div>
            <div class="col-12 col-md-2">
                <label class="form-label small mb-1">Forma pagamento</label>
                <select name="forma_pagamento" class="form-select">
                    <option value="">Todas</option>
                    @foreach($formasPagamento as $fp)
                        <option value="{{ $fp }}" {{ request('forma_pagamento') === $fp ? 'selected' : '' }}>{{ $fp }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-auto d-flex gap-2">
                <button type="submit" class="btn btn-primary"><i class="fa-solid fa-search me-1"></i>Filtrar</button>
                <a href="{{ route('financeiro.fluxo-caixa') }}" class="btn btn-outline-secondary">Limpar</a>
            </div>
        </form>
    </div>
</div>

{{-- Cards Resumo --}}
<div class="row g-3 mb-4">
    <div class="col-12 col-md-4">
        <div class="card border-start border-success border-4">
            <div class="card-body py-3 d-flex align-items-center gap-3">
                <div class="rounded-circle d-flex align-items-center justify-content-center" style="width:48px;height:48px;background:rgba(34,197,94,0.1)">
                    <i class="fa-solid fa-arrow-up text-success fs-5"></i>
                </div>
                <div>
                    <div class="text-muted small">Total Entradas</div>
                    <div class="fw-bold fs-4 text-success">R$ {{ number_format($totalEntradas, 2, ',', '.') }}</div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-12 col-md-4">
        <div class="card border-start border-danger border-4">
            <div class="card-body py-3 d-flex align-items-center gap-3">
                <div class="rounded-circle d-flex align-items-center justify-content-center" style="width:48px;height:48px;background:rgba(239,68,68,0.1)">
                    <i class="fa-solid fa-arrow-down text-danger fs-5"></i>
                </div>
                <div>
                    <div class="text-muted small">Total Saídas</div>
                    <div class="fw-bold fs-4 text-danger">R$ {{ number_format($totalSaidas, 2, ',', '.') }}</div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-12 col-md-4">
        <div class="card border-start border-primary border-4">
            <div class="card-body py-3 d-flex align-items-center gap-3">
                <div class="rounded-circle d-flex align-items-center justify-content-center" style="width:48px;height:48px;background:rgba(37,99,235,0.1)">
                    <i class="fa-solid fa-scale-balanced text-primary fs-5"></i>
                </div>
                <div>
                    <div class="text-muted small">Saldo do Período</div>
                    <div class="fw-bold fs-4 {{ $saldo >= 0 ? 'text-success' : 'text-danger' }}">
                        R$ {{ number_format($saldo, 2, ',', '.') }}
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Tabela de Movimentações --}}
<div class="card">
    <div class="card-body p-0">
        @if($movimentacoes->isEmpty())
            <div class="text-center text-muted py-5">
                <i class="fa-solid fa-money-bill-transfer fa-2x mb-2"></i>
                <p>Nenhuma movimentação encontrada no período selecionado.</p>
            </div>
        @else
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Data</th>
                            <th>Tipo</th>
                            <th>Descrição</th>
                            <th>Categoria</th>
                            <th>Pagamento</th>
                            <th>Referência</th>
                            <th class="text-end">Valor</th>
                            <th>Responsável</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($movimentacoes as $m)
                        <tr>
                            <td class="text-muted small">{{ $m['data']->format('d/m/Y') }}</td>
                            <td>
                                @if($m['tipo'] === 'ENTRADA')
                                    <span class="badge bg-success-subtle text-success"><i class="fa-solid fa-arrow-up me-1"></i>Entrada</span>
                                @else
                                    <span class="badge bg-danger-subtle text-danger"><i class="fa-solid fa-arrow-down me-1"></i>Saída</span>
                                @endif
                            </td>
                            <td>{{ $m['descricao'] }}</td>
                            <td class="small">{{ $m['categoria'] }}</td>
                            <td class="small text-muted">{{ $m['forma_pagamento'] ?? '—' }}</td>
                            <td class="small">{{ $m['referencia'] }}</td>
                            <td class="text-end fw-semibold {{ $m['tipo'] === 'ENTRADA' ? 'text-success' : 'text-danger' }}">
                                {{ $m['tipo'] === 'ENTRADA' ? '+' : '-' }} R$ {{ number_format($m['valor'], 2, ',', '.') }}
                            </td>
                            <td class="small text-muted">{{ $m['responsavel'] }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="p-3 border-top text-muted small">
                {{ $movimentacoes->count() }} movimentações no período
            </div>
        @endif
    </div>
</div>
@endsection
