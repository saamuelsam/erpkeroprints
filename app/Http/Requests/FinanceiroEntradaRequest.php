<?php

namespace App\Http\Requests;

use App\Models\FinanceiroEntrada;
use Illuminate\Foundation\Http\FormRequest;

class FinanceiroEntradaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'data'            => ['required', 'date'],
            'descricao'       => ['required', 'string', 'max:255'],
            'categoria'       => ['required', 'string', 'in:' . implode(',', array_keys(FinanceiroEntrada::CATEGORIAS))],
            'valor'           => ['required', 'numeric', 'min:0.01'],
            'forma_pagamento' => ['nullable', 'string', 'max:50'],
            'cliente_id'      => ['nullable', 'exists:clientes,id'],
            'observacoes'     => ['nullable', 'string', 'max:2000'],
            'status'          => ['nullable', 'in:CONFIRMADA,PENDENTE,CANCELADA'],
        ];
    }

    public function messages(): array
    {
        return [
            'data.required'      => 'Informe a data da entrada.',
            'descricao.required' => 'Informe a descrição da entrada.',
            'categoria.required' => 'Selecione uma categoria.',
            'categoria.in'       => 'Categoria inválida.',
            'valor.required'     => 'Informe o valor da entrada.',
            'valor.min'          => 'O valor não pode ser negativo ou zero.',
        ];
    }
}
