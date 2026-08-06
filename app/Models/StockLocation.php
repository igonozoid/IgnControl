<?php

namespace App\Models;

use App\Traits\Auditable;
use App\Traits\BelongsToCompany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class StockLocation extends Model
{
    use HasFactory, BelongsToCompany, Auditable;

    public const TYPES = [
        'warehouse' => 'Depósito',
        'store' => 'Loja',
        'field' => 'Campo',
        'office' => 'Escritório',
        'internal_use' => 'Uso interno',
    ];

    protected $fillable = [
        'company_id',
        'name',
        'location_type',
        'notes',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function movements(): HasMany
    {
        return $this->hasMany(StockMovement::class, 'location_id');
    }
}
