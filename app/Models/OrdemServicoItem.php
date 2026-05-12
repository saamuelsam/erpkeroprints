<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrdemServicoItem extends Model
{
    protected $table = 'ordem_servico_itens';

    protected $fillable = [
        'ordem_servico_id',
        'produto_id',
        'descricao_item',
        'quantidade',
        'custo_unitario',
        'preco_unitario',
        'total_item',
    ];

    protected $casts = [
        'quantidade'     => 'decimal:3',
        'custo_unitario' => 'decimal:2',
        'preco_unitario' => 'decimal:2',
        'total_item'     => 'decimal:2',
    ];

    public function ordemServico(): BelongsTo
    {
        return $this->belongsTo(OrdemServico::class, 'ordem_servico_id');
    }

    public function produto(): BelongsTo
    {
        return $this->belongsTo(Produto::class);
    }
}
