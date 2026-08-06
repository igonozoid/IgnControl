<?php

namespace App\Services;

use App\Models\CropSeason;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

/**
 * Ciclo de vida da safra — o legado não modelava isso (só tinha
 * atividades avulsas, "Colheita" era só mais um tipo de evento sem
 * produtividade associada). Aqui a safra é o "dono" do ciclo, e marcar
 * como colhida é o que liga Rural ao Estoque: se houver um produto
 * vinculado (harvested_product_id) e produtividade informada, entra uma
 * movimentação harvest_in no ledger — a colheita passa a existir como
 * saldo de estoque, pronta pra Vendas escoar depois.
 */
class CropSeasonService
{
    public function __construct(private StockService $stock) {}

    public function markHarvested(CropSeason $season, array $data): CropSeason
    {
        return DB::transaction(function () use ($season, $data) {
            // Idempotente: se já tinha marcado colhida antes e o usuário
            // está corrigindo a quantidade, desfaz a entrada anterior e
            // relança com o valor novo, em vez de acumular duas linhas.
            $this->stock->reverseByReference('CROP_SEASON', $season->id);

            $season->update([
                'status' => 'harvested',
                'actual_harvest_date' => $data['actual_harvest_date'],
                'actual_yield' => $data['actual_yield'],
            ]);

            if ($season->harvested_product_id && (float) $season->actual_yield > 0) {
                $product = $season->harvestedProduct;

                if ($product && $product->controls_stock) {
                    $this->stock->postMovement([
                        'product_id' => $product->id,
                        'movement_type' => 'harvest_in',
                        'movement_date' => $season->actual_harvest_date,
                        'quantity' => (float) $season->actual_yield,
                        'unit_cost' => (float) $product->default_cost,
                        'reference_type' => 'CROP_SEASON',
                        'reference_id' => $season->id,
                        'created_by_user_id' => Auth::id(),
                    ]);
                }
            }

            return $season->fresh();
        });
    }

    /**
     * Reabre uma safra colhida por engano — estorna a entrada de
     * estoque e volta o status pra "em desenvolvimento".
     */
    public function reopen(CropSeason $season): void
    {
        DB::transaction(function () use ($season) {
            $this->stock->reverseByReference('CROP_SEASON', $season->id);
            $season->update([
                'status' => 'growing',
                'actual_harvest_date' => null,
                'actual_yield' => null,
            ]);
        });
    }
}
