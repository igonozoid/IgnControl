<?php

namespace App\Models;

use App\Traits\Auditable;
use App\Traits\BelongsToCompany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EmployeeSalaryEntry extends Model
{
    use BelongsToCompany, Auditable, HasFactory;

    protected $fillable = [
        'company_id',
        'contact_id',
        'effective_date',
        'nominal_salary',
        'dissidio_percent',
        'net_salary',
        'hourly_value',
        'benefits_value',
        'notes',
    ];

    protected $casts = [
        'effective_date' => 'date:Y-m-d',
        'nominal_salary' => 'decimal:2',
        'dissidio_percent' => 'decimal:6',
        'net_salary' => 'decimal:2',
        'hourly_value' => 'decimal:4',
        'benefits_value' => 'decimal:2',
    ];

    public function contact(): BelongsTo
    {
        return $this->belongsTo(Contact::class);
    }
}
