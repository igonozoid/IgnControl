<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            $table->string('credit_search_url')->nullable()->comment('Link do portal de consulta de crédito (SPC/Serasa etc.), aberto em nova aba na "Busca Avançada" do cadastro de contato')->after('locked_through');
        });
    }

    public function down(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            $table->dropColumn('credit_search_url');
        });
    }
};
