<?php

namespace App\Models;

use App\Traits\Auditable;
use App\Traits\BelongsToCompany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RuralAsset extends Model
{
    use HasFactory, BelongsToCompany, Auditable;

    public const TYPES = [
        'general' => 'Geral',
        'machinery' => 'Maquinário',
        'herd' => 'Lote/rebanho',
        'hive' => 'Colmeia',
        'irrigation' => 'Irrigação',
    ];

    protected $fillable = [
        'company_id',
        'field_id',
        'asset_type',
        'name',
        'code',
        'quantity',
        'unit_code',
        'started_at',
        'notes',
        'is_active',
    ];

    protected $casts = [
        'quantity' => 'decimal:3',
        'started_at' => 'date:Y-m-d',
        'is_active' => 'boolean',
    ];

    public function field(): BelongsTo
    {
        return $this->belongsTo(RuralField::class, 'field_id');
    }
}
