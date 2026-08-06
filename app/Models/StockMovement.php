<?php

namespace App\Models;

use App\Traits\Auditable;
use App\Traits\BelongsToCompany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Ledger de estoque — append-only, igual ao legado (nunca existe uma
 * coluna de "saldo atual" em produto ou local; o saldo é sempre somado a
 * partir daqui, ver StockService::available()). Não edita nem apaga linha
 * nenhuma na mão — reversões são novas linhas (StockService::reverseByReference()).
 */
class StockMovement extends Model
{
    use HasFactory, BelongsToCompany, Auditable;

    // Tipos usáveis hoje pela tela de movimentação manual.
    public const MANUAL_TYPES = [
        'manual_in' => 'Entrada manual',
        'adjustment_in' => 'Ajuste de entrada',
        'adjustment_out' => 'Ajuste de saída',
        'loss_out' => 'Perda/quebra',
    ];

    // Tipos que só um módulo futuro (Vendas/Rural) vai gerar sozinho —
    // já existem no schema pra não precisar de migração nova depois.
    public const SYSTEM_TYPES = [
        'purchase_in' => 'Compra',
        'sale_out' => 'Venda',
        'donation_out' => 'Doação/bônus',
        'return_in' => 'Devolução',
        'consumption_out' => 'Consumo',
        'harvest_in' => 'Colheita',
    ];

    public const TRANSFER_TYPES = [
        'transfer_out' => 'Transferência (saída)',
        'transfer_in' => 'Transferência (entrada)',
    ];

    public const INBOUND_TYPES = ['purchase_in', 'manual_in', 'return_in', 'adjustment_in', 'transfer_in', 'harvest_in'];
    public const OUTBOUND_TYPES = ['sale_out', 'donation_out', 'loss_out', 'adjustment_out', 'transfer_out', 'consumption_out'];

    protected $fillable = [
        'company_id',
        'product_id',
        'location_id',
        'movement_type',
        'movement_date',
        'quantity',
        'unit_cost',
        'total_cost',
        'reference_type',
        'reference_id',
        'transfer_group',
        'notes',
        'created_by_user_id',
    ];

    protected $casts = [
        'movement_date' => 'date:Y-m-d',
        'quantity' => 'decimal:3',
        'unit_cost' => 'decimal:4',
        'total_cost' => 'decimal:4',
    ];

    public static function typeLabel(string $type): string
    {
        return self::MANUAL_TYPES[$type]
            ?? self::SYSTEM_TYPES[$type]
            ?? self::TRANSFER_TYPES[$type]
            ?? $type;
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function location(): BelongsTo
    {
        return $this->belongsTo(StockLocation::class, 'location_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }
}
