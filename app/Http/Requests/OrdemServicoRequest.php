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
            'acao'                   => ['nullable', 'in:salvar,pagar_agora'],
            'cliente_id'             => ['nullable', 'exists:clientes,id'],
            'data_prevista_entrega'  => ['nullable', 'date', 'after_or_equal:today'],
            'descricao_servico'      => ['nullable', 'string', 'max:5000'],
            'observacoes_internas'   => ['nullable', 'string', 'max:2000'],
            'observacoes_cliente'    => ['nullable', 'string', 'max:2000'],
            'valor_servico'          => ['nullable', 'numeric', 'min:0'],
            'custos_adicionais'      => ['nullable', 'numeric', 'min:0'],
            'desconto'               => ['nullable', 'numeric', 'min:0'],
            'forma_pagamento'        => ['nullable', 'required_if:acao,pagar_agora', 'string', 'max:50'],
            'status'                 => ['nullable', 'in:ABERTA,PRODUCAO,AGUARDANDO_APROVACAO,FINALIZADA,ENTREGUE,CANCELADA'],
            'status_pagamento'       => ['nullable', 'in:PENDENTE,PAGO_PARCIAL,PAGO'],

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
            'cliente_id.exists' => 'Cliente invalido.',
            'forma_pagamento.required_if' => 'Selecione a forma de pagamento para receber agora.',
            'data_prevista_entrega.after_or_equal' => 'A data prevista nao pode ser no passado.',
        ];
    }
}
