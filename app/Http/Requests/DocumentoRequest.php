<?php

namespace App\Http\Requests;

use App\Models\Documento;
use Illuminate\Foundation\Http\FormRequest;

class DocumentoRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'tipo'                 => ['required', 'in:' . implode(',', array_keys(Documento::TIPOS))],
            'cliente_id'           => ['required', 'exists:clientes,id'],
            'data_emissao'         => ['required', 'date'],
            'data_vencimento'      => ['nullable', 'date'],
            'desconto'             => ['nullable', 'numeric', 'min:0'],
            'forma_pagamento'      => ['nullable', 'string', 'max:50'],
            'observacoes'          => ['nullable', 'string', 'max:2000'],
            'condicoes_pagamento'  => ['nullable', 'string', 'max:500'],
            // Itens dinâmicos
            'itens'                => ['required', 'array', 'min:1'],
            'itens.*.descricao'    => ['required', 'string', 'max:255'],
            'itens.*.quantidade'   => ['required', 'numeric', 'min:0.001'],
            'itens.*.valor_unitario' => ['required', 'numeric', 'min:0.01'],
            'itens.*.desconto_item'  => ['nullable', 'numeric', 'min:0'],
        ];
    }

    public function messages(): array
    {
        return [
            'tipo.required'                  => 'Selecione o tipo do documento.',
            'cliente_id.required'            => 'Selecione o cliente.',
            'itens.required'                 => 'Adicione pelo menos um item.',
            'itens.min'                      => 'O documento precisa de ao menos 1 item.',
            'itens.*.descricao.required'     => 'Informe a descrição do item.',
            'itens.*.valor_unitario.required' => 'Informe o valor do item.',
        ];
    }
}
