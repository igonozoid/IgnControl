<?php

namespace App\Traits;

use App\Models\Company;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Trait aplicado em todo model de negócio (financeiro, contatos, etc.).
 *
 * O que ele faz:
 * 1. Toda consulta a este model já vem automaticamente filtrada pela
 *    "empresa ativa" do usuário logado (global scope) — assim nenhuma tela
 *    ou controller precisa lembrar de filtrar por company_id manualmente.
 * 2. Ao criar um novo registro, se company_id não foi informado, ele é
 *    preenchido sozinho com a empresa ativa.
 *
 * Isso é o que garante o isolamento entre empresas (multi-tenant) descrito
 * em ARQUITETURA.md.
 */
trait BelongsToCompany
{
    protected static function bootBelongsToCompany(): void
    {
        static::addGlobalScope('company', function (Builder $builder) {
            $companyId = static::currentCompanyId();

            if ($companyId !== null) {
                $builder->where($builder->getModel()->getTable().'.company_id', $companyId);
            }
        });

        static::creating(function ($model) {
            if (empty($model->company_id)) {
                $model->company_id = static::currentCompanyId();
            }
        });
    }

    public static function currentCompanyId(): ?int
    {
        $user = auth()->user();

        return $user?->current_company_id;
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }
}
