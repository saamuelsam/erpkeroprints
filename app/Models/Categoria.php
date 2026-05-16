<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Categoria extends Model
{
    protected $fillable = [
        'nome',
        'descricao',
        'ativo',
    ];

    protected $casts = [
        'ativo' => 'boolean',
    ];

    // ─── Relacionamentos ───────────────────────────────────────────────────────

    public function produtos(): HasMany
    {
        return $this->hasMany(Produto::class);
    }

    // ─── Scopes ────────────────────────────────────────────────────────────────

    public function scopeAtivas($query)
    {
        return $query->where('ativo', true);
    }
}
