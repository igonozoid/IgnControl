<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Company extends Model
{
    use HasFactory;

    /**
     * Módulos/recursos "verticais" — nem toda empresa usa, então ficam
     * liga/desliga por empresa (campo enabled_modules) além da permissão
     * por usuário. hr/inventory/sales/rural também dependem de Permission
     * (as duas coisas precisam bater); cost_centers não é um módulo de
     * Permission — é só um recurso do financeiro que pode ficar oculto.
     * Os demais módulos de Permission::MODULES (financial, contacts,
     * reports, agenda, audit, admin, credentials) são o núcleo do sistema
     * financeiro e ficam sempre disponíveis pra qualquer empresa.
     */
    public const OPTIONAL_MODULES = ['hr', 'inventory', 'sales', 'rural', 'cost_centers'];

    protected $fillable = [
        'name',
        'person_type',
        'legal_name',
        'tax_id',
        'document_secondary',
        'email',
        'phone',
        'website',
        'address_line1',
        'address_line2',
        'district',
        'city',
        'state',
        'postal_code',
        'country',
        'logo_path',
        'base_currency_code',
        'is_active',
        'locked_through',
        'enabled_modules',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'locked_through' => 'date:Y-m-d',
        'enabled_modules' => 'array',
    ];

    /**
     * Esse módulo está disponível pra essa empresa? Módulos do núcleo
     * (fora de OPTIONAL_MODULES) são sempre "sim" — só os verticais
     * dependem do que foi marcado no cadastro da empresa.
     */
    public function hasModuleEnabled(string $module): bool
    {
        if (! in_array($module, self::OPTIONAL_MODULES, true)) {
            return true;
        }

        return in_array($module, $this->enabled_modules ?? [], true);
    }

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
