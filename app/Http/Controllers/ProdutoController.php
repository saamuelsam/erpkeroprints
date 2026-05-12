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
            $query->where('categoria_id', $categoriaId);
        }

        if ($request->input('apenas_ativos') !== '0') {
            $query->ativos();
        }

        if ($request->input('estoque_baixo')) {
            $query->estoqueBaixo();
        }

        $produtos    = $query->orderBy('nome')->paginate(20)->withQueryString();
        $categorias  = Categoria::ativas()->orderBy('nome')->get();
        $totalEstoqueBaixo = Produto::ativos()->estoqueBaixo()->count();

        return view('produtos.index', compact('produtos', 'categorias', 'totalEstoqueBaixo'));
    }

    public function create()
    {
        $categorias = Categoria::ativas()->orderBy('nome')->get();
        return view('produtos.form', ['produto' => new Produto(), 'categorias' => $categorias]);
    }

    public function store(ProdutoRequest $request)
    {
        $dados = $request->validated();

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
        $categorias = Categoria::ativas()->orderBy('nome')->get();
        return view('produtos.form', compact('produto', 'categorias'));
    }

    public function update(ProdutoRequest $request, Produto $produto)
    {
        $produto->update($request->validated());

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

    /**
     * Busca produto por código de barras/nome — usado no PDV
     */
    public function buscarApi(Request $request)
    {
        $termo = $request->input('q', '');

        $produtos = Produto::ativos()
            ->busca($termo)
            ->select(['id', 'nome', 'codigo_interno', 'codigo_barras', 'preco_venda', 'custo_unitario', 'quantidade_estoque', 'unidade_medida'])
            ->limit(10)
            ->get();

        return response()->json($produtos);
    }
}
