<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Produto extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'codigo_interno',
        'codigo_barras',
        'nome',
        'categoria_id',
        'quantidade_estoque',
        'custo_unitario',
        'preco_venda',
        'estoque_minimo',
        'unidade_medida',
        'ativo',
        'observacoes',
    ];

    protected $casts = [
        'quantidade_estoque' => 'decimal:3',
        'custo_unitario'     => 'decimal:2',
        'preco_venda'        => 'decimal:2',
        'estoque_minimo'     => 'decimal:3',
        'ativo'              => 'boolean',
    ];

    // ─── Relacionamentos ───────────────────────────────────────────────────────

    public function categoria(): BelongsTo
    {
        return $this->belongsTo(Categoria::class);
    }

    public function movimentacoes(): HasMany
    {
        return $this->hasMany(EstoqueMovimentacao::class)->latest();
    }

    // ─── Scopes ────────────────────────────────────────────────────────────────

    public function scopeAtivos($query)
    {
        return $query->where('ativo', true);
    }

    public function scopeEstoqueBaixo($query)
    {
        return $query->whereColumn('quantidade_estoque', '<=', 'estoque_minimo')
                     ->where('estoque_minimo', '>', 0);
    }

    public function scopeBusca($query, string $termo)
    {
        return $query->where(function ($q) use ($termo) {
            $q->where('nome', 'like', "%{$termo}%")
              ->orWhere('codigo_interno', 'like', "%{$termo}%")
              ->orWhere('codigo_barras', 'like', "%{$termo}%");
        });
    }

    // ─── Accessors / Computed ──────────────────────────────────────────────────

    public function getLucroUnitarioAttribute(): float
    {
        return round((float)$this->preco_venda - (float)$this->custo_unitario, 2);
    }

    public function getMargemPercentualAttribute(): float
    {
        if ((float)$this->preco_venda <= 0) {
            return 0;
        }
        return round(($this->lucro_unitario / (float)$this->preco_venda) * 100, 2);
    }

    public function getValorEmEstoqueAttribute(): float
    {
        return round((float)$this->quantidade_estoque * (float)$this->custo_unitario, 2);
    }

    public function isEstoqueBaixo(): bool
    {
        return (float)$this->estoque_minimo > 0
            && (float)$this->quantidade_estoque <= (float)$this->estoque_minimo;
    }
}
