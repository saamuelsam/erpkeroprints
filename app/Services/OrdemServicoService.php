<?php

namespace App\Services;

use App\Models\OrdemServico;
use App\Models\OrdemServicoItem;
use App\Models\OrdemServicoHistorico;
use App\Models\Produto;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use RuntimeException;

class OrdemServicoService
{
    public function __construct(protected EstoqueService $estoqueService)
    {
    }

    /**
     * Cria uma nova Ordem de Serviço com número sequencial automático.
     */
    public function criar(array $dados, array $itens = []): OrdemServico
    {
        return DB::transaction(function () use ($dados, $itens) {
            $dados['numero_os']    = $this->gerarNumeroOs();
            $dados['user_id']      = Auth::id();
            $dados['data_abertura']= now()->toDateString();
            $dados['status']       = $dados['status'] ?? 'ABERTA';
            $dados['status_pagamento'] = $dados['status_pagamento'] ?? 'PENDENTE';

            // Calcular valor_final antes de salvar
            $dados['valor_final'] = $this->calcularValorFinal(
                (float)($dados['valor_servico'] ?? 0),
                (float)($dados['custos_adicionais'] ?? 0),
                (float)($dados['desconto'] ?? 0)
            );

            $os = OrdemServico::create($dados);

            if (!empty($itens)) {
                $this->sincronizarItens($os, $itens);
            }

            $this->registrarHistorico($os, null, $os->status);

            return $os->fresh(['itens', 'cliente']);
        });
    }

    /**
     * Atualiza OS com validação de transição de status.
     */
    public function atualizar(OrdemServico $os, array $dados, array $itens = []): OrdemServico
    {
        return DB::transaction(function () use ($os, $dados, $itens) {
            $statusAnterior = $os->status;

            $mudouStatus = isset($dados['status']) && $dados['status'] !== $statusAnterior;

            if ($mudouStatus) {
                $this->validarMudancaStatus($os, $dados['status']);
            }

            // Recalcular valor_final
            $dados['valor_final'] = $this->calcularValorFinal(
                (float)($dados['valor_servico'] ?? $os->valor_servico),
                (float)($dados['custos_adicionais'] ?? $os->custos_adicionais),
                (float)($dados['desconto'] ?? $os->desconto)
            );

            $os->update($dados);

            if ($mudouStatus) {
                $this->registrarHistorico($os, $statusAnterior, $dados['status']);

                if ($dados['status'] === 'FINALIZADA') {
                    $this->baixarEstoqueMateriais($os);
                }
            }

            if (!empty($itens)) {
                $this->sincronizarItens($os, $itens);
            }

            return $os->fresh(['itens', 'cliente']);
        });
    }

    /**
     * Sincroniza os itens da OS com otimização:
     * carrega todos os produtos necessários em UMA só query.
     */
    private function sincronizarItens(OrdemServico $os, array $itens): void
    {
        // Remove itens inválidos (sem quantidade ou sem descrição)
        $itens = array_filter($itens, fn($i) =>
            !empty($i['quantidade']) && (float)$i['quantidade'] > 0
            && (!empty($i['produto_id']) || !empty($i['descricao_item']))
        );

        if (empty($itens)) {
            $os->itens()->delete();
            return;
        }

        // ── Otimização: busca TODOS os produtos necessários de uma vez ──────────
        $produtoIds = array_filter(array_column($itens, 'produto_id'));
        $produtos   = Collection::make();

        if (!empty($produtoIds)) {
            $produtos = Produto::whereIn('id', $produtoIds)
                ->get(['id', 'nome', 'custo_unitario'])
                ->keyBy('id');
        }

        // Deleta e recria os itens
        $os->itens()->delete();

        $registros           = [];
        $custoTotalMateriais = 0.0;
        $now                 = now();

        foreach ($itens as $item) {
            $custoUnitario = 0.0;
            $descricaoItem = $item['descricao_item'] ?? '';

            if (!empty($item['produto_id']) && $produtos->has($item['produto_id'])) {
                $produto       = $produtos->get($item['produto_id']);
                $custoUnitario = (float)$produto->custo_unitario;
                $descricaoItem = $descricaoItem ?: $produto->nome;
            }

            $quantidade = (float)$item['quantidade'];
            $precoUnit  = (float)($item['preco_unitario'] ?? 0);
            $totalItem  = round($quantidade * $precoUnit, 2);
            $custoItem  = round($quantidade * $custoUnitario, 2);

            $registros[] = [
                'ordem_servico_id' => $os->id,
                'produto_id'       => $item['produto_id'] ?? null,
                'descricao_item'   => $descricaoItem ?: 'Item avulso',
                'quantidade'       => $quantidade,
                'custo_unitario'   => $custoUnitario,
                'preco_unitario'   => $precoUnit,
                'total_item'       => $totalItem,
                'created_at'       => $now,
                'updated_at'       => $now,
            ];

            $custoTotalMateriais += $custoItem;
        }

        // ── INSERT em lote ao invés de N inserts individuais ────────────────────
        if (!empty($registros)) {
            OrdemServicoItem::insert($registros);
        }

        // Atualiza custo e valor final na OS
        $os->custo_materiais = round($custoTotalMateriais, 2);
        $os->valor_final     = $this->calcularValorFinal(
            (float)$os->valor_servico,
            (float)$os->custos_adicionais,
            (float)$os->desconto
        );
        $os->saveQuietly(); // saveQuietly evita disparar eventos desnecessários
    }

    private function calcularValorFinal(float $servico, float $adicionais, float $desconto): float
    {
        return max(0, round($servico + $adicionais - $desconto, 2));
    }

    /**
     * Baixa o estoque de todos os materiais ao finalizar a OS.
     */
    private function baixarEstoqueMateriais(OrdemServico $os): void
    {
        // Recarrega itens com eager loading para evitar N+1
        $os->load('itens');

        $produtoIds = $os->itens->whereNotNull('produto_id')->pluck('produto_id');
        $produtos   = Produto::whereIn('id', $produtoIds)->get()->keyBy('id');

        foreach ($os->itens as $item) {
            if ($item->produto_id && $produtos->has($item->produto_id)) {
                $this->estoqueService->movimentar(
                    produto: $produtos->get($item->produto_id),
                    tipo: 'SAIDA_OS',
                    quantidade: (float)$item->quantidade,
                    custoUnitario: (float)$item->custo_unitario,
                    extras: [
                        'motivo'          => "OS #{$os->numero_os}",
                        'referencia_tipo' => 'os',
                        'referencia_id'   => $os->id,
                    ]
                );
            }
        }
    }

    private function validarMudancaStatus(OrdemServico $os, string $novoStatus): void
    {
        if ($os->status === 'ENTREGUE' && !Auth::user()->isAdmin()) {
            throw new RuntimeException('Apenas administradores podem alterar uma OS já entregue.');
        }

        if ($os->status === 'CANCELADA') {
            throw new RuntimeException('Não é possível alterar uma OS cancelada.');
        }
    }

    private function registrarHistorico(OrdemServico $os, ?string $anterior, string $novo): void
    {
        OrdemServicoHistorico::create([
            'ordem_servico_id' => $os->id,
            'user_id'          => Auth::id(),
            'status_anterior'  => $anterior,
            'status_novo'      => $novo,
        ]);
    }

    private function gerarNumeroOs(): string
    {
        // Lock para evitar número duplicado em concorrência
        $ano      = now()->year;
        $ultimo   = OrdemServico::withTrashed()
            ->whereYear('created_at', $ano)
            ->lockForUpdate()
            ->count();
        $sequencial = str_pad($ultimo + 1, 5, '0', STR_PAD_LEFT);
        return "OS-{$ano}-{$sequencial}";
    }
}
