<?php

use App\Livewire\Admin\PeriodLock as AdminPeriodLock;
use App\Livewire\Admin\Users as AdminUsers;
use App\Http\Controllers\AuditLogPrintController;
use App\Http\Controllers\ContactDocumentController;
use App\Livewire\Admin\AuditLogs\Index as AdminAuditLogs;
use App\Livewire\Admin\Credentials\Index as AdminCredentials;
use App\Livewire\Categories\Index as CategoriesIndex;
use App\Livewire\Contacts\Form as ContactsForm;
use App\Livewire\Contacts\Index as ContactsIndex;
use App\Livewire\CostCenters\Index as CostCentersIndex;
use App\Livewire\Currencies\Index as CurrenciesIndex;
use App\Livewire\FinancialAccounts\Index as FinancialAccountsIndex;
use App\Http\Controllers\FinancialEntryReceiptController;
use App\Livewire\FinancialEntries\Index as FinancialEntriesIndex;
use App\Livewire\Hr\Index as HrIndex;
use App\Livewire\Hr\Profile as HrProfile;
use App\Livewire\Reports\AccountStatement as ReportsAccountStatement;
use App\Livewire\Reports\Analytical as ReportsAnalytical;
use App\Livewire\Reports\CashFlow as ReportsCashFlow;
use App\Livewire\Reports\CashForecast as ReportsCashForecast;
use App\Livewire\Reports\CostCenters as ReportsCostCenters;
use App\Livewire\Reports\Dre as ReportsDre;
use App\Livewire\Reports\Index as ReportsIndex;
use App\Livewire\Reports\Payables as ReportsPayables;
use App\Livewire\Reports\Receivables as ReportsReceivables;
use App\Livewire\Reports\Registrations as ReportsRegistrations;
use App\Livewire\Tasks\Index as TasksIndex;
use Illuminate\Support\Facades\Route;

// Rotas das telas de negócio (fora do que o Breeze gera). Este arquivo é
// carregado a partir de bootstrap/app.php (bloco `then:`).

Route::middleware(['web', 'auth'])->group(function () {
    Route::get('/financeiro/contas', FinancialAccountsIndex::class)->name('financial-accounts.index');
    Route::get('/financeiro/categorias', CategoriesIndex::class)->name('categories.index');
    Route::get('/financeiro/centros-de-custo', CostCentersIndex::class)->name('cost-centers.index');
    Route::get('/financeiro/moedas', CurrenciesIndex::class)->name('currencies.index');
    Route::get('/financeiro/lancamentos', FinancialEntriesIndex::class)->name('financial-entries.index');
    Route::get('/financeiro/lancamentos/{financialEntry}/recibo', [FinancialEntryReceiptController::class, 'show'])->name('financial-entries.receipt');
    Route::get('/contatos', ContactsIndex::class)->name('contacts.index');
    Route::get('/contatos/novo', ContactsForm::class)->name('contacts.create');
    Route::get('/contatos/{contact}/editar', ContactsForm::class)->name('contacts.edit');
    Route::get('/contatos/documentos/{contactDocument}', [ContactDocumentController::class, 'download'])->name('contacts.documents.download');
    Route::get('/agenda', TasksIndex::class)->name('tasks.index');
    Route::get('/rh', HrIndex::class)->name('hr.index');
    Route::get('/rh/{contact}/ficha', HrProfile::class)->name('hr.profile');
    Route::get('/admin/usuarios', AdminUsers::class)->name('admin.users.index');
    Route::get('/admin/fechamento', AdminPeriodLock::class)->name('admin.period-lock.index');
    Route::get('/admin/credenciais', AdminCredentials::class)->name('admin.credentials.index');
    Route::get('/admin/auditoria', AdminAuditLogs::class)->name('admin.audit.index');
    Route::get('/admin/auditoria/imprimir', [AuditLogPrintController::class, 'show'])->name('admin.audit.print');

    Route::get('/relatorios', ReportsIndex::class)->name('reports.index');
    Route::get('/relatorios/dre', ReportsDre::class)->name('reports.dre');
    Route::get('/relatorios/fluxo-de-caixa', ReportsCashFlow::class)->name('reports.cash-flow');
    Route::get('/relatorios/contas-a-pagar', ReportsPayables::class)->name('reports.payables');
    Route::get('/relatorios/contas-a-receber', ReportsReceivables::class)->name('reports.receivables');
    Route::get('/relatorios/centros-de-custo', ReportsCostCenters::class)->name('reports.cost-centers');
    Route::get('/relatorios/analitico', ReportsAnalytical::class)->name('reports.analytical');
    Route::get('/relatorios/previsao-de-caixa', ReportsCashForecast::class)->name('reports.cash-forecast');
    Route::get('/relatorios/extrato-de-conta', ReportsAccountStatement::class)->name('reports.account-statement');
    Route::get('/relatorios/cadastrais', ReportsRegistrations::class)->name('reports.registrations');
});
