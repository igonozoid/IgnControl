<?php

namespace App\Models;

use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Permission extends Model
{
    use HasFactory, Auditable;

    public const MODULES = ['financial', 'contacts', 'reports', 'agenda', 'audit', 'admin'];
    public const LEVELS = ['none', 'read', 'full'];

    protected $fillable = [
        'company_id',
        'user_id',
        'module',
        'level',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
