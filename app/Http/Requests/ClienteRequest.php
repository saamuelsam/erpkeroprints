<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ClienteRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // Controle via middleware auth na rota
    }

    public function rules(): array
    {
        // Em update, ignora o registro atual para validação unique
        $clienteId = $this->route('cliente')?->id;

        return [
            'nome'        => ['required', 'string', 'max:150'],
            'telefone'    => ['nullable', 'string', 'max:20'],
            'cpf_cnpj'    => [
                'nullable',
                'string',
                'max:20',
                "unique:clientes,cpf_cnpj,{$clienteId},id,deleted_at,NULL",
            ],
            'email'       => ['nullable', 'email', 'max:150'],
            'cep'         => ['nullable', 'string', 'max:9'],
            'endereco'    => ['nullable', 'string', 'max:255'],
            'numero'      => ['nullable', 'string', 'max:20'],
            'complemento' => ['nullable', 'string', 'max:100'],
            'bairro'      => ['nullable', 'string', 'max:100'],
            'cidade'      => ['nullable', 'string', 'max:100'],
            'estado'      => ['nullable', 'string', 'size:2'],
            'observacoes' => ['nullable', 'string', 'max:2000'],
            'ativo'       => ['boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'nome.required'        => 'O nome do cliente é obrigatório.',
            'email.email'          => 'Informe um e-mail válido.',
            'cpf_cnpj.unique'      => 'Este CPF/CNPJ já está cadastrado.',
            'estado.size'          => 'Estado deve ter 2 caracteres (ex: SP, RJ).',
        ];
    }
}
