<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class OrdemServicoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'cliente_id'             => ['required', 'exists:clientes,id'],
            'data_prevista_entrega'  => ['nullable', 'date', 'after_or_equal:today'],
            'descricao_servico'      => ['required', 'string', 'max:5000'],
            'observacoes_internas'   => ['nullable', 'string', 'max:2000'],
            'observacoes_cliente'    => ['nullable', 'string', 'max:2000'],
            'valor_servico'          => ['required', 'numeric', 'min:0'],
            'custos_adicionais'      => ['nullable', 'numeric', 'min:0'],
            'desconto'               => ['nullable', 'numeric', 'min:0'],
            'forma_pagamento'        => ['nullable', 'string', 'max:50'],
            'status'                 => ['nullable', 'in:ABERTA,PRODUCAO,AGUARDANDO_APROVACAO,FINALIZADA,ENTREGUE,CANCELADA'],
            'status_pagamento'       => ['nullable', 'in:PENDENTE,PAGO_PARCIAL,PAGO'],

            // Itens da OS
            'itens'                  => ['nullable', 'array'],
            'itens.*.produto_id'     => ['nullable', 'exists:produtos,id'],
            'itens.*.descricao_item' => ['nullable', 'string', 'max:255'],
            'itens.*.quantidade'     => ['required_with:itens', 'numeric', 'min:0.001'],
            'itens.*.preco_unitario' => ['required_with:itens', 'numeric', 'min:0'],
        ];
    }

    public function messages(): array
    {
        return [
            'cliente_id.required'        => 'Selecione um cliente.',
            'cliente_id.exists'          => 'Cliente inválido.',
            'descricao_servico.required' => 'Descreva o serviço a ser realizado.',
            'valor_servico.required'     => 'Informe o valor do serviço.',
            'data_prevista_entrega.after_or_equal' => 'A data prevista não pode ser no passado.',
        ];
    }
}
