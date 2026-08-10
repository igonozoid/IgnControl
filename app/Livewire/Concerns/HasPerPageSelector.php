<?php

namespace App\Livewire\Concerns;

use Livewire\Attributes\Url;

/**
 * Seletor "itens por página" (15/30/60/Todos) reutilizável nas telas
 * com WithPagination. "Todos" não é literalmente ilimitado — usa um
 * teto bem alto (PER_PAGE_ALL) que na prática nunca vai ser atingido
 * pelas listas deste sistema, evitando ter que trocar ->paginate() por
 * ->get() condicionalmente em cada tela.
 */
trait HasPerPageSelector
{
    public const PER_PAGE_ALL = 100000;

    #[Url]
    public int $perPage = 15;

    /** @return array<int> */
    public function perPageOptions(): array
    {
        return [15, 30, 60, self::PER_PAGE_ALL];
    }

    public function updatedPerPage(int $value): void
    {
        if (! in_array($value, $this->perPageOptions(), true)) {
            $this->perPage = 15;
        }

        // Mudar a quantidade por página some com a paginação atual —
        // sem isso, trocar de 15 pra 60 na página 3 podia deixar o
        // usuário numa página que não existe mais.
        if (method_exists($this, 'resetPage')) {
            $this->resetPage();
        }
    }
}
