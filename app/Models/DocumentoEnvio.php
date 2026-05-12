<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DocumentoEnvio extends Model
{
    protected $table = 'documento_envios';

    protected $fillable = [
        'documento_id', 'tipo', 'destinatario', 'user_id', 'detalhes',
    ];

    protected $casts = [
        'detalhes' => 'array',
    ];

    public function documento(): BelongsTo
    {
        return $this->belongsTo(Documento::class);
    }

    public function responsavel(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
