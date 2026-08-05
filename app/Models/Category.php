<?php

namespace App\Models;

use App\Traits\Auditable;
use App\Traits\BelongsToCompany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Category extends Model
{
    use HasFactory, BelongsToCompany, Auditable;

    // Seções do DRE gerencial (mesma estrutura usada no sistema legado).
    // Ordem importa: é a ordem de exibição no relatório.
    public const DRE_GROUPS = [
        '01 RECEITA BRUTA' => 'Receita Bruta',
        '02 DEDUCOES DA RECEITA' => 'Deduções da Receita',
        '03 CUSTOS DOS SERVICOS/VENDAS' => 'Custos dos Serviços/Vendas',
        '04 DESPESAS OPERACIONAIS' => 'Despesas Operacionais',
        '05 RESULTADO FINANCEIRO' => 'Resultado Financeiro',
        '06 OUTRAS RECEITAS/DESPESAS' => 'Outras Receitas/Despesas',
    ];

    protected $fillable = [
        'company_id',
        'parent_id',
        'name',
        'type',
        'dre_group',
        'is_active',
        'needs_review',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'needs_review' => 'boolean',
    ];

    public function parent(): BelongsTo
    {
        return $this->belongsTo(Category::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(Category::class, 'parent_id');
    }

    public function financialEntries(): HasMany
    {
        return $this->hasMany(FinancialEntry::class);
    }
}
