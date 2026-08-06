<?php

namespace App\Models;

use App\Traits\Auditable;
use App\Traits\BelongsToCompany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Product extends Model
{
    use HasFactory, BelongsToCompany, Auditable;

    public const TYPES = [
        'product' => 'Produto',
        'service' => 'Serviço',
        'input' => 'Insumo',
        'gift' => 'Brinde',
    ];

    protected $fillable = [
        'company_id',
        'sku',
        'barcode',
        'name',
        'short_name',
        'product_type',
        'category_id',
        'unit_code',
        'default_sale_price',
        'default_cost',
        'controls_stock',
        'is_active',
        'notes',
    ];

    protected $casts = [
        'default_sale_price' => 'decimal:2',
        'default_cost' => 'decimal:2',
        'controls_stock' => 'boolean',
        'is_active' => 'boolean',
    ];

    public function category(): BelongsTo
    {
        return $this->belongsTo(ProductCategory::class, 'category_id');
    }

    public function stockMovements(): HasMany
    {
        return $this->hasMany(StockMovement::class);
    }
}
