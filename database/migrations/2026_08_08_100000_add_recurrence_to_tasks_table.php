<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Recorrência de tarefa, igual ao legado: quando uma tarefa recorrente é
 * concluída, o sistema gera sozinho a próxima ocorrência (não é uma
 * lista de ocorrências futuras pré-geradas). recurrence_weekday e
 * recurrence_day_of_month são só informativos/âncora — não mudam o tipo
 * de cálculo, servem pra mostrar "toda terça" ou "todo dia 10" na tela.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tasks', function (Blueprint $table) {
            $table->string('recurrence_type')->default('none')->after('due_date');
            $table->unsignedTinyInteger('recurrence_weekday')->nullable()->after('recurrence_type');
            $table->unsignedTinyInteger('recurrence_day_of_month')->nullable()->after('recurrence_weekday');
            $table->string('recurrence_note')->nullable()->after('recurrence_day_of_month');
        });
    }

    public function down(): void
    {
        Schema::table('tasks', function (Blueprint $table) {
            $table->dropColumn(['recurrence_type', 'recurrence_weekday', 'recurrence_day_of_month', 'recurrence_note']);
        });
    }
};
