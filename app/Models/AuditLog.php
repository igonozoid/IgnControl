<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

// Credential, Contact, Permission, Task, CostCenter, Category, FinancialEntry
// e FinancialAccount são referenciados sem "use" abaixo (auditableModels(),
// modelLabel()) por estarem no mesmo namespace App\Models.

class AuditLog extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $fillable = [
        'company_id',
        'user_id',
        'action',
        'auditable_type',
        'auditable_id',
        'old_values',
        'new_values',
    ];

    protected $casts = [
        'old_values' => 'array',
        'new_values' => 'array',
        'created_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $log) {
            $log->created_at = $log->created_at ?? now();
        });
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** Tipos de model que atualmente gravam auditoria (trait Auditable + os manuais do cofre de credenciais). */
    public static function auditableModels(): array
    {
        return [
            Credential::class,
            Contact::class,
            Permission::class,
            Task::class,
            CostCenter::class,
            Category::class,
            FinancialEntry::class,
            FinancialAccount::class,
        ];
    }

    public static function actionLabel(string $action): string
    {
        return match ($action) {
            'created' => 'Criado',
            'updated' => 'Atualizado',
            'deleted' => 'Excluído',
            'viewed' => 'Visualizado',
            'copied' => 'Copiado',
            default => ucfirst($action),
        };
    }

    public static function modelLabel(?string $class): string
    {
        return match ($class) {
            Credential::class => 'Credencial',
            Contact::class => 'Contato',
            Permission::class => 'Permissão',
            Task::class => 'Tarefa',
            CostCenter::class => 'Centro de custo',
            Category::class => 'Categoria',
            FinancialEntry::class => 'Lançamento',
            FinancialAccount::class => 'Conta',
            default => $class ? class_basename($class) : '—',
        };
    }
}
