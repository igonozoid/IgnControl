<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * "Fechamento de período": lançamentos com vencimento até essa data
     * ficam travados (não dá pra criar/editar/excluir), pra impedir que
     * alguém mexa em algo que já foi conferido/fechado. null = nada
     * travado ainda.
     */
    public function up(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            $table->date('locked_through')->nullable()->after('is_active');
        });
    }

    public function down(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            $table->dropColumn('locked_through');
        });
    }
};
