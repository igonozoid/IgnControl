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
        'birth_date',
        'secondary_document',
        'email',
        'phone',
        'mobile_phone',
        'address_line1',
        'address_line2',
        'district',
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
        'purchase_frequency',
        'classification',
        'credit_limit',
        'credit_checked',
        'credit_check_date',
        'has_credit_issue',
        'credit_issue_location',
        'mother_name',
    ];

    protected $casts = [
        'birth_date' => 'date:Y-m-d',
        'is_supplier' => 'boolean',
        'is_customer' => 'boolean',
        'is_employee' => 'boolean',
        'is_other' => 'boolean',
        'is_active' => 'boolean',
        'needs_review' => 'boolean',
        'credit_limit' => 'decimal:2',
        'credit_checked' => 'boolean',
        'credit_check_date' => 'date:Y-m-d',
        'has_credit_issue' => 'boolean',
    ];

    public function financialEntries(): HasMany
    {
        return $this->hasMany(FinancialEntry::class);
    }

    public function commercialReferences(): HasMany
    {
        return $this->hasMany(CommercialReference::class);
    }

    public function bankReferences(): HasMany
    {
        return $this->hasMany(BankReference::class);
    }

    public function bankAccounts(): HasMany
    {
        return $this->hasMany(ContactBankAccount::class);
    }
}
