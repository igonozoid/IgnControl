<?php

namespace App\Models;

use App\Traits\Auditable;
use App\Traits\BelongsToCompany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EmployeeProfile extends Model
{
    use BelongsToCompany, Auditable, HasFactory;

    public const STAFF_CATEGORIES = ['domestic_rural', 'office'];

    public const STATUSES = ['active', 'terminated'];

    protected $fillable = [
        'company_id',
        'contact_id',
        'job_title',
        'staff_category',
        'employment_type',
        'union_name',
        'workplace_location',
        'city',
        'state',
        'inss_rate',
        'monthly_hours',
        'dissidio_reference',
        'initial_salary',
        'admission_date',
        'termination_date',
        'status',
        'notes',
    ];

    protected $casts = [
        'inss_rate' => 'decimal:4',
        'monthly_hours' => 'decimal:2',
        'initial_salary' => 'decimal:2',
        'admission_date' => 'date:Y-m-d',
        'termination_date' => 'date:Y-m-d',
    ];

    public function contact(): BelongsTo
    {
        return $this->belongsTo(Contact::class);
    }
}
