@extends('layouts.app')

@section('title', 'Clientes')
@section('page-title', 'Clientes')

@section('content')
{{-- Cabeçalho + Botão Novo --}}
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="mb-0 fw-bold">Clientes</h4>
        <p class="text-muted mb-0 small">Gerencie sua base de clientes</p>
    </div>
    <a href="{{ route('clientes.create') }}" class="btn btn-primary">
        <i class="fa-solid fa-plus me-2"></i>Novo Cliente
    </a>
</div>

{{-- Filtros --}}
<div class="card mb-4">
    <div class="card-body py-3">
        <form method="GET" class="row g-2 align-items-end">
            <div class="col-12 col-md-6">
                <input type="text" name="busca" value="{{ request('busca') }}" class="form-control"
                       placeholder="Buscar por nome, telefone, CPF/CNPJ ou e-mail...">
            </div>
            <div class="col-auto">
                <select name="apenas_ativos" class="form-select">
                    <option value="1" {{ request('apenas_ativos', '1') === '1' ? 'selected' : '' }}>Somente ativos</option>
                    <option value="0" {{ request('apenas_ativos') === '0' ? 'selected' : '' }}>Todos</option>
                </select>
            </div>
            <div class="col-auto d-flex gap-2">
                <button type="submit" class="btn btn-primary"><i class="fa-solid fa-search me-1"></i>Buscar</button>
                <a href="{{ route('clientes.index') }}" class="btn btn-outline-secondary">Limpar</a>
            </div>
        </form>
    </div>
</div>

{{-- Tabela --}}
<div class="card">
    <div class="card-body p-0">
        @if($clientes->isEmpty())
            <div class="text-center text-muted py-5">
                <i class="fa-regular fa-face-frown fa-2x mb-2"></i>
                <p>Nenhum cliente encontrado.</p>
                <a href="{{ route('clientes.create') }}" class="btn btn-primary btn-sm">Cadastrar primeiro cliente</a>
            </div>
        @else
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Nome</th>
                            <th>CPF / CNPJ</th>
                            <th>Telefone</th>
                            <th>E-mail</th>
                            <th>Cidade/UF</th>
                            <th>Status</th>
                            <th class="text-end">Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($clientes as $cliente)
                        <tr>
                            <td>
                                <a href="{{ route('clientes.show', $cliente) }}" class="fw-semibold text-decoration-none text-dark">
                                    {{ $cliente->nome }}
                                </a>
                            </td>
                            <td class="text-muted">{{ $cliente->cpf_cnpj_formatado ?: '—' }}</td>
                            <td class="text-muted">{{ $cliente->telefone_formatado ?: '—' }}</td>
                            <td class="text-muted">{{ $cliente->email ?: '—' }}</td>
                            <td class="text-muted">
                                {{ $cliente->cidade ? "{$cliente->cidade}/{$cliente->estado}" : '—' }}
                            </td>
                            <td>
                                @if($cliente->ativo)
                                    <span class="badge bg-success-subtle text-success fw-semibold">Ativo</span>
                                @else
                                    <span class="badge bg-secondary-subtle text-secondary fw-semibold">Inativo</span>
                                @endif
                            </td>
                            <td class="text-end">
                                <div class="d-flex gap-1 justify-content-end">
                                    <a href="{{ route('clientes.show', $cliente) }}" class="btn btn-sm btn-outline-primary" title="Visualizar">
                                        <i class="fa-solid fa-eye"></i>
                                    </a>
                                    <a href="{{ route('clientes.edit', $cliente) }}" class="btn btn-sm btn-outline-secondary" title="Editar">
                                        <i class="fa-solid fa-pen"></i>
                                    </a>
                                    <form method="POST" action="{{ route('clientes.toggle-ativo', $cliente) }}" class="d-inline">
                                        @csrf @method('PATCH')
                                        <button type="submit" class="btn btn-sm {{ $cliente->ativo ? 'btn-outline-warning' : 'btn-outline-success' }}"
                                                title="{{ $cliente->ativo ? 'Inativar' : 'Ativar' }}">
                                            <i class="fa-solid {{ $cliente->ativo ? 'fa-ban' : 'fa-check' }}"></i>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            {{-- Paginação --}}
            <div class="d-flex justify-content-between align-items-center p-3 border-top">
                <small class="text-muted">
                    Exibindo {{ $clientes->firstItem() }}–{{ $clientes->lastItem() }} de {{ $clientes->total() }} clientes
                </small>
                {{ $clientes->links() }}
            </div>
        @endif
    </div>
</div>
@endsection
