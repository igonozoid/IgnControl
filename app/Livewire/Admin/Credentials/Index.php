<?php

namespace App\Livewire\Admin\Credentials;

use App\Models\AuditLog;
use App\Models\Credential;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Attributes\Validate;
use Livewire\Component;

/**
 * Cofre de credenciais (Configurações > Credenciais no legado). Guarda
 * links de acesso a portais externos com usuário/senha, pra não precisar
 * ficar procurando login em outro lugar. A senha fica criptografada e
 * mascarada por padrão na tela — só aparece depois de um clique explícito
 * em "mostrar", e isso fica registrado na auditoria.
 */
#[Layout('layouts.app')]
class Index extends Component
{
    #[Url]
    public string $search = '';
    #[Url]
    public string $filterCategory = '';

    public bool $showForm = false;
    public ?int $editingId = null;

    #[Validate('required|in:login,bookmark,vault')]
    public string $category = 'login';

    #[Validate('required|string|max:255')]
    public string $title = '';

    #[Validate('nullable|url|max:255')]
    public string $url = '';

    #[Validate('nullable|string|max:255')]
    public string $username = '';

    #[Validate('nullable|string|max:255')]
    public string $password = '';

    #[Validate('nullable|string')]
    public string $notes = '';

    /** Ids revelados na tela (senha em texto claro) nesta sessão de uso da tela. */
    public array $revealed = [];

    public function mount(): void
    {
        abort_unless(Auth::user()->hasModuleAccess('credentials', 'read'), 403);
    }

    public function getCanWriteProperty(): bool
    {
        return Auth::user()->hasModuleAccess('credentials', 'full');
    }

    private function resetForm(): void
    {
        $this->reset(['category', 'title', 'url', 'username', 'password', 'notes', 'editingId']);
        $this->category = 'login';
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

        $credential = Credential::query()->findOrFail($id);

        $this->editingId = $credential->id;
        $this->category = $credential->category;
        $this->title = $credential->title;
        $this->url = (string) $credential->url;
        $this->username = (string) $credential->username;
        $this->password = (string) $credential->password;
        $this->notes = (string) $credential->notes;
        $this->showForm = true;
    }

    public function save(): void
    {
        abort_unless($this->canWrite, 403);

        $data = $this->validate();
        $data['password'] = $data['password'] ?: null;

        if ($this->editingId) {
            Credential::query()->findOrFail($this->editingId)->update($data);
        } else {
            $data['created_by_user_id'] = Auth::id();
            Credential::query()->create($data);
        }

        $this->showForm = false;
        $this->resetForm();
    }

    public function delete(int $id): void
    {
        abort_unless($this->canWrite, 403);
        Credential::query()->findOrFail($id)->delete();
    }

    public function cancel(): void
    {
        $this->showForm = false;
        $this->resetForm();
    }

    /**
     * Mostra a senha em texto claro na tela — fica registrado na
     * auditoria quem viu, quando, e qual credencial (não o valor).
     */
    public function reveal(int $id): void
    {
        $credential = Credential::query()->findOrFail($id);

        AuditLog::query()->create([
            'company_id' => $credential->company_id,
            'user_id' => Auth::id(),
            'action' => 'viewed',
            'auditable_type' => Credential::class,
            'auditable_id' => $credential->id,
            'old_values' => null,
            'new_values' => null,
        ]);

        $this->revealed[] = $id;
    }

    public function hide(int $id): void
    {
        $this->revealed = array_values(array_diff($this->revealed, [$id]));
    }

    public function render()
    {
        $credentials = Credential::query()
            ->when($this->search !== '', fn ($q) => $q->where('title', 'like', "%{$this->search}%"))
            ->when($this->filterCategory !== '', fn ($q) => $q->where('category', $this->filterCategory))
            ->orderBy('title')
            ->get();

        return view('livewire.admin.credentials.index', [
            'credentials' => $credentials,
        ]);
    }
}
