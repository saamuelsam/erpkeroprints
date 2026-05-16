<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ProdutoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $produtoId = $this->route('produto')?->id;

        return [
            'nome'               => ['required', 'string', 'max:150'],
            'categoria_id'       => ['required', 'exists:categorias,id'],
            'subcategoria_id'    => [
                'nullable',
                Rule::exists('subcategorias', 'id')->where(fn($query) => $query->where('categoria_id', $this->input('categoria_id'))),
            ],
            'codigo_interno'     => [
                'nullable', 'string', 'max:50',
                "unique:produtos,codigo_interno,{$produtoId},id,deleted_at,NULL",
            ],
            'codigo_barras'      => [
                'nullable', 'string', 'max:50',
                "unique:produtos,codigo_barras,{$produtoId},id,deleted_at,NULL",
            ],
            'custo_unitario'     => ['required', 'numeric', 'min:0'],
            'preco_venda'        => ['required', 'numeric', 'min:0'],
            'quantidade_estoque' => ['nullable', 'numeric', 'min:0'],
            'estoque_minimo'     => ['nullable', 'numeric', 'min:0'],
            'unidade_medida'     => ['required', 'string', 'max:10'],
            'ativo'              => ['boolean'],
            'observacoes'        => ['nullable', 'string', 'max:2000'],
        ];
    }

    public function messages(): array
    {
        return [
            'nome.required'         => 'O nome do produto é obrigatório.',
            'categoria_id.required' => 'Selecione uma categoria.',
            'categoria_id.exists'   => 'Categoria inválida.',
            'custo_unitario.required' => 'O custo unitário é obrigatório.',
            'preco_venda.required'  => 'O preço de venda é obrigatório.',
            'codigo_interno.unique' => 'Este código interno já está em uso.',
            'codigo_barras.unique'  => 'Este código de barras já está em uso.',
        ];
    }
}
