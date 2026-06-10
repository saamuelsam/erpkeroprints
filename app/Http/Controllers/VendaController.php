<?php

namespace App\Http\Controllers;

use App\Models\Cliente;
use App\Models\Venda;
use App\Services\MercadoPagoPixService;
use App\Services\PixManualService;
use App\Services\VendaService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class VendaController extends Controller
{
    public function __construct(
        protected VendaService $vendaService,
        protected MercadoPagoPixService $mercadoPagoPixService,
        protected PixManualService $pixManualService,
    ) {
    }

    public function index(Request $request)
    {
        $query = Venda::with('cliente', 'responsavel', 'ordemServico');

        if ($busca = $request->input('busca')) {
            $query->where(function ($q) use ($busca) {
                $q->where('numero', 'like', "%{$busca}%")
                    ->orWhere('cliente_nome', 'like', "%{$busca}%")
                    ->orWhereHas('ordemServico', fn($os) => $os->where('numero_os', 'like', "%{$busca}%"))
                    ->orWhereHas('ordemServico', fn($os) => $os->where('cliente_nome', 'like', "%{$busca}%"))
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

    public function comprovante(Venda $venda)
    {
        $venda->load(['cliente', 'itens', 'responsavel', 'ordemServico']);

        return view('vendas.comprovante', compact('venda'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'cliente_id' => ['nullable', 'exists:clientes,id'],
            'cliente_nome' => ['nullable', 'string', 'max:150'],
            'forma_pagamento' => ['required', 'in:DINHEIRO,PIX,CARTAO_DEBITO,CARTAO_CREDITO,OUTROS,MISTO'],
            'desconto' => ['nullable', 'numeric', 'min:0'],
            'valor_recebido' => ['nullable', 'required_if:forma_pagamento,DINHEIRO', 'numeric', 'min:0'],
            'payer_email' => ['nullable', 'email'],
            'pagamentos' => ['exclude_unless:forma_pagamento,MISTO', 'required_if:forma_pagamento,MISTO', 'array', 'min:2'],
            'pagamentos.*.forma' => ['required_with:pagamentos', 'in:DINHEIRO,PIX,CARTAO_DEBITO,CARTAO_CREDITO,OUTROS'],
            'pagamentos.*.valor' => ['required_with:pagamentos', 'numeric', 'min:0.01'],
            'pagamentos.*.valor_recebido' => ['nullable', 'numeric', 'min:0'],
            'itens' => ['required', 'array', 'min:1'],
            'itens.*.produto_id' => ['required', Rule::exists('produtos', 'id')->whereNull('deleted_at')->where('ativo', true)],
            'itens.*.descricao' => ['required', 'string', 'max:255'],
            'itens.*.quantidade' => ['required', 'numeric', 'min:0.001'],
            'itens.*.preco_unitario' => ['required', 'numeric', 'min:0'],
        ], [
            'valor_recebido.required_if' => 'Informe o valor recebido em dinheiro.',
            'pagamentos.required_if' => 'Informe as formas do pagamento misto.',
            'pagamentos.array' => 'As formas do pagamento misto precisam estar em formato valido.',
            'pagamentos.min' => 'Informe pelo menos duas formas de pagamento no pagamento misto.',
            'pagamentos.*.forma.required_with' => 'Selecione a forma de pagamento.',
            'pagamentos.*.forma.in' => 'Uma das formas de pagamento selecionadas e invalida.',
            'pagamentos.*.valor.required_with' => 'Informe o valor de cada forma de pagamento.',
            'pagamentos.*.valor.min' => 'O valor de cada forma de pagamento precisa ser maior que zero.',
            'itens.*.produto_id.exists' => 'Um dos produtos nao esta mais disponivel para venda.',
        ]);

        $venda = null;

        try {
            $venda = $this->vendaService->criar($validated, $validated['itens']);

            if ($venda->usaPix()) {
                if (filled(config('services.mercado_pago.access_token'))) {
                    $pagamento = $this->mercadoPagoPixService->criarPixComValor($venda, $venda->valor_pix, $validated['payer_email'] ?? null);
                    $venda = $this->vendaService->anexarPix($venda, $pagamento);
                } else {
                    $pix = $this->pixManualService->gerarParaVendaComValor($venda, $venda->valor_pix);
                    $venda = $this->vendaService->anexarPixManual($venda, $pix);
                    $venda->setAttribute('pix_qr_code_image_url', $pix['qr_code_image_url']);
                }
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

    public function confirmarManual(Request $request, Venda $venda)
    {
        try {
            if (!$venda->usaPix() || $venda->mercado_pago_payment_id) {
                return response()->json(['message' => 'Esta venda nao usa Pix manual.'], 422);
            }

            $validated = $request->validate([
                'pix_confirmacao_referencia' => ['nullable', 'string', 'min:4', 'max:120'],
                'pix_confirmacao_pagador' => ['nullable', 'string', 'max:150'],
                'pix_confirmacao_observacao' => ['nullable', 'string', 'max:500'],
                'confirmou_extrato' => ['nullable', 'boolean'],
            ], [
                'pix_confirmacao_referencia.min' => 'O codigo/autenticacao do comprovante precisa ter pelo menos 4 caracteres.',
            ]);

            $venda = $this->vendaService->confirmarPagamentoManual($venda, $validated);

            return response()->json([
                'venda' => $this->formatarVenda($venda),
                'message' => 'Pagamento manual conferido e venda finalizada.',
            ]);
        } catch (ValidationException $e) {
            return response()->json([
                'message' => collect($e->errors())->flatten()->first() ?: 'Confira os dados da confirmacao do Pix.',
                'errors' => $e->errors(),
            ], 422);
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
            'cliente_nome' => $venda->cliente_nome,
            'cliente_exibicao' => $venda->cliente_exibicao,
            'comprovante_url' => route('vendas.comprovante', $venda),
            'status' => $venda->status,
            'status_label' => $venda->status_label,
            'forma_pagamento' => $venda->forma_pagamento,
            'forma_pagamento_label' => $venda->forma_pagamento_label,
            'subtotal' => (float) $venda->subtotal,
            'desconto' => (float) $venda->desconto,
            'valor_total' => (float) $venda->valor_total,
            'valor_recebido' => (float) $venda->valor_recebido,
            'troco' => (float) $venda->troco,
            'valor_pix' => (float) $venda->valor_pix,
            'pagamentos' => $venda->pagamentos ?? [],
            'pix_qr_code' => $venda->pix_qr_code,
            'pix_qr_code_base64' => $venda->pix_qr_code_base64,
            'pix_qr_code_image_url' => $venda->getAttribute('pix_qr_code_image_url')
                ?: ($venda->pix_qr_code ? 'https://api.qrserver.com/v1/create-qr-code/?size=320x320&data=' . urlencode($venda->pix_qr_code) : null),
            'pix_manual' => $venda->usaPix() && blank($venda->mercado_pago_payment_id),
            'pix_confirmado_em' => optional($venda->pix_confirmado_em)->toISOString(),
            'pix_confirmacao_referencia' => $venda->pix_confirmacao_referencia,
            'pix_confirmacao_pagador' => $venda->pix_confirmacao_pagador,
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
