<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('estoque_movimentacoes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('produto_id')->constrained('produtos')->onDelete('cascade');
            $table->foreignId('user_id')->constrained('users')->onDelete('restrict');
            $table->enum('tipo', [
                'ENTRADA_COMPRA',
                'ENTRADA_AJUSTE',
                'SAIDA_VENDA',
                'SAIDA_OS',
                'SAIDA_PERDA',
                'SAIDA_AJUSTE',
            ]);
            $table->decimal('quantidade', 10, 3);  // positivo para entrada, negativo para saída
            $table->decimal('custo_unitario_momento', 12, 2)->default(0); // custo no momento da movimentação
            $table->decimal('estoque_anterior', 10, 3)->default(0); // rastreabilidade
            $table->decimal('estoque_posterior', 10, 3)->default(0); // rastreabilidade
            $table->string('motivo', 255)->nullable();
            $table->string('referencia_tipo', 50)->nullable(); // 'venda', 'os', 'compra'
            $table->unsignedBigInteger('referencia_id')->nullable(); // ID da venda/OS
            $table->timestamps();

            $table->index('produto_id');
            $table->index('user_id');
            $table->index('tipo');
            $table->index('created_at');
            $table->index(['referencia_tipo', 'referencia_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('estoque_movimentacoes');
    }
};
