<?php

namespace App\Models;

use App\Traits\Auditable;
use App\Traits\BelongsToCompany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EmployeeThirteenthSalary extends Model
{
    use BelongsToCompany, Auditable, HasFactory;

    protected $table = 'employee_thirteenth_salaries';

    protected $fillable = [
        'company_id',
        'contact_id',
        'year',
        'amount_paid',
        'payment_date',
        'notes',
    ];

    protected $casts = [
        'year' => 'integer',
        'amount_paid' => 'decimal:2',
        'payment_date' => 'date:Y-m-d',
    ];

    public function contact(): BelongsTo
    {
        return $this->belongsTo(Contact::class);
    }
}
