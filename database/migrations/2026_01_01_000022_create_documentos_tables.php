<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // ── Documentos ────────────────────────────────────────────────────────────
        Schema::create('documentos', function (Blueprint $table) {
            $table->id();
            $table->string('numero', 20)->unique();
            $table->enum('tipo', ['RECIBO', 'ORCAMENTO', 'PEDIDO', 'COMPROVANTE', 'OS', 'COBRANCA']);
            $table->foreignId('cliente_id')->constrained('clientes')->onDelete('restrict');

            $table->date('data_emissao');
            $table->date('data_vencimento')->nullable();

            $table->decimal('subtotal', 12, 2)->default(0);
            $table->decimal('desconto', 12, 2)->default(0);
            $table->decimal('valor_total', 12, 2)->default(0);

            $table->string('forma_pagamento', 50)->nullable();
            $table->text('observacoes')->nullable();
            $table->text('condicoes_pagamento')->nullable();

            $table->enum('status', ['RASCUNHO', 'EMITIDO', 'ENVIADO', 'PAGO', 'CANCELADO'])->default('RASCUNHO');
            $table->foreignId('user_id')->constrained('users')->onDelete('restrict');

            $table->timestamps();
            $table->softDeletes();

            $table->index('numero');
            $table->index('tipo');
            $table->index('status');
            $table->index('cliente_id');
            $table->index('data_emissao');
        });

        // ── Itens do Documento ─────────────────────────────────────────────────────
        Schema::create('documento_itens', function (Blueprint $table) {
            $table->id();
            $table->foreignId('documento_id')->constrained('documentos')->onDelete('cascade');
            $table->string('descricao', 255);
            $table->decimal('quantidade', 10, 3)->default(1);
            $table->decimal('valor_unitario', 12, 2)->default(0);
            $table->decimal('desconto_item', 12, 2)->default(0);
            $table->decimal('total_item', 12, 2)->default(0);
            $table->timestamps();

            $table->index('documento_id');
        });

        // ── Envios do Documento ─────────────────────────────────────────────────────
        Schema::create('documento_envios', function (Blueprint $table) {
            $table->id();
            $table->foreignId('documento_id')->constrained('documentos')->onDelete('cascade');
            $table->enum('tipo', ['EMAIL', 'WHATSAPP']);
            $table->string('destinatario', 255);
            $table->foreignId('user_id')->constrained('users')->onDelete('restrict');
            $table->json('detalhes')->nullable();
            $table->timestamps();

            $table->index('documento_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('documento_envios');
        Schema::dropIfExists('documento_itens');
        Schema::dropIfExists('documentos');
    }
};
