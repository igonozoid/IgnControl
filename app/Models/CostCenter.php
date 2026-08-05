<?php

namespace App\Models;

use App\Traits\Auditable;
use App\Traits\BelongsToCompany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CostCenter extends Model
{
    use HasFactory, BelongsToCompany, Auditable;

    protected $fillable = [
        'company_id',
        'name',
        'code',
        'applies_to_expense',
        'applies_to_revenue',
        'expense_budget',
        'revenue_projection',
        'is_active',
        'needs_review',
    ];

    protected $casts = [
        'applies_to_expense' => 'boolean',
        'applies_to_revenue' => 'boolean',
        'expense_budget' => 'decimal:2',
        'revenue_projection' => 'decimal:2',
        'is_active' => 'boolean',
        'needs_review' => 'boolean',
    ];

    public function financialEntries(): HasMany
    {
        return $this->hasMany(FinancialEntry::class);
    }
}
