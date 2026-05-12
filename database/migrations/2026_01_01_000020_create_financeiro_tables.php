<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // ── Fornecedores ──────────────────────────────────────────────────────────
        Schema::create('fornecedores', function (Blueprint $table) {
            $table->id();
            $table->string('nome', 150);
            $table->string('telefone', 20)->nullable();
            $table->string('cpf_cnpj', 20)->nullable();
            $table->string('email', 150)->nullable();
            $table->text('observacoes')->nullable();
            $table->boolean('ativo')->default(true);
            $table->timestamps();
            $table->softDeletes();

            $table->index('nome');
            $table->index('ativo');
        });

        // ── Entradas Financeiras ──────────────────────────────────────────────────
        Schema::create('financeiro_entradas', function (Blueprint $table) {
            $table->id();
            $table->date('data');
            $table->string('descricao', 255);
            $table->string('categoria', 50);
            $table->decimal('valor', 12, 2);
            $table->string('forma_pagamento', 50)->nullable();

            // Referência polimórfica (OS, Venda, Conta a Receber, etc.)
            $table->string('origem_tipo', 50)->nullable();
            $table->unsignedBigInteger('origem_id')->nullable();

            $table->foreignId('cliente_id')->nullable()->constrained('clientes')->onDelete('set null');
            $table->foreignId('user_id')->constrained('users')->onDelete('restrict');

            $table->text('observacoes')->nullable();
            $table->enum('status', ['CONFIRMADA', 'PENDENTE', 'CANCELADA'])->default('CONFIRMADA');

            $table->timestamps();
            $table->softDeletes();

            $table->index('data');
            $table->index('categoria');
            $table->index('status');
            $table->index('cliente_id');
            $table->index(['origem_tipo', 'origem_id']);
        });

        // ── Saídas Financeiras ────────────────────────────────────────────────────
        Schema::create('financeiro_saidas', function (Blueprint $table) {
            $table->id();
            $table->date('data');
            $table->string('descricao', 255);
            $table->string('categoria', 50);
            $table->decimal('valor', 12, 2);
            $table->string('forma_pagamento', 50)->nullable();

            $table->string('fornecedor_nome', 150)->nullable();
            $table->foreignId('fornecedor_id')->nullable()->constrained('fornecedores')->onDelete('set null');

            // Referência polimórfica (Conta a Pagar, etc.)
            $table->string('origem_tipo', 50)->nullable();
            $table->unsignedBigInteger('origem_id')->nullable();

            $table->foreignId('user_id')->constrained('users')->onDelete('restrict');

            $table->text('observacoes')->nullable();
            $table->enum('status', ['CONFIRMADA', 'PENDENTE', 'CANCELADA'])->default('CONFIRMADA');

            $table->timestamps();
            $table->softDeletes();

            $table->index('data');
            $table->index('categoria');
            $table->index('status');
            $table->index('fornecedor_id');
            $table->index(['origem_tipo', 'origem_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('financeiro_saidas');
        Schema::dropIfExists('financeiro_entradas');
        Schema::dropIfExists('fornecedores');
    }
};
