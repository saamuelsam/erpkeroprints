<?php

namespace App\Http\Controllers\Financeiro;

use App\Http\Controllers\Controller;
use App\Http\Requests\FinanceiroSaidaRequest;
use App\Models\FinanceiroSaida;
use App\Models\Fornecedor;
use App\Services\CaixaService;
use Illuminate\Http\Request;

class SaidaController extends Controller
{
    public function __construct(protected CaixaService $caixaService)
    {
    }

    public function index(Request $request)
    {
        $query = FinanceiroSaida::with('fornecedor', 'responsavel');

        if ($busca = $request->input('busca')) {
            $query->busca($busca);
        }

        if ($categoria = $request->input('categoria')) {
            $query->categoria($categoria);
        }

        if ($status = $request->input('status')) {
            $query->where('status', $status);
        }

        if ($dataInicio = $request->input('data_inicio')) {
            $query->whereDate('data', '>=', $dataInicio);
        }

        if ($dataFim = $request->input('data_fim')) {
            $query->whereDate('data', '<=', $dataFim);
        }

        $saidas     = $query->latest('data')->paginate(20)->withQueryString();
        $categorias = FinanceiroSaida::CATEGORIAS;
        $totalFiltro = (clone $query)->sum('valor');

        return view('financeiro.saidas.index', compact('saidas', 'categorias', 'totalFiltro'));
    }

    public function create()
    {
        $fornecedores = Fornecedor::ativos()->orderBy('nome')->get(['id', 'nome']);

        return view('financeiro.saidas.form', [
            'saida'           => new FinanceiroSaida(['data' => now()->toDateString()]),
            'fornecedores'    => $fornecedores,
            'categorias'      => FinanceiroSaida::CATEGORIAS,
            'formasPagamento' => FinanceiroSaida::FORMAS_PAGAMENTO,
        ]);
    }

    public function store(FinanceiroSaidaRequest $request)
    {
        try {
            $this->caixaService->registrarSaida($request->validated());

            return redirect()->route('financeiro.saidas.index')
                ->with('sucesso', 'Saída registrada com sucesso!');
        } catch (\Exception $e) {
            return back()->withInput()->with('erro', $e->getMessage());
        }
    }

    public function edit(FinanceiroSaida $saida)
    {
        $fornecedores = Fornecedor::ativos()->orderBy('nome')->get(['id', 'nome']);

        return view('financeiro.saidas.form', [
            'saida'           => $saida,
            'fornecedores'    => $fornecedores,
            'categorias'      => FinanceiroSaida::CATEGORIAS,
            'formasPagamento' => FinanceiroSaida::FORMAS_PAGAMENTO,
        ]);
    }

    public function update(FinanceiroSaidaRequest $request, FinanceiroSaida $saida)
    {
        if ($saida->origem_tipo) {
            return back()->with('erro', 'Saídas automáticas não podem ser editadas.');
        }

        $saida->update($request->validated());

        return redirect()->route('financeiro.saidas.index')
            ->with('sucesso', 'Saída atualizada com sucesso!');
    }

    public function destroy(FinanceiroSaida $saida)
    {
        if ($saida->origem_tipo) {
            return back()->with('erro', 'Saídas automáticas não podem ser excluídas.');
        }

        $saida->delete();

        return redirect()->route('financeiro.saidas.index')
            ->with('sucesso', 'Saída removida com sucesso!');
    }
}
