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
                  ->orWhere('cliente_nome', 'like', "%{$busca}%")
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

    public function producao()
    {
        $statusFluxo = ['ABERTA', 'AGUARDANDO_APROVACAO', 'PRODUCAO', 'FINALIZADA'];

        $ordens = OrdemServico::with('cliente')
            ->whereIn('status', $statusFluxo)
            ->orderByRaw('data_prevista_entrega IS NULL')
            ->orderBy('data_prevista_entrega')
            ->orderBy('created_at')
            ->get()
            ->groupBy('status');

        $indicadores = [
            'atrasadas' => OrdemServico::whereNotIn('status', ['ENTREGUE', 'CANCELADA'])
                ->whereDate('data_prevista_entrega', '<', today())
                ->count(),
            'hoje' => OrdemServico::whereNotIn('status', ['ENTREGUE', 'CANCELADA'])
                ->whereDate('data_prevista_entrega', today())
                ->count(),
            'producao' => OrdemServico::where('status', 'PRODUCAO')->count(),
            'prontas' => OrdemServico::where('status', 'FINALIZADA')->count(),
        ];

        return view('ordens-servico.producao', [
            'ordensPorStatus' => $ordens,
            'statusFluxo'     => $statusFluxo,
            'statusOpcoes'    => OrdemServico::STATUS_LABELS,
            'indicadores'     => $indicadores,
        ]);
    }

    public function create()
    {
        $clientes        = Cliente::ativos()->orderBy('nome')->get([
            'id',
            'nome',
            'telefone',
            'cpf_cnpj',
            'email',
            'endereco',
            'numero',
            'complemento',
            'bairro',
            'cidade',
            'estado',
            'cep',
        ]);
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
            $dados = $request->safe()->except(['itens', 'acao']);
            $itens = $request->input('itens', []);
            $acao = $request->input('acao', 'salvar');
            $receberAgora = in_array($acao, ['pagar_agora', 'pagar_imprimir_etiqueta'], true);
            $imprimirEtiqueta = in_array($acao, ['salvar_imprimir_etiqueta', 'pagar_imprimir_etiqueta'], true);

            $os = $receberAgora
                ? $this->osService->criarEReceber($dados, $itens)
                : $this->osService->criar($dados, $itens);

            if ($imprimirEtiqueta) {
                return redirect()->route('ordens-servico.etiqueta', $os)
                    ->with('sucesso', $receberAgora
                        ? "OS #{$os->numero_os} finalizada e recebida. A etiqueta esta pronta para impressao."
                        : "OS #{$os->numero_os} criada. A etiqueta esta pronta para impressao.");
            }

            return redirect()->route('ordens-servico.show', $os)
                ->with('sucesso', $receberAgora
                    ? "OS #{$os->numero_os} finalizada e recebida com sucesso!"
                    : "OS #{$os->numero_os} criada com sucesso!");
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

    public function etiqueta(OrdemServico $os)
    {
        $os->load(['cliente', 'itens']);

        return view('ordens-servico.etiqueta', compact('os'));
    }

    public function edit(OrdemServico $os)
    {
        $clientes = Cliente::ativos()->orderBy('nome')->get([
            'id',
            'nome',
            'telefone',
            'cpf_cnpj',
            'email',
            'endereco',
            'numero',
            'complemento',
            'bairro',
            'cidade',
            'estado',
            'cep',
        ]);

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

    public function atualizarStatusRapido(Request $request, OrdemServico $os)
    {
        $validated = $request->validate([
            'status' => ['required', 'in:ABERTA,PRODUCAO,AGUARDANDO_APROVACAO,FINALIZADA,ENTREGUE,CANCELADA'],
        ]);

        try {
            $os = $this->osService->atualizarStatus($os, $validated['status']);

            if ($request->expectsJson()) {
                return response()->json([
                    'message' => "OS #{$os->numero_os} atualizada para {$os->status_label}.",
                    'status' => $os->status,
                    'status_label' => $os->status_label,
                ]);
            }

            return back()->with('sucesso', "OS #{$os->numero_os} atualizada para {$os->status_label}.");
        } catch (\Exception $e) {
            if ($request->expectsJson()) {
                return response()->json(['message' => $e->getMessage()], 422);
            }

            return back()->with('erro', $e->getMessage());
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
