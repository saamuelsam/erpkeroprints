<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Categoria extends Model
{
    protected $fillable = [
        'nome',
        'parent_id',
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

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id')->orderBy('nome');
    }

    public function childrenRecursive(): HasMany
    {
        return $this->children()->with('childrenRecursive');
    }

    public function descendantIds(): array
    {
        $ids = [];

        foreach ($this->children as $child) {
            $ids[] = $child->id;
            $ids = array_merge($ids, $child->descendantIds());
        }

        return $ids;
    }

    public function getNomeCompletoAttribute(): string
    {
        return collect($this->ancestors())
            ->pluck('nome')
            ->push($this->nome)
            ->implode(' > ');
    }

    public function ancestors(): array
    {
        $items = [];
        $parent = $this->parent;

        while ($parent) {
            array_unshift($items, $parent);
            $parent = $parent->parent;
        }

        return $items;
    }

    // ─── Scopes ────────────────────────────────────────────────────────────────

    public function scopeAtivas($query)
    {
        return $query->where('ativo', true);
    }
}
