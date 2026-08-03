<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Company extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'legal_name',
        'tax_id',
        'base_currency_code',
        'is_active',
        'locked_through',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'locked_through' => 'date:Y-m-d',
    ];

    /**
     * Um lançamento com essa data de vencimento está dentro do período
     * fechado (não pode ser criado/editado/excluído)?
     */
    public function isDateLocked(?string $date): bool
    {
        if (! $this->locked_through || ! $date) {
            return false;
        }

        return \Illuminate\Support\Carbon::parse($date)->lte($this->locked_through);
    }

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class)->withPivot('role')->withTimestamps();
    }

    public function permissions(): HasMany
    {
        return $this->hasMany(Permission::class);
    }

    public function financialAccounts(): HasMany
    {
        return $this->hasMany(FinancialAccount::class);
    }

    public function financialEntries(): HasMany
    {
        return $this->hasMany(FinancialEntry::class);
    }

    public function contacts(): HasMany
    {
        return $this->hasMany(Contact::class);
    }

    public function categories(): HasMany
    {
        return $this->hasMany(Category::class);
    }

    public function costCenters(): HasMany
    {
        return $this->hasMany(CostCenter::class);
    }
}
