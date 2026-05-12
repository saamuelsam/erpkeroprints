<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ContaReceber extends Model
{
    use SoftDeletes;

    protected $table = 'contas_receber';

    protected $fillable = [
        'cliente_id', 'descricao', 'valor', 'data_emissao', 'data_vencimento',
        'data_recebimento', 'forma_pagamento', 'status', 'os_id', 'observacoes', 'user_id',
    ];

    protected $casts = [
        'valor'             => 'decimal:2',
        'data_emissao'      => 'date',
        'data_vencimento'   => 'date',
        'data_recebimento'  => 'date',
    ];

    protected $attributes = [
        'status' => 'ABERTA',
    ];

    public const STATUS_LABELS = [
        'ABERTA'    => ['label' => 'Aberta',    'badge' => 'warning'],
        'RECEBIDA'  => ['label' => 'Recebida',  'badge' => 'success'],
        'VENCIDA'   => ['label' => 'Vencida',   'badge' => 'danger'],
        'CANCELADA' => ['label' => 'Cancelada', 'badge' => 'secondary'],
    ];

    // ─── Relacionamentos ───────────────────────────────────────────────────────

    public function cliente(): BelongsTo
    {
        return $this->belongsTo(Cliente::class);
    }

    public function ordemServico(): BelongsTo
    {
        return $this->belongsTo(OrdemServico::class, 'os_id');
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
}
