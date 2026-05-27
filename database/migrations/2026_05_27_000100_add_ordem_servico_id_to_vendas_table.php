<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('vendas', function (Blueprint $table) {
            $table->foreignId('ordem_servico_id')
                ->nullable()
                ->after('cliente_id')
                ->constrained('ordens_servico')
                ->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::table('vendas', function (Blueprint $table) {
            $table->dropConstrainedForeignId('ordem_servico_id');
        });
    }
};
