<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Referências comerciais (contatos de outros fornecedores/clientes
        // que dão referência sobre o contato).
        Schema::create('commercial_references', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('contact_id')->constrained()->cascadeOnDelete();
            $table->string('name')->nullable();
            $table->string('phone')->nullable();
            $table->timestamps();
        });

        // Referências bancárias de terceiros (bancos indicados como
        // referência de crédito, não é a conta do próprio contato).
        Schema::create('bank_references', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('contact_id')->constrained()->cascadeOnDelete();
            $table->string('bank')->nullable();
            $table->string('agency')->nullable();
            $table->string('account')->nullable();
            $table->string('phone')->nullable();
            $table->timestamps();
        });

        // Contas bancárias do próprio contato (pra pagamento/recebimento).
        Schema::create('contact_bank_accounts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('contact_id')->constrained()->cascadeOnDelete();
            $table->string('bank')->nullable();
            $table->string('agency')->nullable();
            $table->string('account')->nullable();
            $table->string('holder')->nullable()->comment('Titular da conta');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('contact_bank_accounts');
        Schema::dropIfExists('bank_references');
        Schema::dropIfExists('commercial_references');
    }
};
