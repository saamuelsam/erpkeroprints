<?php

namespace App\Http\Controllers;

use App\Models\Categoria;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class CategoriaController extends Controller
{
    public function index(Request $request)
    {
        $query = Categoria::withCount('produtos');

        if ($busca = $request->input('busca')) {
            $query->where('nome', 'like', "%{$busca}%");
        }

        $categorias = $query->orderBy('nome')->paginate(20)->withQueryString();

        return view('categorias.index', compact('categorias'));
    }

    public function create()
    {
        return view('categorias.form', ['categoria' => new Categoria()]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'nome'      => ['required', 'string', 'max:100', 'unique:categorias,nome'],
            'descricao' => ['nullable', 'string', 'max:255'],
            'ativo'     => ['boolean'],
        ], [
            'nome.required' => 'O nome da categoria é obrigatório.',
            'nome.unique'   => 'Já existe uma categoria com este nome.',
        ]);

        Categoria::create($validated);

        return redirect()->route('categorias.index')
            ->with('sucesso', 'Categoria criada com sucesso!');
    }

    public function edit(Categoria $categoria)
    {
        return view('categorias.form', compact('categoria'));
    }

    public function update(Request $request, Categoria $categoria)
    {
        $validated = $request->validate([
            'nome'      => ['required', 'string', 'max:100', Rule::unique('categorias', 'nome')->ignore($categoria->id)],
            'descricao' => ['nullable', 'string', 'max:255'],
            'ativo'     => ['boolean'],
        ]);

        $categoria->update($validated);

        return redirect()->route('categorias.index')
            ->with('sucesso', 'Categoria atualizada com sucesso!');
    }

    public function destroy(Categoria $categoria)
    {
        if ($categoria->produtos()->count() > 0) {
            return back()->with('erro', 'Não é possível excluir uma categoria com produtos vinculados. Inative-a em vez disso.');
        }

        $categoria->delete();

        return redirect()->route('categorias.index')
            ->with('sucesso', 'Categoria removida com sucesso!');
    }
}
