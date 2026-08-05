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

    #[Validate('nullable|date')]
    public string $birth_date = '';

    #[Validate('nullable|string|max:32')]
    public string $secondary_document = '';

    #[Validate('nullable|email')]
    public string $email = '';

    #[Validate('nullable|string|max:32')]
    public string $phone = '';

    #[Validate('nullable|string|max:255')]
    public string $district = '';

    #[Validate('nullable|string|max:255')]
    public string $city = '';

    #[Validate('nullable|string|max:255')]
    public string $state = '';

    #[Validate('nullable|string|max:16')]
    public string $postal_code = '';

    #[Validate('nullable|string|max:255')]
    public string $country = '';

    // Crédito
    #[Validate('nullable|string|max:255')]
    public string $purchase_frequency = '';

    #[Validate('nullable|string|max:255')]
    public string $classification = '';

    #[Validate('nullable|numeric|min:0')]
    public string $credit_limit = '';

    #[Validate('boolean')]
    public bool $credit_checked = false;

    #[Validate('required_if:credit_checked,true|nullable|date')]
    public string $credit_check_date = '';

    #[Validate('boolean')]
    public bool $has_credit_issue = false;

    #[Validate('required_if:has_credit_issue,true|nullable|string|max:255')]
    public string $credit_issue_location = '';

    #[Validate('nullable|string|max:255')]
    public string $mother_name = '';

    // Referências e dados bancários — listas simples (array de linhas),
    // recriadas por completo a cada "Salvar" (são poucas linhas por
    // contato, não compensa a complexidade de sincronizar id a id).
    public array $commercialReferenceRows = [];
    public array $bankReferenceRows = [];
    public array $contactBankAccountRows = [];

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
            'name', 'document', 'birth_date', 'secondary_document', 'email', 'phone', 'district',
            'city', 'state', 'postal_code', 'country',
            'purchase_frequency', 'classification', 'credit_limit', 'credit_checked',
            'credit_check_date', 'has_credit_issue', 'credit_issue_location', 'mother_name',
            'commercialReferenceRows', 'bankReferenceRows', 'contactBankAccountRows',
            'is_supplier', 'is_customer', 'is_employee', 'is_other',
            'notes', 'editingId',
        ]);
    }

    public function addCommercialReferenceRow(): void
    {
        $this->commercialReferenceRows[] = ['name' => '', 'phone' => ''];
    }

    public function removeCommercialReferenceRow(int $index): void
    {
        unset($this->commercialReferenceRows[$index]);
        $this->commercialReferenceRows = array_values($this->commercialReferenceRows);
    }

    public function addBankReferenceRow(): void
    {
        $this->bankReferenceRows[] = ['bank' => '', 'agency' => '', 'account' => '', 'phone' => ''];
    }

    public function removeBankReferenceRow(int $index): void
    {
        unset($this->bankReferenceRows[$index]);
        $this->bankReferenceRows = array_values($this->bankReferenceRows);
    }

    public function addContactBankAccountRow(): void
    {
        $this->contactBankAccountRows[] = ['bank' => '', 'agency' => '', 'account' => '', 'holder' => ''];
    }

    public function removeContactBankAccountRow(int $index): void
    {
        unset($this->contactBankAccountRows[$index]);
        $this->contactBankAccountRows = array_values($this->contactBankAccountRows);
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

        $contact = Contact::query()->with(['commercialReferences', 'bankReferences', 'bankAccounts'])->findOrFail($id);

        $this->editingId = $contact->id;
        $this->name = $contact->name;
        $this->document = (string) $contact->document;
        $this->birth_date = $contact->birth_date?->toDateString() ?? '';
        $this->secondary_document = (string) $contact->secondary_document;
        $this->email = (string) $contact->email;
        $this->phone = (string) $contact->phone;
        $this->district = (string) $contact->district;
        $this->city = (string) $contact->city;
        $this->state = (string) $contact->state;
        $this->postal_code = (string) $contact->postal_code;
        $this->country = (string) $contact->country;
        $this->purchase_frequency = (string) $contact->purchase_frequency;
        $this->classification = (string) $contact->classification;
        $this->credit_limit = $contact->credit_limit !== null ? (string) $contact->credit_limit : '';
        $this->credit_checked = $contact->credit_checked;
        $this->credit_check_date = $contact->credit_check_date?->toDateString() ?? '';
        $this->has_credit_issue = $contact->has_credit_issue;
        $this->credit_issue_location = (string) $contact->credit_issue_location;
        $this->mother_name = (string) $contact->mother_name;
        $this->commercialReferenceRows = $contact->commercialReferences
            ->map(fn ($row) => ['name' => (string) $row->name, 'phone' => (string) $row->phone])
            ->all();
        $this->bankReferenceRows = $contact->bankReferences
            ->map(fn ($row) => ['bank' => (string) $row->bank, 'agency' => (string) $row->agency, 'account' => (string) $row->account, 'phone' => (string) $row->phone])
            ->all();
        $this->contactBankAccountRows = $contact->bankAccounts
            ->map(fn ($row) => ['bank' => (string) $row->bank, 'agency' => (string) $row->agency, 'account' => (string) $row->account, 'holder' => (string) $row->holder])
            ->all();
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
        $data['birth_date'] = $data['birth_date'] ?: null;
        $data['credit_limit'] = $data['credit_limit'] !== '' ? $data['credit_limit'] : null;
        $data['credit_check_date'] = $data['credit_check_date'] ?: null;

        unset(
            $data['commercialReferenceRows'],
            $data['bankReferenceRows'],
            $data['contactBankAccountRows'],
        );

        if ($this->editingId) {
            // Editar e salvar já conta como "revisado" — não precisa de
            // um passo separado só pra tirar o selo de pendente.
            $data['needs_review'] = false;
            $contact = Contact::query()->findOrFail($this->editingId);
            $contact->update($data);
        } else {
            $contact = Contact::query()->create($data);
        }

        // Referências: recria do zero a cada salvar (lista pequena, não
        // compensa sincronizar linha a linha por id).
        $contact->commercialReferences()->delete();
        foreach ($this->commercialReferenceRows as $row) {
            if (($row['name'] ?? '') === '' && ($row['phone'] ?? '') === '') {
                continue;
            }
            $contact->commercialReferences()->create($row);
        }

        $contact->bankReferences()->delete();
        foreach ($this->bankReferenceRows as $row) {
            if (collect($row)->filter()->isEmpty()) {
                continue;
            }
            $contact->bankReferences()->create($row);
        }

        $contact->bankAccounts()->delete();
        foreach ($this->contactBankAccountRows as $row) {
            if (collect($row)->filter()->isEmpty()) {
                continue;
            }
            $contact->bankAccounts()->create($row);
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
