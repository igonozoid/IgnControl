<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

// O DRE precisa ser calculado por regime de competência (quando o fato
// gerador aconteceu), não pela data de vencimento — hoje o relatório usa
// due_date, o que faz uma despesa de janeiro com vencimento em fevereiro
// cair errada no DRE de fevereiro. movement_date resolve isso.
//
// Fica nullable no banco (como paid_date) pra não exigir alterar coluna
// existente via doctrine/dbal — quem obriga o preenchimento é a
// validação da tela, igual já acontece com due_date.
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('financial_entries', function (Blueprint $table) {
            $table->date('movement_date')->nullable()->after('due_date')->comment('Data de competência (regime de competência), usada no DRE');
            $table->string('document_number')->nullable()->after('description')->comment('Número da nota/documento');
        });

        // Registros já existentes: melhor aproximação disponível é usar a
        // própria data de vencimento como competência.
        DB::table('financial_entries')->whereNull('movement_date')->update([
            'movement_date' => DB::raw('due_date'),
        ]);
    }

    public function down(): void
    {
        Schema::table('financial_entries', function (Blueprint $table) {
            $table->dropColumn(['movement_date', 'document_number']);
        });
    }
};
