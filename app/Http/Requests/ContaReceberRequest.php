<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ContaReceberRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'cliente_id'      => ['required', 'exists:clientes,id'],
            'descricao'       => ['required', 'string', 'max:255'],
            'valor'           => ['required', 'numeric', 'min:0.01'],
            'data_emissao'    => ['required', 'date'],
            'data_vencimento' => ['required', 'date', 'after_or_equal:data_emissao'],
            'forma_pagamento' => ['nullable', 'string', 'max:50'],
            'os_id'           => ['nullable', 'exists:ordens_servico,id'],
            'observacoes'     => ['nullable', 'string', 'max:2000'],
        ];
    }

    public function messages(): array
    {
        return [
            'cliente_id.required'             => 'Selecione o cliente.',
            'descricao.required'              => 'Informe a descrição.',
            'valor.min'                       => 'O valor deve ser maior que zero.',
            'data_vencimento.after_or_equal'  => 'O vencimento não pode ser anterior à emissão.',
        ];
    }
}
