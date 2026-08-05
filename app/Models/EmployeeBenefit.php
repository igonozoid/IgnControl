<?php

namespace App\Models;

use App\Traits\Auditable;
use App\Traits\BelongsToCompany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EmployeeBenefit extends Model
{
    use BelongsToCompany, Auditable, HasFactory;

    protected $fillable = [
        'company_id',
        'contact_id',
        'name',
        'monthly_value',
        'active',
        'notes',
    ];

    protected $casts = [
        'monthly_value' => 'decimal:2',
        'active' => 'boolean',
    ];

    public function contact(): BelongsTo
    {
        return $this->belongsTo(Contact::class);
    }
}
