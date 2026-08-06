<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('contacts', function (Blueprint $table) {
            $table->string('photo_path')->nullable()->after('document_type');
            $table->date('important_date')->nullable()->comment('Segunda data-lembrete, além do aniversário')->after('birth_date');
            $table->string('important_date_label')->nullable()->comment('Ex.: "Aniversário de fundação"')->after('important_date');
        });
    }

    public function down(): void
    {
        Schema::table('contacts', function (Blueprint $table) {
            $table->dropColumn(['photo_path', 'important_date', 'important_date_label']);
        });
    }
};
