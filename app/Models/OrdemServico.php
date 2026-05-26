<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class OrdemServico extends Model
{
    use SoftDeletes;

    protected $table = 'ordens_servico';

    protected $fillable = [
        'numero_os',
        'cliente_id',
        'cliente_nome',
        'user_id',
        'data_abertura',
        'data_prevista_entrega',
        'data_conclusao',
        'descricao_servico',
        'observacoes_internas',
        'observacoes_cliente',
        'custo_materiais',
        'custos_adicionais',
        'valor_servico',
        'desconto',
        'valor_final',
        'status',
        'status_pagamento',
        'forma_pagamento',
    ];

    // Valores default para novos registros
    protected $attributes = [
        'status'          => 'ABERTA',
        'status_pagamento'=> 'PENDENTE',
        'custo_materiais' => 0,
        'custos_adicionais'=> 0,
        'desconto'        => 0,
        'valor_servico'   => 0,
        'valor_final'     => 0,
    ];

    protected $casts = [
        'data_abertura'         => 'date',
        'data_prevista_entrega' => 'date',
        'data_conclusao'        => 'date',
        'custo_materiais'       => 'decimal:2',
        'custos_adicionais'     => 'decimal:2',
        'valor_servico'         => 'decimal:2',
        'desconto'              => 'decimal:2',
        'valor_final'           => 'decimal:2',
    ];

    // ─── Constantes de Status ─────────────────────────────────────────────────

    public const STATUS_LABELS = [
        'ABERTA'               => ['label' => 'Aberta',            'badge' => 'primary'],
        'PRODUCAO'             => ['label' => 'Em Produção',        'badge' => 'info'],
        'AGUARDANDO_APROVACAO' => ['label' => 'Aguard. Aprovação',  'badge' => 'warning'],
        'FINALIZADA'           => ['label' => 'Finalizada',         'badge' => 'success'],
        'ENTREGUE'             => ['label' => 'Entregue',           'badge' => 'secondary'],
        'CANCELADA'            => ['label' => 'Cancelada',          'badge' => 'danger'],
    ];

    public const STATUS_PAGAMENTO_LABELS = [
        'PENDENTE'     => ['label' => 'Pendente',     'badge' => 'warning'],
        'PAGO_PARCIAL' => ['label' => 'Pago Parcial', 'badge' => 'info'],
        'PAGO'         => ['label' => 'Pago',          'badge' => 'success'],
    ];

    // ─── Relacionamentos ───────────────────────────────────────────────────────

    public function cliente(): BelongsTo
    {
        return $this->belongsTo(Cliente::class);
    }

    public function responsavel(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function itens(): HasMany
    {
        return $this->hasMany(OrdemServicoItem::class, 'ordem_servico_id');
    }

    public function historicos(): HasMany
    {
        return $this->hasMany(OrdemServicoHistorico::class, 'ordem_servico_id')->latest();
    }

    // ─── Accessors — CORRIGIDOS: tratamento de null ───────────────────────────

    public function getStatusLabelAttribute(): string
    {
        $status = $this->status ?? 'ABERTA';
        return self::STATUS_LABELS[$status]['label'] ?? $status;
    }

    public function getClienteExibicaoAttribute(): string
    {
        return $this->cliente?->nome ?: ($this->cliente_nome ?: 'Consumidor final');
    }

    public function getStatusBadgeAttribute(): string
    {
        $status = $this->status ?? 'ABERTA';
        return self::STATUS_LABELS[$status]['badge'] ?? 'secondary';
    }

    public function getStatusPagamentoLabelAttribute(): string
    {
        $sp = $this->status_pagamento ?? 'PENDENTE';
        return self::STATUS_PAGAMENTO_LABELS[$sp]['label'] ?? $sp;
    }

    public function getStatusPagamentoBadgeAttribute(): string
    {
        $sp = $this->status_pagamento ?? 'PENDENTE';
        return self::STATUS_PAGAMENTO_LABELS[$sp]['badge'] ?? 'secondary';
    }

    public function getCustoTotalAttribute(): float
    {
        return round((float)$this->custo_materiais + (float)$this->custos_adicionais, 2);
    }

    public function getLucroAttribute(): float
    {
        return round((float)$this->valor_final - $this->custo_total, 2);
    }

    public function getMargemAttribute(): float
    {
        if ((float)$this->valor_final <= 0) {
            return 0;
        }
        return round(($this->lucro / (float)$this->valor_final) * 100, 2);
    }
}
