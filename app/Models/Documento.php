<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Documento extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'numero', 'tipo', 'cliente_id', 'data_emissao', 'data_vencimento',
        'subtotal', 'desconto', 'valor_total', 'forma_pagamento',
        'observacoes', 'condicoes_pagamento', 'status', 'user_id',
    ];

    protected $casts = [
        'data_emissao'    => 'date',
        'data_vencimento' => 'date',
        'subtotal'        => 'decimal:2',
        'desconto'        => 'decimal:2',
        'valor_total'     => 'decimal:2',
    ];

    protected $attributes = [
        'status'      => 'RASCUNHO',
        'subtotal'    => 0,
        'desconto'    => 0,
        'valor_total' => 0,
    ];

    public const TIPOS = [
        'RECIBO'      => 'Recibo',
        'ORCAMENTO'   => 'Orçamento',
        'PEDIDO'      => 'Pedido',
        'COMPROVANTE' => 'Comprovante',
        'OS'          => 'Ordem de Serviço',
        'COBRANCA'    => 'Cobrança',
    ];

    public const STATUS_LABELS = [
        'RASCUNHO'  => ['label' => 'Rascunho',  'badge' => 'secondary'],
        'EMITIDO'   => ['label' => 'Emitido',   'badge' => 'primary'],
        'ENVIADO'   => ['label' => 'Enviado',   'badge' => 'info'],
        'PAGO'      => ['label' => 'Pago',      'badge' => 'success'],
        'CANCELADO' => ['label' => 'Cancelado', 'badge' => 'danger'],
    ];

    // ─── Relacionamentos ──────────────────────────────────────────────────────

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
        return $this->hasMany(DocumentoItem::class);
    }

    public function envios(): HasMany
    {
        return $this->hasMany(DocumentoEnvio::class);
    }

    // ─── Scopes ───────────────────────────────────────────────────────────────

    public function scopeBusca($query, string $termo)
    {
        return $query->where(function ($q) use ($termo) {
            $q->where('numero', 'like', "%{$termo}%")
              ->orWhere('observacoes', 'like', "%{$termo}%")
              ->orWhereHas('cliente', fn($c) => $c->where('nome', 'like', "%{$termo}%"));
        });
    }

    public function scopeTipo($query, string $tipo)
    {
        return $query->where('tipo', $tipo);
    }

    // ─── Accessors ────────────────────────────────────────────────────────────

    public function getStatusLabelAttribute(): string
    {
        return self::STATUS_LABELS[$this->status]['label'] ?? $this->status ?? 'Rascunho';
    }

    public function getStatusBadgeAttribute(): string
    {
        return self::STATUS_LABELS[$this->status]['badge'] ?? 'secondary';
    }

    public function getTipoLabelAttribute(): string
    {
        return self::TIPOS[$this->tipo] ?? $this->tipo ?? '';
    }

    // ─── Helpers ──────────────────────────────────────────────────────────────

    public function recalcularTotais(): void
    {
        $subtotal = $this->itens()->sum('total_item');
        $this->update([
            'subtotal'    => $subtotal,
            'valor_total' => $subtotal - $this->desconto,
        ]);
    }

    /**
     * Gera o próximo número de documento no formato DOC-YYYY-NNNNN.
     */
    public static function gerarNumero(): string
    {
        $ano = now()->format('Y');
        $ultimoDoc = static::withTrashed()
            ->where('numero', 'like', "DOC-{$ano}-%")
            ->orderByDesc('numero')
            ->first();

        $sequencial = 1;
        if ($ultimoDoc) {
            $partes = explode('-', $ultimoDoc->numero);
            $sequencial = (int) end($partes) + 1;
        }

        return sprintf('DOC-%s-%05d', $ano, $sequencial);
    }
}
