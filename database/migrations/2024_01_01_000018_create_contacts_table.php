<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('contacts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();

            // Ficha completa
            $table->string('name');
            $table->string('display_name')->nullable()->comment('Apelido/nome fantasia, se diferente do nome legal');
            $table->string('document')->nullable()->comment('CPF/CNPJ ou equivalente estrangeiro');
            $table->string('email')->nullable();
            $table->string('phone')->nullable();
            $table->string('mobile_phone')->nullable();
            $table->string('address_line1')->nullable();
            $table->string('address_line2')->nullable();
            $table->string('city')->nullable();
            $table->string('state')->nullable();
            $table->string('postal_code')->nullable();
            $table->string('country')->nullable();
            $table->text('notes')->nullable();

            // Papéis (um contato pode ser mais de um papel ao mesmo tempo)
            $table->boolean('is_supplier')->default(false);
            $table->boolean('is_customer')->default(false);
            $table->boolean('is_employee')->default(false);
            $table->boolean('is_other')->default(false);
            $table->string('other_role_label')->nullable();

            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('contacts');
    }
};
