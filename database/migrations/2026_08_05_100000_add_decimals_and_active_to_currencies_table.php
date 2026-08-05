<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('currencies', function (Blueprint $table) {
            $table->unsignedTinyInteger('decimals')->default(2)->comment('Casas decimais pra exibição/arredondamento')->after('symbol');
            $table->boolean('is_active')->default(true)->after('decimals');
        });
    }

    public function down(): void
    {
        Schema::table('currencies', function (Blueprint $table) {
            $table->dropColumn(['decimals', 'is_active']);
        });
    }
};
