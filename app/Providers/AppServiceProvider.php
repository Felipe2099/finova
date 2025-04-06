<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Filament\Support\Colors\Color;
use Filament\Support\Facades\FilamentColor;
use Illuminate\Support\Facades\Blade;
use App\Services\Customer\Contracts\CustomerServiceInterface;
use App\Services\Customer\Implementations\CustomerService;
use App\Services\Lead\Contracts\LeadServiceInterface;
use App\Services\Lead\Implementations\LeadService;
use App\Services\CustomerGroup\Contracts\CustomerGroupServiceInterface;
use App\Services\CustomerGroup\Implementations\CustomerGroupService;
use App\Services\Transaction\Contracts\TransactionServiceInterface;
use App\Services\Transaction\Implementations\TransactionService;
use App\Services\BankAccount\Contracts\BankAccountServiceInterface;
use App\Services\BankAccount\Implementations\BankAccountService;
use App\Services\Project\Contracts\ProjectServiceInterface;
use App\Services\Project\Implementations\ProjectService;
use App\Services\Payment\Contracts\PaymentServiceInterface;
use App\Services\Payment\Implementations\PaymentService;
use App\Services\Account\Contracts\AccountServiceInterface;
use App\Services\Account\Implementations\AccountService;
use App\Services\Debt\Contracts\DebtServiceInterface;
use App\Services\Debt\Implementations\DebtService;
use App\Services\Loan\Contracts\LoanServiceInterface;
use App\Services\Loan\Implementations\LoanService;
use App\Services\Role\Contracts\RoleServiceInterface;
use App\Services\Role\Implementations\RoleService;
use App\Services\Supplier\Contracts\SupplierServiceInterface;
use App\Services\Supplier\Implementations\SupplierService;
use App\Services\User\Contracts\UserServiceInterface;
use App\Services\User\Implementations\UserService;

/** Transaction Services */
use App\Services\Transaction\Contracts\AccountBalanceServiceInterface;
use App\Services\Transaction\Contracts\ExpenseTransactionServiceInterface;
use App\Services\Transaction\Contracts\IncomeTransactionServiceInterface;
use App\Services\Transaction\Contracts\InstallmentTransactionServiceInterface;
use App\Services\Transaction\Contracts\SubscriptionTransactionServiceInterface;
use App\Services\Transaction\Contracts\TransferTransactionServiceInterface;
use App\Services\Transaction\Implementations\AccountBalanceService;
use App\Services\Transaction\Implementations\ExpenseTransactionService;
use App\Services\Transaction\Implementations\IncomeTransactionService;
use App\Services\Transaction\Implementations\InstallmentTransactionService;
use App\Services\Transaction\Implementations\SubscriptionTransactionService;
use App\Services\Transaction\Implementations\TransferTransactionService;

use App\Services\Planning\Contracts\PlanningServiceInterface;
use App\Services\Planning\Implementations\PlanningService;
use App\Services\Analytics\TransactionAnalyticsService;
use App\Services\CreditCard\Contracts\CreditCardServiceInterface;
use App\Services\CreditCard\Implementations\CreditCardService;

/**
 * Uygulama Servis Sağlayıcısı
 * 
 * Uygulamanın servis kayıtlarını ve başlangıç yapılandırmasını yönetir.
 * Tüm servis arayüzlerinin implementasyonlarını kaydeder ve uygulama başlangıcında
 * gerekli yapılandırmaları gerçekleştirir.
 */
class AppServiceProvider extends ServiceProvider
{
    /**
     * Uygulama servislerini kaydeder
     * 
     * Tüm servis arayüzlerini ilgili implementasyonlarına bağlar.
     * Singleton ve bind kayıtları yapılandırılır.
     * 
     * @return void
     */
    public function register(): void
    {
        // Temel servis arayüzlerini implementasyonlarına bağla
        $this->app->singleton(CustomerServiceInterface::class, CustomerService::class);
        $this->app->singleton(LeadServiceInterface::class, LeadService::class);
        $this->app->singleton(CustomerGroupServiceInterface::class, CustomerGroupService::class);
        $this->app->singleton(TransactionServiceInterface::class, TransactionService::class);
        $this->app->bind(BankAccountServiceInterface::class, BankAccountService::class);
        $this->app->singleton(ProjectServiceInterface::class, ProjectService::class);
        $this->app->singleton(UserServiceInterface::class, UserService::class);
        
        // Kredi kartı servisi
        $this->app->bind(CreditCardServiceInterface::class, CreditCardService::class);
        
        // Diğer temel servisler
        $this->app->bind(DebtServiceInterface::class, DebtService::class);
        $this->app->bind(RoleServiceInterface::class, RoleService::class);
        $this->app->bind(SupplierServiceInterface::class, SupplierService::class);
        $this->app->bind(LoanServiceInterface::class, LoanService::class);
        $this->app->bind(AccountServiceInterface::class, AccountService::class);
        
        // Yardımcı servisleri singleton olarak kaydet
        $this->app->singleton(PaymentServiceInterface::class, PaymentService::class);
        $this->app->singleton(TransactionAnalyticsService::class);

        // İşlem servisleri
        $this->app->bind(AccountBalanceServiceInterface::class, AccountBalanceService::class);
        $this->app->bind(IncomeTransactionServiceInterface::class, IncomeTransactionService::class);
        $this->app->bind(ExpenseTransactionServiceInterface::class, ExpenseTransactionService::class);
        $this->app->bind(TransferTransactionServiceInterface::class, TransferTransactionService::class);
        $this->app->bind(InstallmentTransactionServiceInterface::class, InstallmentTransactionService::class);
        $this->app->bind(SubscriptionTransactionServiceInterface::class, SubscriptionTransactionService::class);

        // Planlama servisi
        $this->app->bind(PlanningServiceInterface::class, PlanningService::class);
    }

    /**
     * Uygulama servislerini başlatır
     * 
     * Uygulama başlangıcında gerekli yapılandırmaları gerçekleştirir.
     * Blade bileşenlerini, renk şemalarını ve sistem ayarlarını yapılandırır.
     * 
     * @return void
     */
    public function boot(): void
    {
        // Blade bileşenlerini kaydet
        Blade::component('auth', \App\View\Auth::class);

        // Filament renk şemalarını kaydet
        FilamentColor::register([
            'danger' => Color::Red,
            'gray' => Color::Gray,
            'primary' => Color::Blue,
            'success' => Color::Green,
            'warning' => Color::Yellow,
        ]);
        
        // Sistem ayarlarını yapılandır
        ini_set('memory_limit', '512M');
        ini_set('max_execution_time', 300);
    }
}
