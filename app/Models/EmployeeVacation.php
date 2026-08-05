<?php

namespace App\Models;

use App\Traits\Auditable;
use App\Traits\BelongsToCompany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EmployeeVacation extends Model
{
    use BelongsToCompany, Auditable, HasFactory;

    protected $fillable = [
        'company_id',
        'contact_id',
        'period_start',
        'period_end',
        'leave_start',
        'leave_end',
        'payment_date',
        'amount_paid',
        'notes',
    ];

    protected $casts = [
        'period_start' => 'date:Y-m-d',
        'period_end' => 'date:Y-m-d',
        'leave_start' => 'date:Y-m-d',
        'leave_end' => 'date:Y-m-d',
        'payment_date' => 'date:Y-m-d',
        'amount_paid' => 'decimal:2',
    ];

    public function contact(): BelongsTo
    {
        return $this->belongsTo(Contact::class);
    }
}
