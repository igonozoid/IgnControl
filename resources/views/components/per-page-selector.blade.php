{{--
    Seletor "itens por página" (15/30/60/Todos), pra colocar junto dos
    filtros de qualquer tela paginada. Usa o trait
    App\Livewire\Concerns\HasPerPageSelector no componente Livewire
    (propriedade $perPage) — o resumo "mostrando X de Y resultados" já
    vem junto da paginação em si (ver resources/views/vendor/pagination/tailwind.blade.php),
    não precisa repetir aqui.

    OBS: o valor de "Todos" está fixo aqui como literal (100000) porque
    PHP não permite acessar uma constante de trait via NomeDoTrait::CONST
    (só através da classe que usa o trait, que varia por tela). Tem que
    ficar sincronizado manualmente com HasPerPageSelector::PER_PAGE_ALL.
--}}
<div>
    <label class="block text-xs font-medium text-gray-500 dark:text-neutral-400">Itens por página</label>
    <select wire:model.live="perPage" class="mt-1 block w-full rounded-md text-xs border-gray-300 dark:border-neutral-600 dark:bg-neutral-700 dark:text-neutral-100">
        <option value="15">15</option>
        <option value="30">30</option>
        <option value="60">60</option>
        <option value="100000">Todos</option>
    </select>
</div>
