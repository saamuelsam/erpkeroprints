@extends('layouts.app')

@section('title', $produto->exists ? 'Editar Produto' : 'Novo Produto')
@section('page-title', $produto->exists ? 'Editar Produto' : 'Novo Produto')

@section('content')
<div class="d-flex align-items-center mb-4 gap-3">
    <a href="{{ route('produtos.index') }}" class="btn btn-sm btn-outline-secondary">
        <i class="fa-solid fa-arrow-left me-1"></i>Voltar
    </a>
    <h4 class="mb-0 fw-bold">{{ $produto->exists ? 'Editar: ' . $produto->nome : 'Novo Produto' }}</h4>
</div>

<form method="POST" action="{{ $produto->exists ? route('produtos.update', $produto) : route('produtos.store') }}">
    @csrf
    @if($produto->exists) @method('PUT') @endif

    <div class="row g-4">
        {{-- Coluna Principal --}}
        <div class="col-12 col-lg-8">
            <div class="card">
                <div class="card-header"><i class="fa-solid fa-box me-2"></i>Dados do Produto</div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-12">
                            <label class="form-label fw-semibold">Nome do Produto <span class="text-danger">*</span></label>
                            <input type="text" name="nome" value="{{ old('nome', $produto->nome) }}"
                                   class="form-control @error('nome') is-invalid @enderror"
                                   placeholder="Ex: Papel A4 75g Resma 500fls">
                            @error('nome') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

                        <div class="col-12 col-md-6">
                            <label class="form-label fw-semibold">Categoria <span class="text-danger">*</span></label>
                            <select name="categoria_id" id="categoria_id" class="form-select @error('categoria_id') is-invalid @enderror">
                                <option value="">Selecione...</option>
                                @foreach($categorias as $cat)
                                    <option value="{{ $cat->id }}" {{ old('categoria_id', $produto->categoria_id) == $cat->id ? 'selected' : '' }}>
                                        {{ $cat->nome_completo }}
                                    </option>
                                @endforeach
                            </select>
                            @error('categoria_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>


                        <div class="col-12 col-md-3 estoque-controlado">
                            <label class="form-label fw-semibold">Unidade de Medida</label>
                            <select name="unidade_medida" class="form-select">
                                @foreach(['UN' => 'Unidade', 'KG' => 'Quilograma', 'G' => 'Grama', 'M' => 'Metro', 'M2' => 'Metro²', 'L' => 'Litro', 'ML' => 'Mililitro', 'CX' => 'Caixa', 'PCT' => 'Pacote', 'RL' => 'Rolo'] as $val => $label)
                                    <option value="{{ $val }}" {{ old('unidade_medida', $produto->unidade_medida ?? 'UN') === $val ? 'selected' : '' }}>
                                        {{ $label }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="col-12 col-md-3 estoque-controlado">
                            <label class="form-label fw-semibold">Estoque Mínimo</label>
                            <input type="number" name="estoque_minimo" value="{{ old('estoque_minimo', $produto->estoque_minimo ?? 0) }}"
                                   class="form-control" step="0.001" min="0">
                        </div>

                        <div class="col-12 col-md-6">
                            <label class="form-label fw-semibold">Código Interno</label>
                            <input type="text" name="codigo_interno" value="{{ old('codigo_interno', $produto->codigo_interno) }}"
                                   class="form-control @error('codigo_interno') is-invalid @enderror"
                                   placeholder="Gerado automaticamente se vazio">
                            @error('codigo_interno') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

                        <div class="col-12 col-md-6">
                            <label class="form-label fw-semibold">Código de Barras (EAN)</label>
                            <input type="text" name="codigo_barras" value="{{ old('codigo_barras', $produto->codigo_barras) }}"
                                   class="form-control @error('codigo_barras') is-invalid @enderror"
                                   placeholder="Ex: 7891234567890">
                            @error('codigo_barras') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

                        <div class="col-12">
                            <label class="form-label fw-semibold">Observações</label>
                            <textarea name="observacoes" class="form-control" rows="2"
                                      placeholder="Especificações técnicas, fornecedores, etc.">{{ old('observacoes', $produto->observacoes) }}</textarea>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Sidebar Financeira --}}
        <div class="col-12 col-lg-4">
            <div class="card">
                <div class="card-header"><i class="fa-solid fa-dollar-sign me-2"></i>Preços e Estoque</div>
                <div class="card-body">
                    <div class="form-check form-switch mb-3">
                        <input type="hidden" name="controla_estoque" value="0">
                        <input class="form-check-input" type="checkbox" name="controla_estoque" value="1" id="controla_estoque"
                               {{ old('controla_estoque', $produto->controla_estoque ?? true) ? 'checked' : '' }}>
                        <label class="form-check-label fw-semibold" for="controla_estoque">Controla quantidade em estoque</label>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Custo Unitário (R$)</label>
                        <div class="input-group">
                            <span class="input-group-text">R$</span>
                            <input type="number" name="custo_unitario" id="custo_unitario"
                                   value="{{ old('custo_unitario', $produto->custo_unitario ?? 0) }}"
                                   class="form-control @error('custo_unitario') is-invalid @enderror"
                                   step="0.01" min="0">
                        </div>
                        @error('custo_unitario') <div class="text-danger small">{{ $message }}</div> @enderror
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Preço de Venda (R$) <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <span class="input-group-text">R$</span>
                            <input type="number" name="preco_venda" id="preco_venda"
                                   value="{{ old('preco_venda', $produto->preco_venda ?? 0) }}"
                                   class="form-control @error('preco_venda') is-invalid @enderror"
                                   step="0.01" min="0">
                        </div>
                        @error('preco_venda') <div class="text-danger small">{{ $message }}</div> @enderror
                    </div>

                    {{-- Cálculo de Lucro em Tempo Real --}}
                    <div class="alert alert-info py-2 px-3 small mb-3" id="lucro-preview">
                        <div class="d-flex justify-content-between"><span>Lucro unitário:</span><strong id="lucro-val">R$ 0,00</strong></div>
                        <div class="d-flex justify-content-between"><span>Margem:</span><strong id="margem-val">0%</strong></div>
                    </div>

                    @if(!$produto->exists)
                    <div class="mb-3 estoque-controlado">
                        <label class="form-label fw-semibold">Quantidade Inicial</label>
                        <input type="number" name="quantidade_estoque" value="{{ old('quantidade_estoque', 0) }}"
                               class="form-control" step="0.001" min="0">
                        <div class="form-text">Estoque inicial do produto.</div>
                    </div>
                    @endif

                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" name="ativo" value="1" id="ativo"
                               {{ old('ativo', $produto->ativo ?? true) ? 'checked' : '' }}>
                        <label class="form-check-label fw-semibold" for="ativo">Produto ativo</label>
                    </div>
                </div>
                <div class="card-footer">
                    <button type="submit" class="btn btn-primary w-100">
                        <i class="fa-solid fa-floppy-disk me-2"></i>
                        {{ $produto->exists ? 'Salvar alterações' : 'Cadastrar produto' }}
                    </button>
                </div>
            </div>
        </div>
    </div>
</form>
@endsection

@push('scripts')
<script>
// Cálculo de lucro em tempo real
function calcLucro() {
    const custo = parseFloat(document.getElementById('custo_unitario').value) || 0;
    const venda = parseFloat(document.getElementById('preco_venda').value) || 0;
    const lucro = venda - custo;
    const margem = venda > 0 ? ((lucro / venda) * 100).toFixed(1) : 0;

    document.getElementById('lucro-val').textContent = 'R$ ' + lucro.toLocaleString('pt-BR', {minimumFractionDigits:2});
    document.getElementById('margem-val').textContent = margem + '%';
    document.getElementById('lucro-preview').className = lucro >= 0
        ? 'alert alert-info py-2 px-3 small mb-3'
        : 'alert alert-danger py-2 px-3 small mb-3';
}

document.getElementById('custo_unitario')?.addEventListener('input', calcLucro);
document.getElementById('preco_venda')?.addEventListener('input', calcLucro);
calcLucro();

const controlaEstoque = document.getElementById('controla_estoque');
const camposEstoque = document.querySelectorAll('.estoque-controlado');

function alternarCamposEstoque() {
    camposEstoque.forEach(campo => campo.classList.toggle('d-none', !controlaEstoque.checked));
}

controlaEstoque?.addEventListener('change', alternarCamposEstoque);
alternarCamposEstoque();

</script>
@endpush
