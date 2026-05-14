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

    public function criar(array $dados, array $itens): Venda
    {
        return DB::transaction(function () use ($dados, $itens) {
            $itensNormalizados = $this->normalizarItens($itens);

            if (empty($itensNormalizados)) {
                throw new RuntimeException('Adicione pelo menos um produto na venda.');
            }

            $subtotal = collect($itensNormalizados)->sum('total_item');
            $desconto = (float) ($dados['desconto'] ?? 0);

            $venda = Venda::create([
                'numero' => Venda::gerarNumero(),
                'cliente_id' => $dados['cliente_id'] ?? null,
                'user_id' => Auth::id(),
                'subtotal' => $subtotal,
                'desconto' => $desconto,
                'valor_total' => max(0, round($subtotal - $desconto, 2)),
                'forma_pagamento' => $dados['forma_pagamento'],
                'status' => 'AGUARDANDO_PAGAMENTO',
            ]);

            foreach ($itensNormalizados as $item) {
                $venda->itens()->create($item);
            }

            if ($venda->forma_pagamento !== 'PIX') {
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

            foreach ($venda->itens as $item) {
                if (!$item->produto_id) {
                    continue;
                }

                $produto = Produto::findOrFail($item->produto_id);
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
        $produtos = Produto::whereIn('id', $produtoIds)
            ->get(['id', 'nome', 'preco_venda', 'custo_unitario'])
            ->keyBy('id');

        return collect($itens)
            ->map(function (array $item) use ($produtos) {
                $produtoId = $item['produto_id'] ?? null;
                $produto = $produtoId ? $produtos->get($produtoId) : null;
                $quantidade = (float) ($item['quantidade'] ?? 0);
                $preco = (float) ($item['preco_unitario'] ?? $produto?->preco_venda ?? 0);

                if ($quantidade <= 0 || $preco < 0) {
                    return null;
                }

                return [
                    'produto_id' => $produto?->id,
                    'descricao' => $item['descricao'] ?? $produto?->nome ?? 'Produto avulso',
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
}
