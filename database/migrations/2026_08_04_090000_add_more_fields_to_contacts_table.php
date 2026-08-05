<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('contacts', function (Blueprint $table) {
            // Aniversário (pessoa física) ou fundação (pessoa jurídica) —
            // nasce genérico o suficiente pros dois casos. Alimenta a
            // Agenda (lembrete anual), por isso vive como data de verdade,
            // não texto solto.
            $table->date('birth_date')->nullable()->after('document');
            // RG (pessoa física) ou Inscrição Estadual (pessoa jurídica).
            $table->string('secondary_document')->nullable()->after('birth_date');
            $table->string('district')->nullable()->comment('Bairro')->after('address_line2');
        });
    }

    public function down(): void
    {
        Schema::table('contacts', function (Blueprint $table) {
            $table->dropColumn(['birth_date', 'secondary_document', 'district']);
        });
    }
};
