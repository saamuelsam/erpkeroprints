<?php

namespace App\Services;

use App\Models\OrdemServico;
use App\Models\EstoqueMovimentacao;
use App\Models\FinanceiroEntrada;
use App\Models\OrdemServicoItem;
use App\Models\OrdemServicoHistorico;
use App\Models\Produto;
use App\Models\Venda;
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
            $this->registrarVendaRecebidaDaOs($os);

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

            $statusFinal = $dados['status'] ?? $os->status;
            $pagamentoFinal = $dados['status_pagamento'] ?? $os->status_pagamento;

            if ($pagamentoFinal === 'PAGO' && in_array($statusFinal, ['FINALIZADA', 'ENTREGUE'], true)) {
                $this->baixarEstoqueMateriais($os);
                $this->registrarVendaRecebidaDaOs($os->fresh(['itens', 'cliente']));
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

    private function criarVendaDaOs(OrdemServico $os): Venda
    {
        $existente = Venda::where('ordem_servico_id', $os->id)
            ->where('status', '!=', 'CANCELADA')
            ->first();

        if ($existente) {
            return $existente;
        }

        $os->loadMissing('itens');
        $subtotal = round((float) $os->valor_final + (float) $os->desconto, 2);

        $venda = Venda::create([
            'numero' => Venda::gerarNumero(),
            'cliente_id' => $os->cliente_id,
            'cliente_nome' => $os->cliente_id ? null : $os->cliente_nome,
            'ordem_servico_id' => $os->id,
            'user_id' => Auth::id(),
            'subtotal' => $subtotal,
            'desconto' => (float) $os->desconto,
            'valor_total' => (float) $os->valor_final,
            'valor_recebido' => null,
            'troco' => 0,
            'forma_pagamento' => $this->mapearFormaPagamentoVenda($os->forma_pagamento),
            'status' => 'PAGA',
            'pago_em' => now(),
        ]);

        if ($os->itens->isEmpty()) {
            $venda->itens()->create([
                'produto_id' => null,
                'descricao' => "OS #{$os->numero_os} - " . ($os->descricao_servico ?: 'Servico avulso'),
                'quantidade' => 1,
                'preco_unitario' => (float) $os->valor_final,
                'custo_unitario' => (float) $os->custo_total,
                'total_item' => (float) $os->valor_final,
            ]);

            return $venda;
        }

        foreach ($os->itens as $item) {
            $venda->itens()->create([
                'produto_id' => $item->produto_id,
                'descricao' => $item->descricao_item,
                'quantidade' => (float) $item->quantidade,
                'preco_unitario' => (float) $item->preco_unitario,
                'custo_unitario' => (float) $item->custo_unitario,
                'total_item' => (float) $item->total_item,
            ]);
        }

        $valorItens = (float) $os->itens->sum('total_item');
        $valorServico = max(0, round((float) $os->valor_servico + (float) $os->custos_adicionais, 2));

        if ($valorServico > 0) {
            $venda->itens()->create([
                'produto_id' => null,
                'descricao' => "OS #{$os->numero_os} - Servico",
                'quantidade' => 1,
                'preco_unitario' => $valorServico,
                'custo_unitario' => 0,
                'total_item' => $valorServico,
            ]);
        }

        $subtotalCalculado = round($valorItens + $valorServico, 2);
        if (abs($subtotalCalculado - $subtotal) > 0.009) {
            $venda->update(['subtotal' => $subtotalCalculado]);
        }

        return $venda;
    }

    private function registrarVendaRecebidaDaOs(OrdemServico $os): Venda
    {
        $venda = $this->criarVendaDaOs($os);

        FinanceiroEntrada::where('origem_tipo', 'os')
            ->where('origem_id', $os->id)
            ->where('status', '!=', 'CANCELADA')
            ->update([
                'origem_tipo' => 'venda',
                'origem_id' => $venda->id,
            ]);

        $this->caixaService->registrarEntradaAutomatica(
            descricao: "Recebimento da OS #{$os->numero_os}",
            valor: (float) $os->valor_final,
            categoria: 'PAGAMENTO_OS',
            formaPagamento: $os->forma_pagamento ?: 'A combinar',
            clienteId: $os->cliente_id,
            origemTipo: 'venda',
            origemId: $venda->id
        );

        return $venda;
    }

    private function mapearFormaPagamentoVenda(?string $formaPagamento): string
    {
        $forma = strtolower(iconv('UTF-8', 'ASCII//TRANSLIT', $formaPagamento ?? '') ?: ($formaPagamento ?? ''));

        if (str_contains($forma, 'dinheiro')) {
            return 'DINHEIRO';
        }

        if (str_contains($forma, 'pix')) {
            return 'PIX';
        }

        if (str_contains($forma, 'debito')) {
            return 'CARTAO_DEBITO';
        }

        if (str_contains($forma, 'credito')) {
            return 'CARTAO_CREDITO';
        }

        return 'OUTROS';
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
