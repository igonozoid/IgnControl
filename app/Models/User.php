<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable, HasApiTokens;

    protected $fillable = [
        'name',
        'email',
        'password',
        'current_company_id',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function currentCompany(): BelongsTo
    {
        return $this->belongsTo(Company::class, 'current_company_id');
    }

    public function companies(): BelongsToMany
    {
        return $this->belongsToMany(Company::class)->withPivot('role')->withTimestamps();
    }

    public function permissions(): HasMany
    {
        return $this->hasMany(Permission::class);
    }

    /**
     * Nível de acesso do usuário a um módulo, na empresa ativa.
     * Retorna 'none' se não houver registro (ou seja, o padrão é bloquear).
     */
    public function moduleLevel(string $module): string
    {
        if (! $this->current_company_id) {
            return 'none';
        }

        return $this->permissions()
            ->where('company_id', $this->current_company_id)
            ->where('module', $module)
            ->value('level') ?? 'none';
    }

    public function hasModuleAccess(string $module, string $minLevel = 'read'): bool
    {
        // Precisa das duas coisas: a empresa ativa oferecer esse módulo
        // (liga/desliga por empresa, ex.: uma empresa que não faz
        // agropecuária não tem Rural) E o usuário ter permissão pra ele
        // dentro dessa empresa. Uma coisa não substitui a outra.
        if (! $this->currentCompany?->hasModuleEnabled($module)) {
            return false;
        }

        $order = ['none' => 0, 'read' => 1, 'full' => 2];
        $current = $order[$this->moduleLevel($module)] ?? 0;
        $required = $order[$minLevel] ?? 1;

        return $current >= $required;
    }
}
