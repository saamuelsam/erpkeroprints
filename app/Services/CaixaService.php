<?php

namespace App\Services;

use App\Models\FinanceiroEntrada;
use App\Models\FinanceiroSaida;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Carbon;

class CaixaService
{
    /**
     * Registra uma entrada financeira manual.
     */
    public function registrarEntrada(array $dados): FinanceiroEntrada
    {
        return DB::transaction(function () use ($dados) {
            $dados['user_id'] = Auth::id();
            $dados['status']  = $dados['status'] ?? 'CONFIRMADA';

            return FinanceiroEntrada::create($dados);
        });
    }

    /**
     * Registra uma saída financeira manual.
     */
    public function registrarSaida(array $dados): FinanceiroSaida
    {
        return DB::transaction(function () use ($dados) {
            $dados['user_id'] = Auth::id();
            $dados['status']  = $dados['status'] ?? 'CONFIRMADA';

            return FinanceiroSaida::create($dados);
        });
    }

    /**
     * Registra entrada automática (chamada por OS paga, conta recebida, etc.)
     * Evita duplicidade verificando origem_tipo + origem_id.
     */
    public function registrarEntradaAutomatica(
        string $descricao,
        float  $valor,
        string $categoria,
        string $formaPagamento,
        ?int   $clienteId,
        string $origemTipo,
        int    $origemId
    ): FinanceiroEntrada {
        // Verifica se já existe entrada para esta origem (evita duplicidade)
        $existente = FinanceiroEntrada::where('origem_tipo', $origemTipo)
            ->where('origem_id', $origemId)
            ->where('status', '!=', 'CANCELADA')
            ->first();

        if ($existente) {
            return $existente;
        }

        return $this->registrarEntrada([
            'data'            => now()->toDateString(),
            'descricao'       => $descricao,
            'categoria'       => $categoria,
            'valor'           => $valor,
            'forma_pagamento' => $formaPagamento,
            'origem_tipo'     => $origemTipo,
            'origem_id'       => $origemId,
            'cliente_id'      => $clienteId,
        ]);
    }

    /**
     * Registra saída automática (conta paga, etc.)
     * Evita duplicidade verificando origem_tipo + origem_id.
     */
    public function registrarSaidaAutomatica(
        string $descricao,
        float  $valor,
        string $categoria,
        string $formaPagamento,
        ?int   $fornecedorId,
        string $origemTipo,
        int    $origemId
    ): FinanceiroSaida {
        $existente = FinanceiroSaida::where('origem_tipo', $origemTipo)
            ->where('origem_id', $origemId)
            ->where('status', '!=', 'CANCELADA')
            ->first();

        if ($existente) {
            return $existente;
        }

        return $this->registrarSaida([
            'data'            => now()->toDateString(),
            'descricao'       => $descricao,
            'categoria'       => $categoria,
            'valor'           => $valor,
            'forma_pagamento' => $formaPagamento,
            'fornecedor_id'   => $fornecedorId,
            'origem_tipo'     => $origemTipo,
            'origem_id'       => $origemId,
        ]);
    }

    /**
     * Retorna dados do fluxo de caixa para um período.
     */
    public function fluxoCaixa(Carbon $inicio, Carbon $fim, array $filtros = []): array
    {
        $queryEntradas = FinanceiroEntrada::periodo($inicio, $fim)
            ->confirmadas()
            ->with('cliente', 'responsavel');

        $querySaidas = FinanceiroSaida::periodo($inicio, $fim)
            ->confirmadas()
            ->with('fornecedor', 'responsavel');

        // Aplicar filtros
        if (!empty($filtros['categoria'])) {
            $queryEntradas->categoria($filtros['categoria']);
            $querySaidas->categoria($filtros['categoria']);
        }

        if (!empty($filtros['forma_pagamento'])) {
            $queryEntradas->where('forma_pagamento', $filtros['forma_pagamento']);
            $querySaidas->where('forma_pagamento', $filtros['forma_pagamento']);
        }

        $entradas = $queryEntradas->get()->map(fn($e) => [
            'data'            => $e->data,
            'tipo'            => 'ENTRADA',
            'descricao'       => $e->descricao,
            'categoria'       => $e->categoria_label,
            'forma_pagamento' => $e->forma_pagamento,
            'valor'           => (float) $e->valor,
            'responsavel'     => $e->responsavel->name ?? '—',
            'referencia'      => $e->cliente->nome ?? '—',
            'id'              => $e->id,
        ]);

        $saidas = $querySaidas->get()->map(fn($s) => [
            'data'            => $s->data,
            'tipo'            => 'SAIDA',
            'descricao'       => $s->descricao,
            'categoria'       => $s->categoria_label,
            'forma_pagamento' => $s->forma_pagamento,
            'valor'           => (float) $s->valor,
            'responsavel'     => $s->responsavel->name ?? '—',
            'referencia'      => $s->fornecedor_nome ?? $s->fornecedor->nome ?? '—',
            'id'              => $s->id,
        ]);

        // Merge e ordena cronologicamente
        $movimentacoes = $entradas->merge($saidas)
            ->sortByDesc('data')
            ->values();

        return [
            'movimentacoes'  => $movimentacoes,
            'total_entradas' => $entradas->sum('valor'),
            'total_saidas'   => $saidas->sum('valor'),
            'saldo'          => $entradas->sum('valor') - $saidas->sum('valor'),
        ];
    }

    /**
     * Saldo resumido de um período.
     */
    public function saldo(Carbon $inicio, Carbon $fim): array
    {
        $totalEntradas = FinanceiroEntrada::periodo($inicio, $fim)
            ->confirmadas()
            ->sum('valor');

        $totalSaidas = FinanceiroSaida::periodo($inicio, $fim)
            ->confirmadas()
            ->sum('valor');

        return [
            'entradas' => (float) $totalEntradas,
            'saidas'   => (float) $totalSaidas,
            'saldo'    => (float) $totalEntradas - (float) $totalSaidas,
        ];
    }
}
