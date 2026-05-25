<?php

namespace App\Http\Controllers\Financeiro;

use App\Http\Controllers\Controller;
use App\Http\Requests\FinanceiroEntradaRequest;
use App\Models\Cliente;
use App\Models\FinanceiroEntrada;
use App\Models\VendaItem;
use App\Services\CaixaService;
use Illuminate\Http\Request;

class EntradaController extends Controller
{
    public function __construct(protected CaixaService $caixaService)
    {
    }

    public function index(Request $request)
    {
        $query = FinanceiroEntrada::with('cliente', 'responsavel', 'vendaOrigem.itens.produto');

        if ($request->boolean('somente_removidas')) {
            $query->onlyTrashed();
        } elseif ($request->boolean('incluir_removidas')) {
            $query->withTrashed();
        }

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

        $vendaIdsFiltro = (clone $query)
            ->where('origem_tipo', 'venda')
            ->pluck('origem_id');

        $produtosVendidosFiltro = VendaItem::query()
            ->selectRaw('descricao, SUM(quantidade) as quantidade_total, SUM(total_item) as valor_total')
            ->whereIn('venda_id', $vendaIdsFiltro)
            ->groupBy('descricao')
            ->orderByDesc('quantidade_total')
            ->limit(8)
            ->get();

        $entradas   = $query->orderByDesc('data')->orderByDesc('created_at')->paginate(50)->withQueryString();
        $categorias = FinanceiroEntrada::CATEGORIAS;

        // Totais do filtro atual
        $totalFiltro = (clone $query)->sum('valor');

        return view('financeiro.entradas.index', compact('entradas', 'categorias', 'totalFiltro', 'produtosVendidosFiltro'));
    }

    public function create()
    {
        $clientes = Cliente::ativos()->orderBy('nome')->get(['id', 'nome']);

        return view('financeiro.entradas.form', [
            'entrada'         => new FinanceiroEntrada(['data' => now()->toDateString()]),
            'clientes'        => $clientes,
            'categorias'      => FinanceiroEntrada::CATEGORIAS,
            'formasPagamento' => FinanceiroEntrada::FORMAS_PAGAMENTO,
        ]);
    }

    public function store(FinanceiroEntradaRequest $request)
    {
        try {
            $this->caixaService->registrarEntrada($request->validated());

            return redirect()->route('financeiro.entradas.index')
                ->with('sucesso', 'Entrada registrada com sucesso!');
        } catch (\Exception $e) {
            return back()->withInput()->with('erro', $e->getMessage());
        }
    }

    public function edit(FinanceiroEntrada $entrada)
    {
        $clientes = Cliente::ativos()->orderBy('nome')->get(['id', 'nome']);

        return view('financeiro.entradas.form', [
            'entrada'         => $entrada,
            'clientes'        => $clientes,
            'categorias'      => FinanceiroEntrada::CATEGORIAS,
            'formasPagamento' => FinanceiroEntrada::FORMAS_PAGAMENTO,
        ]);
    }

    public function update(FinanceiroEntradaRequest $request, FinanceiroEntrada $entrada)
    {
        // Não permitir edição de entradas automáticas
        if ($entrada->origem_tipo) {
            return back()->with('erro', 'Entradas automáticas não podem ser editadas.');
        }

        $entrada->update($request->validated());

        return redirect()->route('financeiro.entradas.index')
            ->with('sucesso', 'Entrada atualizada com sucesso!');
    }

    public function destroy(FinanceiroEntrada $entrada)
    {
        if ($entrada->origem_tipo) {
            return back()->with('erro', 'Entradas automáticas não podem ser excluídas.');
        }

        $entrada->delete();

        return redirect()->route('financeiro.entradas.index')
            ->with('sucesso', 'Entrada removida com sucesso!');
    }
}
