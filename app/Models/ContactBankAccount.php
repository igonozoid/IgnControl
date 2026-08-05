<?php

namespace App\Models;

use App\Traits\BelongsToCompany;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ContactBankAccount extends Model
{
    use BelongsToCompany;

    protected $fillable = [
        'company_id',
        'contact_id',
        'bank',
        'agency',
        'account',
        'holder',
    ];

    public function contact(): BelongsTo
    {
        return $this->belongsTo(Contact::class);
    }
}
