<?php

namespace App\Services;

use App\Models\Venda;

class PixManualService
{
    public function gerarParaVenda(Venda $venda): array
    {
        $payload = $this->payload(
            chave: config('services.pix_manual.key'),
            nome: config('services.pix_manual.merchant_name'),
            cidade: config('services.pix_manual.merchant_city'),
            valor: (float) $venda->valor_total,
            identificador: $venda->numero,
        );

        return [
            'qr_code' => $payload,
            'qr_code_image_url' => 'https://api.qrserver.com/v1/create-qr-code/?size=320x320&data=' . urlencode($payload),
        ];
    }

    private function payload(string $chave, string $nome, string $cidade, float $valor, string $identificador): string
    {
        $gui = $this->campo('00', 'br.gov.bcb.pix');
        $pixKey = $this->campo('01', $chave);
        $merchantAccount = $this->campo('26', $gui . $pixKey);
        $additionalData = $this->campo('62', $this->campo('05', $this->sanitizar($identificador, 25)));

        $payloadSemCrc =
            $this->campo('00', '01') .
            $merchantAccount .
            $this->campo('52', '0000') .
            $this->campo('53', '986') .
            $this->campo('54', number_format($valor, 2, '.', '')) .
            $this->campo('58', 'BR') .
            $this->campo('59', $this->sanitizar($nome, 25)) .
            $this->campo('60', $this->sanitizar($cidade, 15)) .
            $additionalData .
            '6304';

        return $payloadSemCrc . $this->crc16($payloadSemCrc);
    }

    private function campo(string $id, string $valor): string
    {
        return $id . str_pad((string) strlen($valor), 2, '0', STR_PAD_LEFT) . $valor;
    }

    private function sanitizar(string $valor, int $limite): string
    {
        $semAcento = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $valor) ?: $valor;
        $limpo = preg_replace('/[^A-Za-z0-9 .\-]/', '', $semAcento) ?: '';

        return strtoupper(substr($limpo, 0, $limite));
    }

    private function crc16(string $payload): string
    {
        $crc = 0xFFFF;
        $polynomial = 0x1021;

        for ($i = 0, $length = strlen($payload); $i < $length; $i++) {
            $crc ^= ord($payload[$i]) << 8;

            for ($bit = 0; $bit < 8; $bit++) {
                if (($crc & 0x8000) !== 0) {
                    $crc = ($crc << 1) ^ $polynomial;
                } else {
                    $crc <<= 1;
                }

                $crc &= 0xFFFF;
            }
        }

        return strtoupper(str_pad(dechex($crc), 4, '0', STR_PAD_LEFT));
    }
}
