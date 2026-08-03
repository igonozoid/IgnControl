<?php

namespace App\Models;

use App\Traits\Auditable;
use App\Traits\BelongsToCompany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Contact extends Model
{
    use HasFactory, BelongsToCompany, Auditable;

    protected $fillable = [
        'company_id',
        'name',
        'display_name',
        'document',
        'email',
        'phone',
        'mobile_phone',
        'address_line1',
        'address_line2',
        'city',
        'state',
        'postal_code',
        'country',
        'notes',
        'is_supplier',
        'is_customer',
        'is_employee',
        'is_other',
        'other_role_label',
        'is_active',
        'needs_review',
    ];

    protected $casts = [
        'is_supplier' => 'boolean',
        'is_customer' => 'boolean',
        'is_employee' => 'boolean',
        'is_other' => 'boolean',
        'is_active' => 'boolean',
        'needs_review' => 'boolean',
    ];

    public function financialEntries(): HasMany
    {
        return $this->hasMany(FinancialEntry::class);
    }
}
