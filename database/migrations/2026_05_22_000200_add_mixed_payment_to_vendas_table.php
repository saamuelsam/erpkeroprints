<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("ALTER TABLE vendas MODIFY forma_pagamento ENUM('DINHEIRO','PIX','CARTAO_DEBITO','CARTAO_CREDITO','OUTROS','MISTO') NOT NULL");

        Schema::table('vendas', function (Blueprint $table) {
            $table->json('pagamentos')->nullable()->after('forma_pagamento');
        });
    }

    public function down(): void
    {
        DB::table('vendas')->where('forma_pagamento', 'MISTO')->update(['forma_pagamento' => 'OUTROS']);

        Schema::table('vendas', function (Blueprint $table) {
            $table->dropColumn('pagamentos');
        });

        DB::statement("ALTER TABLE vendas MODIFY forma_pagamento ENUM('DINHEIRO','PIX','CARTAO_DEBITO','CARTAO_CREDITO','OUTROS') NOT NULL");
    }
};
