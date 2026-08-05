<?php

namespace App\Livewire\Contacts;

use App\Models\Contact;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
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

    public function mount(): void
    {
        abort_unless(Auth::user()->hasModuleAccess('contacts', 'read'), 403);
    }

    public function getCanWriteProperty(): bool
    {
        return Auth::user()->hasModuleAccess('contacts', 'full');
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
