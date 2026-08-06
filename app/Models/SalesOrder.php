<?php

namespace App\Models;

use App\Traits\Auditable;
use App\Traits\BelongsToCompany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SalesOrder extends Model
{
    use HasFactory, BelongsToCompany, Auditable;

    public const SALE_TYPES = [
        'sale' => 'Venda',
        'donation' => 'Doação',
        'bonus' => 'Bônus',
        'return' => 'Devolução',
    ];

    public const STATUSES = [
        'draft' => 'Rascunho',
        'confirmed' => 'Confirmado',
        'settled' => 'Liquidado',
        'cancelled' => 'Cancelado',
    ];

    protected $fillable = [
        'company_id',
        'contact_id',
        'sale_type',
        'status',
        'sale_date',
        'due_date',
        'currency_code',
        'subtotal_amount',
        'discount_amount',
        'tax_amount',
        'total_amount',
        'generate_financial_entry',
        'financial_account_id',
        'category_id',
        'cost_center_id',
        'financial_entry_id',
        'notes',
        'created_by_user_id',
    ];

    protected $casts = [
        'sale_date' => 'date:Y-m-d',
        'due_date' => 'date:Y-m-d',
        'subtotal_amount' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'total_amount' => 'decimal:2',
        'generate_financial_entry' => 'boolean',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $order) {
            if (empty($order->created_by_user_id)) {
                $order->created_by_user_id = auth()->id();
            }
        });
    }

    public function contact(): BelongsTo
    {
        return $this->belongsTo(Contact::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(SalesOrderItem::class);
    }

    public function financialAccount(): BelongsTo
    {
        return $this->belongsTo(FinancialAccount::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function costCenter(): BelongsTo
    {
        return $this->belongsTo(CostCenter::class);
    }

    public function financialEntry(): BelongsTo
    {
        return $this->belongsTo(FinancialEntry::class);
    }

    /** Tipo de movimentação de estoque que essa venda gera, por tipo de venda. */
    public function stockMovementType(): string
    {
        return match ($this->sale_type) {
            'return' => 'return_in',
            default => in_array($this->sale_type, ['donation', 'bonus'], true) ? 'donation_out' : 'sale_out',
        };
    }
}
