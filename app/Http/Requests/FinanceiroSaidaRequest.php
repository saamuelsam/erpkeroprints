<?php

namespace App\Http\Requests;

use App\Models\FinanceiroSaida;
use Illuminate\Foundation\Http\FormRequest;

class FinanceiroSaidaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'data'             => ['required', 'date'],
            'descricao'        => ['required', 'string', 'max:255'],
            'categoria'        => ['required', 'string', 'in:' . implode(',', array_keys(FinanceiroSaida::CATEGORIAS))],
            'valor'            => ['required', 'numeric', 'min:0.01'],
            'forma_pagamento'  => ['nullable', 'string', 'max:50'],
            'fornecedor_nome'  => ['nullable', 'string', 'max:150'],
            'fornecedor_id'    => ['nullable', 'exists:fornecedores,id'],
            'observacoes'      => ['nullable', 'string', 'max:2000'],
            'status'           => ['nullable', 'in:CONFIRMADA,PENDENTE,CANCELADA'],
        ];
    }

    public function messages(): array
    {
        return [
            'data.required'      => 'Informe a data da saída.',
            'descricao.required' => 'Informe a descrição da saída.',
            'categoria.required' => 'Selecione uma categoria.',
            'categoria.in'       => 'Categoria inválida.',
            'valor.required'     => 'Informe o valor da saída.',
            'valor.min'          => 'O valor não pode ser negativo ou zero.',
        ];
    }
}
