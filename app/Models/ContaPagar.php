<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ContaPagar extends Model
{
    use SoftDeletes;

    protected $table = 'contas_pagar';

    protected $fillable = [
        'fornecedor_id', 'descricao', 'valor', 'data_emissao', 'data_vencimento',
        'data_pagamento', 'forma_pagamento', 'status', 'categoria', 'observacoes', 'user_id',
    ];

    protected $casts = [
        'valor'           => 'decimal:2',
        'data_emissao'    => 'date',
        'data_vencimento' => 'date',
        'data_pagamento'  => 'date',
    ];

    protected $attributes = [
        'status' => 'ABERTA',
    ];

    public const STATUS_LABELS = [
        'ABERTA'    => ['label' => 'Aberta',    'badge' => 'warning'],
        'PAGA'      => ['label' => 'Paga',      'badge' => 'success'],
        'VENCIDA'   => ['label' => 'Vencida',   'badge' => 'danger'],
        'CANCELADA' => ['label' => 'Cancelada', 'badge' => 'secondary'],
    ];

    public const CATEGORIAS = [
        'COMPRA_MERCADORIA' => 'Compra de mercadoria',
        'COMPRA_MATERIAL'   => 'Compra de material gráfico',
        'ALUGUEL'           => 'Aluguel',
        'ENERGIA'           => 'Energia',
        'INTERNET'          => 'Internet',
        'AGUA'              => 'Água',
        'SALARIOS'          => 'Salários',
        'MANUTENCAO'        => 'Manutenção',
        'TRANSPORTE'        => 'Transporte',
        'IMPOSTOS'          => 'Impostos',
        'TAXAS'             => 'Taxas',
        'OUTROS'            => 'Outros',
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

    // ─── Scopes ────────────────────────────────────────────────────────────────

    public function scopeAbertas($query)
    {
        return $query->where('status', 'ABERTA');
    }

    public function scopeVencidas($query)
    {
        return $query->where('status', 'ABERTA')
                     ->where('data_vencimento', '<', now()->toDateString());
    }

    public function scopePeriodo($query, $inicio, $fim)
    {
        return $query->whereBetween('data_vencimento', [$inicio, $fim]);
    }

    // ─── Accessors ────────────────────────────────────────────────────────────

    public function getStatusLabelAttribute(): string
    {
        return self::STATUS_LABELS[$this->status]['label'] ?? $this->status ?? 'Aberta';
    }

    public function getStatusBadgeAttribute(): string
    {
        return self::STATUS_LABELS[$this->status]['badge'] ?? 'secondary';
    }

    public function getIsVencidaAttribute(): bool
    {
        return $this->status === 'ABERTA'
            && $this->data_vencimento
            && $this->data_vencimento->isPast();
    }

    public function getCategoriaLabelAttribute(): string
    {
        return self::CATEGORIAS[$this->categoria] ?? $this->categoria ?? '';
    }
}
