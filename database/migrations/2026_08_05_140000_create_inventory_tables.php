<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_categories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['company_id', 'name']);
        });

        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->string('sku')->nullable();
            $table->string('barcode')->nullable();
            $table->string('name');
            $table->string('short_name')->nullable();
            // 'input' = insumo (consumido por outra operação, ex.: Rural no
            // futuro), 'gift' = brinde. Igual ao legado, mesmo sem Vendas
            // ainda existir aqui — mantém os tipos já usáveis quando entrar.
            $table->string('product_type')->default('product'); // product | service | input | gift
            $table->foreignId('category_id')->nullable()->constrained('product_categories')->nullOnDelete();
            $table->string('unit_code')->default('UN');
            $table->decimal('default_sale_price', 15, 2)->default(0);
            $table->decimal('default_cost', 15, 2)->default(0);
            // Serviço normalmente não controla estoque, mas não travamos
            // isso — o usuário decide por produto (mesmo comportamento do
            // legado: desmarcar simplesmente tira o produto de qualquer
            // validação/saldo de estoque).
            $table->boolean('controls_stock')->default(true);
            $table->boolean('is_active')->default(true);
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique(['company_id', 'name']);
            $table->unique(['company_id', 'sku']);
        });

        Schema::create('stock_locations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('location_type')->default('warehouse'); // warehouse | store | field | office | internal_use
            $table->text('notes')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['company_id', 'name']);
        });

        Schema::create('stock_movements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            // restrict (não cascade) de propósito: apagar um produto não
            // pode apagar o histórico de movimentação dele em silêncio —
            // a tela bloqueia a exclusão nesse caso e orienta a
            // desativar em vez de excluir (mesmo padrão de Moedas).
            $table->foreignId('product_id')->constrained()->restrictOnDelete();
            // Nullable (uma movimentação pode não ter local definido —
            // igual ao legado, ex.: consumo sem local informado) mas
            // restrict on delete: um local só é apagado se não tiver
            // nenhuma movimentação apontando pra ele, mesmo raciocínio
            // do product_id acima.
            $table->foreignId('location_id')->nullable()->constrained('stock_locations')->restrictOnDelete();
            // Ledger append-only — o saldo nunca fica numa coluna, é sempre
            // somado a partir daqui (mesmo desenho do legado). Os tipos
            // além de manual_in/adjustment_*/loss_out/transfer_* já
            // preparam terreno pra quando Vendas/Rural existirem
            // (sale_out, donation_out, return_in, consumption_out,
            // purchase_in) — a tela de hoje só oferece um subconjunto.
            $table->string('movement_type');
            $table->date('movement_date');
            $table->decimal('quantity', 15, 3);
            $table->decimal('unit_cost', 15, 4)->default(0);
            $table->decimal('total_cost', 15, 4)->default(0);
            // Vínculo polimórfico solto (sem FK de verdade, igual ao
            // legado) — quem gerou esse movimento (ex.: futuramente um
            // pedido de venda). 'transfer_group' liga as duas pontas de
            // uma transferência (mais simples que o "UPDATE depois do
            // insert" que o legado fazia).
            $table->string('reference_type')->nullable();
            $table->unsignedBigInteger('reference_id')->nullable();
            $table->uuid('transfer_group')->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('created_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['company_id', 'product_id', 'location_id']);
            $table->index(['reference_type', 'reference_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_movements');
        Schema::dropIfExists('stock_locations');
        Schema::dropIfExists('products');
        Schema::dropIfExists('product_categories');
    }
};
