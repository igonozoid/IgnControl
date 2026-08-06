<?php

namespace App\Models;

use App\Traits\Auditable;
use App\Traits\BelongsToCompany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Auth;

class RuralOccurrence extends Model
{
    use HasFactory, BelongsToCompany, Auditable;

    public const TYPES = [
        'pest' => 'Praga',
        'disease' => 'Doença',
        'spraying' => 'Aplicação',
        'loss' => 'Perda',
        'maintenance' => 'Manutenção',
        'other' => 'Outro',
    ];

    public const SEVERITIES = [
        'low' => 'Baixa',
        'normal' => 'Normal',
        'high' => 'Alta',
        'critical' => 'Crítica',
    ];

    public const STATUSES = [
        'open' => 'Aberta',
        'monitored' => 'Em monitoramento',
        'resolved' => 'Resolvida',
        'cancelled' => 'Cancelada',
    ];

    protected $fillable = [
        'company_id',
        'field_id',
        'asset_id',
        'crop_season_id',
        'occurrence_date',
        'occurrence_type',
        'severity',
        'description',
        'action_taken',
        'status',
        'notes',
        'created_by_user_id',
    ];

    protected $casts = [
        'occurrence_date' => 'date:Y-m-d',
    ];

    protected static function booted(): void
    {
        static::creating(function (RuralOccurrence $occurrence): void {
            $occurrence->created_by_user_id ??= Auth::id();
        });
    }

    public function field(): BelongsTo
    {
        return $this->belongsTo(RuralField::class, 'field_id');
    }

    public function asset(): BelongsTo
    {
        return $this->belongsTo(RuralAsset::class, 'asset_id');
    }

    public function cropSeason(): BelongsTo
    {
        return $this->belongsTo(CropSeason::class, 'crop_season_id');
    }
}
