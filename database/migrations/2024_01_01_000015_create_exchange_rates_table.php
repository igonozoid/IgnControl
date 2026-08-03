<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('exchange_rates', function (Blueprint $table) {
            $table->id();
            $table->string('currency_code', 3);
            $table->foreign('currency_code')->references('code')->on('currencies')->cascadeOnDelete();
            $table->date('rate_date');
            $table->decimal('rate_to_base', 20, 8)
                ->comment('Quantas unidades da moeda-base da empresa equivalem a 1 unidade desta moeda, nesta data');
            $table->timestamps();

            $table->unique(['currency_code', 'rate_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('exchange_rates');
    }
};
