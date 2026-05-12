<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrdemServicoHistorico extends Model
{
    protected $table = 'ordem_servico_historicos';

    protected $fillable = [
        'ordem_servico_id',
        'user_id',
        'status_anterior',
        'status_novo',
        'observacao',
    ];

    public function ordemServico(): BelongsTo
    {
        return $this->belongsTo(OrdemServico::class, 'ordem_servico_id');
    }

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
