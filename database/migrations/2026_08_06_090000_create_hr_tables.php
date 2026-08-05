<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// RH: "funcionário" continua sendo um Contact com is_employee = true (já
// existia esse campo), essas tabelas só guardam os dados específicos de
// RH desse contato. O "empregador" (Marco, Vera etc.) não é um campo
// aqui — cada um deles é uma empresa (company) separada no sistema, e o
// isolamento já vem de graça pelo company_id (BelongsToCompany).
return new class extends Migration
{
    public function up(): void
    {
        // Perfil de RH — 1:1 com o contato. staff_category separa
        // doméstico/rural (controle manual completo, é a realidade de
        // hoje) de escritório (folha com a contabilidade), mas ambos usam
        // as mesmas tabelas de histórico — só pra filtrar/agrupar na tela.
        Schema::create('employee_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('contact_id')->unique()->constrained()->cascadeOnDelete();
            $table->string('job_title')->nullable()->comment('Função, ex.: Jardineiro(a), Auxiliar de Escritório');
            $table->string('staff_category')->default('domestic_rural')->comment('domestic_rural ou office');
            $table->string('employment_type')->nullable()->comment('CLT, PF/informal, Diarista, Estagiário, Consultoria etc.');
            $table->string('union_name')->nullable()->comment('Sindicato');
            $table->string('workplace_location')->nullable()->comment('Local de trabalho');
            $table->string('city')->nullable();
            $table->string('state')->nullable();
            $table->decimal('inss_rate', 6, 4)->nullable()->comment('Alíquota INSS patronal, ex.: 0.08 = 8%');
            $table->decimal('monthly_hours', 8, 2)->nullable();
            $table->string('dissidio_reference')->nullable()->comment('Ex.: Sal. Mín. Federal');
            $table->decimal('initial_salary', 15, 2)->nullable();
            $table->date('admission_date')->nullable();
            $table->date('termination_date')->nullable();
            $table->string('status')->default('active')->comment('active ou terminated');
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        // Evolução salarial — cada linha é um "reajuste"/registro no tempo.
        Schema::create('employee_salary_entries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('contact_id')->constrained()->cascadeOnDelete();
            $table->date('effective_date');
            $table->decimal('nominal_salary', 15, 2)->nullable()->comment('Salário nominal (carteira)');
            $table->decimal('dissidio_percent', 8, 6)->nullable();
            $table->decimal('net_salary', 15, 2)->nullable()->comment('Valor líquido/acerto realmente pago');
            $table->decimal('hourly_value', 15, 4)->nullable();
            $table->decimal('benefits_value', 15, 2)->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        // Férias — período aquisitivo, período de gozo (pode ser parcial,
        // por isso não é um único registro por ano) e o pagamento.
        Schema::create('employee_vacations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('contact_id')->constrained()->cascadeOnDelete();
            $table->date('period_start')->comment('Início do período aquisitivo');
            $table->date('period_end')->comment('Fim do período aquisitivo');
            $table->date('leave_start')->nullable()->comment('Início do gozo');
            $table->date('leave_end')->nullable()->comment('Fim do gozo');
            $table->date('payment_date')->nullable();
            $table->decimal('amount_paid', 15, 2)->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        // 13º salário — um registro por ano.
        Schema::create('employee_thirteenth_salaries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('contact_id')->constrained()->cascadeOnDelete();
            $table->unsignedSmallInteger('year');
            $table->decimal('amount_paid', 15, 2)->nullable();
            $table->date('payment_date')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        // Benefícios recorrentes mensais (Unimed, seguro de vida etc.),
        // entram no total da folha mensal enquanto active = true.
        Schema::create('employee_benefits', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('contact_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->decimal('monthly_value', 15, 2)->default(0);
            $table->boolean('active')->default(true);
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('employee_benefits');
        Schema::dropIfExists('employee_thirteenth_salaries');
        Schema::dropIfExists('employee_vacations');
        Schema::dropIfExists('employee_salary_entries');
        Schema::dropIfExists('employee_profiles');
    }
};
