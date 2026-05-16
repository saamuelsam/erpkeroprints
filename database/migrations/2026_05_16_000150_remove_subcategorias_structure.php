<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('produtos', 'subcategoria_id')) {
            Schema::table('produtos', function (Blueprint $table) {
                $table->dropConstrainedForeignId('subcategoria_id');
            });
        }

        Schema::dropIfExists('subcategorias');
    }

    public function down(): void
    {
        // Estrutura removida por simplificacao da navegacao de categorias.
    }
};
