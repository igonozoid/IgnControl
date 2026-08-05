<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Seção do DRE gerencial a que a categoria pertence (Receita Bruta,
// Deduções, Custos, Despesas Operacionais, Resultado Financeiro, Outras).
// Opcional: quando em branco, o relatório infere pela palavra-chave do
// nome da categoria (mesma lógica que já existia no sistema legado).
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('categories', function (Blueprint $table) {
            $table->string('dre_group')->nullable()->after('type');
        });
    }

    public function down(): void
    {
        Schema::table('categories', function (Blueprint $table) {
            $table->dropColumn('dre_group');
        });
    }
};
