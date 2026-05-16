<?php

namespace App\Http\Controllers;

use App\Models\Categoria;
use App\Models\Subcategoria;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class SubcategoriaController extends Controller
{
    public function store(Request $request, Categoria $categoria)
    {
        $validated = $request->validate([
            'nome' => [
                'required',
                'string',
                'max:100',
                Rule::unique('subcategorias', 'nome')->where(fn($query) => $query->where('categoria_id', $categoria->id)),
            ],
            'descricao' => ['nullable', 'string', 'max:255'],
            'ativo' => ['boolean'],
        ]);

        $categoria->subcategorias()->create($validated);

        return back()->with('sucesso', 'Subcategoria criada com sucesso!');
    }

    public function update(Request $request, Categoria $categoria, Subcategoria $subcategoria)
    {
        abort_unless($subcategoria->categoria_id === $categoria->id, 404);

        $validated = $request->validate([
            'nome' => [
                'required',
                'string',
                'max:100',
                Rule::unique('subcategorias', 'nome')
                    ->where(fn($query) => $query->where('categoria_id', $categoria->id))
                    ->ignore($subcategoria->id),
            ],
            'descricao' => ['nullable', 'string', 'max:255'],
            'ativo' => ['boolean'],
        ]);

        $subcategoria->update($validated);

        return back()->with('sucesso', 'Subcategoria atualizada com sucesso!');
    }

    public function destroy(Categoria $categoria, Subcategoria $subcategoria)
    {
        abort_unless($subcategoria->categoria_id === $categoria->id, 404);

        if ($subcategoria->produtos()->exists()) {
            return back()->with('erro', 'Nao e possivel excluir uma subcategoria com produtos vinculados.');
        }

        $subcategoria->delete();

        return back()->with('sucesso', 'Subcategoria removida com sucesso!');
    }
}
