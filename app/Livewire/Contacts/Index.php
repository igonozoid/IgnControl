<?php

namespace App\Livewire\Contacts;

use App\Models\Contact;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Attributes\Validate;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.app')]
class Index extends Component
{
    use WithPagination;

    // Filtros
    #[Url]
    public string $search = '';
    #[Url]
    public string $filterRole = ''; // '' | supplier | customer | employee | other
    #[Url]
    public bool $onlyNeedsReview = false;

    public bool $showForm = false;
    public ?int $editingId = null;

    #[Validate('required|string|max:255')]
    public string $name = '';

    #[Validate('nullable|string|max:32')]
    public string $document = '';

    #[Validate('nullable|email')]
    public string $email = '';

    #[Validate('nullable|string|max:32')]
    public string $phone = '';

    #[Validate('boolean')]
    public bool $is_supplier = false;

    #[Validate('boolean')]
    public bool $is_customer = false;

    #[Validate('boolean')]
    public bool $is_employee = false;

    #[Validate('boolean')]
    public bool $is_other = false;

    #[Validate('nullable|string')]
    public string $notes = '';

    public function mount(): void
    {
        abort_unless(Auth::user()->hasModuleAccess('contacts', 'read'), 403);
    }

    public function getCanWriteProperty(): bool
    {
        return Auth::user()->hasModuleAccess('contacts', 'full');
    }

    private function resetForm(): void
    {
        $this->reset([
            'name', 'document', 'email', 'phone',
            'is_supplier', 'is_customer', 'is_employee', 'is_other',
            'notes', 'editingId',
        ]);
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

        $contact = Contact::query()->findOrFail($id);

        $this->editingId = $contact->id;
        $this->name = $contact->name;
        $this->document = (string) $contact->document;
        $this->email = (string) $contact->email;
        $this->phone = (string) $contact->phone;
        $this->is_supplier = $contact->is_supplier;
        $this->is_customer = $contact->is_customer;
        $this->is_employee = $contact->is_employee;
        $this->is_other = $contact->is_other;
        $this->notes = (string) $contact->notes;
        $this->showForm = true;
    }

    public function save(): void
    {
        abort_unless($this->canWrite, 403);

        $data = $this->validate();

        if ($this->editingId) {
            // Editar e salvar já conta como "revisado" — não precisa de
            // um passo separado só pra tirar o selo de pendente.
            $data['needs_review'] = false;
            Contact::query()->findOrFail($this->editingId)->update($data);
        } else {
            Contact::query()->create($data);
        }

        $this->showForm = false;
        $this->resetForm();
    }

    public function delete(int $id): void
    {
        abort_unless($this->canWrite, 403);
        Contact::query()->findOrFail($id)->delete();
    }

    public function markReviewed(int $id): void
    {
        abort_unless($this->canWrite, 403);
        Contact::query()->findOrFail($id)->update(['needs_review' => false]);
    }

    public function cancel(): void
    {
        $this->showForm = false;
        $this->resetForm();
    }

    public function render()
    {
        $roleColumns = [
            'supplier' => 'is_supplier',
            'customer' => 'is_customer',
            'employee' => 'is_employee',
            'other' => 'is_other',
        ];

        $contacts = Contact::query()
            ->when($this->search !== '', function ($q) {
                $q->where(function ($q2) {
                    $q2->where('name', 'like', "%{$this->search}%")
                        ->orWhere('document', 'like', "%{$this->search}%")
                        ->orWhere('email', 'like', "%{$this->search}%");
                });
            })
            ->when($this->filterRole !== '' && isset($roleColumns[$this->filterRole]), fn ($q) => $q->where($roleColumns[$this->filterRole], true))
            ->when($this->onlyNeedsReview, fn ($q) => $q->where('needs_review', true))
            ->orderBy('name')
            ->paginate(15);

        return view('livewire.contacts.index', [
            'contacts' => $contacts,
        ]);
    }
}
