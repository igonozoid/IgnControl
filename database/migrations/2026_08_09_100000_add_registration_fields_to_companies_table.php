<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            // Campos que existiam no cadastro de entidade do sistema
            // legado (control_entities) e nunca migraram pra cá.
            $table->string('person_type', 2)->default('PJ')->after('name');
            $table->string('document_secondary', 32)->nullable()->after('tax_id')->comment('Inscrição Estadual ou equivalente');
            $table->string('email')->nullable()->after('document_secondary');
            $table->string('phone', 32)->nullable()->after('email');
            $table->string('website')->nullable()->after('phone');
            $table->string('address_line1')->nullable()->after('website');
            $table->string('address_line2')->nullable()->after('address_line1');
            $table->string('district')->nullable()->after('address_line2');
            $table->string('city')->nullable()->after('district');
            $table->string('state', 2)->nullable()->after('city');
            $table->string('postal_code', 16)->nullable()->after('state');
            $table->string('country')->nullable()->after('postal_code');
            $table->string('logo_path')->nullable()->after('country');
        });
    }

    public function down(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            $table->dropColumn([
                'person_type',
                'document_secondary',
                'email',
                'phone',
                'website',
                'address_line1',
                'address_line2',
                'district',
                'city',
                'state',
                'postal_code',
                'country',
                'logo_path',
            ]);
        });
    }
};
