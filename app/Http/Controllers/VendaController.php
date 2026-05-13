<?php

namespace App\Http\Controllers;

use App\Models\Cliente;
use App\Models\Venda;
use App\Services\MercadoPagoPixService;
use App\Services\VendaService;
use Illuminate\Http\Request;

class VendaController extends Controller
{
    public function __construct(
        protected VendaService $vendaService,
        protected MercadoPagoPixService $mercadoPagoPixService,
    ) {
    }

    public function index(Request $request)
    {
        $query = Venda::with('cliente', 'responsavel');

        if ($busca = $request->input('busca')) {
            $query->where(function ($q) use ($busca) {
                $q->where('numero', 'like', "%{$busca}%")
                    ->orWhereHas('cliente', fn($c) => $c->where('nome', 'like', "%{$busca}%"));
            });
        }

        if ($status = $request->input('status')) {
            $query->where('status', $status);
        }

        $vendas = $query->latest()->paginate(20)->withQueryString();

        return view('vendas.index', [
            'vendas' => $vendas,
            'statusOpcoes' => Venda::STATUS_LABELS,
        ]);
    }

    public function pdv()
    {
        return view('vendas.pdv', [
            'clientes' => Cliente::ativos()->orderBy('nome')->get(['id', 'nome', 'email']),
            'formasPagamento' => Venda::FORMAS_PAGAMENTO,
            'mercadoPagoConfigurado' => filled(config('services.mercado_pago.access_token')),
        ]);
    }

    public function cliente()
    {
        return view('vendas.cliente');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'cliente_id' => ['nullable', 'exists:clientes,id'],
            'forma_pagamento' => ['required', 'in:DINHEIRO,PIX,CARTAO_DEBITO,CARTAO_CREDITO,OUTROS'],
            'desconto' => ['nullable', 'numeric', 'min:0'],
            'payer_email' => ['nullable', 'email'],
            'itens' => ['required', 'array', 'min:1'],
            'itens.*.produto_id' => ['required', 'exists:produtos,id'],
            'itens.*.descricao' => ['required', 'string', 'max:255'],
            'itens.*.quantidade' => ['required', 'numeric', 'min:0.001'],
            'itens.*.preco_unitario' => ['required', 'numeric', 'min:0'],
        ]);

        if ($validated['forma_pagamento'] === 'PIX' && blank(config('services.mercado_pago.access_token'))) {
            return response()->json([
                'message' => 'Configure MERCADO_PAGO_ACCESS_TOKEN no .env para usar Pix automatico.',
            ], 422);
        }

        $venda = null;

        try {
            $venda = $this->vendaService->criar($validated, $validated['itens']);

            if ($venda->forma_pagamento === 'PIX') {
                $pagamento = $this->mercadoPagoPixService->criarPix($venda, $validated['payer_email'] ?? null);
                $venda = $this->vendaService->anexarPix($venda, $pagamento);
            }

            return response()->json([
                'venda' => $this->formatarVenda($venda),
                'message' => $venda->status === 'PAGA'
                    ? 'Venda finalizada com sucesso.'
                    : 'Pix gerado. Aguarde a confirmacao do pagamento.',
            ]);
        } catch (\Exception $e) {
            if ($venda && $venda->status === 'AGUARDANDO_PAGAMENTO') {
                $this->vendaService->cancelar($venda);
            }

            return response()->json(['message' => $e->getMessage()], 422);
        }
    }

    public function consultarPagamento(Venda $venda)
    {
        try {
            if ($venda->status === 'PAGA') {
                return response()->json(['venda' => $this->formatarVenda($venda->fresh(['itens.produto', 'cliente']))]);
            }

            if ($venda->mercado_pago_payment_id) {
                $pagamento = $this->mercadoPagoPixService->consultarPagamento($venda->mercado_pago_payment_id);
                $venda = $this->vendaService->sincronizarPagamentoMercadoPago($venda, $pagamento);
            }

            return response()->json(['venda' => $this->formatarVenda($venda)]);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }

    public function cancelar(Venda $venda)
    {
        try {
            $this->vendaService->cancelar($venda);

            return response()->json(['message' => 'Venda cancelada.']);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }

    private function formatarVenda(Venda $venda): array
    {
        $venda->loadMissing('itens.produto', 'cliente');

        return [
            'id' => $venda->id,
            'numero' => $venda->numero,
            'status' => $venda->status,
            'status_label' => $venda->status_label,
            'forma_pagamento' => $venda->forma_pagamento,
            'forma_pagamento_label' => $venda->forma_pagamento_label,
            'subtotal' => (float) $venda->subtotal,
            'desconto' => (float) $venda->desconto,
            'valor_total' => (float) $venda->valor_total,
            'pix_qr_code' => $venda->pix_qr_code,
            'pix_qr_code_base64' => $venda->pix_qr_code_base64,
            'mercado_pago_status' => $venda->mercado_pago_status,
            'itens' => $venda->itens->map(fn($item) => [
                'descricao' => $item->descricao,
                'quantidade' => (float) $item->quantidade,
                'preco_unitario' => (float) $item->preco_unitario,
                'total_item' => (float) $item->total_item,
            ])->values(),
        ];
    }
}
