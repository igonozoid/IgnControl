<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Transferência entre contas de moedas diferentes: o campo "amount"
 * sempre foi o valor debitado da conta de origem; agora uma
 * transferência pode opcionalmente registrar quanto chegou de fato na
 * conta de destino (destination_amount — quando nulo, currentBalance()
 * continua assumindo "chegou o mesmo valor que saiu", igual ao
 * comportamento de sempre), a que taxa (exchange_rate, só informativo,
 * calculado a partir dos dois valores) e quanto custou a operação em
 * tarifa (fee_amount, debitado a mais da origem).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('financial_entries', function (Blueprint $table) {
            $table->decimal('destination_amount', 18, 4)->nullable()->after('amount');
            $table->decimal('exchange_rate', 18, 6)->nullable()->after('destination_amount');
            $table->decimal('fee_amount', 18, 4)->nullable()->after('exchange_rate');
        });
    }

    public function down(): void
    {
        Schema::table('financial_entries', function (Blueprint $table) {
            $table->dropColumn(['destination_amount', 'exchange_rate', 'fee_amount']);
        });
    }
};
