<?php

namespace App\Livewire\RuralActivities;

use App\Models\Contact;
use App\Models\CropSeason;
use App\Models\Product;
use App\Models\RuralActivity;
use App\Models\RuralAsset;
use App\Models\RuralField;
use App\Models\RuralOccurrence;
use App\Services\RuralActivityService;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Attributes\Validate;
use Livewire\Component;

#[Layout('layouts.app')]
class Index extends Component
{
    #[Url]
    public string $filterFieldActivities = '';

    #[Url]
    public string $filterStatusActivities = '';

    // Atividade
    public bool $showForm = false;
    public ?int $editingId = null;

    #[Validate('nullable|integer|exists:crop_seasons,id')]
    public string $crop_season_id = '';

    #[Validate('nullable|integer|exists:rural_fields,id')]
    public string $field_id = '';

    #[Validate('nullable|integer|exists:rural_assets,id')]
    public string $asset_id = '';

    #[Validate('required|in:planting,pruning,spraying,pest_control,fertilization,irrigation,harvest,tech_visit,other')]
    public string $activity_type = 'tech_visit';

    #[Validate('nullable|date')]
    public string $scheduled_date = '';

    #[Validate('nullable|date')]
    public string $performed_date = '';

    #[Validate('required|in:planned,in_progress,done')]
    public string $status = 'planned';

    #[Validate('nullable|integer|exists:contacts,id')]
    public string $responsible_contact_id = '';

    #[Validate('nullable|string')]
    public string $notes = '';

    public array $itemRows = [];

    // Ocorrência
    public bool $showOccurrenceForm = false;
    public ?int $editingOccurrenceId = null;

    #[Validate('nullable|integer|exists:rural_fields,id')]
    public string $occ_field_id = '';

    #[Validate('nullable|integer|exists:rural_assets,id')]
    public string $occ_asset_id = '';

    #[Validate('nullable|integer|exists:crop_seasons,id')]
    public string $occ_crop_season_id = '';

    #[Validate('required|date')]
    public string $occurrence_date = '';

    #[Validate('required|in:pest,disease,spraying,loss,maintenance,other')]
    public string $occurrence_type = 'pest';

    #[Validate('required|in:low,normal,high,critical')]
    public string $severity = 'normal';

    #[Validate('required|string')]
    public string $description = '';

    #[Validate('nullable|string')]
    public string $action_taken = '';

    #[Validate('required|in:open,monitored,resolved,cancelled')]
    public string $occ_status = 'open';

    #[Validate('nullable|string')]
    public string $occ_notes = '';

    public function mount(): void
    {
        abort_unless(Auth::user()->hasModuleAccess('rural', 'read'), 403);
    }

    public function getCanWriteProperty(): bool
    {
        return Auth::user()->hasModuleAccess('rural', 'full');
    }

    // --- Atividade ---

    public function create(): void
    {
        abort_unless($this->canWrite, 403);
        $this->resetForm();
        $this->showForm = true;
    }

    public function edit(int $id): void
    {
        abort_unless($this->canWrite, 403);

        $activity = RuralActivity::query()->with('items')->findOrFail($id);

        abort_if($activity->status === 'cancelled', 403);

        $this->editingId = $activity->id;
        $this->crop_season_id = (string) $activity->crop_season_id;
        $this->field_id = (string) $activity->field_id;
        $this->asset_id = (string) $activity->asset_id;
        $this->activity_type = $activity->activity_type;
        $this->scheduled_date = $activity->scheduled_date?->toDateString() ?? '';
        $this->performed_date = $activity->performed_date?->toDateString() ?? '';
        $this->status = $activity->status;
        $this->responsible_contact_id = (string) $activity->responsible_contact_id;
        $this->notes = (string) $activity->notes;
        $this->itemRows = $activity->items->map(fn ($item) => [
            'product_id' => (string) $item->product_id,
            'quantity' => (string) $item->quantity,
            'notes' => (string) $item->notes,
        ])->all();
        $this->showForm = true;
    }

    public function addItemRow(): void
    {
        $this->itemRows[] = ['product_id' => '', 'quantity' => '1', 'notes' => ''];
    }

    public function removeItemRow(int $index): void
    {
        unset($this->itemRows[$index]);
        $this->itemRows = array_values($this->itemRows);
    }

    public function save(): void
    {
        abort_unless($this->canWrite, 403);

        $data = $this->validate([
            'crop_season_id' => 'nullable|integer|exists:crop_seasons,id',
            'field_id' => 'nullable|integer|exists:rural_fields,id',
            'asset_id' => 'nullable|integer|exists:rural_assets,id',
            'activity_type' => 'required|in:planting,pruning,spraying,pest_control,fertilization,irrigation,harvest,tech_visit,other',
            'scheduled_date' => 'nullable|date',
            'performed_date' => 'nullable|date',
            'status' => 'required|in:planned,in_progress,done',
            'responsible_contact_id' => 'nullable|integer|exists:contacts,id',
            'notes' => 'nullable|string',
        ]);

        $data['crop_season_id'] = $data['crop_season_id'] ?: null;
        $data['field_id'] = $data['field_id'] ?: null;
        $data['asset_id'] = $data['asset_id'] ?: null;
        $data['scheduled_date'] = $data['scheduled_date'] ?: null;
        $data['performed_date'] = $data['performed_date'] ?: null;
        $data['responsible_contact_id'] = $data['responsible_contact_id'] ?: null;
        $data['notes'] = $data['notes'] ?: null;

        $activity = $this->editingId ? RuralActivity::query()->findOrFail($this->editingId) : null;

        app(RuralActivityService::class)->upsert($activity, $data, $this->itemRows);

        $this->showForm = false;
        $this->resetForm();
    }

    public function cancelActivity(int $id): void
    {
        abort_unless($this->canWrite, 403);

        $activity = RuralActivity::query()->findOrFail($id);

        app(RuralActivityService::class)->cancel($activity);
    }

    public function delete(int $id): void
    {
        abort_unless($this->canWrite, 403);

        $activity = RuralActivity::query()->findOrFail($id);

        // Só planejada pode ser excluída de fato — concluída já tem
        // consumo de estoque, em andamento/cancelada seguem o mesmo
        // caminho de "cancelar" pra manter rastro.
        abort_unless($activity->status === 'planned', 403);

        $activity->delete();
    }

    public function cancel(): void
    {
        $this->showForm = false;
        $this->resetForm();
    }

    private function resetForm(): void
    {
        $this->reset(['crop_season_id', 'field_id', 'asset_id', 'scheduled_date', 'performed_date', 'responsible_contact_id', 'notes', 'editingId', 'itemRows']);
        $this->activity_type = 'tech_visit';
        $this->status = 'planned';
    }

    // --- Ocorrência ---

    public function createOccurrence(): void
    {
        abort_unless($this->canWrite, 403);
        $this->resetOccurrenceForm();
        $this->showOccurrenceForm = true;
    }

    public function editOccurrence(int $id): void
    {
        abort_unless($this->canWrite, 403);

        $occurrence = RuralOccurrence::query()->findOrFail($id);

        $this->editingOccurrenceId = $occurrence->id;
        $this->occ_field_id = (string) $occurrence->field_id;
        $this->occ_asset_id = (string) $occurrence->asset_id;
        $this->occ_crop_season_id = (string) $occurrence->crop_season_id;
        $this->occurrence_date = $occurrence->occurrence_date->toDateString();
        $this->occurrence_type = $occurrence->occurrence_type;
        $this->severity = $occurrence->severity;
        $this->description = $occurrence->description;
        $this->action_taken = (string) $occurrence->action_taken;
        $this->occ_status = $occurrence->status;
        $this->occ_notes = (string) $occurrence->notes;
        $this->showOccurrenceForm = true;
    }

    public function saveOccurrence(): void
    {
        abort_unless($this->canWrite, 403);

        $data = $this->validate([
            'occ_field_id' => 'nullable|integer|exists:rural_fields,id',
            'occ_asset_id' => 'nullable|integer|exists:rural_assets,id',
            'occ_crop_season_id' => 'nullable|integer|exists:crop_seasons,id',
            'occurrence_date' => 'required|date',
            'occurrence_type' => 'required|in:pest,disease,spraying,loss,maintenance,other',
            'severity' => 'required|in:low,normal,high,critical',
            'description' => 'required|string',
            'action_taken' => 'nullable|string',
            'occ_status' => 'required|in:open,monitored,resolved,cancelled',
            'occ_notes' => 'nullable|string',
        ]);

        $payload = [
            'field_id' => $data['occ_field_id'] ?: null,
            'asset_id' => $data['occ_asset_id'] ?: null,
            'crop_season_id' => $data['occ_crop_season_id'] ?: null,
            'occurrence_date' => $data['occurrence_date'],
            'occurrence_type' => $data['occurrence_type'],
            'severity' => $data['severity'],
            'description' => $data['description'],
            'action_taken' => $data['action_taken'] ?: null,
            'status' => $data['occ_status'],
            'notes' => $data['occ_notes'] ?: null,
        ];

        if ($this->editingOccurrenceId) {
            RuralOccurrence::query()->findOrFail($this->editingOccurrenceId)->update($payload);
        } else {
            RuralOccurrence::query()->create($payload);
        }

        $this->showOccurrenceForm = false;
        $this->resetOccurrenceForm();
    }

    public function deleteOccurrence(int $id): void
    {
        abort_unless($this->canWrite, 403);

        RuralOccurrence::query()->findOrFail($id)->delete();
    }

    public function cancelOccurrenceForm(): void
    {
        $this->showOccurrenceForm = false;
        $this->resetOccurrenceForm();
    }

    private function resetOccurrenceForm(): void
    {
        $this->reset(['occ_field_id', 'occ_asset_id', 'occ_crop_season_id', 'action_taken', 'occ_notes', 'editingOccurrenceId']);
        $this->occurrence_date = now()->toDateString();
        $this->occurrence_type = 'pest';
        $this->severity = 'normal';
        $this->description = '';
        $this->occ_status = 'open';
    }

    public function render()
    {
        $activities = RuralActivity::query()
            ->with(['field', 'cropSeason', 'items'])
            ->when($this->filterFieldActivities !== '', fn ($q) => $q->where('field_id', $this->filterFieldActivities))
            ->when($this->filterStatusActivities !== '', fn ($q) => $q->where('status', $this->filterStatusActivities))
            ->orderByDesc('scheduled_date')
            ->orderByDesc('id')
            ->paginate(10, pageName: 'activities-page');

        $occurrences = RuralOccurrence::query()
            ->with('field')
            ->orderByDesc('occurrence_date')
            ->orderByDesc('id')
            ->paginate(10, pageName: 'occurrences-page');

        return view('livewire.rural-activities.index', [
            'activities' => $activities,
            'occurrences' => $occurrences,
            'fields' => RuralField::query()->where('is_active', true)->orderBy('name')->get(),
            'assets' => RuralAsset::query()->where('is_active', true)->orderBy('name')->get(),
            'seasons' => CropSeason::query()->whereNotIn('status', ['cancelled'])->orderByDesc('id')->get(),
            'products' => Product::query()->where('is_active', true)->orderBy('name')->get(),
            'contacts' => Contact::query()->orderBy('name')->get(),
        ]);
    }
}
