<?php

namespace App\Traits;

use App\Models\AuditLog;

/**
 * Trait aplicado em models cuja escrita é "sensível" e precisa ficar
 * registrada (lançamentos financeiros, contatos, permissões, etc.).
 *
 * Não exige nada do desenvolvedor além de usar o trait: toda criação,
 * atualização e exclusão gera automaticamente uma linha em audit_logs,
 * com os valores antes/depois.
 */
trait Auditable
{
    protected static function bootAuditable(): void
    {
        static::created(function ($model) {
            $model->writeAuditLog('created', null, $model->getAttributes());
        });

        static::updated(function ($model) {
            $model->writeAuditLog('updated', $model->getOriginal(), $model->getChanges());
        });

        static::deleted(function ($model) {
            $model->writeAuditLog('deleted', $model->getAttributes(), null);
        });
    }

    protected function writeAuditLog(string $action, ?array $old, ?array $new): void
    {
        AuditLog::query()->create([
            'company_id' => $this->company_id ?? null,
            'user_id' => auth()->id(),
            'action' => $action,
            'auditable_type' => static::class,
            'auditable_id' => $this->getKey(),
            'old_values' => $old,
            'new_values' => $new,
        ]);
    }
}
