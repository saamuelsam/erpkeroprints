<?php

namespace App\Http\Controllers;

use App\Http\Requests\OrdemServicoRequest;
use App\Models\Cliente;
use App\Models\OrdemServico;
use App\Models\Produto;
use App\Services\OrdemServicoService;
use Illuminate\Http\Request;

class OrdemServicoController extends Controller
{
    public function __construct(protected OrdemServicoService $osService)
    {
    }

    public function index(Request $request)
    {
        $query = OrdemServico::with('cliente');

        if ($busca = $request->input('busca')) {
            $query->where(function ($q) use ($busca) {
                $q->where('numero_os', 'like', "%{$busca}%")
                  ->orWhereHas('cliente', fn($c) => $c->where('nome', 'like', "%{$busca}%"));
            });
        }

        if ($status = $request->input('status')) {
            $query->where('status', $status);
        }

        if ($dataInicio = $request->input('data_inicio')) {
            $query->whereDate('data_abertura', '>=', $dataInicio);
        }

        if ($dataFim = $request->input('data_fim')) {
            $query->whereDate('data_abertura', '<=', $dataFim);
        }

        $ordens       = $query->latest()->paginate(20)->withQueryString();
        $statusOpcoes = OrdemServico::STATUS_LABELS;

        return view('ordens-servico.index', compact('ordens', 'statusOpcoes'));
    }

    public function create()
    {
        $clientes        = Cliente::ativos()->orderBy('nome')->get(['id', 'nome']);
        $formasPagamento = $this->formasPagamento();

        return view('ordens-servico.form', [
            'os'              => new OrdemServico(),
            'clientes'        => $clientes,
            'formasPagamento' => $formasPagamento,
            'statusOpcoes'    => OrdemServico::STATUS_LABELS,
        ]);
    }

    public function store(OrdemServicoRequest $request)
    {
        try {
            $os = $this->osService->criar(
                $request->safe()->except('itens'),
                $request->input('itens', [])
            );

            return redirect()->route('ordens-servico.show', $os)
                ->with('sucesso', "OS #{$os->numero_os} criada com sucesso!");
        } catch (\Exception $e) {
            return back()->withInput()->with('erro', $e->getMessage());
        }
    }

    public function show(OrdemServico $os)
    {
        $os->load([
            'cliente',
            'responsavel',
            'itens',
            'historicos.usuario',
        ]);

        return view('ordens-servico.show', compact('os'));
    }

    public function edit(OrdemServico $os)
    {
        $clientes = Cliente::ativos()->orderBy('nome')->get(['id', 'nome']);

        $os->load('itens');

        return view('ordens-servico.form', [
            'os'              => $os,
            'clientes'        => $clientes,
            'formasPagamento' => $this->formasPagamento(),
            'statusOpcoes'    => OrdemServico::STATUS_LABELS,
        ]);
    }

    public function update(OrdemServicoRequest $request, OrdemServico $os)
    {
        try {
            $os = $this->osService->atualizar(
                $os,
                $request->safe()->except('itens'),
                $request->input('itens', [])
            );

            return redirect()->route('ordens-servico.show', $os)
                ->with('sucesso', "OS #{$os->numero_os} atualizada com sucesso!");
        } catch (\Exception $e) {
            return back()->withInput()->with('erro', $e->getMessage());
        }
    }

    public function destroy(OrdemServico $os)
    {
        if (in_array($os->status, ['ENTREGUE', 'FINALIZADA'])) {
            return back()->with('erro', 'Não é possível cancelar uma OS finalizada ou entregue.');
        }

        $os->update(['status' => 'CANCELADA']);
        $os->delete();

        return redirect()->route('ordens-servico.index')
            ->with('sucesso', 'OS cancelada com sucesso!');
    }

    private function formasPagamento(): array
    {
        return ['Dinheiro', 'Pix', 'Cartão de Débito', 'Cartão de Crédito', 'Boleto', 'A combinar'];
    }
}
