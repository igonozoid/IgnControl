<?php

namespace App\Models;

use App\Traits\Auditable;
use App\Traits\BelongsToCompany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Auth;

class RuralActivity extends Model
{
    use HasFactory, BelongsToCompany, Auditable;

    public const TYPES = [
        'planting' => 'Plantio',
        'pruning' => 'Poda',
        'spraying' => 'Pulverização',
        'pest_control' => 'Controle de pragas',
        'fertilization' => 'Adubação',
        'irrigation' => 'Irrigação',
        'harvest' => 'Colheita',
        'tech_visit' => 'Visita técnica',
        'other' => 'Outro',
    ];

    public const STATUSES = [
        'planned' => 'Planejada',
        'in_progress' => 'Em andamento',
        'done' => 'Concluída',
        'cancelled' => 'Cancelada',
    ];

    protected $fillable = [
        'company_id',
        'crop_season_id',
        'field_id',
        'asset_id',
        'activity_type',
        'scheduled_date',
        'performed_date',
        'status',
        'responsible_contact_id',
        'notes',
        'created_by_user_id',
    ];

    protected $casts = [
        'scheduled_date' => 'date:Y-m-d',
        'performed_date' => 'date:Y-m-d',
    ];

    protected static function booted(): void
    {
        static::creating(function (RuralActivity $activity): void {
            $activity->created_by_user_id ??= Auth::id();
        });
    }

    public function cropSeason(): BelongsTo
    {
        return $this->belongsTo(CropSeason::class, 'crop_season_id');
    }

    public function field(): BelongsTo
    {
        return $this->belongsTo(RuralField::class, 'field_id');
    }

    public function asset(): BelongsTo
    {
        return $this->belongsTo(RuralAsset::class, 'asset_id');
    }

    public function responsibleContact(): BelongsTo
    {
        return $this->belongsTo(Contact::class, 'responsible_contact_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(RuralActivityItem::class, 'activity_id');
    }
}
