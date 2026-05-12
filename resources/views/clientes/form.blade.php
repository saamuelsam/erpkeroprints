@extends('layouts.app')

@section('title', $cliente->exists ? 'Editar Cliente' : 'Novo Cliente')
@section('page-title', $cliente->exists ? 'Editar Cliente' : 'Novo Cliente')

@section('content')
<div class="d-flex align-items-center mb-4 gap-3">
    <a href="{{ route('clientes.index') }}" class="btn btn-sm btn-outline-secondary">
        <i class="fa-solid fa-arrow-left me-1"></i>Voltar
    </a>
    <h4 class="mb-0 fw-bold">{{ $cliente->exists ? 'Editar: ' . $cliente->nome : 'Novo Cliente' }}</h4>
</div>

<form method="POST" action="{{ $cliente->exists ? route('clientes.update', $cliente) : route('clientes.store') }}">
    @csrf
    @if($cliente->exists) @method('PUT') @endif

    <div class="row g-4">
        {{-- Dados Principais --}}
        <div class="col-12 col-lg-8">
            <div class="card">
                <div class="card-header"><i class="fa-solid fa-user me-2"></i>Dados do Cliente</div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-12">
                            <label class="form-label fw-semibold">Nome <span class="text-danger">*</span></label>
                            <input type="text" name="nome" value="{{ old('nome', $cliente->nome) }}"
                                   class="form-control @error('nome') is-invalid @enderror"
                                   placeholder="Nome completo ou razão social">
                            @error('nome') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

                        <div class="col-12 col-md-6">
                            <label class="form-label fw-semibold">CPF / CNPJ</label>
                            <input type="text" name="cpf_cnpj" value="{{ old('cpf_cnpj', $cliente->cpf_cnpj) }}"
                                   class="form-control @error('cpf_cnpj') is-invalid @enderror"
                                   placeholder="000.000.000-00 ou 00.000.000/0000-00" id="cpf_cnpj">
                            @error('cpf_cnpj') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

                        <div class="col-12 col-md-6">
                            <label class="form-label fw-semibold">Telefone</label>
                            <input type="text" name="telefone" value="{{ old('telefone', $cliente->telefone) }}"
                                   class="form-control" placeholder="(11) 99999-9999" id="telefone">
                        </div>

                        <div class="col-12">
                            <label class="form-label fw-semibold">E-mail</label>
                            <input type="email" name="email" value="{{ old('email', $cliente->email) }}"
                                   class="form-control @error('email') is-invalid @enderror"
                                   placeholder="cliente@email.com">
                            @error('email') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

                        <div class="col-12">
                            <label class="form-label fw-semibold">Observações</label>
                            <textarea name="observacoes" class="form-control" rows="3"
                                      placeholder="Informações adicionais sobre o cliente...">{{ old('observacoes', $cliente->observacoes) }}</textarea>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Endereço --}}
            <div class="card mt-4">
                <div class="card-header"><i class="fa-solid fa-location-dot me-2"></i>Endereço</div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-12 col-md-3">
                            <label class="form-label fw-semibold">CEP</label>
                            <input type="text" name="cep" value="{{ old('cep', $cliente->cep) }}"
                                   class="form-control" placeholder="00000-000" id="cep" maxlength="9">
                        </div>
                        <div class="col-12 col-md-7">
                            <label class="form-label fw-semibold">Endereço</label>
                            <input type="text" name="endereco" value="{{ old('endereco', $cliente->endereco) }}"
                                   class="form-control" placeholder="Rua, Avenida..." id="endereco">
                        </div>
                        <div class="col-12 col-md-2">
                            <label class="form-label fw-semibold">Número</label>
                            <input type="text" name="numero" value="{{ old('numero', $cliente->numero) }}"
                                   class="form-control" placeholder="Nº">
                        </div>
                        <div class="col-12 col-md-4">
                            <label class="form-label fw-semibold">Complemento</label>
                            <input type="text" name="complemento" value="{{ old('complemento', $cliente->complemento) }}"
                                   class="form-control" placeholder="Apto, Sala..." id="complemento">
                        </div>
                        <div class="col-12 col-md-4">
                            <label class="form-label fw-semibold">Bairro</label>
                            <input type="text" name="bairro" value="{{ old('bairro', $cliente->bairro) }}"
                                   class="form-control" id="bairro">
                        </div>
                        <div class="col-12 col-md-3">
                            <label class="form-label fw-semibold">Cidade</label>
                            <input type="text" name="cidade" value="{{ old('cidade', $cliente->cidade) }}"
                                   class="form-control" id="localidade">
                        </div>
                        <div class="col-12 col-md-1">
                            <label class="form-label fw-semibold">UF</label>
                            <input type="text" name="estado" value="{{ old('estado', $cliente->estado) }}"
                                   class="form-control" maxlength="2" id="uf" style="text-transform:uppercase">
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Sidebar do Form --}}
        <div class="col-12 col-lg-4">
            <div class="card">
                <div class="card-header"><i class="fa-solid fa-sliders me-2"></i>Configurações</div>
                <div class="card-body">
                    <div class="form-check form-switch mb-3">
                        <input class="form-check-input" type="checkbox" name="ativo" value="1" id="ativo"
                               {{ old('ativo', $cliente->ativo ?? true) ? 'checked' : '' }}>
                        <label class="form-check-label fw-semibold" for="ativo">Cliente ativo</label>
                        <div class="text-muted small">Clientes inativos não aparecem nas buscas.</div>
                    </div>
                </div>
                <div class="card-footer">
                    <button type="submit" class="btn btn-primary w-100">
                        <i class="fa-solid fa-floppy-disk me-2"></i>
                        {{ $cliente->exists ? 'Salvar alterações' : 'Cadastrar cliente' }}
                    </button>
                </div>
            </div>
        </div>
    </div>
</form>
@endsection

@push('scripts')
<script>
// Auto-preenchimento de CEP via ViaCEP
document.getElementById('cep')?.addEventListener('blur', function () {
    const cep = this.value.replace(/\D/g, '');
    if (cep.length !== 8) return;

    fetch(`https://viacep.com.br/ws/${cep}/json/`)
        .then(r => r.json())
        .then(data => {
            if (data.erro) return;
            document.getElementById('endereco').value = data.logradouro || '';
            document.getElementById('bairro').value = data.bairro || '';
            document.getElementById('localidade').value = data.localidade || '';
            document.getElementById('uf').value = data.uf || '';
            document.getElementById('complemento').focus();
        })
        .catch(() => {});
});
</script>
@endpush
