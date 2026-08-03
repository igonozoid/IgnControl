<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ExchangeRate extends Model
{
    use HasFactory;

    protected $fillable = [
        'currency_code',
        'rate_date',
        'rate_to_base',
    ];

    protected $casts = [
        'rate_date' => 'date',
        'rate_to_base' => 'decimal:8',
    ];

    public function currency(): BelongsTo
    {
        return $this->belongsTo(Currency::class, 'currency_code', 'code');
    }

    /**
     * Busca a taxa vigente numa data (a última taxa conhecida naquele dia
     * ou antes dela) — é assim que o histórico é aplicado a um lançamento.
     */
    public static function rateOn(string $currencyCode, string $date): ?self
    {
        return static::query()
            ->where('currency_code', $currencyCode)
            ->whereDate('rate_date', '<=', $date)
            ->orderByDesc('rate_date')
            ->first();
    }
}
