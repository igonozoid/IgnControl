<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Contato/categoria/centro de custo criados "na hora", direto do
     * formulário de lançamento, nascem com needs_review=true — é um
     * lembrete visual pra alguém completar o cadastro depois (endereço,
     * documento, etc.), sem travar quem só quer lançar rápido agora.
     */
    public function up(): void
    {
        Schema::table('contacts', function (Blueprint $table) {
            $table->boolean('needs_review')->default(false)->after('is_active');
        });
        Schema::table('categories', function (Blueprint $table) {
            $table->boolean('needs_review')->default(false)->after('is_active');
        });
        Schema::table('cost_centers', function (Blueprint $table) {
            $table->boolean('needs_review')->default(false)->after('is_active');
        });
    }

    public function down(): void
    {
        Schema::table('contacts', fn (Blueprint $table) => $table->dropColumn('needs_review'));
        Schema::table('categories', fn (Blueprint $table) => $table->dropColumn('needs_review'));
        Schema::table('cost_centers', fn (Blueprint $table) => $table->dropColumn('needs_review'));
    }
};
