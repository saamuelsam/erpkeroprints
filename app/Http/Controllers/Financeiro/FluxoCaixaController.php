<?php

namespace App\Http\Controllers\Financeiro;

use App\Http\Controllers\Controller;
use App\Models\FinanceiroEntrada;
use App\Models\FinanceiroSaida;
use App\Services\CaixaService;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class FluxoCaixaController extends Controller
{
    public function __construct(protected CaixaService $caixaService)
    {
    }

    public function index(Request $request)
    {
        // Período padrão: mês atual
        $inicio = Carbon::parse($request->input('data_inicio', now()->startOfMonth()->toDateString()));
        $fim    = Carbon::parse($request->input('data_fim', now()->toDateString()));

        $filtros = array_filter([
            'categoria'       => $request->input('categoria'),
            'forma_pagamento' => $request->input('forma_pagamento'),
        ]);

        $tipo = $request->input('tipo'); // 'ENTRADA', 'SAIDA' ou null (todos)

        $resultado = $this->caixaService->fluxoCaixa($inicio, $fim, $filtros);

        // Filtrar por tipo se solicitado
        if ($tipo) {
            $resultado['movimentacoes'] = $resultado['movimentacoes']
                ->filter(fn($m) => $m['tipo'] === $tipo)
                ->values();
        }

        // Todas as categorias para o filtro
        $todasCategorias = array_merge(
            FinanceiroEntrada::CATEGORIAS,
            FinanceiroSaida::CATEGORIAS,
        );

        $formasPagamento = FinanceiroEntrada::FORMAS_PAGAMENTO;

        return view('financeiro.fluxo-caixa.index', [
            'movimentacoes'   => $resultado['movimentacoes'],
            'totalEntradas'   => $resultado['total_entradas'],
            'totalSaidas'     => $resultado['total_saidas'],
            'saldo'           => $resultado['saldo'],
            'dataInicio'      => $inicio,
            'dataFim'         => $fim,
            'categorias'      => $todasCategorias,
            'formasPagamento' => $formasPagamento,
        ]);
    }
}
