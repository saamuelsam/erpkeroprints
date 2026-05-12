<?php

namespace App\Http\Controllers\Financeiro;

use App\Http\Controllers\Controller;
use App\Http\Requests\ContaReceberRequest;
use App\Models\Cliente;
use App\Models\ContaReceber;
use App\Models\FinanceiroEntrada;
use App\Services\CaixaService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ContaReceberController extends Controller
{
    public function __construct(protected CaixaService $caixaService)
    {
    }

    public function index(Request $request)
    {
        $query = ContaReceber::with('cliente');

        if ($busca = $request->input('busca')) {
            $query->where(function ($q) use ($busca) {
                $q->where('descricao', 'like', "%{$busca}%")
                  ->orWhereHas('cliente', fn($c) => $c->where('nome', 'like', "%{$busca}%"));
            });
        }

        if ($status = $request->input('status')) {
            $query->where('status', $status);
        }

        if ($dataInicio = $request->input('data_inicio')) {
            $query->whereDate('data_vencimento', '>=', $dataInicio);
        }

        if ($dataFim = $request->input('data_fim')) {
            $query->whereDate('data_vencimento', '<=', $dataFim);
        }

        $contas     = $query->latest('data_vencimento')->paginate(20)->withQueryString();
        $totalAberto = ContaReceber::abertas()->sum('valor');
        $totalVencido = ContaReceber::vencidas()->sum('valor');

        return view('financeiro.contas-receber.index', compact('contas', 'totalAberto', 'totalVencido'));
    }

    public function create()
    {
        $clientes = Cliente::ativos()->orderBy('nome')->get(['id', 'nome']);

        return view('financeiro.contas-receber.form', [
            'conta'           => new ContaReceber(['data_emissao' => now()->toDateString()]),
            'clientes'        => $clientes,
            'formasPagamento' => FinanceiroEntrada::FORMAS_PAGAMENTO,
        ]);
    }

    public function store(ContaReceberRequest $request)
    {
        $dados = $request->validated();
        $dados['user_id'] = Auth::id();

        ContaReceber::create($dados);

        return redirect()->route('financeiro.contas-receber.index')
            ->with('sucesso', 'Conta a receber cadastrada!');
    }

    public function edit(ContaReceber $contas_receber)
    {
        $conta = $contas_receber;

        if ($conta->status !== 'ABERTA') {
            return back()->with('erro', 'Somente contas abertas podem ser editadas.');
        }

        $clientes = Cliente::ativos()->orderBy('nome')->get(['id', 'nome']);

        return view('financeiro.contas-receber.form', [
            'conta'           => $conta,
            'clientes'        => $clientes,
            'formasPagamento' => FinanceiroEntrada::FORMAS_PAGAMENTO,
        ]);
    }

    public function update(ContaReceberRequest $request, ContaReceber $contas_receber)
    {
        $conta = $contas_receber;

        if ($conta->status !== 'ABERTA') {
            return back()->with('erro', 'Somente contas abertas podem ser editadas.');
        }

        $conta->update($request->validated());

        return redirect()->route('financeiro.contas-receber.index')
            ->with('sucesso', 'Conta atualizada!');
    }

    /**
     * Baixar recebimento — gera entrada automática no caixa.
     */
    public function baixar(Request $request, ContaReceber $conta)
    {
        if ($conta->status === 'RECEBIDA') {
            return back()->with('erro', 'Esta conta já foi recebida.');
        }

        if ($conta->status === 'CANCELADA') {
            return back()->with('erro', 'Contas canceladas não podem ser baixadas.');
        }

        $formaPagamento = $request->input('forma_pagamento', 'Dinheiro');

        DB::transaction(function () use ($conta, $formaPagamento) {
            $conta->update([
                'status'           => 'RECEBIDA',
                'data_recebimento' => now()->toDateString(),
                'forma_pagamento'  => $formaPagamento,
            ]);

            $this->caixaService->registrarEntradaAutomatica(
                descricao: "Recebimento: {$conta->descricao}",
                valor: (float) $conta->valor,
                categoria: 'CONTA_RECEBER',
                formaPagamento: $formaPagamento,
                clienteId: $conta->cliente_id,
                origemTipo: 'conta_receber',
                origemId: $conta->id,
            );
        });

        return back()->with('sucesso', 'Recebimento confirmado e entrada registrada no caixa!');
    }

    public function destroy(ContaReceber $contas_receber)
    {
        $conta = $contas_receber;

        if ($conta->status === 'RECEBIDA') {
            return back()->with('erro', 'Contas recebidas não podem ser excluídas.');
        }

        $conta->update(['status' => 'CANCELADA']);

        return redirect()->route('financeiro.contas-receber.index')
            ->with('sucesso', 'Conta cancelada!');
    }
}
