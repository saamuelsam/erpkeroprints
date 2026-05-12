@extends('layouts.app')

@section('title', $documento->exists ? 'Editar Documento' : 'Novo Documento')
@section('page-title', $documento->exists ? 'Editar Documento' : 'Novo Documento')

@section('content')
<div class="d-flex align-items-center mb-4 gap-3">
    <a href="{{ route('financeiro.documentos.index') }}" class="btn btn-sm btn-outline-secondary">
        <i class="fa-solid fa-arrow-left me-1"></i>Voltar
    </a>
    <h4 class="mb-0 fw-bold">{{ $documento->exists ? 'Editar Documento' : 'Novo Documento' }}</h4>
</div>

<form method="POST" action="{{ $documento->exists ? route('financeiro.documentos.update', $documento) : route('financeiro.documentos.store') }}" id="documentoForm">
    @csrf
    @if($documento->exists) @method('PUT') @endif

    <div class="row g-4">
        <div class="col-12 col-lg-8">
            {{-- Cabeçalho --}}
            <div class="card mb-4">
                <div class="card-header"><i class="fa-solid fa-file-lines me-2"></i>Dados do Documento</div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-12 col-md-4">
                            <label class="form-label fw-semibold">Tipo <span class="text-danger">*</span></label>
                            <select name="tipo" class="form-select @error('tipo') is-invalid @enderror" required>
                                @foreach($tipos as $val => $label)
                                    <option value="{{ $val }}" {{ old('tipo', $documento->tipo) === $val ? 'selected' : '' }}>{{ $label }}</option>
                                @endforeach
                            </select>
                            @error('tipo') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

                        <div class="col-12 col-md-4">
                            <label class="form-label fw-semibold">Data Emissão <span class="text-danger">*</span></label>
                            <input type="date" name="data_emissao" value="{{ old('data_emissao', $documento->data_emissao?->format('Y-m-d') ?? now()->format('Y-m-d')) }}"
                                   class="form-control" required>
                        </div>

                        <div class="col-12 col-md-4">
                            <label class="form-label fw-semibold">Vencimento</label>
                            <input type="date" name="data_vencimento" value="{{ old('data_vencimento', $documento->data_vencimento?->format('Y-m-d')) }}"
                                   class="form-control">
                        </div>

                        <div class="col-12 col-md-6">
                            <label class="form-label fw-semibold">Cliente <span class="text-danger">*</span></label>
                            <select name="cliente_id" class="form-select @error('cliente_id') is-invalid @enderror" required>
                                <option value="">Selecione...</option>
                                @foreach($clientes as $c)
                                    <option value="{{ $c->id }}" {{ old('cliente_id', $documento->cliente_id) == $c->id ? 'selected' : '' }}>{{ $c->nome }}</option>
                                @endforeach
                            </select>
                            @error('cliente_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

                        <div class="col-12 col-md-6">
                            <label class="form-label fw-semibold">Forma de Pagamento</label>
                            <select name="forma_pagamento" class="form-select">
                                <option value="">Selecione...</option>
                                @foreach($formasPagamento as $fp)
                                    <option value="{{ $fp }}" {{ old('forma_pagamento', $documento->forma_pagamento) === $fp ? 'selected' : '' }}>{{ $fp }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="col-12">
                            <label class="form-label fw-semibold">Observações</label>
                            <textarea name="observacoes" rows="2" class="form-control" placeholder="Observações do documento...">{{ old('observacoes', $documento->observacoes) }}</textarea>
                        </div>

                        <div class="col-12">
                            <label class="form-label fw-semibold">Condições de Pagamento</label>
                            <input type="text" name="condicoes_pagamento" value="{{ old('condicoes_pagamento', $documento->condicoes_pagamento) }}"
                                   class="form-control" placeholder="Ex: 30/60/90 dias">
                        </div>
                    </div>
                </div>
            </div>

            {{-- Itens --}}
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <span><i class="fa-solid fa-list me-2"></i>Itens do Documento</span>
                    <button type="button" class="btn btn-sm btn-success" onclick="adicionarItem()">
                        <i class="fa-solid fa-plus me-1"></i>Adicionar Item
                    </button>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0" id="tabelaItens">
                            <thead class="table-light">
                                <tr>
                                    <th style="width:40%">Descrição</th>
                                    <th style="width:12%">Qtd</th>
                                    <th style="width:15%">Valor Unit.</th>
                                    <th style="width:12%">Desc.</th>
                                    <th style="width:15%">Total</th>
                                    <th style="width:6%"></th>
                                </tr>
                            </thead>
                            <tbody id="itensBody">
                                {{-- preenchido via JS --}}
                            </tbody>
                            <tfoot>
                                <tr class="table-light">
                                    <td colspan="4" class="text-end fw-bold">Subtotal:</td>
                                    <td class="fw-bold text-end" id="subtotalDisplay">R$ 0,00</td>
                                    <td></td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-12 col-lg-4">
            {{-- Desconto + Total --}}
            <div class="card mb-4">
                <div class="card-header"><i class="fa-solid fa-calculator me-2"></i>Totais</div>
                <div class="card-body">
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Desconto global (R$)</label>
                        <div class="input-group">
                            <span class="input-group-text">R$</span>
                            <input type="number" name="desconto" id="descontoGlobal" step="0.01" min="0"
                                   value="{{ old('desconto', $documento->desconto ?? 0) }}"
                                   class="form-control" oninput="calcularTotal()">
                        </div>
                    </div>
                    <div class="border-top pt-3">
                        <div class="d-flex justify-content-between fs-4 fw-bold">
                            <span>Total:</span>
                            <span class="text-success" id="totalDisplay">R$ 0,00</span>
                        </div>
                    </div>
                </div>
            </div>

            <div class="d-grid gap-2">
                <button type="submit" class="btn btn-primary btn-lg">
                    <i class="fa-solid fa-floppy-disk me-2"></i>
                    {{ $documento->exists ? 'Salvar' : 'Criar Documento' }}
                </button>
                <a href="{{ route('financeiro.documentos.index') }}" class="btn btn-outline-secondary">Cancelar</a>
            </div>
        </div>
    </div>
</form>
@endsection

@push('scripts')
<script>
let itemIndex = 0;

function adicionarItem(descricao = '', quantidade = 1, valorUnitario = '', descontoItem = 0) {
    const tbody = document.getElementById('itensBody');
    const row = document.createElement('tr');
    row.innerHTML = `
        <td><input type="text" name="itens[${itemIndex}][descricao]" value="${descricao}" class="form-control form-control-sm" placeholder="Descrição do item" required></td>
        <td><input type="number" name="itens[${itemIndex}][quantidade]" value="${quantidade}" class="form-control form-control-sm" step="0.001" min="0.001" oninput="calcularTotal()" required></td>
        <td><input type="number" name="itens[${itemIndex}][valor_unitario]" value="${valorUnitario}" class="form-control form-control-sm" step="0.01" min="0.01" oninput="calcularTotal()" required></td>
        <td><input type="number" name="itens[${itemIndex}][desconto_item]" value="${descontoItem}" class="form-control form-control-sm" step="0.01" min="0" oninput="calcularTotal()"></td>
        <td class="text-end fw-semibold item-total">R$ 0,00</td>
        <td><button type="button" class="btn btn-sm btn-outline-danger" onclick="this.closest('tr').remove(); calcularTotal()"><i class="fa-solid fa-trash"></i></button></td>
    `;
    tbody.appendChild(row);
    itemIndex++;
    calcularTotal();
}

function calcularTotal() {
    let subtotal = 0;
    document.querySelectorAll('#itensBody tr').forEach(row => {
        const qty = parseFloat(row.querySelector('[name*="quantidade"]').value) || 0;
        const price = parseFloat(row.querySelector('[name*="valor_unitario"]').value) || 0;
        const disc = parseFloat(row.querySelector('[name*="desconto_item"]').value) || 0;
        const total = (qty * price) - disc;
        row.querySelector('.item-total').textContent = 'R$ ' + total.toFixed(2).replace('.', ',');
        subtotal += total;
    });

    const desconto = parseFloat(document.getElementById('descontoGlobal').value) || 0;
    const total = subtotal - desconto;

    document.getElementById('subtotalDisplay').textContent = 'R$ ' + subtotal.toFixed(2).replace('.', ',');
    document.getElementById('totalDisplay').textContent = 'R$ ' + total.toFixed(2).replace('.', ',');
}

// Carregar itens existentes (edição)
document.addEventListener('DOMContentLoaded', function() {
    @if($documento->exists && $documento->itens->count())
        @foreach($documento->itens as $item)
            adicionarItem(
                @json($item->descricao),
                {{ $item->quantidade }},
                {{ $item->valor_unitario }},
                {{ $item->desconto_item }}
            );
        @endforeach
    @else
        adicionarItem();
    @endif
});
</script>
@endpush
