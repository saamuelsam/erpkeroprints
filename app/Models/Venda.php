<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Venda extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'numero',
        'cliente_id',
        'ordem_servico_id',
        'user_id',
        'subtotal',
        'desconto',
        'valor_total',
        'valor_recebido',
        'troco',
        'forma_pagamento',
        'pagamentos',
        'status',
        'mercado_pago_payment_id',
        'mercado_pago_status',
        'pix_qr_code',
        'pix_qr_code_base64',
        'pix_confirmado_por',
        'pix_confirmado_em',
        'pix_confirmacao_referencia',
        'pix_confirmacao_pagador',
        'pix_confirmacao_observacao',
        'pago_em',
    ];

    protected $casts = [
        'subtotal' => 'decimal:2',
        'desconto' => 'decimal:2',
        'valor_total' => 'decimal:2',
        'valor_recebido' => 'decimal:2',
        'troco' => 'decimal:2',
        'pagamentos' => 'array',
        'pix_confirmado_em' => 'datetime',
        'pago_em' => 'datetime',
    ];

    protected $attributes = [
        'subtotal' => 0,
        'desconto' => 0,
        'valor_total' => 0,
        'troco' => 0,
        'status' => 'AGUARDANDO_PAGAMENTO',
    ];

    public const FORMAS_PAGAMENTO = [
        'DINHEIRO' => 'Dinheiro',
        'PIX' => 'Pix',
        'CARTAO_DEBITO' => 'Cartao de Debito',
        'CARTAO_CREDITO' => 'Cartao de Credito',
        'OUTROS' => 'Outros',
        'MISTO' => 'Pagamento misto',
    ];

    public const STATUS_LABELS = [
        'AGUARDANDO_PAGAMENTO' => ['label' => 'Aguardando pagamento', 'badge' => 'warning'],
        'PAGA' => ['label' => 'Paga', 'badge' => 'success'],
        'CANCELADA' => ['label' => 'Cancelada', 'badge' => 'danger'],
    ];

    public function cliente(): BelongsTo
    {
        return $this->belongsTo(Cliente::class);
    }

    public function ordemServico(): BelongsTo
    {
        return $this->belongsTo(OrdemServico::class);
    }

    public function responsavel(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function pixConfirmador(): BelongsTo
    {
        return $this->belongsTo(User::class, 'pix_confirmado_por');
    }

    public function itens(): HasMany
    {
        return $this->hasMany(VendaItem::class);
    }

    public function getFormaPagamentoLabelAttribute(): string
    {
        if ($this->forma_pagamento === 'MISTO' && is_array($this->pagamentos)) {
            return collect($this->pagamentos)
                ->map(fn($pagamento) => (self::FORMAS_PAGAMENTO[$pagamento['forma'] ?? ''] ?? $pagamento['forma'] ?? '') . ' R$ ' . number_format((float) ($pagamento['valor'] ?? 0), 2, ',', '.'))
                ->filter()
                ->implode(' + ');
        }

        return self::FORMAS_PAGAMENTO[$this->forma_pagamento] ?? $this->forma_pagamento ?? '';
    }

    public function getValorPixAttribute(): float
    {
        if ($this->forma_pagamento === 'PIX') {
            return (float) $this->valor_total;
        }

        if ($this->forma_pagamento !== 'MISTO' || !is_array($this->pagamentos)) {
            return 0.0;
        }

        return round(collect($this->pagamentos)
            ->where('forma', 'PIX')
            ->sum(fn($pagamento) => (float) ($pagamento['valor'] ?? 0)), 2);
    }

    public function usaPix(): bool
    {
        return $this->forma_pagamento === 'PIX' || $this->valor_pix > 0;
    }

    public function getStatusLabelAttribute(): string
    {
        return self::STATUS_LABELS[$this->status]['label'] ?? $this->status ?? '';
    }

    public function getStatusBadgeAttribute(): string
    {
        return self::STATUS_LABELS[$this->status]['badge'] ?? 'secondary';
    }

    public static function gerarNumero(): string
    {
        $ano = now()->format('Y');
        $ultimo = static::withTrashed()
            ->where('numero', 'like', "VD-{$ano}-%")
            ->orderByDesc('numero')
            ->first();

        $sequencial = 1;
        if ($ultimo) {
            $partes = explode('-', $ultimo->numero);
            $sequencial = (int) end($partes) + 1;
        }

        return sprintf('VD-%s-%05d', $ano, $sequencial);
    }
}
