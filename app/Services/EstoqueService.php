<?php

namespace App\Services;

use App\Models\Produto;
use App\Models\EstoqueMovimentacao;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use RuntimeException;

class EstoqueService
{
    /**
     * Registra uma movimentação de estoque e atualiza o produto.
     * Usa transação para garantir integridade total.
     *
     * @param  Produto  $produto
     * @param  string   $tipo     Ex: 'ENTRADA_COMPRA', 'SAIDA_VENDA'
     * @param  float    $quantidade  Sempre positivo; o tipo define a direção
     * @param  float    $custoUnitario
     * @param  array    $extras   ['motivo', 'referencia_tipo', 'referencia_id']
     * @return EstoqueMovimentacao
     */
    public function movimentar(
        Produto $produto,
        string $tipo,
        float $quantidade,
        float $custoUnitario,
        array $extras = []
    ): EstoqueMovimentacao {
        return DB::transaction(function () use ($produto, $tipo, $quantidade, $custoUnitario, $extras) {
            // Bloqueia a linha do produto para evitar race condition
            $produto = Produto::lockForUpdate()->findOrFail($produto->id);

            $estoqueAnterior = (float) $produto->quantidade_estoque;

            if ($this->isSaida($tipo)) {
                $this->validarEstoqueSuficiente($produto, $quantidade);
                $novoEstoque = $estoqueAnterior - $quantidade;
            } else {
                $novoEstoque = $estoqueAnterior + $quantidade;
            }

            // Atualiza o estoque do produto
            $produto->quantidade_estoque = $novoEstoque;
            if ($this->isEntrada($tipo)) {
                // Atualiza custo unitário na entrada (custo médio ponderado pode ser implementado aqui)
                $produto->custo_unitario = $custoUnitario;
            }
            $produto->save();

            // Registra a movimentação
            return EstoqueMovimentacao::create([
                'produto_id'              => $produto->id,
                'user_id'                 => Auth::id(),
                'tipo'                    => $tipo,
                'quantidade'              => $quantidade,
                'custo_unitario_momento'  => $custoUnitario,
                'estoque_anterior'        => $estoqueAnterior,
                'estoque_posterior'       => $novoEstoque,
                'motivo'                  => $extras['motivo'] ?? null,
                'referencia_tipo'         => $extras['referencia_tipo'] ?? null,
                'referencia_id'           => $extras['referencia_id'] ?? null,
            ]);
        });
    }

    private function isSaida(string $tipo): bool
    {
        return str_starts_with($tipo, 'SAIDA_');
    }

    private function isEntrada(string $tipo): bool
    {
        return str_starts_with($tipo, 'ENTRADA_');
    }

    private function validarEstoqueSuficiente(Produto $produto, float $quantidade): void
    {
        // Futuramente: verificar configuração de "permitir estoque negativo"
        if ((float) $produto->quantidade_estoque < $quantidade) {
            throw new RuntimeException(
                "Estoque insuficiente para o produto '{$produto->nome}'. " .
                "Disponível: {$produto->quantidade_estoque} {$produto->unidade_medida}."
            );
        }
    }
}
