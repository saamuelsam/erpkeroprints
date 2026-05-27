<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProdutoRequest;
use App\Models\Categoria;
use App\Models\Produto;
use App\Services\EstoqueService;
use Illuminate\Http\Request;

class ProdutoController extends Controller
{
    public function __construct(protected EstoqueService $estoqueService)
    {
    }

    public function index(Request $request)
    {
        $query = Produto::with('categoria');

        if ($busca = $request->input('busca')) {
            $query->busca($busca);
        }

        if ($categoriaId = $request->input('categoria_id')) {
            $categoria = Categoria::with('childrenRecursive')->find($categoriaId);
            $categoriaIds = $categoria ? array_merge([$categoria->id], $categoria->descendantIds()) : [$categoriaId];
            $query->whereIn('categoria_id', $categoriaIds);
        }

        if ($request->input('apenas_ativos') !== '0') {
            $query->ativos();
        }

        if ($request->input('estoque_baixo')) {
            $query->estoqueBaixo();
        }

        $produtos    = $query->orderBy('nome')->paginate(20)->withQueryString();
        $categorias  = Categoria::ativas()->with('parent')->orderBy('nome')->get();
        $totalEstoqueBaixo = Produto::ativos()->estoqueBaixo()->count();

        return view('produtos.index', compact('produtos', 'categorias', 'totalEstoqueBaixo'));
    }

    public function create()
    {
        $categorias = Categoria::ativas()->with('parent')->orderBy('nome')->get();
        return view('produtos.form', ['produto' => new Produto(), 'categorias' => $categorias]);
    }

    public function store(ProdutoRequest $request)
    {
        $dados = $request->validated();
        $dados['custo_unitario'] = $dados['custo_unitario'] ?? 0;
        $dados['controla_estoque'] = (bool) ($dados['controla_estoque'] ?? false);

        if (!$dados['controla_estoque']) {
            $dados['quantidade_estoque'] = 0;
            $dados['estoque_minimo'] = 0;
        }

        // Gera código interno automático se não foi informado
        if (empty($dados['codigo_interno'])) {
            $dados['codigo_interno'] = 'PRD-' . str_pad(Produto::withTrashed()->count() + 1, 6, '0', STR_PAD_LEFT);
        }

        Produto::create($dados);

        return redirect()->route('produtos.index')
            ->with('sucesso', 'Produto cadastrado com sucesso!');
    }

    public function show(Produto $produto)
    {
        $produto->load(['categoria', 'movimentacoes.usuario']);
        $movimentacoes = $produto->movimentacoes()->paginate(15);
        return view('produtos.show', compact('produto', 'movimentacoes'));
    }

    public function edit(Produto $produto)
    {
        $categorias = Categoria::ativas()->with('parent')->orderBy('nome')->get();
        return view('produtos.form', compact('produto', 'categorias'));
    }

    public function update(ProdutoRequest $request, Produto $produto)
    {
        $dados = $request->validated();
        $dados['custo_unitario'] = $dados['custo_unitario'] ?? 0;
        $dados['controla_estoque'] = (bool) ($dados['controla_estoque'] ?? false);

        if (!$dados['controla_estoque']) {
            $dados['quantidade_estoque'] = 0;
            $dados['estoque_minimo'] = 0;
        }

        $produto->update($dados);

        return redirect()->route('produtos.index')
            ->with('sucesso', 'Produto atualizado com sucesso!');
    }

    public function destroy(Produto $produto)
    {
        $produto->delete(); // Soft delete

        return redirect()->route('produtos.index')
            ->with('sucesso', 'Produto removido com sucesso!');
    }

    /**
     * Registra entrada manual de estoque
     */
    public function entradaEstoque(Request $request, Produto $produto)
    {
        if (!$produto->controla_estoque) {
            return back()->with('erro', 'Este produto nao controla estoque.');
        }

        $validated = $request->validate([
            'quantidade'    => ['required', 'numeric', 'min:0.001'],
            'custo_unitario'=> ['required', 'numeric', 'min:0'],
            'tipo'          => ['required', 'in:ENTRADA_COMPRA,ENTRADA_AJUSTE'],
            'motivo'        => ['nullable', 'string', 'max:255'],
        ]);

        try {
            $this->estoqueService->movimentar(
                produto: $produto,
                tipo: $validated['tipo'],
                quantidade: (float) $validated['quantidade'],
                custoUnitario: (float) $validated['custo_unitario'],
                extras: ['motivo' => $validated['motivo'] ?? 'Entrada manual']
            );

            return back()->with('sucesso', "Entrada de {$validated['quantidade']} {$produto->unidade_medida} registrada!");
        } catch (\Exception $e) {
            return back()->with('erro', $e->getMessage());
        }
    }

    public function ajustarEstoque(Request $request, Produto $produto)
    {
        if (!$produto->controla_estoque) {
            return back()->with('erro', 'Este produto nao controla estoque.');
        }

        $validated = $request->validate([
            'quantidade_estoque' => ['required', 'numeric', 'min:0'],
            'motivo'             => ['nullable', 'string', 'max:255'],
        ]);

        $estoqueAtual = (float) $produto->quantidade_estoque;
        $novoEstoque = (float) $validated['quantidade_estoque'];
        $diferenca = round($novoEstoque - $estoqueAtual, 3);

        if ($diferenca == 0.0) {
            return back()->with('sucesso', 'Estoque mantido sem alterações.');
        }

        try {
            $this->estoqueService->movimentar(
                produto: $produto,
                tipo: $diferenca > 0 ? 'ENTRADA_AJUSTE' : 'SAIDA_AJUSTE',
                quantidade: abs($diferenca),
                custoUnitario: (float) $produto->custo_unitario,
                extras: ['motivo' => $validated['motivo'] ?? 'Correção manual de estoque']
            );

            return back()->with('sucesso', 'Estoque ajustado com sucesso!');
        } catch (\Exception $e) {
            return back()->with('erro', $e->getMessage());
        }
    }

    /**
     * Busca produto por código de barras/nome — usado no PDV
     */
    public function buscarApi(Request $request)
    {
        $termo = trim((string) $request->input('q', ''));

        if ($termo === '') {
            return response()->json([]);
        }

        $limite = min(max((int) $request->input('limit', 50), 1), 100);
        $termoLike = "%{$termo}%";
        $termoInicio = "{$termo}%";

        $query = Produto::ativos()
            ->select(['id', 'nome', 'codigo_interno', 'codigo_barras', 'preco_venda', 'custo_unitario', 'quantidade_estoque', 'unidade_medida'])
            ->limit($limite);

        if ($request->boolean('exact')) {
            $query->where(function ($q) use ($termo) {
                $q->where('codigo_barras', $termo)
                    ->orWhere('codigo_interno', $termo);
            });
        } else {
            $query->busca($termo)
                ->orderByRaw(
                    'CASE
                        WHEN codigo_barras = ? OR codigo_interno = ? THEN 0
                        WHEN nome LIKE ? THEN 1
                        WHEN nome LIKE ? THEN 2
                        WHEN codigo_interno LIKE ? OR codigo_barras LIKE ? THEN 3
                        ELSE 4
                    END',
                    [$termo, $termo, $termoInicio, $termoLike, $termoLike, $termoLike]
                )
                ->orderBy('nome');
        }

        $produtos = $query->get();

        return response()->json($produtos);
    }
}
