<?php

namespace App\Models;

use App\Traits\Auditable;
use App\Traits\BelongsToCompany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Cofre de credenciais: links de acesso a portais externos (SPC, Serasa,
 * bancos etc.), com usuário e senha. A senha é criptografada em repouso
 * (cast 'encrypted' do Laravel, usa APP_KEY) — nunca fica em texto puro
 * no banco. O Auditable grava criação/edição/exclusão automaticamente;
 * "visualizar a senha" é logado à parte, na Livewire (ver reveal()).
 */
class Credential extends Model
{
    use BelongsToCompany, Auditable, HasFactory;

    public const CATEGORIES = ['login', 'bookmark', 'vault'];

    protected $fillable = [
        'company_id',
        'category',
        'title',
        'url',
        'username',
        'password',
        'notes',
        'created_by_user_id',
    ];

    protected $casts = [
        'password' => 'encrypted',
    ];

    protected $hidden = [
        'password',
    ];

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }
}
