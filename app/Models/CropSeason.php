<?php

namespace App\Models;

use App\Traits\Auditable;
use App\Traits\BelongsToCompany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CropSeason extends Model
{
    use HasFactory, BelongsToCompany, Auditable;

    public const STATUSES = [
        'planned' => 'Planejada',
        'planted' => 'Plantada',
        'growing' => 'Em desenvolvimento',
        'harvested' => 'Colhida',
        'cancelled' => 'Cancelada',
    ];

    protected $fillable = [
        'company_id',
        'field_id',
        'crop_name',
        'variety',
        'season_label',
        'planting_date',
        'expected_harvest_date',
        'actual_harvest_date',
        'planted_area',
        'area_unit',
        'status',
        'expected_yield',
        'actual_yield',
        'yield_unit',
        'harvested_product_id',
        'notes',
    ];

    protected $casts = [
        'planting_date' => 'date:Y-m-d',
        'expected_harvest_date' => 'date:Y-m-d',
        'actual_harvest_date' => 'date:Y-m-d',
        'planted_area' => 'decimal:2',
        'expected_yield' => 'decimal:3',
        'actual_yield' => 'decimal:3',
    ];

    public function field(): BelongsTo
    {
        return $this->belongsTo(RuralField::class, 'field_id');
    }

    public function harvestedProduct(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'harvested_product_id');
    }

    public function activities(): HasMany
    {
        return $this->hasMany(RuralActivity::class, 'crop_season_id');
    }

    public function occurrences(): HasMany
    {
        return $this->hasMany(RuralOccurrence::class, 'crop_season_id');
    }
}
