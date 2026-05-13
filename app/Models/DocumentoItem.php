<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DocumentoItem extends Model
{
    protected $table = 'documento_itens';

    protected $fillable = [
        'documento_id', 'descricao', 'quantidade',
        'valor_unitario', 'desconto_item', 'total_item',
    ];

    protected $casts = [
        'quantidade'     => 'decimal:3',
        'valor_unitario' => 'decimal:2',
        'desconto_item'  => 'decimal:2',
        'total_item'     => 'decimal:2',
    ];

    protected $attributes = [
        'quantidade'     => 1,
        'valor_unitario' => 0,
        'desconto_item'  => 0,
        'total_item'     => 0,
    ];

    public function documento(): BelongsTo
    {
        return $this->belongsTo(Documento::class);
    }

    /**
     * Calcular total do item automaticamente.
     */
    protected static function booted(): void
    {
        static::saving(function (DocumentoItem $item) {
            $desconto = $item->desconto_item ?? 0;

            $item->total_item = ($item->quantidade * $item->valor_unitario) - $desconto;
        });
    }
}
