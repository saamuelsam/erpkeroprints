<?php

namespace App\Services;

use App\Models\Produto;
use App\Models\Venda;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class VendaService
{
    public function __construct(
        protected EstoqueService $estoqueService,
        protected CaixaService $caixaService,
    ) {
    }

    public function criar(array $dados, array $itens, bool $confirmarAutomaticamente = true): Venda
    {
        return DB::transaction(function () use ($dados, $itens, $confirmarAutomaticamente) {
            $itensNormalizados = $this->normalizarItens($itens);

            if (empty($itensNormalizados)) {
                throw new RuntimeException('Adicione pelo menos um produto na venda.');
            }

            $subtotal = collect($itensNormalizados)->sum('total_item');
            $desconto = (float) ($dados['desconto'] ?? 0);
            $valorTotal = max(0, round($subtotal - $desconto, 2));

            if ($desconto > $subtotal) {
                throw new RuntimeException('O desconto nao pode ser maior que o subtotal da venda.');
            }

            $valorRecebido = $dados['forma_pagamento'] === 'DINHEIRO'
                ? round((float) ($dados['valor_recebido'] ?? 0), 2)
                : null;
            if (!$confirmarAutomaticamente) {
                $valorRecebido = null;
            }
            $pagamentos = !$confirmarAutomaticamente
                ? null
                : $this->normalizarPagamentos($dados, $valorTotal);

            if ($confirmarAutomaticamente && $dados['forma_pagamento'] === 'DINHEIRO' && $valorRecebido < $valorTotal) {
                throw new RuntimeException('O valor recebido em dinheiro e menor que o total da venda.');
            }
            if ($dados['forma_pagamento'] === 'MISTO') {
                $valorRecebido = $this->valorRecebidoDinheiroMisto($pagamentos);
            }

            $clienteNome = trim((string) ($dados['cliente_nome'] ?? ''));

            $venda = Venda::create([
                'numero' => Venda::gerarNumero(),
                'cliente_id' => $dados['cliente_id'] ?? null,
                'cliente_nome' => empty($dados['cliente_id']) && $clienteNome !== '' ? $clienteNome : null,
                'user_id' => Auth::id(),
                'subtotal' => $subtotal,
                'desconto' => $desconto,
                'valor_total' => $valorTotal,
                'valor_recebido' => $valorRecebido,
                'troco' => $this->calcularTroco($dados['forma_pagamento'], $valorTotal, $valorRecebido, $pagamentos),
                'forma_pagamento' => $dados['forma_pagamento'],
                'pagamentos' => $pagamentos,
                'status' => 'AGUARDANDO_PAGAMENTO',
            ]);

            foreach ($itensNormalizados as $item) {
                $venda->itens()->create($item);
            }

            if ($confirmarAutomaticamente && !$venda->usaPix()) {
                $this->confirmarPagamento($venda);
            }

            return $venda->fresh(['itens.produto', 'cliente']);
        });
    }

    public function atualizarPedidoPendente(Venda $venda, array $dados, array $itens, bool $confirmarAutomaticamente = false): Venda
    {
        return DB::transaction(function () use ($venda, $dados, $itens, $confirmarAutomaticamente) {
            $venda = Venda::lockForUpdate()->findOrFail($venda->id);

            if ($venda->status !== 'AGUARDANDO_PAGAMENTO') {
                throw new RuntimeException('Somente pedidos pendentes podem ser editados.');
            }

            if (filled($venda->mercado_pago_payment_id) || filled($venda->pix_qr_code)) {
                throw new RuntimeException('Este Pix ja foi gerado. Cancele e crie um novo pedido para alterar itens.');
            }

            $itensNormalizados = $this->normalizarItens($itens);

            if (empty($itensNormalizados)) {
                throw new RuntimeException('Adicione pelo menos um produto no pedido.');
            }

            $subtotal = collect($itensNormalizados)->sum('total_item');
            $desconto = (float) ($dados['desconto'] ?? 0);
            $valorTotal = max(0, round($subtotal - $desconto, 2));

            if ($desconto > $subtotal) {
                throw new RuntimeException('O desconto nao pode ser maior que o subtotal da venda.');
            }

            $valorRecebido = $dados['forma_pagamento'] === 'DINHEIRO'
                ? round((float) ($dados['valor_recebido'] ?? 0), 2)
                : null;
            if (!$confirmarAutomaticamente) {
                $valorRecebido = null;
            }

            $pagamentos = !$confirmarAutomaticamente
                ? null
                : $this->normalizarPagamentos($dados, $valorTotal);

            if ($confirmarAutomaticamente && $dados['forma_pagamento'] === 'DINHEIRO' && $valorRecebido < $valorTotal) {
                throw new RuntimeException('O valor recebido em dinheiro e menor que o total da venda.');
            }
            if ($dados['forma_pagamento'] === 'MISTO') {
                $valorRecebido = $this->valorRecebidoDinheiroMisto($pagamentos);
            }

            $clienteNome = trim((string) ($dados['cliente_nome'] ?? ''));

            $venda->update([
                'cliente_id' => $dados['cliente_id'] ?? null,
                'cliente_nome' => empty($dados['cliente_id']) && $clienteNome !== '' ? $clienteNome : null,
                'subtotal' => $subtotal,
                'desconto' => $desconto,
                'valor_total' => $valorTotal,
                'valor_recebido' => $valorRecebido,
                'troco' => $this->calcularTroco($dados['forma_pagamento'], $valorTotal, $valorRecebido, $pagamentos),
                'forma_pagamento' => $dados['forma_pagamento'],
                'pagamentos' => $pagamentos,
            ]);

            $venda->itens()->delete();
            foreach ($itensNormalizados as $item) {
                $venda->itens()->create($item);
            }

            if ($confirmarAutomaticamente && !$venda->usaPix()) {
                $this->confirmarPagamento($venda);
            }

            return $venda->fresh(['itens.produto', 'cliente']);
        });
    }

    public function anexarPix(Venda $venda, array $pagamento): Venda
    {
        $dadosPix = data_get($pagamento, 'point_of_interaction.transaction_data', []);

        $venda->update([
            'mercado_pago_payment_id' => (string) data_get($pagamento, 'id'),
            'mercado_pago_status' => data_get($pagamento, 'status'),
            'pix_qr_code' => data_get($dadosPix, 'qr_code'),
            'pix_qr_code_base64' => data_get($dadosPix, 'qr_code_base64'),
        ]);

        if (data_get($pagamento, 'status') === 'approved') {
            $this->confirmarPagamento($venda);
        }

        return $venda->fresh(['itens.produto', 'cliente']);
    }

    public function anexarPixManual(Venda $venda, array $pix): Venda
    {
        $venda->update([
            'mercado_pago_status' => 'manual_pix_pending',
            'pix_qr_code' => $pix['qr_code'] ?? null,
            'pix_qr_code_base64' => null,
        ]);

        $venda->setAttribute('pix_qr_code_image_url', $pix['qr_code_image_url'] ?? null);

        return $venda->fresh(['itens.produto', 'cliente']);
    }

    public function sincronizarPagamentoMercadoPago(Venda $venda, array $pagamento): Venda
    {
        $venda->update([
            'mercado_pago_status' => data_get($pagamento, 'status'),
        ]);

        if (data_get($pagamento, 'status') === 'approved') {
            $this->confirmarPagamento($venda);
        }

        return $venda->fresh(['itens.produto', 'cliente']);
    }

    public function confirmarPagamentoManual(Venda $venda, array $dados): Venda
    {
        return DB::transaction(function () use ($venda, $dados) {
            $venda = Venda::lockForUpdate()->findOrFail($venda->id);

            if ($venda->status === 'CANCELADA') {
                throw new RuntimeException('Venda cancelada nao pode ser confirmada.');
            }

            $venda->update([
                'mercado_pago_status' => 'manual_pix_confirmed',
                'pix_confirmado_por' => Auth::id(),
                'pix_confirmado_em' => now(),
                'pix_confirmacao_referencia' => $dados['pix_confirmacao_referencia'] ?? null,
                'pix_confirmacao_pagador' => $dados['pix_confirmacao_pagador'] ?? null,
                'pix_confirmacao_observacao' => $dados['pix_confirmacao_observacao'] ?? null,
            ]);

            return $this->confirmarPagamento($venda);
        });
    }

    public function confirmarPagamento(Venda $venda): Venda
    {
        return DB::transaction(function () use ($venda) {
            $venda = Venda::with('itens')->lockForUpdate()->findOrFail($venda->id);

            if ($venda->status === 'PAGA') {
                return $venda;
            }

            if ($venda->status === 'CANCELADA') {
                throw new RuntimeException('Venda cancelada nao pode ser confirmada.');
            }

            if ($venda->forma_pagamento === 'DINHEIRO' && $venda->valor_recebido === null) {
                $venda->update([
                    'valor_recebido' => (float) $venda->valor_total,
                    'troco' => 0,
                ]);
            }

            foreach ($venda->itens as $item) {
                if (!$item->produto_id) {
                    continue;
                }

                $produto = Produto::findOrFail($item->produto_id);

                if (!$produto->controla_estoque) {
                    continue;
                }

                $this->estoqueService->movimentar(
                    produto: $produto,
                    tipo: 'SAIDA_VENDA',
                    quantidade: (float) $item->quantidade,
                    custoUnitario: (float) $item->custo_unitario,
                    extras: [
                        'motivo' => "Venda {$venda->numero}",
                        'referencia_tipo' => 'venda',
                        'referencia_id' => $venda->id,
                    ]
                );
            }

            $venda->update([
                'status' => 'PAGA',
                'pago_em' => now(),
            ]);

            $this->caixaService->registrarEntradaAutomatica(
                descricao: "Venda {$venda->numero}",
                valor: (float) $venda->valor_total,
                categoria: 'VENDA',
                formaPagamento: $venda->forma_pagamento_label,
                clienteId: $venda->cliente_id,
                origemTipo: 'venda',
                origemId: $venda->id
            );

            return $venda->fresh(['itens.produto', 'cliente']);
        });
    }

    public function cancelar(Venda $venda): Venda
    {
        if ($venda->status === 'PAGA') {
            throw new RuntimeException('Venda paga nao pode ser cancelada por aqui.');
        }

        $venda->update(['status' => 'CANCELADA']);

        return $venda;
    }

    private function normalizarItens(array $itens): array
    {
        $produtoIds = collect($itens)->pluck('produto_id')->filter()->unique()->values();
        $produtos = Produto::ativos()
            ->whereIn('id', $produtoIds)
            ->get(['id', 'nome', 'preco_venda', 'custo_unitario'])
            ->keyBy('id');

        return collect($itens)
            ->map(function (array $item) use ($produtos) {
                $produtoId = $item['produto_id'] ?? null;
                $produto = $produtoId ? $produtos->get($produtoId) : null;
                $quantidade = (float) ($item['quantidade'] ?? 0);
                $preco = (float) ($produto?->preco_venda ?? 0);

                if (!$produto || $quantidade <= 0 || $preco < 0) {
                    return null;
                }

                return [
                    'produto_id' => $produto?->id,
                    'descricao' => $produto->nome,
                    'quantidade' => $quantidade,
                    'preco_unitario' => $preco,
                    'custo_unitario' => (float) ($produto?->custo_unitario ?? 0),
                    'total_item' => round($quantidade * $preco, 2),
                ];
            })
            ->filter()
            ->values()
            ->all();
    }

    private function normalizarPagamentos(array $dados, float $valorTotal): ?array
    {
        if (($dados['forma_pagamento'] ?? null) !== 'MISTO') {
            return null;
        }

        $pagamentos = collect($dados['pagamentos'] ?? [])
            ->map(function (array $pagamento) {
                $forma = $pagamento['forma'] ?? null;
                $valor = round((float) ($pagamento['valor'] ?? 0), 2);
                $valorRecebido = $forma === 'DINHEIRO'
                    ? round((float) ($pagamento['valor_recebido'] ?? $valor), 2)
                    : null;

                if (!$forma || $valor <= 0) {
                    return null;
                }

                if (!array_key_exists($forma, Venda::FORMAS_PAGAMENTO) || $forma === 'MISTO') {
                    return null;
                }

                if ($forma === 'DINHEIRO' && $valorRecebido < $valor) {
                    throw new RuntimeException('No pagamento misto, o dinheiro recebido nao pode ser menor que a parte em dinheiro.');
                }

                return [
                    'forma' => $forma,
                    'valor' => $valor,
                    'valor_recebido' => $valorRecebido,
                    'troco' => $valorRecebido === null ? 0 : max(0, round($valorRecebido - $valor, 2)),
                ];
            })
            ->filter()
            ->values();

        if ($pagamentos->count() < 2) {
            throw new RuntimeException('Informe pelo menos duas formas de pagamento no pagamento misto.');
        }

        $soma = round($pagamentos->sum(fn($pagamento) => (float) $pagamento['valor']), 2);
        if (abs($soma - $valorTotal) > 0.009) {
            throw new RuntimeException('A soma das formas de pagamento precisa ser igual ao total da venda.');
        }

        return $pagamentos->all();
    }

    private function valorRecebidoDinheiroMisto(?array $pagamentos): ?float
    {
        $valor = collect($pagamentos ?? [])
            ->where('forma', 'DINHEIRO')
            ->sum(fn($pagamento) => (float) ($pagamento['valor_recebido'] ?? 0));

        return $valor > 0 ? round($valor, 2) : null;
    }

    private function calcularTroco(string $formaPagamento, float $valorTotal, ?float $valorRecebido, ?array $pagamentos): float
    {
        if ($formaPagamento === 'DINHEIRO') {
            return $valorRecebido === null ? 0 : max(0, round($valorRecebido - $valorTotal, 2));
        }

        if ($formaPagamento !== 'MISTO') {
            return 0;
        }

        return round(collect($pagamentos ?? [])
            ->where('forma', 'DINHEIRO')
            ->sum(fn($pagamento) => (float) ($pagamento['troco'] ?? 0)), 2);
    }
}
