<?php

namespace App\Livewire\Contacts;

use App\Models\Contact;
use App\Models\Credential;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Validate;
use Livewire\Component;

/**
 * Cadastro/edição de contato — página cheia com abas (Dados básicos,
 * Crédito, Referências, Observações), no lugar do slide-over de 460px
 * que existia antes. Contato tem gente demais pra caber espremido num
 * painel lateral (mesmo padrão adotado na ficha de RH). Continua sendo
 * UM formulário só — as abas só escondem/mostram seções, o "Salvar"
 * grava tudo de uma vez, não importa em qual aba a pessoa está.
 */
#[Layout('layouts.app')]
class Form extends Component
{
    public ?Contact $contact = null;

    public string $tab = 'basic'; // basic | credit | references | notes

    #[Validate('required|string|max:255')]
    public string $name = '';

    #[Validate('nullable|string|max:32')]
    public string $document = '';

    #[Validate('required|in:individual,company')]
    public string $document_type = 'individual';

    /**
     * Enquanto o tipo de pessoa não foi escolhido manualmente, ele segue
     * a mesma inferência automática que a tela sempre usou (14 dígitos =
     * CNPJ). Assim que o usuário mexe no rádio (ou ao carregar um
     * contato existente, que já tem o tipo salvo), a inferência para de
     * sobrescrever — vira uma escolha explícita, igual ao legado. Público
     * (não só uma variável local) pra sobreviver entre os round-trips do
     * Livewire.
     */
    public bool $documentTypeManuallySet = false;

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
    public string $address_line1 = '';

    #[Validate('nullable|string|max:255')]
    public string $address_line2 = '';

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

    // Busca Básica (CNPJ): guarda o resultado da última consulta pra
    // anexar o PDF só no momento do "Salvar" — se o contato ainda não
    // existe (cadastro novo), não tem em quem anexar antes disso.
    public bool $searchingCnpj = false;
    public ?array $lastCnpjLookupPayload = null;
    public string $lastCnpjLookupDocument = '';

    /** Documentos já anexados ao contato em edição (só leitura na tela). */
    public array $existingDocuments = [];

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

    public function mount(?Contact $contact = null): void
    {
        abort_unless(Auth::user()->hasModuleAccess('contacts', 'read'), 403);

        if ($contact && $contact->exists) {
            abort_unless($this->canWrite, 403);

            $contact->load(['commercialReferences', 'bankReferences', 'bankAccounts', 'documents']);
            $this->contact = $contact;

            $this->name = $contact->name;
            $this->document = (string) $contact->document;
            $this->document_type = $contact->document_type ?: 'individual';
            $this->documentTypeManuallySet = true;
            $this->birth_date = $contact->birth_date?->toDateString() ?? '';
            $this->secondary_document = (string) $contact->secondary_document;
            $this->email = (string) $contact->email;
            $this->phone = (string) $contact->phone;
            $this->district = (string) $contact->district;
            $this->address_line1 = (string) $contact->address_line1;
            $this->address_line2 = (string) $contact->address_line2;
            $this->city = (string) $contact->city;
            $this->state = (string) $contact->state;
            $this->postal_code = (string) $contact->postal_code;
            $this->country = (string) $contact->country;
            $this->purchase_frequency = (string) $contact->purchase_frequency;
            $this->classification = (string) $contact->classification;
            $this->credit_limit = $contact->credit_limit !== null ? (string) $contact->credit_limit : '';
            $this->credit_checked = (bool) $contact->credit_checked;
            $this->credit_check_date = $contact->credit_check_date?->toDateString() ?? '';
            $this->has_credit_issue = (bool) $contact->has_credit_issue;
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
            $this->existingDocuments = $contact->documents
                ->map(fn ($doc) => ['id' => $doc->id, 'original_name' => $doc->original_name, 'category' => $doc->category, 'created_at' => $doc->created_at?->format('d/m/Y H:i')])
                ->all();
            $this->is_supplier = (bool) $contact->is_supplier;
            $this->is_customer = (bool) $contact->is_customer;
            $this->is_employee = (bool) $contact->is_employee;
            $this->is_other = (bool) $contact->is_other;
            $this->notes = (string) $contact->notes;
        } else {
            abort_unless($this->canWrite, 403);
        }
    }

    public function getCanWriteProperty(): bool
    {
        return Auth::user()->hasModuleAccess('contacts', 'full');
    }

    /**
     * Tipo de pessoa é Jurídica? É o que decide se o botão "Busca
     * Básica" aparece — CPF não tem fonte pública gratuita, então não
     * faz sentido oferecer o botão nesse caso.
     */
    public function getIsCnpjDocumentProperty(): bool
    {
        return $this->document_type === 'company';
    }

    /**
     * Enquanto o usuário não escolheu o tipo de pessoa manualmente pelo
     * rádio, seguimos inferindo pelo tamanho do documento — mesma regra
     * que a tela sempre usou. Assim que ele mexe no rádio (ou o contato
     * já tem um tipo salvo), a inferência automática para.
     */
    public function updatedDocument(): void
    {
        if ($this->documentTypeManuallySet) {
            return;
        }

        $digits = preg_replace('/\D/', '', $this->document);
        $this->document_type = strlen($digits) === 14 ? 'company' : 'individual';
    }

    public function updatedDocumentType(): void
    {
        $this->documentTypeManuallySet = true;
    }

    /**
     * Links salvos no cofre de credenciais (Admin > Credenciais) — a
     * "Busca Avançada" abre um desses em nova aba. Só entram credenciais
     * com URL preenchida (uma sem link não serve pra abrir nada).
     */
    public function getCreditSearchLinksProperty(): Collection
    {
        if (! Auth::user()->hasModuleAccess('credentials', 'read')) {
            return collect();
        }

        return Credential::query()
            ->whereNotNull('url')
            ->where('url', '!=', '')
            ->orderBy('title')
            ->get(['id', 'title', 'url']);
    }

    /**
     * Busca Básica: consulta o CNPJ na BrasilAPI (fonte pública gratuita,
     * dados da Receita Federal) e preenche os campos automaticamente. O
     * PDF só é gerado e anexado no "Salvar" (ver save()), porque um
     * contato novo ainda não tem id pra anexar nada antes disso.
     */
    public function buscarCnpj(): void
    {
        abort_unless($this->canWrite, 403);

        $cnpj = preg_replace('/\D/', '', $this->document);

        if (strlen($cnpj) !== 14) {
            $this->addError('document', 'Informe um CNPJ válido (14 dígitos) para usar a Busca Básica.');

            return;
        }

        $this->searchingCnpj = true;

        $response = Http::timeout(15)->get("https://brasilapi.com.br/api/cnpj/v1/{$cnpj}");

        $this->searchingCnpj = false;

        if ($response->failed()) {
            $this->addError('document', 'Não foi possível consultar o CNPJ agora. Tente novamente em instantes.');

            return;
        }

        $dados = $response->json();

        $this->name = $dados['razao_social'] ?? $this->name;
        $this->address_line1 = trim(($dados['logradouro'] ?? '').($dados['numero'] ? ', '.$dados['numero'] : ''));
        $this->address_line2 = $dados['complemento'] ?? $this->address_line2;
        $this->district = $dados['bairro'] ?? $this->district;
        $this->city = $dados['municipio'] ?? $this->city;
        $this->state = $dados['uf'] ?? $this->state;
        $this->postal_code = $dados['cep'] ?? $this->postal_code;
        $this->country = 'Brasil';

        if ($this->phone === '' && ! empty($dados['ddd_telefone_1'])) {
            $this->phone = $dados['ddd_telefone_1'];
        }
        if ($this->email === '' && ! empty($dados['email'])) {
            $this->email = $dados['email'];
        }

        $this->lastCnpjLookupPayload = $dados;
        $this->lastCnpjLookupDocument = $cnpj;
    }

    private function attachCnpjLookupPdf(Contact $contact): void
    {
        $pdf = Pdf::loadView('pdf.contact-cnpj-lookup', ['dados' => $this->lastCnpjLookupPayload]);
        $bytes = $pdf->output();

        $filename = 'consulta-cnpj-'.now()->format('Ymd-His').'.pdf';
        $path = "contacts/{$contact->id}/{$filename}";

        Storage::disk('local')->put($path, $bytes);

        $contact->documents()->create([
            'category' => 'consulta_cnpj',
            'original_name' => $filename,
            'stored_path' => $path,
            'mime_type' => 'application/pdf',
            'file_size' => strlen($bytes),
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

        $documentDigits = preg_replace('/\D/', '', $data['document'] ?? '');

        if ($this->contact) {
            // Editar e salvar já conta como "revisado" — não precisa de
            // um passo separado só pra tirar o selo de pendente.
            $data['needs_review'] = false;
            $this->contact->update($data);
            $contact = $this->contact;
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

        // Se a Busca Básica foi usada pra este mesmo documento, anexa o
        // PDF da consulta agora que o contato já tem id.
        if ($this->lastCnpjLookupPayload && $this->lastCnpjLookupDocument === $documentDigits) {
            $this->attachCnpjLookupPdf($contact);
        }

        $this->redirect(route('contacts.index'), navigate: true);
    }

    public function render()
    {
        return view('livewire.contacts.form');
    }
}
