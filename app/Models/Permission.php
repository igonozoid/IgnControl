<?php

namespace App\Models;

use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Permission extends Model
{
    use HasFactory, Auditable;

    public const MODULES = ['financial', 'contacts', 'reports', 'agenda', 'audit', 'admin', 'credentials', 'hr', 'inventory', 'sales', 'rural'];
    public const LEVELS = ['none', 'read', 'full'];

    /**
     * Rótulos em PT-BR só pra exibição (telas de Usuários e Permissões)
     * — os valores gravados no banco continuam em inglês (MODULES/LEVELS
     * acima), pra não precisar de migração nem tocar em nenhuma lógica
     * de autorização que já compara com essas strings.
     */
    public const MODULE_LABELS = [
        'financial' => 'Financeiro',
        'contacts' => 'Contatos',
        'reports' => 'Relatórios',
        'agenda' => 'Agenda',
        'audit' => 'Auditoria',
        'admin' => 'Administração',
        'credentials' => 'Credenciais',
        'hr' => 'RH',
        'inventory' => 'Estoque',
        'sales' => 'Vendas',
        'rural' => 'Rural',
    ];

    public const LEVEL_LABELS = [
        'none' => 'Nenhum',
        'read' => 'Leitura',
        'full' => 'Completo',
    ];

    protected $fillable = [
        'company_id',
        'user_id',
        'module',
        'level',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
