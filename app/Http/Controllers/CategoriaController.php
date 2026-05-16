<?php

namespace App\Http\Controllers;

use App\Models\Categoria;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CategoriaController extends Controller
{
    public function index(Request $request)
    {
        $query = Categoria::with('parent')->withCount('produtos');

        if ($busca = $request->input('busca')) {
            $query->where('nome', 'like', "%{$busca}%");
        }

        $categorias = $query->orderBy('nome')->get();
        $categoriasArvore = $request->filled('busca')
            ? $categorias->each(fn($categoria) => $categoria->setRelation('children', collect()))
            : $this->montarArvore($categorias);

        return view('categorias.index', compact('categorias', 'categoriasArvore'));
    }

    public function create()
    {
        return view('categorias.form', [
            'categoria' => new Categoria(),
            'categoriasPai' => Categoria::with('parent')->orderBy('nome')->get(),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'nome' => ['required', 'string', 'max:100'],
            'parent_id' => ['nullable', 'exists:categorias,id'],
            'descricao' => ['nullable', 'string', 'max:255'],
            'ativo' => ['boolean'],
        ]);

        Categoria::create($validated);

        return redirect()->route('categorias.index')
            ->with('sucesso', 'Categoria criada com sucesso!');
    }

    public function edit(Categoria $categoria)
    {
        $idsBloqueados = array_merge([$categoria->id], $categoria->load('childrenRecursive')->descendantIds());

        return view('categorias.form', [
            'categoria' => $categoria,
            'categoriasPai' => Categoria::with('parent')->whereNotIn('id', $idsBloqueados)->orderBy('nome')->get(),
        ]);
    }

    public function update(Request $request, Categoria $categoria)
    {
        $idsBloqueados = array_merge([$categoria->id], $categoria->load('childrenRecursive')->descendantIds());

        $validated = $request->validate([
            'nome' => ['required', 'string', 'max:100'],
            'parent_id' => ['nullable', 'exists:categorias,id', function ($attribute, $value, $fail) use ($idsBloqueados) {
                if ($value && in_array((int) $value, $idsBloqueados, true)) {
                    $fail('Escolha outra categoria pai para evitar ciclos na arvore.');
                }
            }],
            'descricao' => ['nullable', 'string', 'max:255'],
            'ativo' => ['boolean'],
        ]);

        $categoria->update($validated);

        return redirect()->route('categorias.index')
            ->with('sucesso', 'Categoria atualizada com sucesso!');
    }

    public function destroy(Categoria $categoria)
    {
        try {
            DB::transaction(function () use ($categoria) {
                $destinoId = $categoria->parent_id;

                if (!$destinoId) {
                    $semCategoria = Categoria::firstOrCreate(
                        ['nome' => 'Sem categoria', 'parent_id' => null],
                        ['descricao' => 'Produtos sem categoria definida', 'ativo' => true]
                    );

                    if ($categoria->is($semCategoria)) {
                        if ($categoria->produtos()->exists() || $categoria->children()->exists()) {
                            throw new \RuntimeException('A categoria Sem categoria nao pode ser excluida enquanto estiver em uso.');
                        }
                    } else {
                        $destinoId = $semCategoria->id;
                    }
                }

                $categoria->produtos()->update(['categoria_id' => $destinoId]);
                $categoria->children()->update(['parent_id' => $categoria->parent_id]);
                $categoria->delete();
            });
        } catch (\RuntimeException $e) {
            return back()->with('erro', $e->getMessage());
        }

        return redirect()->route('categorias.index')
            ->with('sucesso', 'Categoria removida com sucesso!');
    }

    private function montarArvore($categorias, ?int $parentId = null)
    {
        return $categorias
            ->where('parent_id', $parentId)
            ->sortBy('nome')
            ->values()
            ->map(function ($categoria) use ($categorias) {
                $categoria->setRelation('children', $this->montarArvore($categorias, $categoria->id));
                return $categoria;
            });
    }
}
