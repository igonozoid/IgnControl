<?php

namespace App\Models;

use App\Traits\BelongsToCompany;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SalesOrderItem extends Model
{
    use BelongsToCompany;

    protected $fillable = [
        'company_id',
        'sales_order_id',
        'product_id',
        'description_snapshot',
        'quantity',
        'unit_code',
        'unit_price',
        'discount_amount',
        'tax_profile_id',
        'tax_rate_percent',
        'tax_amount',
        'line_total',
    ];

    protected $casts = [
        'quantity' => 'decimal:3',
        'unit_price' => 'decimal:4',
        'discount_amount' => 'decimal:2',
        'tax_rate_percent' => 'decimal:3',
        'tax_amount' => 'decimal:2',
        'line_total' => 'decimal:2',
    ];

    public function salesOrder(): BelongsTo
    {
        return $this->belongsTo(SalesOrder::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function taxProfile(): BelongsTo
    {
        return $this->belongsTo(ProductTaxProfile::class, 'tax_profile_id');
    }
}
