<?php

declare(strict_types=1);

use Illuminate\Support\Facades\{Route, Auth};

// Livewire Auth Components
use App\Livewire\Auth\{
    Login,
    Register
};

// Livewire Post Components
use App\Livewire\{
    Dashboard
};

// Livewire Settings Components
use App\Livewire\Settings\{
    SettingsIndex,
    SiteSettings,
    PaymentSettings,
    NotificationSettings,
    SmtpSettings
};

// Livewire Role Components
use App\Livewire\Role\RoleManager;

// Livewire Income & Expense Components
use App\Livewire\Categories\{
    CategoryManager
};

// Livewire Transaction Components
use App\Livewire\Transaction\{
    TransactionManager,
    TransactionForm
};

// Mevduat Yönetimi
use App\Livewire\BankAccount;

// Livewire Customer Group Components
use App\Livewire\CustomerGroup\CustomerGroupManager;

// Livewire Customer Components
use App\Livewire\Customer\CustomerManager;

// Livewire Supplier Components
use App\Livewire\Supplier\SupplierManager;

// Livewire Lead Components
use App\Livewire\Lead\LeadManager;

// Livewire Customer Detail Components
use App\Livewire\Customer\CustomerDetail;

// Proposal Management
use App\Http\Controllers\ProposalController;
use App\Livewire\Proposal\ProposalTemplateManager;
use App\Livewire\Proposal\ProposalTemplateForm;


// Proje Yönetimi
use App\Livewire\Project\ProjectManager;
use App\Livewire\Project\Board\BoardManager;

// Finans Yönetimi Routes
use App\Livewire\Account\AccountManager;
use App\Livewire\Account\BankAccountManager;
use App\Livewire\Account\CryptoWalletManager;
use App\Livewire\Account\VirtualPosManager;
use App\Livewire\Account\CreditCardManager;
use App\Livewire\Account\CreditCardTransactions;
use App\Livewire\Account\AccountHistory;

// Livewire Debt Components
use App\Livewire\Debt\DebtManager;
use App\Livewire\Debt\DebtPayments;

// Livewire Loan Components
use App\Livewire\Loan\LoanManager;

// Analiz ve Takip Components
use App\Livewire\Analysis\CashFlowAnalysis;
use App\Livewire\Analysis\ProfitLossAnalysis;
use App\Livewire\Analysis\ExpenseCategoryAnalysis;
use App\Livewire\Analysis\IncomeSourceAnalysis;
use App\Livewire\Analysis\BudgetPerformanceAnalysis;
use App\Livewire\Analysis\CustomerProfitabilityAnalysis;
use App\Livewire\Analysis\ProjectProfitabilityAnalysis;
use App\Livewire\Analysis\CategoryAnalysis;

// Livewire User Components
use App\Livewire\User\UserManager;
use App\Livewire\User\UserForm;

use App\Livewire\Commission\CommissionManager;
use App\Livewire\Commission\UserCommissionHistory;


/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

// Public Routes
Route::view('/', 'welcome')->name('home');

// Auth Routes
Route::get('/login', Login::class)->name('login');
Route::get('/register', Register::class)->name('register');

// Protected Routes
Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/logout', function () {
        Auth::logout();
        return redirect()->route('login');
    })->name('logout');

    // Admin Routes
    Route::prefix('admin')->name('admin.')->group(function () {
        Route::get('/dashboard', Dashboard::class)->name('dashboard');

        // Role Management
        Route::get('/roles', RoleManager::class)->name('roles.index');

        // Settings Management
        Route::prefix('settings')->name('settings.')->group(function () {
            Route::get('/', SettingsIndex::class)->name('index');
            Route::get('/site', SiteSettings::class)->name('site');
            Route::get('/payment', PaymentSettings::class)->name('payment');
            Route::get('/smtp', SmtpSettings::class)->name('smtp');
            Route::get('/notification', NotificationSettings::class)->name('notification');
        });

        // User Management
        Route::prefix('users')->name('users.')->group(function () {
            Route::get('/', UserManager::class)->name('index');
            Route::get('/create', UserForm::class)->name('create');
            Route::get('/{user}/edit', UserForm::class)->name('edit');
            Route::get('/{user}/commissions', UserCommissionHistory::class)->name('commissions');
        });

        // Gelir & Gider Yönetimi
        Route::prefix('transactions')->group(function () {
            Route::get('/', TransactionManager::class)->name('transactions.index');
            Route::get('/create', TransactionForm::class)->name('transactions.create');
            Route::get('/{transaction}/edit', TransactionForm::class)->name('transactions.edit');
        });

        // Category Management
        Route::prefix('categories')->name('categories.')->group(function () {
            Route::get('/', CategoryManager::class)->name('index');
        });

        // Proposal Routes
        Route::prefix('proposals')->name('proposals.')->group(function () {
            Route::get('/templates', ProposalTemplateManager::class)->name('templates');
            Route::get('/templates/create', ProposalTemplateForm::class)->name('create');
            Route::get('/templates/{template}/edit', ProposalTemplateForm::class)->name('edit');
            Route::get('/{proposal}/pdf', [ProposalController::class, 'downloadPdf'])->name('pdf');
        });

        // Supplier Routes
        Route::prefix('suppliers')->name('suppliers.')->group(function () {
            Route::get('/', SupplierManager::class)->name('index');
        });

        // Customer Routes
        Route::prefix('customers')->name('customers.')->group(function () {
            Route::get('/', CustomerManager::class)->name('index');
            Route::get('/groups', CustomerGroupManager::class)->name('groups');
            Route::get('/potential', LeadManager::class)->name('potential');
            Route::get('/{customer}', CustomerDetail::class)->name('show');
        });

        // Proje Yönetimi
        Route::prefix('projects')->name('projects.')->group(function () {
            Route::get('/', ProjectManager::class)->name('index');
            Route::get('/{project}/boards', BoardManager::class)->name('boards');
        });

        // Kredi Yönetimi
        Route::prefix('loans')->name('loans.')->group(function () {
            Route::get('/', LoanManager::class)->name('index');
        });

        // Borç & Alacak Takibi
        Route::prefix('debts')->name('debts.')->group(function () {
            Route::get('/', DebtManager::class)->name('index');
            // Not active - Route::get('/payments/{debt}', DebtPayments::class)->name('payments');
        });

        // Finans Yönetimi Routes
        Route::prefix('accounts')->name('accounts.')->group(function () {
            Route::get('/bank', BankAccountManager::class)->name('bank');
            Route::get('/credit-cards', CreditCardManager::class)->name('credit-cards');
            Route::get('/crypto', CryptoWalletManager::class)->name('crypto');
            Route::get('/virtual-pos', VirtualPosManager::class)->name('virtual-pos');
            Route::get('/{account}/history', AccountHistory::class)->name('history');
        });
        
        // Analiz ve Takip Routes
        Route::prefix('analysis')->name('analysis.')->group(function () {
            // Finansal Analizler
            Route::get('/cash-flow', CashFlowAnalysis::class)->name('cash-flow');
            Route::get('/categories', CategoryAnalysis::class)->name('categories');
        });

        // Planlama Modülü
        Route::prefix('planning')->name('planning.')->group(function () {
            Route::get('/savings', \App\Livewire\Planning\SavingsPlanner::class)->name('savings');
            Route::get('/investments', \App\Livewire\Planning\InvestmentPlanner::class)->name('investments');
        });

    });
});