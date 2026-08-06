<?php

namespace App\Models;

use App\Traits\BelongsToCompany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RuralActivityItem extends Model
{
    // Sem Auditable de propósito — linha filha recriada a cada "Salvar"
    // da atividade, mesmo padrão de SalesOrderItem/CommercialReference:
    // quem é auditado é a atividade em si.
    use HasFactory, BelongsToCompany;

    protected $fillable = [
        'company_id',
        'activity_id',
        'product_id',
        'quantity',
        'unit_code',
        'notes',
    ];

    protected $casts = [
        'quantity' => 'decimal:3',
    ];

    public function activity(): BelongsTo
    {
        return $this->belongsTo(RuralActivity::class, 'activity_id');
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
