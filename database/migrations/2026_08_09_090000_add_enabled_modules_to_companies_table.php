<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            // Módulos "verticais" (nem toda empresa usa) que ficam
            // liga/desliga por empresa, independente da permissão de
            // cada usuário. Financeiro, Contatos, Relatórios, Agenda,
            // Administração, Credenciais e Auditoria são o núcleo do
            // sistema e continuam sempre ativos (não entram aqui).
            $table->json('enabled_modules')->nullable()->after('base_currency_code');
        });

        // Empresas que já existem podem já estar usando esses módulos
        // (RH, Estoque, Vendas, Rural) — backfill com tudo ligado pra
        // não tirar acesso de ninguém sem querer. Empresas criadas daqui
        // pra frente nascem com nada marcado; quem cria escolhe.
        DB::table('companies')->update([
            'enabled_modules' => json_encode(['hr', 'inventory', 'sales', 'rural']),
        ]);
    }

    public function down(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            $table->dropColumn('enabled_modules');
        });
    }
};
