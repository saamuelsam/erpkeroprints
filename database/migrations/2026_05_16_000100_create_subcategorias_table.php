<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('subcategorias', function (Blueprint $table) {
            $table->id();
            $table->foreignId('categoria_id')->constrained('categorias')->cascadeOnDelete();
            $table->string('nome', 100);
            $table->string('descricao', 255)->nullable();
            $table->boolean('ativo')->default(true);
            $table->timestamps();

            $table->unique(['categoria_id', 'nome']);
            $table->index('ativo');
        });

        Schema::table('produtos', function (Blueprint $table) {
            $table->foreignId('subcategoria_id')
                ->nullable()
                ->after('categoria_id')
                ->constrained('subcategorias')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('produtos', function (Blueprint $table) {
            $table->dropConstrainedForeignId('subcategoria_id');
        });

        Schema::dropIfExists('subcategorias');
    }
};
