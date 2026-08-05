<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Currency extends Model
{
    use HasFactory;

    protected $primaryKey = 'code';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'code',
        'name',
        'symbol',
        'decimals',
        'is_active',
    ];

    protected $casts = [
        'decimals' => 'integer',
        'is_active' => 'boolean',
    ];

    public function exchangeRates(): HasMany
    {
        return $this->hasMany(ExchangeRate::class, 'currency_code', 'code');
    }
}
