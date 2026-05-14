<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('vendas', function (Blueprint $table) {
            $table->foreignId('pix_confirmado_por')->nullable()->after('pix_qr_code_base64')->constrained('users')->nullOnDelete();
            $table->timestamp('pix_confirmado_em')->nullable()->after('pix_confirmado_por');
            $table->string('pix_confirmacao_referencia', 120)->nullable()->after('pix_confirmado_em');
            $table->string('pix_confirmacao_pagador', 150)->nullable()->after('pix_confirmacao_referencia');
            $table->text('pix_confirmacao_observacao')->nullable()->after('pix_confirmacao_pagador');
        });
    }

    public function down(): void
    {
        Schema::table('vendas', function (Blueprint $table) {
            $table->dropConstrainedForeignId('pix_confirmado_por');
            $table->dropColumn([
                'pix_confirmado_em',
                'pix_confirmacao_referencia',
                'pix_confirmacao_pagador',
                'pix_confirmacao_observacao',
            ]);
        });
    }
};
