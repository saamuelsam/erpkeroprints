<?php

namespace App\Http\Controllers\Financeiro;

use App\Http\Controllers\Controller;
use App\Http\Requests\ContaPagarRequest;
use App\Models\ContaPagar;
use App\Models\FinanceiroSaida;
use App\Models\Fornecedor;
use App\Services\CaixaService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ContaPagarController extends Controller
{
    public function __construct(protected CaixaService $caixaService)
    {
    }

    public function index(Request $request)
    {
        $query = ContaPagar::with('fornecedor');

        if ($busca = $request->input('busca')) {
            $query->where('descricao', 'like', "%{$busca}%");
        }

        if ($status = $request->input('status')) {
            $query->where('status', $status);
        }

        if ($categoria = $request->input('categoria')) {
            $query->where('categoria', $categoria);
        }

        if ($dataInicio = $request->input('data_inicio')) {
            $query->whereDate('data_vencimento', '>=', $dataInicio);
        }

        if ($dataFim = $request->input('data_fim')) {
            $query->whereDate('data_vencimento', '<=', $dataFim);
        }

        $contas      = $query->latest('data_vencimento')->paginate(20)->withQueryString();
        $totalAberto = ContaPagar::abertas()->sum('valor');
        $totalVencido = ContaPagar::vencidas()->sum('valor');

        return view('financeiro.contas-pagar.index', compact('contas', 'totalAberto', 'totalVencido'));
    }

    public function create()
    {
        $fornecedores = Fornecedor::ativos()->orderBy('nome')->get(['id', 'nome']);

        return view('financeiro.contas-pagar.form', [
            'conta'           => new ContaPagar(['data_emissao' => now()->toDateString()]),
            'fornecedores'    => $fornecedores,
            'categorias'      => ContaPagar::CATEGORIAS,
            'formasPagamento' => FinanceiroSaida::FORMAS_PAGAMENTO,
        ]);
    }

    public function store(ContaPagarRequest $request)
    {
        $dados = $request->validated();
        $dados['user_id'] = Auth::id();

        ContaPagar::create($dados);

        return redirect()->route('financeiro.contas-pagar.index')
            ->with('sucesso', 'Conta a pagar cadastrada!');
    }

    public function edit(ContaPagar $contas_pagar)
    {
        $conta = $contas_pagar;

        if ($conta->status !== 'ABERTA') {
            return back()->with('erro', 'Somente contas abertas podem ser editadas.');
        }

        $fornecedores = Fornecedor::ativos()->orderBy('nome')->get(['id', 'nome']);

        return view('financeiro.contas-pagar.form', [
            'conta'           => $conta,
            'fornecedores'    => $fornecedores,
            'categorias'      => ContaPagar::CATEGORIAS,
            'formasPagamento' => FinanceiroSaida::FORMAS_PAGAMENTO,
        ]);
    }

    public function update(ContaPagarRequest $request, ContaPagar $contas_pagar)
    {
        $conta = $contas_pagar;

        if ($conta->status !== 'ABERTA') {
            return back()->with('erro', 'Somente contas abertas podem ser editadas.');
        }

        $conta->update($request->validated());

        return redirect()->route('financeiro.contas-pagar.index')
            ->with('sucesso', 'Conta atualizada!');
    }

    /**
     * Baixar pagamento — gera saída automática no caixa.
     */
    public function baixar(Request $request, ContaPagar $conta)
    {
        if ($conta->status === 'PAGA') {
            return back()->with('erro', 'Esta conta já foi paga.');
        }

        if ($conta->status === 'CANCELADA') {
            return back()->with('erro', 'Contas canceladas não podem ser baixadas.');
        }

        $formaPagamento = $request->input('forma_pagamento', 'Dinheiro');

        DB::transaction(function () use ($conta, $formaPagamento) {
            $conta->update([
                'status'          => 'PAGA',
                'data_pagamento'  => now()->toDateString(),
                'forma_pagamento' => $formaPagamento,
            ]);

            $this->caixaService->registrarSaidaAutomatica(
                descricao: "Pagamento: {$conta->descricao}",
                valor: (float) $conta->valor,
                categoria: 'CONTA_PAGAR',
                formaPagamento: $formaPagamento,
                fornecedorId: $conta->fornecedor_id,
                origemTipo: 'conta_pagar',
                origemId: $conta->id,
            );
        });

        return back()->with('sucesso', 'Pagamento confirmado e saída registrada no caixa!');
    }

    public function destroy(ContaPagar $contas_pagar)
    {
        $conta = $contas_pagar;

        if ($conta->status === 'PAGA') {
            return back()->with('erro', 'Contas pagas não podem ser excluídas.');
        }

        $conta->update(['status' => 'CANCELADA']);

        return redirect()->route('financeiro.contas-pagar.index')
            ->with('sucesso', 'Conta cancelada!');
    }
}
