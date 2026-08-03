<?php

namespace App\Models;

use App\Traits\Auditable;
use App\Traits\BelongsToCompany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FinancialEntry extends Model
{
    use HasFactory, BelongsToCompany, Auditable;

    protected $fillable = [
        'company_id',
        'type',
        'financial_account_id',
        'destination_account_id',
        'contact_id',
        'category_id',
        'cost_center_id',
        'currency_code',
        'amount',
        'exchange_rate_id',
        'description',
        'due_date',
        'paid_date',
        'status',
        'created_by',
    ];

    protected $casts = [
        'amount' => 'decimal:4',
        // 'date:Y-m-d' (em vez de 'date') força o Eloquent a gravar e a
        // serializar só a data, sem hora — evita que o SQLite (usado nos
        // testes) grave um timestamp completo que quebraria comparações
        // tipo whereBetween('due_date', [...]) por ordenação de string.
        'due_date' => 'date:Y-m-d',
        'paid_date' => 'date:Y-m-d',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $entry) {
            if (empty($entry->created_by)) {
                $entry->created_by = auth()->id();
            }
        });

        // 'saving' dispara tanto na criação quanto na atualização — é o
        // que garante que EDITAR a data de vencimento ou a moeda de um
        // lançamento já existente também recalcula a taxa histórica, e
        // não só na criação. Numa atualização, só recalcula se uma das
        // duas realmente mudou (senão manteria a taxa já registrada).
        static::saving(function (self $entry) {
            if (empty($entry->currency_code) || empty($entry->due_date)) {
                return;
            }

            if ($entry->exists && ! $entry->isDirty(['due_date', 'currency_code'])) {
                return;
            }

            $rate = ExchangeRate::rateOn($entry->currency_code, (string) $entry->due_date);
            if ($rate) {
                $entry->exchange_rate_id = $rate->id;
            }
        });
    }

    public function financialAccount(): BelongsTo
    {
        return $this->belongsTo(FinancialAccount::class, 'financial_account_id');
    }

    public function destinationAccount(): BelongsTo
    {
        return $this->belongsTo(FinancialAccount::class, 'destination_account_id');
    }

    public function contact(): BelongsTo
    {
        return $this->belongsTo(Contact::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function costCenter(): BelongsTo
    {
        return $this->belongsTo(CostCenter::class);
    }

    public function exchangeRate(): BelongsTo
    {
        return $this->belongsTo(ExchangeRate::class);
    }

    public function currency(): BelongsTo
    {
        return $this->belongsTo(Currency::class, 'currency_code', 'code');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
