<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_tax_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('tax_mode')->default('rate'); // rate | fixed | exempt
            $table->decimal('default_rate_percent', 6, 3)->default(0);
            $table->text('notes')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['company_id', 'name']);
        });

        Schema::table('products', function (Blueprint $table) {
            $table->foreignId('tax_profile_id')->nullable()->after('category_id')->constrained('product_tax_profiles')->nullOnDelete();
            // Categoria financeira sugerida pra quando esse produto é
            // vendido (ex.: "Venda de Produtos") — só uma sugestão pro
            // formulário do pedido pré-preencher, não trava nada.
            $table->foreignId('dre_category_id')->nullable()->after('tax_profile_id')->constrained('categories')->nullOnDelete();
        });

        Schema::create('sales_orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('contact_id')->nullable()->constrained('contacts')->nullOnDelete();
            $table->string('sale_type')->default('sale'); // sale | donation | bonus | return
            $table->string('status')->default('draft'); // draft | confirmed | settled | cancelled
            $table->date('sale_date');
            $table->date('due_date')->nullable();
            $table->string('currency_code', 3);
            $table->foreign('currency_code')->references('code')->on('currencies')->restrictOnDelete();
            $table->decimal('subtotal_amount', 15, 2)->default(0);
            $table->decimal('discount_amount', 15, 2)->default(0);
            $table->decimal('tax_amount', 15, 2)->default(0);
            $table->decimal('total_amount', 15, 2)->default(0);
            // Geração do lançamento financeiro é explícita (conta +
            // categoria escolhidas no formulário) — o legado tentava
            // adivinhar conta/categoria "padrão"; aqui seguimos o
            // padrão do resto do sistema, sempre explícito.
            $table->boolean('generate_financial_entry')->default(true);
            $table->foreignId('financial_account_id')->nullable()->constrained('financial_accounts')->restrictOnDelete();
            $table->foreignId('category_id')->nullable()->constrained('categories')->restrictOnDelete();
            $table->foreignId('cost_center_id')->nullable()->constrained('cost_centers')->restrictOnDelete();
            $table->foreignId('financial_entry_id')->nullable()->constrained('financial_entries')->nullOnDelete();
            $table->text('notes')->nullable();
            $table->foreignId('created_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('sales_order_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('sales_order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained()->restrictOnDelete();
            // Cópia congelada do nome do produto no momento da venda —
            // se o produto for renomeado depois, o pedido antigo não
            // muda de descrição (mesmo comportamento do legado).
            $table->string('description_snapshot');
            $table->decimal('quantity', 15, 3);
            $table->string('unit_code')->default('UN');
            $table->decimal('unit_price', 15, 4)->default(0);
            $table->decimal('discount_amount', 15, 2)->default(0);
            $table->foreignId('tax_profile_id')->nullable()->constrained('product_tax_profiles')->nullOnDelete();
            $table->decimal('tax_rate_percent', 6, 3)->default(0);
            $table->decimal('tax_amount', 15, 2)->default(0);
            $table->decimal('line_total', 15, 2)->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sales_order_items');
        Schema::dropIfExists('sales_orders');
        Schema::table('products', function (Blueprint $table) {
            $table->dropConstrainedForeignId('dre_category_id');
            $table->dropConstrainedForeignId('tax_profile_id');
        });
        Schema::dropIfExists('product_tax_profiles');
    }
};
