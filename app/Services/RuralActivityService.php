<?php

namespace App\Services;

use App\Models\Product;
use App\Models\RuralActivity;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

/**
 * Atividade rural (poda, pulverização, colheita etc.) com consumo de
 * insumo — mesmo espírito do legado (baixa de estoque quando a atividade
 * é marcada como concluída), mas usando o StockService/ledger que já
 * temos em vez do "apaga e reinsere sem estorno" que o legado fazia.
 *
 * Editar recria os itens e a movimentação de estoque do zero
 * (reverseByReference + repost), igual ao padrão já usado em
 * SalesOrderService::syncStock — só gera consumo de fato quando o
 * status é "done".
 */
class RuralActivityService
{
    public function __construct(private StockService $stock) {}

    public function upsert(?RuralActivity $activity, array $header, array $itemRows): RuralActivity
    {
        return DB::transaction(function () use ($activity, $header, $itemRows) {
            if ($activity) {
                $activity->update($header);
            } else {
                $activity = RuralActivity::query()->create($header);
            }

            $activity->items()->delete();
            $this->stock->reverseByReference('RURAL_ACTIVITY', $activity->id);

            $products = Product::query()->whereIn('id', array_column($itemRows, 'product_id'))->get()->keyBy('id');

            foreach ($itemRows as $row) {
                $product = $products->get($row['product_id']);

                if (! $product) {
                    continue;
                }

                $quantity = (float) $row['quantity'];

                $activity->items()->create([
                    'product_id' => $product->id,
                    'quantity' => $quantity,
                    'unit_code' => $product->unit_code,
                    'notes' => $row['notes'] ?? null,
                ]);

                if ($activity->status === 'done' && $product->controls_stock) {
                    $this->stock->postMovement([
                        'product_id' => $product->id,
                        'movement_type' => 'consumption_out',
                        'movement_date' => $activity->performed_date ?? $activity->scheduled_date ?? now()->toDateString(),
                        'quantity' => $quantity,
                        'unit_cost' => (float) $product->default_cost,
                        'reference_type' => 'RURAL_ACTIVITY',
                        'reference_id' => $activity->id,
                        'created_by_user_id' => Auth::id(),
                    ]);
                }
            }

            return $activity->fresh(['items']);
        });
    }

    /**
     * Cancela a atividade: estorna (apaga) o consumo de insumo que ela
     * tinha lançado — como a atividade nunca "aconteceu" de fato do
     * ponto de vista contábil, não é um estorno com contra-lançamento
     * (diferente do cancelamento de venda, que preserva o rastro de uma
     * venda que existiu de verdade).
     */
    public function cancel(RuralActivity $activity): void
    {
        if ($activity->status === 'cancelled') {
            return;
        }

        DB::transaction(function () use ($activity) {
            $this->stock->reverseByReference('RURAL_ACTIVITY', $activity->id);
            $activity->update(['status' => 'cancelled']);
        });
    }
}
