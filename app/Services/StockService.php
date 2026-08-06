<?php

namespace App\Services;

use App\Exceptions\InsufficientStockException;
use App\Models\Product;
use App\Models\StockMovement;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

/**
 * Camada única pra tudo que mexe em saldo de estoque — desenhada de
 * propósito pra ser o único lugar que sabe a "matemática" de estoque
 * (mesmo espírito do legado: ledger append-only, saldo sempre somado na
 * hora, nunca uma coluna de "quantidade atual").
 *
 * O contrato aqui (postMovement/available/reverseByReference, com
 * reference_type+reference_id polimórfico) é pensado pra um módulo
 * futuro (Vendas, Rural) plugar sem precisar mexer nesta classe: quem
 * gera a movimentação só chama postMovement() com o reference dele, e
 * reverseByReference() desfaz tudo que aquele reference já lançou —
 * mesmo padrão que o legado usava pra editar/cancelar uma venda.
 */
class StockService
{
    /**
     * Saldo disponível de um produto — soma todas as entradas menos
     * todas as saídas do ledger. Sem $locationId, soma todos os locais
     * (saldo consolidado da empresa). exclude* existe pra recalcular "o
     * saldo sem contar esta própria movimentação/pedido" antes de
     * revalidar uma edição — mesmo uso do legado.
     */
    public function available(
        int $productId,
        ?int $locationId = null,
        ?string $excludeReferenceType = null,
        ?int $excludeReferenceId = null,
    ): float {
        $query = StockMovement::query()->where('product_id', $productId);

        if ($locationId !== null) {
            $query->where('location_id', $locationId);
        }

        if ($excludeReferenceType !== null && $excludeReferenceId !== null) {
            // Exclui só as linhas que batem EXATAMENTE com esse
            // reference — tudo o mais (outro reference, ou nenhum)
            // continua contando pro saldo.
            $query->where(fn ($q) => $q->where('reference_type', '!=', $excludeReferenceType)
                ->orWhere('reference_id', '!=', $excludeReferenceId)
                ->orWhereNull('reference_type')
                ->orWhereNull('reference_id'));
        }

        $inbound = (clone $query)->whereIn('movement_type', StockMovement::INBOUND_TYPES)->sum('quantity');
        $outbound = (clone $query)->whereIn('movement_type', StockMovement::OUTBOUND_TYPES)->sum('quantity');

        return (float) $inbound - (float) $outbound;
    }

    /**
     * Registra uma movimentação. Se for de saída, valida saldo primeiro
     * e lança InsufficientStockException se não tiver o suficiente —
     * nunca deixa o saldo ficar negativo.
     */
    public function postMovement(array $data): StockMovement
    {
        $quantity = (float) ($data['quantity'] ?? 0);

        if (in_array($data['movement_type'], StockMovement::OUTBOUND_TYPES, true)) {
            $this->assertAvailable(
                (int) $data['product_id'],
                $data['location_id'] ?? null,
                $quantity,
                $data['reference_type'] ?? null,
                $data['reference_id'] ?? null,
            );
        }

        $data['total_cost'] = $quantity * (float) ($data['unit_cost'] ?? 0);

        return StockMovement::query()->create($data);
    }

    /**
     * Transferência entre dois locais do mesmo produto — duas linhas no
     * ledger (uma TRANSFER_OUT na origem, uma TRANSFER_IN no destino),
     * ligadas por um transfer_group compartilhado (mais simples que o
     * "UPDATE depois do insert" que o legado fazia pra cruzar as duas
     * pontas).
     */
    public function transfer(array $data): array
    {
        $quantity = (float) ($data['quantity'] ?? 0);

        $this->assertAvailable((int) $data['product_id'], $data['from_location_id'], $quantity);

        $group = (string) Str::uuid();

        $out = StockMovement::query()->create([
            'product_id' => $data['product_id'],
            'location_id' => $data['from_location_id'],
            'movement_type' => 'transfer_out',
            'movement_date' => $data['movement_date'],
            'quantity' => $quantity,
            'unit_cost' => $data['unit_cost'] ?? 0,
            'total_cost' => $quantity * (float) ($data['unit_cost'] ?? 0),
            'transfer_group' => $group,
            'notes' => $data['notes'] ?? null,
            'created_by_user_id' => $data['created_by_user_id'] ?? null,
        ]);

        $in = StockMovement::query()->create([
            'product_id' => $data['product_id'],
            'location_id' => $data['to_location_id'],
            'movement_type' => 'transfer_in',
            'movement_date' => $data['movement_date'],
            'quantity' => $quantity,
            'unit_cost' => $data['unit_cost'] ?? 0,
            'total_cost' => $quantity * (float) ($data['unit_cost'] ?? 0),
            'transfer_group' => $group,
            'notes' => $data['notes'] ?? null,
            'created_by_user_id' => $data['created_by_user_id'] ?? null,
        ]);

        return [$out, $in];
    }

    /**
     * Apaga toda movimentação que um reference (ex.: um pedido de venda
     * futuro) já tinha gerado — pra recriar do zero numa edição, ou pra
     * estornar num cancelamento (quem chama decide se insere movimentos
     * de estorno depois, esse método só limpa).
     */
    public function reverseByReference(string $referenceType, int $referenceId): void
    {
        StockMovement::query()
            ->where('reference_type', $referenceType)
            ->where('reference_id', $referenceId)
            ->delete();
    }

    /**
     * Saldo por produto (consolidado, todos os locais) — usado pela
     * tela de movimentações pra mostrar "disponível" ao lado de cada
     * produto controlado.
     */
    public function balanceByProduct(): Collection
    {
        return Product::query()
            ->where('controls_stock', true)
            ->where('is_active', true)
            ->orderBy('name')
            ->get()
            ->map(fn (Product $product) => [
                'product' => $product,
                'available' => $this->available($product->id),
            ]);
    }

    private function assertAvailable(
        int $productId,
        ?int $locationId,
        float $quantity,
        ?string $excludeReferenceType = null,
        ?int $excludeReferenceId = null,
    ): void {
        $available = $this->available($productId, $locationId, $excludeReferenceType, $excludeReferenceId);

        if ($quantity > $available) {
            $product = Product::query()->find($productId);

            throw new InsufficientStockException(sprintf(
                'Estoque insuficiente para %s. Disponível: %s; solicitado: %s.',
                $product?->name ?? "produto #{$productId}",
                number_format($available, 3, ',', '.'),
                number_format($quantity, 3, ',', '.'),
            ));
        }
    }
}
