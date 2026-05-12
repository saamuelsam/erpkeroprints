<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
        'perfil',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password'          => 'hashed',
        ];
    }

    // Perfis do sistema
    public const PERFIS = [
        'admin'    => 'Administrador',
        'gerente'  => 'Gerente',
        'atendente'=> 'Atendente',
        'caixa'    => 'Operador de Caixa',
        'producao' => 'Produção',
    ];

    public function isAdmin(): bool
    {
        return $this->perfil === 'admin';
    }

    public function getPerfilLabelAttribute(): string
    {
        return self::PERFIS[$this->perfil] ?? $this->perfil ?? 'Sem perfil';
    }
}
