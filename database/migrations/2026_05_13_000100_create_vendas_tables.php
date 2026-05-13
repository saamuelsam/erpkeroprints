<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vendas', function (Blueprint $table) {
            $table->id();
            $table->string('numero', 24)->unique();
            $table->foreignId('cliente_id')->nullable()->constrained('clientes')->onDelete('set null');
            $table->foreignId('user_id')->constrained('users')->onDelete('restrict');
            $table->decimal('subtotal', 12, 2)->default(0);
            $table->decimal('desconto', 12, 2)->default(0);
            $table->decimal('valor_total', 12, 2)->default(0);
            $table->enum('forma_pagamento', ['DINHEIRO', 'PIX', 'CARTAO_DEBITO', 'CARTAO_CREDITO', 'OUTROS']);
            $table->enum('status', ['AGUARDANDO_PAGAMENTO', 'PAGA', 'CANCELADA'])->default('AGUARDANDO_PAGAMENTO');
            $table->string('mercado_pago_payment_id', 80)->nullable()->index();
            $table->string('mercado_pago_status', 50)->nullable();
            $table->text('pix_qr_code')->nullable();
            $table->longText('pix_qr_code_base64')->nullable();
            $table->timestamp('pago_em')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index('numero');
            $table->index('status');
            $table->index('forma_pagamento');
            $table->index('created_at');
        });

        Schema::create('venda_itens', function (Blueprint $table) {
            $table->id();
            $table->foreignId('venda_id')->constrained('vendas')->onDelete('cascade');
            $table->foreignId('produto_id')->nullable()->constrained('produtos')->onDelete('restrict');
            $table->string('descricao', 255);
            $table->decimal('quantidade', 10, 3);
            $table->decimal('preco_unitario', 12, 2);
            $table->decimal('custo_unitario', 12, 2)->default(0);
            $table->decimal('total_item', 12, 2);
            $table->timestamps();

            $table->index('venda_id');
            $table->index('produto_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('venda_itens');
        Schema::dropIfExists('vendas');
    }
};
