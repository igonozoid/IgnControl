<?php

namespace App\Livewire\Reports;

use App\Models\RuralActivity;
use App\Models\StockMovement;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;

/**
 * Custo de insumo por talhão e por ativo — mesma lógica do legado
 * (fetch_rural_field_cost_rows/fetch_rural_asset_cost_rows), só que lida
 * direto do ledger de estoque (stock_movements.total_cost) em vez de
 * persistir um valor calculado. reference_type/reference_id é solto
 * (sem FK de verdade), então a ligação com talhão/ativo/safra passa
 * pela atividade que gerou a movimentação.
 */
#[Layout('layouts.app')]
class RuralCosts extends Component
{
    #[Url]
    public string $from = '';

    #[Url]
    public string $to = '';

    public function mount(): void
    {
        abort_unless(Auth::user()->hasModuleAccess('rural', 'read'), 403);

        $this->from = $this->from ?: now()->startOfMonth()->toDateString();
        $this->to = $this->to ?: now()->toDateString();
    }

    public function render()
    {
        // Eloquent grava colunas `date` com hora embutida ("Y-m-d
        // 00:00:00" — o cast `date:Y-m-d` só formata a exibição, não a
        // gravação), o que deixa whereBetween com string pura de data
        // ("2026-08-06") pouco confiável no SQLite. `date(movement_date)`
        // normaliza os dois lados pra "Y-m-d" antes de comparar.
        $movements = StockMovement::query()
            ->where('reference_type', 'RURAL_ACTIVITY')
            ->where('movement_type', 'consumption_out')
            ->whereBetween(DB::raw('date(movement_date)'), [$this->from, $this->to])
            ->with('product')
            ->orderByDesc('movement_date')
            ->get();

        $activities = RuralActivity::query()
            ->with(['field', 'asset', 'cropSeason'])
            ->whereIn('id', $movements->pluck('reference_id')->unique())
            ->get()
            ->keyBy('id');

        $rows = $movements->map(function (StockMovement $movement) use ($activities) {
            $activity = $activities->get($movement->reference_id);

            return [
                'date' => $movement->movement_date,
                'product' => $movement->product?->name ?? '—',
                'quantity' => $movement->quantity,
                'unit_code' => $movement->product?->unit_code ?? '',
                'cost' => (float) $movement->total_cost,
                'field' => $activity?->field?->name,
                'asset' => $activity?->asset?->name,
                'season' => $activity?->cropSeason?->season_label,
                'activity_type' => $activity ? (RuralActivity::TYPES[$activity->activity_type] ?? $activity->activity_type) : null,
            ];
        });

        $byField = $rows
            ->filter(fn ($row) => $row['field'] !== null)
            ->groupBy('field')
            ->map(fn ($group) => $group->sum('cost'))
            ->sortDesc();

        $byAsset = $rows
            ->filter(fn ($row) => $row['asset'] !== null)
            ->groupBy('asset')
            ->map(fn ($group) => $group->sum('cost'))
            ->sortDesc();

        return view('livewire.reports.rural-costs', [
            'rows' => $rows,
            'byField' => $byField,
            'byAsset' => $byAsset,
            'totalCost' => $rows->sum('cost'),
        ]);
    }
}
