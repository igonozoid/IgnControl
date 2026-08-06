<?php

namespace App\Models;

use App\Traits\Auditable;
use App\Traits\BelongsToCompany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProductTaxProfile extends Model
{
    use HasFactory, BelongsToCompany, Auditable;

    public const MODES = [
        'rate' => 'Percentual',
        'fixed' => 'Valor fixo',
        'exempt' => 'Isento',
    ];

    protected $fillable = [
        'company_id',
        'name',
        'tax_mode',
        'default_rate_percent',
        'notes',
        'is_active',
    ];

    protected $casts = [
        'default_rate_percent' => 'decimal:3',
        'is_active' => 'boolean',
    ];
}
