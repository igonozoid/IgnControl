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
     *
     * Numa transferência entre contas de moedas diferentes, o que sai da
     * origem (amount, + a tarifa da operação) não é o mesmo número que
     * chega no destino (destination_amount) — por isso a saída soma
     * "amount + fee_amount" e a entrada usa "destination_amount" quando
     * preenchido, caindo pra "amount" (comportamento de sempre) quando a
     * transferência é na mesma moeda e esse campo ficou em branco.
     */
    public function currentBalance(): string
    {
        $paidOut = (float) $this->entries()
            ->whereIn('type', ['expense', 'transfer'])
            ->where('status', 'paid')
            ->selectRaw('COALESCE(SUM(amount + COALESCE(fee_amount, 0)), 0) as total')
            ->value('total');

        $paidIn = $this->entries()
            ->where('type', 'income')
            ->where('status', 'paid')
            ->sum('amount');

        $transfersIn = (float) $this->incomingTransfers()
            ->where('status', 'paid')
            ->selectRaw('COALESCE(SUM(COALESCE(destination_amount, amount)), 0) as total')
            ->value('total');

        return bcadd(bcadd($this->opening_balance, $paidIn, 4), bcsub($transfersIn, $paidOut, 4), 4);
    }
}
