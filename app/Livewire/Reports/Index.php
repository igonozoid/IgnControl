<?php

namespace App\Livewire\Reports;

use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Component;

/**
 * Catálogo de relatórios — só uma lista de links, no mesmo espírito do
 * "ReportsCatalogPage" do sistema antigo.
 */
#[Layout('layouts.app')]
class Index extends Component
{
    public function mount(): void
    {
        abort_unless(Auth::user()->hasModuleAccess('reports', 'read'), 403);
    }

    public function render()
    {
        return view('livewire.reports.index');
    }
}
