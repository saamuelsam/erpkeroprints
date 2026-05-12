<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // ── Contas a Receber ──────────────────────────────────────────────────────
        Schema::create('contas_receber', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cliente_id')->constrained('clientes')->onDelete('restrict');
            $table->string('descricao', 255);
            $table->decimal('valor', 12, 2);
            $table->date('data_emissao');
            $table->date('data_vencimento');
            $table->date('data_recebimento')->nullable();
            $table->string('forma_pagamento', 50)->nullable();

            $table->enum('status', ['ABERTA', 'RECEBIDA', 'VENCIDA', 'CANCELADA'])->default('ABERTA');

            // Referência opcional à OS ou Venda que gerou a conta
            $table->foreignId('os_id')->nullable()->constrained('ordens_servico')->onDelete('set null');

            $table->text('observacoes')->nullable();
            $table->foreignId('user_id')->constrained('users')->onDelete('restrict');

            $table->timestamps();
            $table->softDeletes();

            $table->index('cliente_id');
            $table->index('status');
            $table->index('data_vencimento');
            $table->index('data_emissao');
        });

        // ── Contas a Pagar ────────────────────────────────────────────────────────
        Schema::create('contas_pagar', function (Blueprint $table) {
            $table->id();
            $table->foreignId('fornecedor_id')->nullable()->constrained('fornecedores')->onDelete('set null');
            $table->string('descricao', 255);
            $table->decimal('valor', 12, 2);
            $table->date('data_emissao');
            $table->date('data_vencimento');
            $table->date('data_pagamento')->nullable();
            $table->string('forma_pagamento', 50)->nullable();

            $table->enum('status', ['ABERTA', 'PAGA', 'VENCIDA', 'CANCELADA'])->default('ABERTA');

            $table->string('categoria', 50)->nullable();
            $table->text('observacoes')->nullable();
            $table->foreignId('user_id')->constrained('users')->onDelete('restrict');

            $table->timestamps();
            $table->softDeletes();

            $table->index('fornecedor_id');
            $table->index('status');
            $table->index('data_vencimento');
            $table->index('categoria');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('contas_pagar');
        Schema::dropIfExists('contas_receber');
    }
};
