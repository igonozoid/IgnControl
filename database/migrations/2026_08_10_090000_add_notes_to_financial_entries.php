<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * O recibo do sistema legado tem "Observação" como campo padrão,
 * separado da descrição do lançamento ("Referente a") — não existia
 * nenhuma coluna livre pra isso em financial_entries, só `description`.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('financial_entries', function (Blueprint $table) {
            $table->text('notes')->nullable()->after('description');
        });
    }

    public function down(): void
    {
        Schema::table('financial_entries', function (Blueprint $table) {
            $table->dropColumn('notes');
        });
    }
};
