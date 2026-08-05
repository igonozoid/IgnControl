<?php

namespace App\Models;

use App\Traits\BelongsToCompany;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ContactDepartmentContact extends Model
{
    use BelongsToCompany;

    protected $fillable = [
        'company_id',
        'contact_id',
        'name',
        'role',
        'extension',
        'email',
    ];

    public function contact(): BelongsTo
    {
        return $this->belongsTo(Contact::class);
    }
}
