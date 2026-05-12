@extends('layouts.app')

@section('title', $cliente->nome)
@section('page-title', 'Detalhes do Cliente')

@section('content')
<div class="d-flex align-items-center mb-4 gap-3">
    <a href="{{ route('clientes.index') }}" class="btn btn-sm btn-outline-secondary">
        <i class="fa-solid fa-arrow-left me-1"></i>Voltar
    </a>
    <h4 class="mb-0 fw-bold">{{ $cliente->nome }}</h4>
    @if(!$cliente->ativo)
        <span class="badge bg-secondary">Inativo</span>
    @endif
    <div class="ms-auto d-flex gap-2">
        <a href="{{ route('clientes.edit', $cliente) }}" class="btn btn-sm btn-outline-primary">
            <i class="fa-solid fa-pen me-1"></i>Editar
        </a>
    </div>
</div>

<div class="row g-4">
    <div class="col-12 col-lg-5">
        <div class="card">
            <div class="card-header"><i class="fa-solid fa-address-card me-2"></i>Informações</div>
            <div class="card-body">
                <dl class="row mb-0 small">
                    <dt class="col-sm-4 text-muted">CPF/CNPJ</dt>
                    <dd class="col-sm-8">{{ $cliente->cpf_cnpj_formatado ?: '—' }}</dd>

                    <dt class="col-sm-4 text-muted">Telefone</dt>
                    <dd class="col-sm-8">{{ $cliente->telefone_formatado ?: '—' }}</dd>

                    <dt class="col-sm-4 text-muted">E-mail</dt>
                    <dd class="col-sm-8">{{ $cliente->email ?: '—' }}</dd>

                    <dt class="col-sm-4 text-muted">Endereço</dt>
                    <dd class="col-sm-8">
                        @if($cliente->endereco)
                            {{ $cliente->endereco }}, {{ $cliente->numero }}
                            @if($cliente->complemento) — {{ $cliente->complemento }} @endif
                            <br>{{ $cliente->bairro }} — {{ $cliente->cidade }}/{{ $cliente->estado }}
                            <br>CEP: {{ $cliente->cep }}
                        @else
                            —
                        @endif
                    </dd>

                    @if($cliente->observacoes)
                    <dt class="col-sm-4 text-muted">Observações</dt>
                    <dd class="col-sm-8">{{ $cliente->observacoes }}</dd>
                    @endif

                    <dt class="col-sm-4 text-muted">Cadastrado em</dt>
                    <dd class="col-sm-8">{{ $cliente->created_at->format('d/m/Y H:i') }}</dd>
                </dl>
            </div>
        </div>
    </div>

    <div class="col-12 col-lg-7">
        <div class="card">
            <div class="card-header"><i class="fa-solid fa-clipboard-list me-2"></i>Últimas Ordens de Serviço</div>
            <div class="card-body p-0">
                @if($cliente->ordensServico->isEmpty())
                    <div class="text-center text-muted py-4">
                        <p class="mb-0 small">Nenhuma ordem de serviço para este cliente.</p>
                    </div>
                @else
                    <div class="table-responsive">
                        <table class="table table-sm mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Nº OS</th>
                                    <th>Abertura</th>
                                    <th>Status</th>
                                    <th class="text-end">Valor</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($cliente->ordensServico as $os)
                                <tr>
                                    <td>
                                        <a href="{{ route('ordens-servico.show', $os) }}" class="fw-semibold text-decoration-none">
                                            {{ $os->numero_os }}
                                        </a>
                                    </td>
                                    <td>{{ $os->data_abertura->format('d/m/Y') }}</td>
                                    <td><span class="badge bg-{{ $os->status_badge }}">{{ $os->status_label }}</span></td>
                                    <td class="text-end">R$ {{ number_format($os->valor_final, 2, ',', '.') }}</td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection
