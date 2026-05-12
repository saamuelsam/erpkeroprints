<?php

namespace App\Http\Controllers;

use App\Http\Requests\ClienteRequest;
use App\Models\Cliente;
use Illuminate\Http\Request;

class ClienteController extends Controller
{
    public function index(Request $request)
    {
        $query = Cliente::query();

        if ($busca = $request->input('busca')) {
            $query->busca($busca);
        }

        if ($request->input('apenas_ativos') !== '0') {
            $query->ativos();
        }

        $clientes = $query->orderBy('nome')->paginate(20)->withQueryString();

        return view('clientes.index', compact('clientes'));
    }

    public function create()
    {
        return view('clientes.form', ['cliente' => new Cliente()]);
    }

    public function store(ClienteRequest $request)
    {
        Cliente::create($request->validated());

        return redirect()->route('clientes.index')
            ->with('sucesso', 'Cliente cadastrado com sucesso!');
    }

    public function show(Cliente $cliente)
    {
        $cliente->load(['ordensServico' => fn($q) => $q->latest()->take(10)]);
        return view('clientes.show', compact('cliente'));
    }

    public function edit(Cliente $cliente)
    {
        return view('clientes.form', compact('cliente'));
    }

    public function update(ClienteRequest $request, Cliente $cliente)
    {
        $cliente->update($request->validated());

        return redirect()->route('clientes.index')
            ->with('sucesso', 'Cliente atualizado com sucesso!');
    }

    public function destroy(Cliente $cliente)
    {
        // Soft delete — não apaga físico por compliance LGPD
        $cliente->delete();

        return redirect()->route('clientes.index')
            ->with('sucesso', 'Cliente inativado com sucesso!');
    }

    public function toggleAtivo(Cliente $cliente)
    {
        $cliente->update(['ativo' => !$cliente->ativo]);

        $msg = $cliente->ativo ? 'Cliente ativado!' : 'Cliente inativado!';
        return back()->with('sucesso', $msg);
    }
}
