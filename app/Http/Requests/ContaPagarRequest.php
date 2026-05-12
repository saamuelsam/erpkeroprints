<?php

namespace App\Http\Requests;

use App\Models\ContaPagar;
use Illuminate\Foundation\Http\FormRequest;

class ContaPagarRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'fornecedor_id'   => ['nullable', 'exists:fornecedores,id'],
            'descricao'       => ['required', 'string', 'max:255'],
            'valor'           => ['required', 'numeric', 'min:0.01'],
            'data_emissao'    => ['required', 'date'],
            'data_vencimento' => ['required', 'date', 'after_or_equal:data_emissao'],
            'forma_pagamento' => ['nullable', 'string', 'max:50'],
            'categoria'       => ['nullable', 'string', 'in:' . implode(',', array_keys(ContaPagar::CATEGORIAS))],
            'observacoes'     => ['nullable', 'string', 'max:2000'],
        ];
    }

    public function messages(): array
    {
        return [
            'descricao.required'             => 'Informe a descrição.',
            'valor.min'                      => 'O valor deve ser maior que zero.',
            'data_vencimento.after_or_equal' => 'O vencimento não pode ser anterior à emissão.',
        ];
    }
}
