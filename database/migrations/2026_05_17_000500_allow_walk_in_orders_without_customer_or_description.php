<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement('ALTER TABLE ordens_servico MODIFY cliente_id BIGINT UNSIGNED NULL');
        DB::statement('ALTER TABLE ordens_servico MODIFY descricao_servico TEXT NULL');
    }

    public function down(): void
    {
        DB::statement("UPDATE ordens_servico SET descricao_servico = '' WHERE descricao_servico IS NULL");
        DB::statement('ALTER TABLE ordens_servico MODIFY descricao_servico TEXT NOT NULL');
    }
};
