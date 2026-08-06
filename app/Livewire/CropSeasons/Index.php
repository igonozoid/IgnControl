<?php

namespace App\Livewire\CropSeasons;

use App\Models\CropSeason;
use App\Models\Product;
use App\Models\RuralField;
use App\Services\CropSeasonService;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Attributes\Validate;
use Livewire\Component;

#[Layout('layouts.app')]
class Index extends Component
{
    #[Url]
    public string $filterField = '';

    #[Url]
    public string $filterStatus = '';

    public bool $showForm = false;
    public ?int $editingId = null;

    #[Validate('required|integer|exists:rural_fields,id')]
    public string $field_id = '';

    #[Validate('required|string|max:255')]
    public string $crop_name = '';

    #[Validate('nullable|string|max:255')]
    public string $variety = '';

    #[Validate('required|string|max:255')]
    public string $season_label = '';

    #[Validate('nullable|date')]
    public string $planting_date = '';

    #[Validate('nullable|date')]
    public string $expected_harvest_date = '';

    #[Validate('nullable|numeric|min:0')]
    public string $planted_area = '';

    #[Validate('required|string|max:20')]
    public string $area_unit = 'ha';

    #[Validate('required|in:planned,planted,growing,cancelled')]
    public string $status = 'planned';

    #[Validate('nullable|numeric|min:0')]
    public string $expected_yield = '';

    #[Validate('required|string|max:20')]
    public string $yield_unit = 'kg';

    #[Validate('nullable|integer|exists:products,id')]
    public string $harvested_product_id = '';

    #[Validate('nullable|string')]
    public string $notes = '';

    // Modal dedicado de "marcar como colhida" — separado do formulário
    // principal pra não deixar status="harvested" ser digitado à mão
    // sem produtividade real associada.
    public bool $showHarvestForm = false;
    public ?int $harvestingId = null;

    #[Validate('required|date')]
    public string $actual_harvest_date = '';

    #[Validate('required|numeric|min:0.001')]
    public string $actual_yield = '';

    public function mount(): void
    {
        abort_unless(Auth::user()->hasModuleAccess('rural', 'read'), 403);
    }

    public function getCanWriteProperty(): bool
    {
        return Auth::user()->hasModuleAccess('rural', 'full');
    }

    public function create(): void
    {
        abort_unless($this->canWrite, 403);
        $this->resetForm();
        $this->showForm = true;
    }

    public function edit(int $id): void
    {
        abort_unless($this->canWrite, 403);

        $season = CropSeason::query()->findOrFail($id);

        $this->editingId = $season->id;
        $this->field_id = (string) $season->field_id;
        $this->crop_name = $season->crop_name;
        $this->variety = (string) $season->variety;
        $this->season_label = $season->season_label;
        $this->planting_date = $season->planting_date?->toDateString() ?? '';
        $this->expected_harvest_date = $season->expected_harvest_date?->toDateString() ?? '';
        $this->planted_area = (string) $season->planted_area;
        $this->area_unit = $season->area_unit;
        $this->status = in_array($season->status, ['planned', 'planted', 'growing', 'cancelled'], true) ? $season->status : 'growing';
        $this->expected_yield = (string) $season->expected_yield;
        $this->yield_unit = $season->yield_unit;
        $this->harvested_product_id = (string) $season->harvested_product_id;
        $this->notes = (string) $season->notes;
        $this->showForm = true;
    }

    public function save(): void
    {
        abort_unless($this->canWrite, 403);

        // Rules explícitas (não $this->validate() sem argumento): a
        // classe também tem #[Validate] nos campos do modal de colheita
        // (actual_harvest_date/actual_yield), que ficam vazios até o
        // usuário abrir aquele modal — validar tudo junto quebraria o
        // salvamento normal da safra.
        $data = $this->validate([
            'field_id' => 'required|integer|exists:rural_fields,id',
            'crop_name' => 'required|string|max:255',
            'variety' => 'nullable|string|max:255',
            'season_label' => 'required|string|max:255',
            'planting_date' => 'nullable|date',
            'expected_harvest_date' => 'nullable|date',
            'planted_area' => 'nullable|numeric|min:0',
            'area_unit' => 'required|string|max:20',
            'status' => 'required|in:planned,planted,growing,cancelled',
            'expected_yield' => 'nullable|numeric|min:0',
            'yield_unit' => 'required|string|max:20',
            'harvested_product_id' => 'nullable|integer|exists:products,id',
            'notes' => 'nullable|string',
        ]);
        $data['variety'] = $data['variety'] ?: null;
        $data['planting_date'] = $data['planting_date'] ?: null;
        $data['expected_harvest_date'] = $data['expected_harvest_date'] ?: null;
        $data['planted_area'] = $data['planted_area'] !== '' ? $data['planted_area'] : null;
        $data['expected_yield'] = $data['expected_yield'] !== '' ? $data['expected_yield'] : null;
        $data['harvested_product_id'] = $data['harvested_product_id'] ?: null;
        $data['notes'] = $data['notes'] ?: null;

        if ($this->editingId) {
            CropSeason::query()->findOrFail($this->editingId)->update($data);
        } else {
            CropSeason::query()->create($data);
        }

        $this->showForm = false;
        $this->resetForm();
    }

    public function delete(int $id): void
    {
        abort_unless($this->canWrite, 403);

        $season = CropSeason::query()->findOrFail($id);

        // Colhida tem entrada de estoque vinculada — não sai de forma
        // silenciosa. Só planejada/cancelada pode ser excluída de fato.
        abort_unless(in_array($season->status, ['planned', 'cancelled'], true), 403);

        $season->delete();
    }

    public function cancel(): void
    {
        $this->showForm = false;
        $this->resetForm();
    }

    public function openHarvestForm(int $id): void
    {
        abort_unless($this->canWrite, 403);

        $season = CropSeason::query()->findOrFail($id);

        $this->harvestingId = $season->id;
        $this->actual_harvest_date = $season->actual_harvest_date?->toDateString() ?? now()->toDateString();
        $this->actual_yield = (string) ($season->actual_yield ?? $season->expected_yield ?? '');
        $this->showHarvestForm = true;
    }

    public function markHarvested(): void
    {
        abort_unless($this->canWrite, 403);

        $data = $this->validate([
            'actual_harvest_date' => 'required|date',
            'actual_yield' => 'required|numeric|min:0.001',
        ]);

        $season = CropSeason::query()->findOrFail($this->harvestingId);

        app(CropSeasonService::class)->markHarvested($season, $data);

        $this->showHarvestForm = false;
        $this->reset(['harvestingId', 'actual_harvest_date', 'actual_yield']);
    }

    public function reopenSeason(int $id): void
    {
        abort_unless($this->canWrite, 403);

        $season = CropSeason::query()->findOrFail($id);

        app(CropSeasonService::class)->reopen($season);
    }

    public function closeHarvestForm(): void
    {
        $this->showHarvestForm = false;
        $this->reset(['harvestingId', 'actual_harvest_date', 'actual_yield']);
    }

    private function resetForm(): void
    {
        $this->reset(['field_id', 'variety', 'planting_date', 'expected_harvest_date', 'planted_area', 'expected_yield', 'harvested_product_id', 'notes', 'editingId']);
        $this->crop_name = '';
        $this->season_label = '';
        $this->area_unit = 'ha';
        $this->status = 'planned';
        $this->yield_unit = 'kg';
    }

    public function render()
    {
        $seasons = CropSeason::query()
            ->with('field.property')
            ->when($this->filterField !== '', fn ($q) => $q->where('field_id', $this->filterField))
            ->when($this->filterStatus !== '', fn ($q) => $q->where('status', $this->filterStatus))
            ->orderByDesc('id')
            ->paginate(15);

        return view('livewire.crop-seasons.index', [
            'seasons' => $seasons,
            'fields' => RuralField::query()->where('is_active', true)->orderBy('name')->get(),
            'products' => Product::query()->where('is_active', true)->orderBy('name')->get(),
        ]);
    }
}
