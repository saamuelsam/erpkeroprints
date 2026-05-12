<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class FinanceiroSaida extends Model
{
    use SoftDeletes;

    protected $table = 'financeiro_saidas';

    protected $fillable = [
        'data',
        'descricao',
        'categoria',
        'valor',
        'forma_pagamento',
        'fornecedor_nome',
        'fornecedor_id',
        'origem_tipo',
        'origem_id',
        'user_id',
        'observacoes',
        'status',
    ];

    protected $casts = [
        'data'  => 'date',
        'valor' => 'decimal:2',
    ];

    protected $attributes = [
        'status' => 'CONFIRMADA',
    ];

    // ─── Constantes ───────────────────────────────────────────────────────────

    public const CATEGORIAS = [
        'COMPRA_MERCADORIA'  => 'Compra de mercadoria',
        'COMPRA_MATERIAL'    => 'Compra de material gráfico',
        'ALUGUEL'            => 'Aluguel',
        'ENERGIA'            => 'Energia',
        'INTERNET'           => 'Internet',
        'AGUA'               => 'Água',
        'SALARIOS'           => 'Salários',
        'MANUTENCAO'         => 'Manutenção',
        'TRANSPORTE'         => 'Transporte',
        'IMPOSTOS'           => 'Impostos',
        'TAXAS'              => 'Taxas',
        'CONTA_PAGAR'        => 'Baixa de conta a pagar',
        'OUTROS'             => 'Outros',
    ];

    public const FORMAS_PAGAMENTO = [
        'Dinheiro', 'Pix', 'Cartão de Débito', 'Cartão de Crédito',
        'Boleto', 'Transferência', 'Outros',
    ];

    public const STATUS_LABELS = [
        'CONFIRMADA' => ['label' => 'Confirmada', 'badge' => 'success'],
        'PENDENTE'   => ['label' => 'Pendente',   'badge' => 'warning'],
        'CANCELADA'  => ['label' => 'Cancelada',  'badge' => 'danger'],
    ];

    // ─── Relacionamentos ───────────────────────────────────────────────────────

    public function fornecedor(): BelongsTo
    {
        return $this->belongsTo(Fornecedor::class);
    }

    public function responsavel(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function origem(): MorphTo
    {
        return $this->morphTo('origem', 'origem_tipo', 'origem_id');
    }

    // ─── Scopes ────────────────────────────────────────────────────────────────

    public function scopePeriodo($query, $inicio, $fim)
    {
        return $query->whereBetween('data', [$inicio, $fim]);
    }

    public function scopeConfirmadas($query)
    {
        return $query->where('status', 'CONFIRMADA');
    }

    public function scopeCategoria($query, string $categoria)
    {
        return $query->where('categoria', $categoria);
    }

    public function scopeBusca($query, string $termo)
    {
        return $query->where('descricao', 'like', "%{$termo}%");
    }

    // ─── Accessors ────────────────────────────────────────────────────────────

    public function getStatusLabelAttribute(): string
    {
        return self::STATUS_LABELS[$this->status]['label'] ?? $this->status ?? 'Confirmada';
    }

    public function getStatusBadgeAttribute(): string
    {
        return self::STATUS_LABELS[$this->status]['badge'] ?? 'secondary';
    }

    public function getCategoriaLabelAttribute(): string
    {
        return self::CATEGORIAS[$this->categoria] ?? $this->categoria ?? '';
    }
}
