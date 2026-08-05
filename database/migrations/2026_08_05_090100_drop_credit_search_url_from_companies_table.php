<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Substituído pelo cofre de credenciais (tabela `credentials`): a Busca
 * Avançada, no cadastro de contato, agora lista os links salvos lá em vez
 * de depender de uma única URL fixa aqui.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            $table->dropColumn('credit_search_url');
        });
    }

    public function down(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            $table->string('credit_search_url')->nullable()->after('locked_through');
        });
    }
};
