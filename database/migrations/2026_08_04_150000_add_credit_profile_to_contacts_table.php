<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('contacts', function (Blueprint $table) {
            $table->string('purchase_frequency')->nullable()->comment('Frequência de compra, texto livre')->after('country');
            $table->string('classification')->nullable()->comment('Classificação do cliente, texto livre')->after('purchase_frequency');
            $table->decimal('credit_limit', 15, 2)->nullable()->after('classification');
            $table->boolean('credit_checked')->default(false)->comment('Crédito consultado')->after('credit_limit');
            $table->date('credit_check_date')->nullable()->comment('Data da consulta de crédito')->after('credit_checked');
            $table->boolean('has_credit_issue')->default(false)->comment('Possui pendência de crédito')->after('credit_check_date');
            $table->string('credit_issue_location')->nullable()->comment('Local da pendência de crédito')->after('has_credit_issue');
            $table->string('mother_name')->nullable()->comment('Nome da mãe, usado em consulta de crédito de pessoa física')->after('credit_issue_location');
        });
    }

    public function down(): void
    {
        Schema::table('contacts', function (Blueprint $table) {
            $table->dropColumn([
                'purchase_frequency',
                'classification',
                'credit_limit',
                'credit_checked',
                'credit_check_date',
                'has_credit_issue',
                'credit_issue_location',
                'mother_name',
            ]);
        });
    }
};
