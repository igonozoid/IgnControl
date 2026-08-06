<?php

namespace App\Models;

use App\Traits\Auditable;
use App\Traits\BelongsToCompany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class RuralField extends Model
{
    use HasFactory, BelongsToCompany, Auditable;

    public const TYPES = [
        'general' => 'Geral',
        'crop' => 'Lavoura',
        'pasture' => 'Pastagem',
        'orchard' => 'Pomar',
        'apiary' => 'Apiário',
    ];

    protected $fillable = [
        'company_id',
        'property_id',
        'name',
        'display_label',
        'field_type',
        'size_area',
        'size_unit',
        'notes',
        'is_active',
    ];

    protected $casts = [
        'size_area' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    public function property(): BelongsTo
    {
        return $this->belongsTo(RuralProperty::class, 'property_id');
    }

    public function assets(): HasMany
    {
        return $this->hasMany(RuralAsset::class, 'field_id');
    }

    public function cropSeasons(): HasMany
    {
        return $this->hasMany(CropSeason::class, 'field_id');
    }
}
