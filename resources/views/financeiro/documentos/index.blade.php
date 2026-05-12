@extends('layouts.app')

@section('title', 'Documentos')
@section('page-title', 'Documentos')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="mb-0 fw-bold">Documentos</h4>
        <p class="text-muted mb-0 small">Emita recibos, orçamentos, cobranças e comprovantes</p>
    </div>
    <a href="{{ route('financeiro.documentos.create') }}" class="btn btn-primary">
        <i class="fa-solid fa-plus me-2"></i>Novo Documento
    </a>
</div>

{{-- Filtros --}}
<div class="card mb-4">
    <div class="card-body py-3">
        <form method="GET" class="row g-2 align-items-end">
            <div class="col-12 col-md-3">
                <input type="text" name="busca" value="{{ request('busca') }}" class="form-control" placeholder="Buscar nº, cliente...">
            </div>
            <div class="col-12 col-md-2">
                <select name="tipo" class="form-select">
                    <option value="">Todos tipos</option>
                    @foreach($tipos as $val => $label)
                        <option value="{{ $val }}" {{ request('tipo') === $val ? 'selected' : '' }}>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-12 col-md-2">
                <select name="status" class="form-select">
                    <option value="">Todos status</option>
                    @foreach(\App\Models\Documento::STATUS_LABELS as $val => $info)
                        <option value="{{ $val }}" {{ request('status') === $val ? 'selected' : '' }}>{{ $info['label'] }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-auto d-flex gap-2">
                <button type="submit" class="btn btn-primary"><i class="fa-solid fa-search me-1"></i>Filtrar</button>
                <a href="{{ route('financeiro.documentos.index') }}" class="btn btn-outline-secondary">Limpar</a>
            </div>
        </form>
    </div>
</div>

{{-- Tabela --}}
<div class="card">
    <div class="card-body p-0">
        @if($documentos->isEmpty())
            <div class="text-center text-muted py-5">
                <i class="fa-solid fa-file-pdf fa-2x mb-2"></i>
                <p>Nenhum documento encontrado.</p>
                <a href="{{ route('financeiro.documentos.create') }}" class="btn btn-primary btn-sm">Criar primeiro documento</a>
            </div>
        @else
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Nº</th>
                            <th>Tipo</th>
                            <th>Cliente</th>
                            <th>Emissão</th>
                            <th class="text-end">Valor</th>
                            <th>Status</th>
                            <th class="text-end">Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($documentos as $doc)
                        <tr>
                            <td class="fw-semibold text-primary">
                                <a href="{{ route('financeiro.documentos.show', $doc) }}">{{ $doc->numero }}</a>
                            </td>
                            <td><span class="badge bg-dark-subtle text-dark">{{ $doc->tipo_label }}</span></td>
                            <td>{{ $doc->cliente->nome ?? '—' }}</td>
                            <td class="text-muted small">{{ $doc->data_emissao->format('d/m/Y') }}</td>
                            <td class="text-end fw-semibold">R$ {{ number_format($doc->valor_total, 2, ',', '.') }}</td>
                            <td><span class="badge bg-{{ $doc->status_badge }}">{{ $doc->status_label }}</span></td>
                            <td class="text-end">
                                <div class="d-flex gap-1 justify-content-end">
                                    <a href="{{ route('financeiro.documentos.show', $doc) }}" class="btn btn-sm btn-outline-primary" title="Ver">
                                        <i class="fa-solid fa-eye"></i>
                                    </a>
                                    <a href="{{ route('financeiro.documentos.pdf', $doc) }}" class="btn btn-sm btn-outline-danger" title="PDF" target="_blank">
                                        <i class="fa-solid fa-file-pdf"></i>
                                    </a>
                                    @if(in_array($doc->status, ['RASCUNHO', 'EMITIDO']))
                                        <a href="{{ route('financeiro.documentos.edit', $doc) }}" class="btn btn-sm btn-outline-secondary" title="Editar">
                                            <i class="fa-solid fa-pen"></i>
                                        </a>
                                    @endif
                                </div>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="d-flex justify-content-between align-items-center p-3 border-top">
                <small class="text-muted">{{ $documentos->total() }} documentos</small>
                {{ $documentos->links() }}
            </div>
        @endif
    </div>
</div>
@endsection
