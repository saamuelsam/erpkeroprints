<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Cliente extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'nome',
        'telefone',
        'cpf_cnpj',
        'email',
        'cep',
        'endereco',
        'numero',
        'complemento',
        'bairro',
        'cidade',
        'estado',
        'observacoes',
        'ativo',
    ];

    protected $casts = [
        'ativo' => 'boolean',
    ];

    // ─── Relacionamentos ───────────────────────────────────────────────────────

    public function ordensServico(): HasMany
    {
        return $this->hasMany(OrdemServico::class);
    }

    // ─── Scopes ────────────────────────────────────────────────────────────────

    public function scopeAtivos($query)
    {
        return $query->where('ativo', true);
    }

    public function scopeBusca($query, string $termo)
    {
        return $query->where(function ($q) use ($termo) {
            $q->where('nome', 'like', "%{$termo}%")
              ->orWhere('telefone', 'like', "%{$termo}%")
              ->orWhere('cpf_cnpj', 'like', "%{$termo}%")
              ->orWhere('email', 'like', "%{$termo}%");
        });
    }

    // ─── Accessors ────────────────────────────────────────────────────────────

    public function getCpfCnpjFormatadoAttribute(): string
    {
        $val = preg_replace('/\D/', '', $this->cpf_cnpj ?? '');
        if (strlen($val) === 11) {
            return preg_replace('/(\d{3})(\d{3})(\d{3})(\d{2})/', '$1.$2.$3-$4', $val);
        }
        if (strlen($val) === 14) {
            return preg_replace('/(\d{2})(\d{3})(\d{3})(\d{4})(\d{2})/', '$1.$2.$3/$4-$5', $val);
        }
        return $this->cpf_cnpj ?? '';
    }

    public function getTelefoneFormatadoAttribute(): string
    {
        $val = preg_replace('/\D/', '', $this->telefone ?? '');
        if (strlen($val) === 11) {
            return preg_replace('/(\d{2})(\d{5})(\d{4})/', '($1) $2-$3', $val);
        }
        if (strlen($val) === 10) {
            return preg_replace('/(\d{2})(\d{4})(\d{4})/', '($1) $2-$3', $val);
        }
        return $this->telefone ?? '';
    }
}
