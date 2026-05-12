<?php

namespace App\Http\Controllers;

use App\Models\OrdemServico;
use App\Models\Produto;

class HomeController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function index()
    {
        // Contadores em tempo real (sem cache para evitar dados desatualizados no desenvolvimento)
        $indicadores = [
            'os_abertas'      => OrdemServico::where('status', 'ABERTA')->count(),
            'os_producao'     => OrdemServico::where('status', 'PRODUCAO')->count(),
            'estoque_baixo'   => Produto::ativos()->estoqueBaixo()->count(),
            'os_entrega_hoje' => OrdemServico::whereDate('data_prevista_entrega', today())
                                    ->whereNotIn('status', ['ENTREGUE', 'CANCELADA'])
                                    ->count(),
        ];

        $ultimasOs = OrdemServico::with(['cliente'])
            ->whereNotIn('status', ['CANCELADA'])
            ->latest()
            ->take(5)
            ->get();

        $produtosEstoqueBaixo = Produto::with('categoria')
            ->ativos()
            ->estoqueBaixo()
            ->take(5)
            ->get();

        return view('home', compact('indicadores', 'ultimasOs', 'produtosEstoqueBaixo'));
    }
}
