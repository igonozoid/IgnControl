<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Sublista de pessoas de contato dentro de um contato jurídico —
        // útil quando a empresa-cliente/fornecedora é grande e o contato
        // "físico" muda por área (financeiro, compras, etc). Só faz
        // sentido pra pessoa jurídica, mas não trava isso no banco — a
        // tela é quem decide mostrar ou não a seção.
        Schema::create('contact_department_contacts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('contact_id')->constrained()->cascadeOnDelete();
            $table->string('name')->nullable();
            $table->string('role')->nullable()->comment('Cargo');
            $table->string('extension')->nullable()->comment('Ramal');
            $table->string('email')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('contact_department_contacts');
    }
};
