<?php

namespace App\Services;

use App\Models\OrdemServico;
use App\Models\EstoqueMovimentacao;
use App\Models\OrdemServicoItem;
use App\Models\OrdemServicoHistorico;
use App\Models\Produto;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use RuntimeException;

class OrdemServicoService
{
    public function __construct(
        protected EstoqueService $estoqueService,
        protected CaixaService $caixaService
    )
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
            $dados = $this->normalizarDados($dados);

            // Calcular valor_final antes de salvar
            $dados['valor_final'] = $this->calcularValorFinal(
                (float)($dados['valor_servico'] ?? 0),
                0,
                (float)($dados['custos_adicionais'] ?? 0),
                (float)($dados['desconto'] ?? 0)
            );

            $os = OrdemServico::create($dados);

            $this->sincronizarItens($os, $itens);

            $this->registrarHistorico($os, null, $os->status);

            return $os->fresh(['itens', 'cliente']);
        });
    }

    public function criarEReceber(array $dados, array $itens = []): OrdemServico
    {
        return DB::transaction(function () use ($dados, $itens) {
            $dados['status'] = 'FINALIZADA';
            $dados['status_pagamento'] = 'PAGO';
            $dados['data_conclusao'] = now()->toDateString();

            $os = $this->criar($dados, $itens);

            $this->baixarEstoqueMateriais($os);
            $this->caixaService->registrarEntradaAutomatica(
                descricao: "Recebimento da OS #{$os->numero_os}",
                valor: (float) $os->valor_final,
                categoria: 'PAGAMENTO_OS',
                formaPagamento: $os->forma_pagamento ?: 'A combinar',
                clienteId: $os->cliente_id,
                origemTipo: 'os',
                origemId: $os->id
            );

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
            $dados = $this->normalizarDados($dados);

            $mudouStatus = isset($dados['status']) && $dados['status'] !== $statusAnterior;

            if ($mudouStatus) {
                $this->validarMudancaStatus($os, $dados['status']);
            }

            // Recalcular valor_final
            $dados['valor_final'] = $this->calcularValorFinal(
                (float)($dados['valor_servico'] ?? $os->valor_servico),
                (float)$os->itens()->sum('total_item'),
                (float)($dados['custos_adicionais'] ?? $os->custos_adicionais),
                (float)($dados['desconto'] ?? $os->desconto)
            );

            $os->update($dados);

            $this->sincronizarItens($os, $itens);

            if ($mudouStatus) {
                $this->registrarHistorico($os, $statusAnterior, $dados['status']);

                if ($dados['status'] === 'FINALIZADA') {
                    $this->baixarEstoqueMateriais($os);
                }
            }

            return $os->fresh(['itens', 'cliente']);
        });
    }

    public function atualizarStatus(OrdemServico $os, string $novoStatus): OrdemServico
    {
        return DB::transaction(function () use ($os, $novoStatus) {
            $statusAnterior = $os->status;

            if ($novoStatus === $statusAnterior) {
                return $os->fresh(['itens', 'cliente']);
            }

            $this->validarMudancaStatus($os, $novoStatus);

            $dados = ['status' => $novoStatus];

            if (in_array($novoStatus, ['FINALIZADA', 'ENTREGUE'], true) && !$os->data_conclusao) {
                $dados['data_conclusao'] = now()->toDateString();
            }

            $os->update($dados);
            $this->registrarHistorico($os, $statusAnterior, $novoStatus);

            if ($novoStatus === 'FINALIZADA') {
                $this->baixarEstoqueMateriais($os);
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
            $os->custo_materiais = 0;
            $os->valor_final = $this->calcularValorFinal(
                (float)$os->valor_servico,
                0,
                (float)$os->custos_adicionais,
                (float)$os->desconto
            );
            $os->saveQuietly();
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
        $valorTotalItens     = 0.0;
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
            $valorTotalItens     += $totalItem;
        }

        // ── INSERT em lote ao invés de N inserts individuais ────────────────────
        if (!empty($registros)) {
            OrdemServicoItem::insert($registros);
        }

        // Atualiza custo e valor final na OS
        $os->custo_materiais = round($custoTotalMateriais, 2);
        $os->valor_final     = $this->calcularValorFinal(
            (float)$os->valor_servico,
            $valorTotalItens,
            (float)$os->custos_adicionais,
            (float)$os->desconto
        );
        $os->saveQuietly(); // saveQuietly evita disparar eventos desnecessários
    }

    private function calcularValorFinal(float $servico, float $itens, float $adicionais, float $desconto): float
    {
        return max(0, round($servico + $itens + $adicionais - $desconto, 2));
    }

    /**
     * Baixa o estoque de todos os materiais ao finalizar a OS.
     */
    private function baixarEstoqueMateriais(OrdemServico $os): void
    {
        $jaBaixouEstoque = EstoqueMovimentacao::where('tipo', 'SAIDA_OS')
            ->where('referencia_tipo', 'os')
            ->where('referencia_id', $os->id)
            ->exists();

        if ($jaBaixouEstoque) {
            return;
        }

        // Recarrega itens com eager loading para evitar N+1
        $os->load('itens');

        $produtoIds = $os->itens->whereNotNull('produto_id')->pluck('produto_id');
        $produtos   = Produto::whereIn('id', $produtoIds)->get()->keyBy('id');

        foreach ($os->itens as $item) {
            if ($item->produto_id && $produtos->has($item->produto_id) && $produtos->get($item->produto_id)->controla_estoque) {
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

    private function normalizarDados(array $dados): array
    {
        foreach (['cliente_id', 'cliente_nome', 'descricao_servico', 'data_prevista_entrega', 'forma_pagamento'] as $campo) {
            if (array_key_exists($campo, $dados) && $dados[$campo] === '') {
                $dados[$campo] = null;
            }
        }

        if (!empty($dados['cliente_id'])) {
            $dados['cliente_nome'] = null;
        }

        foreach (['valor_servico', 'custos_adicionais', 'desconto'] as $campo) {
            if (!array_key_exists($campo, $dados) || $dados[$campo] === null || $dados[$campo] === '') {
                $dados[$campo] = 0;
            }
        }

        return $dados;
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
