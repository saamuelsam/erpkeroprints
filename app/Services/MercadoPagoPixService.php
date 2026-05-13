<?php

namespace App\Services;

use App\Models\Venda;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class MercadoPagoPixService
{
    public function criarPix(Venda $venda, ?string $emailPagador = null): array
    {
        $token = config('services.mercado_pago.access_token');

        if (!$token) {
            throw new RuntimeException('Configure MERCADO_PAGO_ACCESS_TOKEN no .env para gerar Pix automatico.');
        }

        $response = Http::withToken($token)
            ->withHeaders([
                'X-Idempotency-Key' => 'venda-' . $venda->id . '-' . $venda->updated_at?->timestamp,
            ])
            ->post('https://api.mercadopago.com/v1/payments', [
                'transaction_amount' => (float) $venda->valor_total,
                'description' => "Venda {$venda->numero} - Kero Prints",
                'payment_method_id' => 'pix',
                'external_reference' => $venda->numero,
                'notification_url' => route('webhooks.mercado-pago'),
                'payer' => [
                    'email' => $emailPagador ?: config('services.mercado_pago.default_payer_email'),
                ],
            ]);

        if (!$response->successful()) {
            throw new RuntimeException('Mercado Pago recusou a criacao do Pix: ' . $response->body());
        }

        return $response->json();
    }

    public function consultarPagamento(string $paymentId): array
    {
        $token = config('services.mercado_pago.access_token');

        if (!$token) {
            throw new RuntimeException('Configure MERCADO_PAGO_ACCESS_TOKEN no .env para consultar pagamentos.');
        }

        $response = Http::withToken($token)
            ->get("https://api.mercadopago.com/v1/payments/{$paymentId}");

        if (!$response->successful()) {
            throw new RuntimeException('Nao foi possivel consultar o pagamento no Mercado Pago: ' . $response->body());
        }

        return $response->json();
    }
}
