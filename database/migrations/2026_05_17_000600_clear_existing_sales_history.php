<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::transaction(function () {
            $vendaIds = DB::table('vendas')->pluck('id');

            if ($vendaIds->isEmpty()) {
                return;
            }

            $retornosEstoque = DB::table('estoque_movimentacoes')
                ->select('produto_id', DB::raw('SUM(quantidade) as quantidade'))
                ->where('tipo', 'SAIDA_VENDA')
                ->where('referencia_tipo', 'venda')
                ->whereIn('referencia_id', $vendaIds)
                ->groupBy('produto_id')
                ->get();

            foreach ($retornosEstoque as $retorno) {
                DB::table('produtos')
                    ->where('id', $retorno->produto_id)
                    ->increment('quantidade_estoque', (float) $retorno->quantidade);
            }

            DB::table('estoque_movimentacoes')
                ->where('tipo', 'SAIDA_VENDA')
                ->where('referencia_tipo', 'venda')
                ->whereIn('referencia_id', $vendaIds)
                ->delete();

            DB::table('financeiro_entradas')
                ->where('origem_tipo', 'venda')
                ->whereIn('origem_id', $vendaIds)
                ->delete();

            DB::table('venda_itens')
                ->whereIn('venda_id', $vendaIds)
                ->delete();

            DB::table('vendas')
                ->whereIn('id', $vendaIds)
                ->delete();
        });
    }

    public function down(): void
    {
        // Dados excluidos nao podem ser restaurados automaticamente.
    }
};
