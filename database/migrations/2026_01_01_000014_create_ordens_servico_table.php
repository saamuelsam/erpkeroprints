<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ordens_servico', function (Blueprint $table) {
            $table->id();
            $table->string('numero_os', 20)->unique(); // Ex: OS-2026-00001
            $table->foreignId('cliente_id')->constrained('clientes')->onDelete('restrict');
            $table->foreignId('user_id')->constrained('users')->onDelete('restrict'); // Responsável
            $table->date('data_abertura');
            $table->date('data_prevista_entrega')->nullable();
            $table->date('data_conclusao')->nullable();
            $table->text('descricao_servico');
            $table->text('observacoes_internas')->nullable();
            $table->text('observacoes_cliente')->nullable(); // Visível no comprovante

            // Valores financeiros — nunca usar float
            $table->decimal('custo_materiais', 12, 2)->default(0);
            $table->decimal('custos_adicionais', 12, 2)->default(0);
            $table->decimal('valor_servico', 12, 2)->default(0); // Valor cobrado pelos serviços
            $table->decimal('desconto', 12, 2)->default(0);
            $table->decimal('valor_final', 12, 2)->default(0);   // = valor_servico - desconto

            $table->enum('status', [
                'ABERTA',
                'PRODUCAO',
                'AGUARDANDO_APROVACAO',
                'FINALIZADA',
                'ENTREGUE',
                'CANCELADA',
            ])->default('ABERTA');

            $table->enum('status_pagamento', [
                'PENDENTE',
                'PAGO_PARCIAL',
                'PAGO',
            ])->default('PENDENTE');

            $table->string('forma_pagamento', 50)->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->index('numero_os');
            $table->index('cliente_id');
            $table->index('status');
            $table->index('status_pagamento');
            $table->index('data_abertura');
            $table->index('data_prevista_entrega');
        });

        // Itens / materiais da OS
        Schema::create('ordem_servico_itens', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ordem_servico_id')->constrained('ordens_servico')->onDelete('cascade');
            $table->foreignId('produto_id')->nullable()->constrained('produtos')->onDelete('restrict');
            $table->string('descricao_item', 255); // Descrição manual ou nome do produto
            $table->decimal('quantidade', 10, 3);
            $table->decimal('custo_unitario', 12, 2)->default(0);
            $table->decimal('preco_unitario', 12, 2)->default(0); // Valor cobrado do item
            $table->decimal('total_item', 12, 2)->default(0);
            $table->timestamps();

            $table->index('ordem_servico_id');
            $table->index('produto_id');
        });

        // Histórico de mudanças de status da OS
        Schema::create('ordem_servico_historicos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ordem_servico_id')->constrained('ordens_servico')->onDelete('cascade');
            $table->foreignId('user_id')->constrained('users')->onDelete('restrict');
            $table->string('status_anterior', 30)->nullable();
            $table->string('status_novo', 30);
            $table->text('observacao')->nullable();
            $table->timestamps();

            $table->index('ordem_servico_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ordem_servico_historicos');
        Schema::dropIfExists('ordem_servico_itens');
        Schema::dropIfExists('ordens_servico');
    }
};
