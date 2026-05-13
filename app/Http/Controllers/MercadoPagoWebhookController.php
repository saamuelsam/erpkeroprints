<?php

namespace App\Http\Controllers;

use App\Models\Venda;
use App\Services\MercadoPagoPixService;
use App\Services\VendaService;
use Illuminate\Http\Request;

class MercadoPagoWebhookController extends Controller
{
    public function __invoke(
        Request $request,
        MercadoPagoPixService $mercadoPagoPixService,
        VendaService $vendaService,
    ) {
        $paymentId = data_get($request->all(), 'data.id')
            ?? $request->input('id')
            ?? $request->input('payment_id');

        if (!$paymentId) {
            return response()->json(['ok' => true]);
        }

        $venda = Venda::where('mercado_pago_payment_id', (string) $paymentId)->first();

        if (!$venda) {
            return response()->json(['ok' => true]);
        }

        $pagamento = $mercadoPagoPixService->consultarPagamento((string) $paymentId);
        $vendaService->sincronizarPagamentoMercadoPago($venda, $pagamento);

        return response()->json(['ok' => true]);
    }
}
