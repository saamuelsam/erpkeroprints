<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Http;

class CepController extends Controller
{
    /**
     * Busca endereço via ViaCEP.
     * GET /api/cep/{cep}
     */
    public function buscar(string $cep): JsonResponse
    {
        $cep = preg_replace('/\D/', '', $cep);

        if (strlen($cep) !== 8) {
            return response()->json(['error' => 'CEP inválido.'], 422);
        }

        try {
            $response = Http::timeout(5)->get("https://viacep.com.br/ws/{$cep}/json/");

            if ($response->failed() || isset($response->json()['erro'])) {
                return response()->json(['error' => 'CEP não encontrado.'], 404);
            }

            $data = $response->json();

            return response()->json([
                'cep'         => $data['cep'] ?? '',
                'logradouro'  => $data['logradouro'] ?? '',
                'complemento' => $data['complemento'] ?? '',
                'bairro'      => $data['bairro'] ?? '',
                'cidade'      => $data['localidade'] ?? '',
                'uf'          => $data['uf'] ?? '',
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Erro ao consultar CEP.'], 500);
        }
    }
}
