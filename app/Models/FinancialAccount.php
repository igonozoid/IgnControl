<?php

namespace App\Models;

use App\Traits\Auditable;
use App\Traits\BelongsToCompany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class FinancialAccount extends Model
{
    use HasFactory, BelongsToCompany, Auditable;

    protected $fillable = [
        'company_id',
        'name',
        'type',
        'currency_code',
        'opening_balance',
        'bank_name',
        'bank_agency',
        'bank_account_number',
        'is_active',
    ];

    protected $casts = [
        'opening_balance' => 'decimal:4',
        'is_active' => 'boolean',
    ];

    public function currency(): BelongsTo
    {
        return $this->belongsTo(Currency::class, 'currency_code', 'code');
    }

    public function entries(): HasMany
    {
        return $this->hasMany(FinancialEntry::class, 'financial_account_id');
    }

    public function incomingTransfers(): HasMany
    {
        return $this->hasMany(FinancialEntry::class, 'destination_account_id');
    }

    /**
     * Saldo atual = saldo inicial + entradas pagas - saídas pagas
     * (transferências contam como saída na origem e entrada no destino).
     */
    public function currentBalance(): string
    {
        $paidOut = $this->entries()
            ->whereIn('type', ['expense', 'transfer'])
            ->where('status', 'paid')
            ->sum('amount');

        $paidIn = $this->entries()
            ->where('type', 'income')
            ->where('status', 'paid')
            ->sum('amount');

        $transfersIn = $this->incomingTransfers()
            ->where('status', 'paid')
            ->sum('amount');

        return bcadd(bcadd($this->opening_balance, $paidIn, 4), bcsub($transfersIn, $paidOut, 4), 4);
    }
}
