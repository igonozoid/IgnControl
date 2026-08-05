<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('contacts', function (Blueprint $table) {
            $table->string('document_type')->default('individual')
                ->comment('individual (pessoa física) ou company (pessoa jurídica) — explícito, igual ao legado')
                ->after('document');
        });

        // Backfill: contatos existentes não tinham essa distinção
        // explícita, então inferimos pelo tamanho do documento (mesma
        // regra que já era usada só pra decidir o botão de Busca Básica).
        DB::table('contacts')->whereNotNull('document')->orderBy('id')->chunkById(200, function ($rows) {
            foreach ($rows as $row) {
                $digits = preg_replace('/\D/', '', (string) $row->document);
                if (strlen($digits) === 14) {
                    DB::table('contacts')->where('id', $row->id)->update(['document_type' => 'company']);
                }
            }
        });
    }

    public function down(): void
    {
        Schema::table('contacts', function (Blueprint $table) {
            $table->dropColumn('document_type');
        });
    }
};
