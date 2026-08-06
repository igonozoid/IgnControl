<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rural_properties', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('city')->nullable();
            $table->string('state')->nullable();
            $table->string('country')->nullable();
            $table->text('notes')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['company_id', 'name']);
        });

        Schema::create('rural_fields', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            // restrict, não cascade: apagar uma propriedade não pode
            // levar junto o histórico de talhão/safra/atividade em
            // silêncio — mesma disciplina do Estoque (produto/local).
            $table->foreignId('property_id')->constrained('rural_properties')->restrictOnDelete();
            $table->string('name');
            // Rótulo configurável ("Talhão", "Quadra", "Piquete"...) —
            // o legado já tratava isso como texto livre por entidade.
            $table->string('display_label')->default('Talhão');
            $table->string('field_type')->default('general'); // general | crop | pasture | orchard | apiary
            $table->decimal('size_area', 10, 2)->nullable();
            $table->string('size_unit')->default('ha');
            $table->text('notes')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['company_id', 'property_id', 'name']);
        });

        Schema::create('rural_assets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            // Ativo é opcionalmente ligado a um talhão (maquinário pode
            // circular entre talhões, colmeia/lote costuma ficar fixo).
            $table->foreignId('field_id')->nullable()->constrained('rural_fields')->nullOnDelete();
            $table->string('asset_type')->default('general'); // machinery | herd | hive | irrigation | general
            $table->string('name');
            $table->string('code')->nullable();
            $table->decimal('quantity', 15, 3)->nullable();
            $table->string('unit_code')->default('UN');
            $table->date('started_at')->nullable();
            $table->text('notes')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('crop_seasons', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            // restrict: uma safra é o "dono" do histórico de atividades e
            // custo do talhão naquele ciclo — não pode sumir junto do
            // talhão em silêncio.
            $table->foreignId('field_id')->constrained('rural_fields')->restrictOnDelete();
            $table->string('crop_name');
            $table->string('variety')->nullable();
            $table->string('season_label'); // ex.: "Safra 2025/2026"
            $table->date('planting_date')->nullable();
            $table->date('expected_harvest_date')->nullable();
            $table->date('actual_harvest_date')->nullable();
            $table->decimal('planted_area', 10, 2)->nullable();
            $table->string('area_unit')->default('ha');
            $table->string('status')->default('planned'); // planned | planted | growing | harvested | cancelled
            $table->decimal('expected_yield', 15, 3)->nullable();
            $table->decimal('actual_yield', 15, 3)->nullable();
            $table->string('yield_unit')->default('kg');
            // Produto que representa essa safra no Estoque (opcional) —
            // ao marcar como colhida, entra uma movimentação harvest_in
            // com a quantidade colhida, ligando Rural ao Estoque/Vendas
            // sem o legado nunca ter feito isso (gap que fechamos aqui).
            $table->foreignId('harvested_product_id')->nullable()->constrained('products')->nullOnDelete();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique(['company_id', 'field_id', 'season_label']);
        });

        Schema::create('rural_activities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('crop_season_id')->nullable()->constrained('crop_seasons')->nullOnDelete();
            $table->foreignId('field_id')->nullable()->constrained('rural_fields')->nullOnDelete();
            $table->foreignId('asset_id')->nullable()->constrained('rural_assets')->nullOnDelete();
            $table->string('activity_type'); // planting | pruning | spraying | pest_control | fertilization | irrigation | harvest | tech_visit | other
            $table->date('scheduled_date')->nullable();
            $table->date('performed_date')->nullable();
            $table->string('status')->default('planned'); // planned | in_progress | done | cancelled
            $table->foreignId('responsible_contact_id')->nullable()->constrained('contacts')->nullOnDelete();
            $table->text('notes')->nullable();
            $table->foreignId('created_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('rural_activity_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('activity_id')->constrained('rural_activities')->cascadeOnDelete();
            // restrict, igual ao padrão de Vendas/Estoque: apagar um
            // produto usado como insumo não pode sumir com o histórico
            // de consumo em atividade.
            $table->foreignId('product_id')->constrained('products')->restrictOnDelete();
            $table->decimal('quantity', 15, 3);
            $table->string('unit_code')->default('UN');
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('rural_occurrences', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('field_id')->nullable()->constrained('rural_fields')->nullOnDelete();
            $table->foreignId('asset_id')->nullable()->constrained('rural_assets')->nullOnDelete();
            $table->foreignId('crop_season_id')->nullable()->constrained('crop_seasons')->nullOnDelete();
            $table->date('occurrence_date');
            $table->string('occurrence_type'); // pest | disease | spraying | loss | maintenance | other
            $table->string('severity')->default('normal'); // low | normal | high | critical
            $table->text('description');
            $table->text('action_taken')->nullable();
            $table->string('status')->default('open'); // open | monitored | resolved | cancelled
            $table->text('notes')->nullable();
            $table->foreignId('created_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rural_occurrences');
        Schema::dropIfExists('rural_activity_items');
        Schema::dropIfExists('rural_activities');
        Schema::dropIfExists('crop_seasons');
        Schema::dropIfExists('rural_assets');
        Schema::dropIfExists('rural_fields');
        Schema::dropIfExists('rural_properties');
    }
};
