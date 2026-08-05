<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('cost_centers', function (Blueprint $table) {
            // Por padrão um centro de custo vale pros dois lados, igual
            // ao comportamento que já existia (sem distinção nenhuma).
            $table->boolean('applies_to_expense')->default(true)->after('code');
            $table->boolean('applies_to_revenue')->default(true)->after('applies_to_expense');
            $table->decimal('expense_budget', 15, 2)->nullable()->comment('Orçamento de despesa pro período')->after('applies_to_revenue');
            $table->decimal('revenue_projection', 15, 2)->nullable()->comment('Projeção de receita pro período')->after('expense_budget');
        });
    }

    public function down(): void
    {
        Schema::table('cost_centers', function (Blueprint $table) {
            $table->dropColumn(['applies_to_expense', 'applies_to_revenue', 'expense_budget', 'revenue_projection']);
        });
    }
};
