<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EstoqueMovimentacao extends Model
{
    protected $table = 'estoque_movimentacoes';

    protected $fillable = [
        'produto_id',
        'user_id',
        'tipo',
        'quantidade',
        'custo_unitario_momento',
        'estoque_anterior',
        'estoque_posterior',
        'motivo',
        'referencia_tipo',
        'referencia_id',
    ];

    protected $casts = [
        'quantidade'             => 'decimal:3',
        'custo_unitario_momento' => 'decimal:2',
        'estoque_anterior'       => 'decimal:3',
        'estoque_posterior'      => 'decimal:3',
    ];

    // ─── Relacionamentos ───────────────────────────────────────────────────────

    public function produto(): BelongsTo
    {
        return $this->belongsTo(Produto::class);
    }

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    // ─── Helpers ──────────────────────────────────────────────────────────────

    public function getTipoLabelAttribute(): string
    {
        return match ($this->tipo) {
            'ENTRADA_COMPRA'  => 'Entrada por Compra',
            'ENTRADA_AJUSTE'  => 'Entrada por Ajuste',
            'SAIDA_VENDA'     => 'Saída por Venda',
            'SAIDA_OS'        => 'Saída por Ordem de Serviço',
            'SAIDA_PERDA'     => 'Saída por Perda',
            'SAIDA_AJUSTE'    => 'Saída por Ajuste',
            default           => $this->tipo,
        };
    }

    public function isEntrada(): bool
    {
        return str_starts_with($this->tipo, 'ENTRADA_');
    }
}
