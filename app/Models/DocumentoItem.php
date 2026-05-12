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
            $item->total_item = ($item->quantidade * $item->valor_unitario) - $item->desconto_item;
        });
    }
}
