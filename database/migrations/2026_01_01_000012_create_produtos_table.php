<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('produtos', function (Blueprint $table) {
            $table->id();
            $table->string('codigo_interno', 50)->nullable()->unique();
            $table->string('codigo_barras', 50)->nullable()->unique();
            $table->string('nome', 150);
            $table->foreignId('categoria_id')->constrained('categorias')->onDelete('restrict');
            $table->decimal('quantidade_estoque', 10, 3)->default(0);
            $table->decimal('custo_unitario', 12, 2)->default(0);
            $table->decimal('preco_venda', 12, 2)->default(0);
            $table->decimal('estoque_minimo', 10, 3)->default(0);
            $table->string('unidade_medida', 10)->default('UN')
                ->comment('UN=Unidade, KG=Quilograma, M=Metro, M2=Metro², L=Litro, CX=Caixa, PCT=Pacote');
            $table->boolean('ativo')->default(true);
            $table->text('observacoes')->nullable();
            $table->timestamps();
            $table->softDeletes();

            // Índices para busca rápida no PDV e listagens
            $table->index('nome');
            $table->index('codigo_barras');
            $table->index('codigo_interno');
            $table->index('categoria_id');
            $table->index('ativo');
            $table->index(['quantidade_estoque', 'estoque_minimo']); // Para alerta de estoque baixo
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('produtos');
    }
};
