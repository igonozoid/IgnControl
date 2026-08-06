<?php

namespace App\Services;

use App\Models\FinancialEntry;
use App\Models\Product;
use App\Models\SalesOrder;
use App\Models\StockMovement;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

/**
 * Única porta de entrada pra criar/editar/cancelar um pedido de venda —
 * quem chama (a tela) nunca mexe direto em stock_movements nem em
 * financial_entries. Mesmo espírito do StockService: uma classe só que
 * sabe a regra de negócio inteira.
 *
 * Fiel ao legado em três pontos: (1) editar um pedido recria os itens E
 * as movimentações de estoque do zero (delete+insert, não faz diff);
 * (2) só "sale_type=sale" gera lançamento financeiro — doação/bônus/
 * devolução nunca geram; (3) cancelar preserva o histórico de estoque
 * através de movimentos de estorno, nunca apaga o que já aconteceu.
 *
 * Deliberadamente diferente do legado em um ponto: o legado, ao cancelar
 * uma venda já recebida, criava uma despesa de estorno pra não mexer no
 * lançamento original. Aqui só marcamos o lançamento vinculado como
 * "canceled" (status que os relatórios já sabem ignorar) — mais simples
 * e usa um conceito que já existe no resto do sistema, em vez de inventar
 * um lançamento espelho.
 */
class SalesOrderService
{
    public function __construct(private StockService $stock)
    {
    }

    /**
     * @param  array<int, array{product_id:int, quantity:string|float, unit_price:string|float, discount_amount?:string|float, tax_rate_percent?:string|float, tax_profile_id?:?int}>  $itemRows
     */
    public function upsert(?SalesOrder $order, array $header, array $itemRows): SalesOrder
    {
        return DB::transaction(function () use ($order, $header, $itemRows) {
            $products = Product::query()->whereIn('id', array_column($itemRows, 'product_id'))->get()->keyBy('id');

            $subtotal = 0;
            $discount = 0;
            $tax = 0;
            $lines = [];

            foreach ($itemRows as $row) {
                $product = $products->get($row['product_id']);
                if (! $product) {
                    continue;
                }

                $quantity = (float) $row['quantity'];
                $unitPrice = (float) $row['unit_price'];
                $lineDiscount = (float) ($row['discount_amount'] ?? 0);
                $taxRate = (float) ($row['tax_rate_percent'] ?? 0);
                $lineSubtotal = $quantity * $unitPrice;
                $lineTax = round(($lineSubtotal - $lineDiscount) * $taxRate / 100, 2);
                $lineTotal = $lineSubtotal - $lineDiscount + $lineTax;

                $subtotal += $lineSubtotal;
                $discount += $lineDiscount;
                $tax += $lineTax;

                $lines[] = [
                    'product' => $product,
                    'quantity' => $quantity,
                    'unit_code' => $product->unit_code,
                    'unit_price' => $unitPrice,
                    'discount_amount' => $lineDiscount,
                    'tax_profile_id' => $row['tax_profile_id'] ?? null,
                    'tax_rate_percent' => $taxRate,
                    'tax_amount' => $lineTax,
                    'line_total' => $lineTotal,
                ];
            }

            $header['subtotal_amount'] = $subtotal;
            $header['discount_amount'] = $discount;
            $header['tax_amount'] = $tax;
            $header['total_amount'] = $subtotal - $discount + $tax;

            if ($order) {
                $order->update($header);
            } else {
                $order = SalesOrder::query()->create($header);
            }

            $order->items()->delete();
            foreach ($lines as $line) {
                $order->items()->create([
                    'product_id' => $line['product']->id,
                    'description_snapshot' => $line['product']->name,
                    'quantity' => $line['quantity'],
                    'unit_code' => $line['unit_code'],
                    'unit_price' => $line['unit_price'],
                    'discount_amount' => $line['discount_amount'],
                    'tax_profile_id' => $line['tax_profile_id'],
                    'tax_rate_percent' => $line['tax_rate_percent'],
                    'tax_amount' => $line['tax_amount'],
                    'line_total' => $line['line_total'],
                ]);
            }

            $this->syncStock($order, $lines);
            $this->syncFinancialEntry($order);

            return $order->fresh(['items', 'financialEntry']);
        });
    }

    /**
     * Cancela o pedido: marca como cancelado, gera movimentos de
     * estorno de estoque (preserva o histórico, não apaga nada) e marca
     * o lançamento financeiro vinculado como cancelado, se existir.
     */
    public function cancel(SalesOrder $order): void
    {
        if ($order->status === 'cancelled') {
            return;
        }

        DB::transaction(function () use ($order) {
            $alreadyReversed = StockMovement::query()
                ->where('reference_type', 'SALE_ORDER_CANCEL')
                ->where('reference_id', $order->id)
                ->exists();

            if (! $alreadyReversed) {
                $movements = StockMovement::query()
                    ->where('reference_type', 'SALE_ORDER')
                    ->where('reference_id', $order->id)
                    ->get();

                foreach ($movements as $movement) {
                    $reverseType = $movement->movement_type === 'return_in' ? 'adjustment_out' : 'return_in';

                    $this->stock->postMovement([
                        'product_id' => $movement->product_id,
                        'location_id' => $movement->location_id,
                        'movement_type' => $reverseType,
                        'movement_date' => now()->toDateString(),
                        'quantity' => $movement->quantity,
                        'unit_cost' => $movement->unit_cost,
                        'reference_type' => 'SALE_ORDER_CANCEL',
                        'reference_id' => $order->id,
                        'notes' => "Estorno do pedido de venda #{$order->id}",
                        'created_by_user_id' => Auth::id(),
                    ]);
                }
            }

            $order->update(['status' => 'cancelled']);

            if ($order->financial_entry_id) {
                $order->financialEntry?->update(['status' => 'canceled']);
            }
        });
    }

    /**
     * Apaga tudo que esse pedido já tinha lançado no estoque e, se o
     * status atual gera movimentação (confirmado/liquidado, e o tipo de
     * venda não é isento disso), lança de novo — um movimento por
     * produto distinto (soma das linhas), igual ao legado.
     */
    private function syncStock(SalesOrder $order, array $lines): void
    {
        $this->stock->reverseByReference('SALE_ORDER', $order->id);

        if (! in_array($order->status, ['confirmed', 'settled'], true)) {
            return;
        }

        $byProduct = collect($lines)
            ->filter(fn ($line) => $line['product']->controls_stock)
            ->groupBy(fn ($line) => $line['product']->id);

        foreach ($byProduct as $productLines) {
            $product = $productLines->first()['product'];
            $quantity = $productLines->sum('quantity');
            $unitCost = (float) $product->default_cost;

            $this->stock->postMovement([
                'product_id' => $product->id,
                'movement_type' => $order->stockMovementType(),
                'movement_date' => $order->sale_date,
                'quantity' => $quantity,
                'unit_cost' => $unitCost,
                'reference_type' => 'SALE_ORDER',
                'reference_id' => $order->id,
                'created_by_user_id' => Auth::id(),
            ]);
        }
    }

    /**
     * Cria/atualiza/apaga o lançamento de receita vinculado — só existe
     * quando: "gerar lançamento" está marcado, o tipo é venda de
     * verdade (não doação/bônus/devolução), o status já é
     * confirmado/liquidado, e o total é maior que zero.
     */
    private function syncFinancialEntry(SalesOrder $order): void
    {
        $shouldGenerate = $order->generate_financial_entry
            && $order->sale_type === 'sale'
            && in_array($order->status, ['confirmed', 'settled'], true)
            && (float) $order->total_amount > 0
            && $order->financial_account_id;

        if (! $shouldGenerate) {
            if ($order->financial_entry_id) {
                $entryId = $order->financial_entry_id;
                $order->update(['financial_entry_id' => null]);
                FinancialEntry::query()->find($entryId)?->delete();
            }

            return;
        }

        $isSettled = $order->status === 'settled';

        $data = [
            'company_id' => $order->company_id,
            'type' => 'income',
            'financial_account_id' => $order->financial_account_id,
            'contact_id' => $order->contact_id,
            'category_id' => $order->category_id,
            'cost_center_id' => $order->cost_center_id,
            'currency_code' => $order->currency_code,
            'amount' => $order->total_amount,
            'description' => "Venda #{$order->id}",
            'document_number' => "VENDA-{$order->id}",
            'due_date' => $order->due_date ?: $order->sale_date,
            'movement_date' => $order->sale_date,
            'status' => $isSettled ? 'paid' : 'pending',
            'paid_date' => $isSettled ? $order->sale_date : null,
        ];

        if ($order->financial_entry_id && ($entry = FinancialEntry::query()->find($order->financial_entry_id))) {
            $entry->update($data);
        } else {
            $entry = FinancialEntry::query()->create($data);
            $order->update(['financial_entry_id' => $entry->id]);
        }
    }
}
