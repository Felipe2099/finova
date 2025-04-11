<?php

namespace App\Livewire\Transaction;

use App\Models\Account;
use App\Models\Category;
use App\Models\Transaction;
use App\DTOs\Transaction\TransactionData;
use App\Services\Transaction\Implementations\TransactionService;
use App\Services\Transaction\Contracts\SubscriptionTransactionServiceInterface;
use App\Services\Currency\CurrencyService;
use Filament\Forms;
use Livewire\Component;
use Illuminate\Contracts\View\View;
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Notifications\Notification;
use Carbon\Carbon;
use App\Enums\PaymentMethodEnum;
// Added log
use Illuminate\Support\Facades\Log;

/**
 * İşlem Form Bileşeni
 * 
 * Bu bileşen, finansal işlemlerin oluşturulması ve düzenlenmesi için form sağlar.
 * Özellikler:
 * - Gelir ve gider işlemleri
 * - Transfer işlemleri
 * - Çoklu para birimi desteği
 * - KDV ve stopaj hesaplamaları
 * - Taksitli işlemler
 * - Abonelik işlemleri
 * - Hesap yönetimi
 * - Kategori yönetimi
 * 
 * @package App\Livewire\Transaction
 */

class TransactionForm extends Component implements Forms\Contracts\HasForms
{
    use Forms\Concerns\InteractsWithForms;
    use InteractsWithActions;

    /** @var Transaction|null Düzenlenen işlem */
    public ?Transaction $transaction = null;

    /** @var array Form verileri */
    public $data = [];
    public bool $isCopyMode = false; // Kopyalama modunu takip etmek için özellik eklendi

    /** @var TransactionService İşlem servisi */
    private TransactionService $transactionService; // Tekrar private yapıldı

    /** @var CurrencyService Para birimi servisi */
    private CurrencyService $currencyService; // Tekrar private yapıldı

    /** @var SubscriptionTransactionServiceInterface Abonelik servisi */
    private SubscriptionTransactionServiceInterface $subscriptionService; // Eklendi ve private yapıldı

    /**
     * Bileşen başlatılırken servisleri enjekte eder
     *
     * @param TransactionService $transactionService İşlem servisi
     * @param CurrencyService $currencyService Para birimi servisi
     * @param SubscriptionTransactionServiceInterface $subscriptionService Abonelik servisi
     * @return void
     */
    public function boot(
        TransactionService $transactionService,
        CurrencyService $currencyService,
        SubscriptionTransactionServiceInterface $subscriptionService // Eklendi
    ): void
    {
        $this->transactionService = $transactionService;
        $this->currencyService = $currencyService;
        $this->subscriptionService = $subscriptionService; // Eklendi
    }

     /**
     * Bileşen başlatılırken çalışır
     *
     * @param Transaction|null $transaction Düzenlenecek işlem
     * @return void
     */

    public function mount($transaction = null): void
    {
        $this->isCopyMode = false;

        // Kopyalama modunu kontrol et ve ayarla
        if (request()->filled('copy_from')) { 

            $this->isCopyMode = true; 
            $originalTransactionId = (int) request()->query('copy_from');
            $originalTransaction = Transaction::find($originalTransactionId);
            if ($originalTransaction) {
                $this->fillDefaultFormData();
                $this->copyTransactionData($originalTransaction);
                Notification::make()
                    ->title('İşlem bilgileri kopyalandı.')
                    ->info()
                    ->send();
            } else {
                 Notification::make()
                    ->title('Kopyalanacak işlem bulunamadı.')
                    ->danger()
                    ->send();
                 $this->fillDefaultFormData();
                 $this->isCopyMode = false; // Kopyalama başarısızsa modu kapat
            }
        }
        // Düzenleme modunu kontrol et
        elseif ($transaction) {
            $this->transaction = $transaction;
            $this->fillDefaultFormData();
            $this->loadTransactionData();
        }
        // Yeni işlem modu
        else {
            $this->fillDefaultFormData();
        }
    }

    // Varsayılan form verilerini doldurmak için yardımcı metod
    private function fillDefaultFormData(): void
    {
         $this->form->fill([
            'type' => null,
            'payment_method' => PaymentMethodEnum::CASH->value,
            'currency' => 'TRY',
            'exchange_rate' => 1,
            'date' => now(),
            'is_subscription' => false,
            'is_taxable' => false,
            'user_id' => auth()->id(),
            'account_select' => null,
            // Diğer sıfırlanması gereken alanlar buraya eklenebilir
            'category_id' => null,
            'customer_id' => null,
            'supplier_id' => null,
            'amount' => null,
            'try_equivalent' => null,
            'fee_amount' => null,
            'description' => null,
            'installments' => null,
            'remaining_installments' => null,
            'monthly_amount' => null,
            'next_payment_date' => null,
            'subscription_period' => null,
            'tax_rate' => null,
            'tax_amount' => null,
            'has_withholding' => false,
            'withholding_rate' => null,
            'withholding_amount' => null,
            'status' => 'completed',
            'reference_id' => null,
        ]);
    }

    /**
     * İşlem verilerini form'a yükler
     * 
     * @return void
     */
    private function loadTransactionData(): void
    {
        $formData = [
            'type' => $this->transaction->type,
            'payment_method' => $this->transaction->payment_method?->value ?? PaymentMethodEnum::CASH->value,
            'currency' => $this->transaction->currency,
            'exchange_rate' => $this->transaction->exchange_rate ?? 1,
            'date' => $this->transaction->date,
            'is_subscription' => $this->transaction->is_subscription,
            'is_taxable' => $this->transaction->is_taxable,
            'user_id' => $this->transaction->user_id,
            'category_id' => $this->transaction->category_id,
            'customer_id' => $this->transaction->customer_id,
            'supplier_id' => $this->transaction->supplier_id,
            'amount' => $this->transaction->amount,
            'try_equivalent' => $this->transaction->try_equivalent,
            'fee_amount' => $this->transaction->fee_amount,
            'description' => $this->transaction->description,
            'installments' => $this->transaction->installments,
            'remaining_installments' => $this->transaction->remaining_installments,
            'monthly_amount' => $this->transaction->monthly_amount,
            'next_payment_date' => $this->transaction->next_payment_date,
            'subscription_period' => $this->transaction->subscription_period,
            'auto_renew' => $this->transaction->auto_renew,
            'tax_rate' => $this->transaction->tax_rate,
            'tax_amount' => $this->transaction->tax_amount,
            'has_withholding' => $this->transaction->has_withholding,
            'withholding_rate' => $this->transaction->withholding_rate,
            'withholding_amount' => $this->transaction->withholding_amount,
            'status' => $this->transaction->status,
            'reference_id' => $this->transaction->reference_id,
            'account_select' => null,
        ];
        
        // Nakit olmayan işlemler için hesap seçimini ayarla
        if ($this->transaction->payment_method && $this->transaction->payment_method->value !== PaymentMethodEnum::CASH->value) {
            if ($this->transaction->type === 'income') {
                $formData['account_select'] = $this->transaction->destination_account_id;
            } else {
                $formData['account_select'] = $this->transaction->source_account_id;
            }
        }

        // Vergi hesaplamaları
        if ($this->transaction->is_taxable && $this->transaction->tax_rate) {
            $taxRate = $this->transaction->tax_rate / 100;
            $netAmount = $this->transaction->amount / (1 + $taxRate);
            $formData['tax_amount'] = round($this->transaction->amount - $netAmount, 2);
        }

        // Kur ayarlaması
        if ($this->transaction->currency === 'TRY') {
            $formData['exchange_rate'] = 1;
        }

        // Taksit bilgileri
        if ($this->transaction->installments > 1) {
            $formData['installments'] = $this->transaction->installments;
            $formData['remaining_installments'] = $this->transaction->remaining_installments;
            $formData['monthly_amount'] = round($this->transaction->amount / $this->transaction->installments, 2);
        }

        // Form verilerini yükle
        $this->form->fill($formData);
    }

    /**
     * Verilen işlemden verileri forma kopyalar (Hızlı İşlem için)
     *
     * @param Transaction $originalTransaction Kopyalanacak işlem
     * @return void
     */
    private function copyTransactionData(Transaction $originalTransaction): void
    {
        // Orijinal işlemden tüm ilgili verileri al
        $formData = [
            'type' => $originalTransaction->type,
            'category_id' => $originalTransaction->category_id,
            'customer_id' => $originalTransaction->customer_id,
            'supplier_id' => $originalTransaction->supplier_id,
            'amount' => $originalTransaction->amount,
            'currency' => $originalTransaction->currency,
            'exchange_rate' => $originalTransaction->exchange_rate ?? 1,
            'try_equivalent' => $originalTransaction->try_equivalent,
            'fee_amount' => $originalTransaction->fee_amount,
            'description' => $originalTransaction->description,
            'payment_method' => $originalTransaction->payment_method?->value ?? PaymentMethodEnum::CASH->value,
            'source_account_id' => $originalTransaction->source_account_id,
            'destination_account_id' => $originalTransaction->destination_account_id,
            'is_taxable' => $originalTransaction->is_taxable,
            'tax_rate' => $originalTransaction->tax_rate,
            'tax_amount' => $originalTransaction->tax_amount,
            'has_withholding' => $originalTransaction->has_withholding,
            'withholding_rate' => $originalTransaction->withholding_rate,
            'withholding_amount' => $originalTransaction->withholding_amount,
            // Orijinal abonelik bilgilerini de alalım (formda göstermek için)
            'is_subscription' => $originalTransaction->is_subscription,
            'subscription_period' => $originalTransaction->subscription_period,
            // 'auto_renew' => $originalTransaction->auto_renew, // Kullanılmıyor
            'user_id' => auth()->id(), // Kullanıcıyı mevcut kullanıcı yap
        ];

        // Yeni işlem için bazı alanları sıfırla/ayarla
        $formData['date'] = now(); // Tarihi bugüne ayarla
        // $formData['is_subscription'] = false; // Formda gösterilsin, kaydederken false yapılır
        $formData['next_payment_date'] = null; // Yeni işlem için geçerli değil
        $formData['remaining_installments'] = null; // Yeni işlem için geçerli değil
        $formData['status'] = 'completed'; // Durumu varsayılana ayarla
        $formData['reference_id'] = $originalTransaction->id; // Referans olarak eski ID'yi tutabiliriz

        // Hesap seçimi için account_select'i ayarla
        $formData['account_select'] = null;
        if ($originalTransaction->payment_method && $originalTransaction->payment_method->value !== PaymentMethodEnum::CASH->value) {
            if ($originalTransaction->type === 'income') {
                $formData['account_select'] = $originalTransaction->destination_account_id;
            } else {
                $formData['account_select'] = $originalTransaction->source_account_id;
            }
        }

        // Form verilerini yükle
        $this->form->fill($formData);
    }

    /**
     * Ödeme yöntemini belirler
     * 
     * @param Transaction $transaction İşlem
     * @return string|null Ödeme yöntemi
     */
    private function getPaymentMethod(Transaction $transaction): ?string
    {
        // Önce payment_method alanını kontrol et
        if ($transaction->payment_method) {
            return $transaction->payment_method->value;
        }

        // Eğer payment_method yoksa, hesap tipine göre belirle
        if ($transaction->sourceAccount) {
            return match($transaction->sourceAccount->type) {
                Account::TYPE_BANK_ACCOUNT => PaymentMethodEnum::BANK->value,
                Account::TYPE_CREDIT_CARD => PaymentMethodEnum::CREDIT_CARD->value,
                Account::TYPE_CRYPTO_WALLET => PaymentMethodEnum::CRYPTO->value,
                Account::TYPE_VIRTUAL_POS => PaymentMethodEnum::VIRTUAL_POS->value,
                default => PaymentMethodEnum::CASH->value
            };
        }

        // Eğer destination_account varsa (gelir işlemleri için)
        if ($transaction->destinationAccount) {
            return match($transaction->destinationAccount->type) {
                Account::TYPE_BANK_ACCOUNT => PaymentMethodEnum::BANK->value,
                Account::TYPE_CRYPTO_WALLET => PaymentMethodEnum::CRYPTO->value,
                Account::TYPE_VIRTUAL_POS => PaymentMethodEnum::VIRTUAL_POS->value,
                default => PaymentMethodEnum::CASH->value
            };
        }

        return PaymentMethodEnum::CASH->value;
    }

    /**
     * Hesap ID'sini döndürür
     * 
     * @param Transaction $transaction İşlem
     * @return int|null Hesap ID'si
     */
    private function getAccountId(Transaction $transaction): ?int
    {
        return $transaction->source_account_id ?? $transaction->destination_account_id;
    }

    /**
     * Döviz kurunu hesaplar
     * 
     * @param string|null $currency Para birimi
     * @param Carbon|null $date Tarih
     * @return float|null Döviz kuru
     */
    private function getExchangeRate(?string $currency, ?Carbon $date = null): ?float
    {
        if (!$currency || $currency === 'TRY') {
            return 1.0;
        }

        try {
            // boot() ile enjekte edilen servisi kullan
            $rates = $this->currencyService->getExchangeRates($date);
            if (!$rates || !isset($rates[$currency])) {
                return null;
            }

            // Yabancı Para -> TRY (direkt buying rate'i kullan)
            return $rates[$currency]['buying'];

        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Form şemasını oluşturur
     * 
     * @param Forms\Form $form Form nesnesi
     * @return Forms\Form Yapılandırılmış form
     */
    public function form(Forms\Form $form): Forms\Form
    {
        // Servisler mount() metodunda başlatıldı.

        $schema = [
            // Ana işlem kartı
            Forms\Components\Section::make('İşlem Bilgileri')
                ->schema([
                    ...$this->getTransactionDetailsSchema(),
                    ...$this->getCurrencyAndAmountSchema(),
                    Forms\Components\Textarea::make('description')
                        ->label('Açıklama')
                        ->rows(2),
                   Forms\Components\Hidden::make('reference_id'), // Kopyalanan işlemin orijinal ID'sini tutmak için
                ]),
        ];

        // Abonelik bölümünü almayı dene
        $subscriptionSchema = $this->getSubscriptionSchema();
        // Sadece null değilse şemaya ekle
        if ($subscriptionSchema !== null) {
            $schema[] = $subscriptionSchema;
        }

        // Vergilendirme bölümünü ekle
        $schema[] = $this->getTaxationSchema();

        return $form
            ->schema($schema)
            ->statePath('data');
    }

    /**
     * Önbelleğe alınmış hesap seçeneklerini döndürür
     * 
     * @param string|null $type Hesap tipi
     * @return array Hesap seçenekleri
     */
    private function getCachedAccountOptions(string $type = null): array
    {
        $cacheKey = $type ? "user_{auth()->id()}_accounts_{$type}" : "user_{auth()->id()}_accounts";
        return cache()->remember($cacheKey, now()->addHours(24), function () use ($type) {
            $query = Account::query()
                ->where('user_id', auth()->id())
                ->where('status', true)  // Sadece aktif hesaplar
                ->whereNull('deleted_at'); // Silinmemiş hesaplar
            
            if ($type) {
                $query->where('type', $type);
            }
            
            return $query->get()
                ->mapWithKeys(function ($account) {
                    $typeName = match($account->type) {
                        'bank_account' => 'Banka',
                        'credit_card' => 'Kredi Kartı',
                        'crypto_wallet' => 'Kripto',
                        'virtual_pos' => 'Sanal POS',
                        'cash' => 'Nakit',
                        default => $account->type,
                    };
                    return [$account->id => "{$account->name} ({$account->currency}) - {$typeName}"];
                })->toArray();
        });
    }

    /**
     * Önbelleğe alınmış kategori seçeneklerini döndürür
     * 
     * @param string $type Kategori tipi
     * @return array Kategori seçenekleri
     */
    private function getCachedCategoryOptions(string $type): array
    {
        $cacheKey = "user_{auth()->id()}_categories_{$type}";
        return cache()->remember($cacheKey, now()->addHours(24), function () use ($type) {
            return Category::query()
                ->where('user_id', auth()->id())
                ->where('type', $type)
                ->where('status', true)
                ->pluck('name', 'id')
                ->toArray();
        });
    }
    
    /**
     * İşlem detayları form şemasını oluşturur
     * 
     * @return array Form bileşenleri
     */
    protected function getTransactionDetailsSchema(): array
    {
        return [
            Forms\Components\Grid::make(2)->schema([
                Forms\Components\Select::make('type')
                    ->label('İşlem')
                    ->options([
                        'income' => 'Gelir',
                        'expense' => 'Gider',
                    ])
                    ->placeholder('İşlem türü seçin')
                    ->native(false)
                    ->required()
                    ->live()
                    ->afterStateUpdated(function ($state, Forms\Set $set) {
                        // State değiştiğinde form alanlarını sıfırla
                        $set('category_id', null);
                        $set('payment_method', PaymentMethodEnum::CASH->value);
                        $set('account_select', null);
                        $set('source_account_id', null);
                        $set('destination_account_id', null);
                        $set('customer_id', null);
                        $set('currency', 'TRY');
                        $set('exchange_rate', 1);
                        $set('amount', null);
                        $set('try_equivalent', null);
                        $set('fee_amount', null);
                        $set('description', null);
                        $set('installments', null);
                        $set('remaining_installments', null);
                        $set('monthly_amount', null);
                        // $set('next_payment_date', null); // Abonelik için sıfırlanmamalı
                        // $set('is_subscription', false); // Abonelik için sıfırlanmamalı
                        // $set('subscription_period', null); // Abonelik için sıfırlanmamalı
                        // $set('auto_renew', false); // Kullanılmıyor, sıfırlanabilir veya kaldırılabilir
                        $set('is_taxable', false);
                        $set('tax_rate', null);
                        $set('tax_amount', null);
                        $set('has_withholding', false);
                        $set('withholding_rate', null);
                        $set('withholding_amount', null);
                    }),

                Forms\Components\Select::make('category_id')
                    ->label('Kategori')
                    ->options(function (callable $get): array {
                        $type = $get('type');
                        if (!$type) {
                            return []; // Return empty if no type is selected
                        }
                        // Fetch categories based on the selected type
                        return Category::query()
                            ->where('type', $type)
                            ->where('status', true)
                            ->pluck('name', 'id')
                            ->toArray();
                    })
                    ->placeholder(fn (callable $get) => $get('type') ? 'Kategori seçiniz' : 'Önce işlem seçiniz')
                    ->searchable()
                    ->preload()
                    ->native(false)
                    ->required()
                    ->live()
                    ->reactive(), // Make category field reactive

            ]),

            Forms\Components\Select::make('customer_id')
                ->label('Müşteri')
                ->options(function () {
                    return \App\Models\Customer::query()
                        ->where('status', true)
                        ->pluck('name', 'id')
                        ->toArray();
                })
                ->placeholder('Müşteri seçiniz')
                ->searchable()
                ->preload()
                ->native(false)
                ->visible(fn (callable $get) => $get('type') === 'income'),

            Forms\Components\Grid::make(2)->schema([
                Forms\Components\Select::make('payment_method')
                    ->label('Ödeme Yöntemi')
                    ->options(function (callable $get) {
                        // Gelir işlemi için seçenekler
                        if ($get('type') === 'income') {
                            return [
                                PaymentMethodEnum::CASH->value => PaymentMethodEnum::CASH->label(),
                                PaymentMethodEnum::BANK->value => PaymentMethodEnum::BANK->label(),
                                PaymentMethodEnum::CRYPTO->value => PaymentMethodEnum::CRYPTO->label(),
                                PaymentMethodEnum::VIRTUAL_POS->value => PaymentMethodEnum::VIRTUAL_POS->label(),
                            ];
                        }
                        
                        // Gider işlemi için seçenekler
                        if ($get('type') === 'expense') {
                            return [
                                PaymentMethodEnum::CASH->value => PaymentMethodEnum::CASH->label(),
                                PaymentMethodEnum::BANK->value => PaymentMethodEnum::BANK->label(),
                                PaymentMethodEnum::CRYPTO->value => PaymentMethodEnum::CRYPTO->label(),
                                PaymentMethodEnum::CREDIT_CARD->value => PaymentMethodEnum::CREDIT_CARD->label(),
                            ];
                        }

                        // Varsayılan olarak sadece nakit
                        return [
                            PaymentMethodEnum::CASH->value => PaymentMethodEnum::CASH->label(),
                        ];
                    })
                    ->placeholder('Ödeme yöntemi seçiniz')
                    ->default(PaymentMethodEnum::CASH->value)
                    ->disabled(fn (callable $get) => !in_array($get('type'), ['income', 'expense']))
                    ->required()
                    ->native(false)
                    ->live()
                    ->afterStateUpdated(function ($state, Forms\Set $set) {
                        // Ödeme yöntemi değiştiğinde hesap seçimini temizle
                        $set('account_select', null);
                        $set('source_account_id', null);
                        $set('destination_account_id', null);
                        $set('amount', null);
                        $set('try_equivalent', null);
                        $set('fee_amount', null);
                        $set('description', null);
                        $set('installments', null);
                        $set('remaining_installments', null);
                        $set('monthly_amount', null);
                        $set('next_payment_date', null);

                        // Kripto cüzdan seçildiğinde USD para birimini otomatik seç
                        if ($state === PaymentMethodEnum::CRYPTO->value) {
                            $set('currency', 'USD');
                            // USD kuru al
                            $exchangeRate = app(CurrencyService::class)->getExchangeRate('USD');
                            if ($exchangeRate) {
                                $set('exchange_rate', $exchangeRate['buying']);
                            }
                        } else {
                            // Kripto dışındaki işlemlerde TRY'ye dön
                            $set('currency', 'TRY');
                            $set('exchange_rate', 1);
                        }
                    }),

                Forms\Components\Select::make('account_select')
                    ->label(function (callable $get) {
                        return match($get('payment_method')) {
                            PaymentMethodEnum::BANK->value => 'Banka Hesabı',
                            PaymentMethodEnum::CREDIT_CARD->value => 'Kredi Kartı',
                            PaymentMethodEnum::CRYPTO->value => 'Kripto Cüzdan',
                            PaymentMethodEnum::VIRTUAL_POS->value => 'Sanal POS',
                            default => 'Hesap'
                        };
                    })
                    ->options(function (callable $get) {
                        if ($get('payment_method') === PaymentMethodEnum::CASH->value) {
                            return [];
                        }

                        $query = Account::query()
                            ->where('user_id', auth()->id())
                            ->where('status', true)
                            ->whereNull('deleted_at');

                        // Ödeme yöntemine göre hesap tipini filtrele
                        $query->when($get('payment_method') === PaymentMethodEnum::BANK->value, 
                            fn($q) => $q->where('type', Account::TYPE_BANK_ACCOUNT)
                        )
                        ->when($get('payment_method') === PaymentMethodEnum::CREDIT_CARD->value, 
                            fn($q) => $q->where('type', Account::TYPE_CREDIT_CARD)
                        )
                        ->when($get('payment_method') === PaymentMethodEnum::CRYPTO->value, 
                            fn($q) => $q->where('type', Account::TYPE_CRYPTO_WALLET)
                        )
                        ->when($get('payment_method') === PaymentMethodEnum::VIRTUAL_POS->value, 
                            fn($q) => $q->where('type', Account::TYPE_VIRTUAL_POS)
                        );

                        return $query->get()
                            ->mapWithKeys(fn ($account) => [
                                $account->id => match($account->type) {
                                    Account::TYPE_BANK_ACCOUNT, Account::TYPE_CRYPTO_WALLET => "{$account->name} ({$account->formatted_balance}) - {$account->currency}",
                                    default => $account->name,
                                }
                            ])
                            ->toArray();
                    })
                    ->placeholder(function (callable $get) {
                        if ($get('payment_method') === PaymentMethodEnum::CASH->value) {
                            return 'Önce ödeme yöntemi seçiniz';
                        }
                        return 'Hesap seçiniz';
                    })
                    ->searchable()
                    ->preload()
                    ->native(false)
                    ->disabled(fn (callable $get) => $get('payment_method') === PaymentMethodEnum::CASH->value)
                    ->required(fn (callable $get) => in_array($get('payment_method'), [
                        PaymentMethodEnum::BANK->value,
                        PaymentMethodEnum::CREDIT_CARD->value,
                        PaymentMethodEnum::CRYPTO->value,
                        PaymentMethodEnum::VIRTUAL_POS->value
                    ]))
                    ->live()
                    ->afterStateUpdated(function ($state, Forms\Set $set, callable $get) {
                        if ($state) {
                            $account = Account::find($state);
                            if ($account && $account->currency !== $get('currency')) {
                                Notification::make()
                                    ->info()
                                    ->title('Döviz İşlemi')
                                    ->body("Bu işlem {$account->currency} hesabından {$get('currency')} cinsinden yapılacak. Kur: {$get('exchange_rate')}")
                                    ->send();
                            }
                        }
                    }),
            ]),
        ];
    }

    /**
     * Para birimi ve tutar form şemasını oluşturur
     * 
     * @return array Form bileşenleri
     */
    private function getCurrencyAndAmountSchema(): array
    {
        return [
            Forms\Components\Grid::make(2)->schema([
                Forms\Components\Select::make('currency')
                    ->label('Para Birimi')
                    ->options(function (callable $get) {
                        if ($get('payment_method') === PaymentMethodEnum::CRYPTO->value) {
                            return ['USD' => 'Amerikan Doları ($)'];
                        }
                        return [
                            'TRY' => 'Türk Lirası (₺)',
                            'USD' => 'Amerikan Doları ($)',
                            'EUR' => 'Euro (€)',
                            'GBP' => 'İngiliz Sterlini (£)',
                        ];
                    })
                    ->default('TRY')
                    ->required()
                    ->native(false)
                    ->live()
                    ->afterStateUpdated(function ($state, Forms\Set $set, callable $get) {
                        // Kur hesaplama
                        if ($state && $state !== 'TRY') {
                            $date = $get('date') ? Carbon::parse($get('date')) : now();
                            $exchangeRate = $this->currencyService->getExchangeRate($state, $date);
                            
                            if ($exchangeRate !== null) {
                                $set('exchange_rate', $exchangeRate['buying']);
                                if ($get('amount')) {
                                    $set('try_equivalent', round($get('amount') * $exchangeRate['buying'], 2));
                                }
                            } else {
                                if (!$get('exchange_rate')) {
                                    $set('exchange_rate', 1);
                                }
                                
                                Notification::make()
                                    ->warning()
                                    ->title('Döviz kuru alınamadı')
                                    ->body('Seçilen tarih için döviz kuru bilgisi alınamadı. Lütfen kuru manuel olarak giriniz.')
                                    ->send();
                            }
                        } else {
                            $set('exchange_rate', 1);
                            $set('try_equivalent', $get('amount'));
                        }

                        // Hesap seçili ise para birimi uyumsuzluğu kontrolü
                        if ($get('account_select')) {
                            $account = Account::find($get('account_select'));
                            if ($account && $account->currency !== $state) {
                                Notification::make()
                                    ->info()
                                    ->title('Döviz İşlemi')
                                    ->body("Bu işlem {$account->currency} hesabından {$state} cinsinden yapılacak. Kur: {$get('exchange_rate')}")
                                    ->send();
                            }
                        }
                    }),

                Forms\Components\TextInput::make('exchange_rate')
                    ->label('Kur')
                    ->numeric()
                    ->required(fn (callable $get) => $get('currency') !== 'TRY')
                    ->default(1)
                    ->disabled(fn (callable $get) => $get('currency') === 'TRY')
                    ->dehydrated()
                    ->live()
                    ->afterStateUpdated(function ($state, Forms\Set $set, callable $get) {
                        if ($state) {
                            $amount = floatval($get('amount') ?? 0);
                            $currency = $get('currency');
                            
                            if ($currency === 'USD') {
                                $set('try_equivalent', round($amount * $state, 2));
                            } else {
                                $set('try_equivalent', round($amount * $state, 2));
                            }
                        }
                    })
                    ->placeholder('Kur değeri')
                    ->helperText('TL karşılığını hesaplamak için kur değeri'),
            ]),

            Forms\Components\Grid::make(2)->schema([
                Forms\Components\TextInput::make('amount')
                    ->label('Tutar (Brüt)')
                    ->required()
                    ->numeric()
                    ->minValue(0)
                    ->live()
                    ->afterStateUpdated(function ($state, Forms\Set $set, callable $get) {
                        $amount = floatval($state ?? 0);
                        $exchangeRate = floatval($get('exchange_rate') ?? 1);
                        $currency = $get('currency');
                        
                        if ($currency && $currency !== 'TRY') {
                            $tryAmount = $amount * $exchangeRate;
                            $set('try_equivalent', round($tryAmount, 2));
                        } else {
                            $set('try_equivalent', $amount);
                        }
                        
                        if ($get('is_taxable') && $get('tax_rate')) {
                            $taxRate = floatval($get('tax_rate')) / 100;
                            $netAmount = $amount / (1 + $taxRate);
                            $set('tax_amount', round($amount - $netAmount, 2));
                        }
                        
                        if ($get('is_taxable') && $get('has_withholding') && $get('withholding_rate')) {
                            $withholdingRate = floatval($get('withholding_rate')) / 100;
                            $set('withholding_amount', round($amount * $withholdingRate, 2));
                        }
                    })
                    ->prefix(fn (callable $get) => match($get('currency')) {
                        'TRY' => '₺',
                        'USD' => '$',
                        'EUR' => '€',
                        'GBP' => '£',
                        default => '₺',
                    }),

                Forms\Components\DatePicker::make('date')
                    ->label('Tarih')
                    ->required()
                    ->default(now())
                    ->displayFormat('d.m.Y')
                    ->maxDate(now())
                    ->native(false)
                    ->live()
                    ->afterStateUpdated(function ($state, Forms\Set $set, callable $get) {
                        $currency = $get('currency');
                        if ($currency && $currency !== 'TRY') {
                            $date = $state ? Carbon::parse($state) : now();
                            
                            if ($date->isAfter(now())) {
                                Notification::make()
                                    ->danger()
                                    ->title('Hatalı Tarih')
                                    ->body('İleri tarihli işlem eklenemez!')
                                    ->send();
                                    
                                $set('date', now()->format('Y-m-d'));
                                return;
                            }
                            
                            $exchangeRate = $this->currencyService->getExchangeRate($currency, $date);
                            
                            if ($exchangeRate !== null) {
                                $set('exchange_rate', $exchangeRate['buying']);
                                
                                if ($get('amount')) {
                                    $set('try_equivalent', round($get('amount') * $exchangeRate['buying'], 2));
                                }
                            } else {
                                if (!$get('exchange_rate')) {
                                    $set('exchange_rate', 1);
                                }
                                
                                Notification::make()
                                    ->warning()
                                    ->title('Döviz kuru alınamadı')
                                    ->body('Seçilen tarih için döviz kuru bilgisi alınamadı. Lütfen kuru manuel olarak giriniz.')
                                    ->send();
                            }
                        }
                    }),
            ]),

            Forms\Components\Grid::make(1)->schema([
                Forms\Components\TextInput::make('try_equivalent')
                    ->label('TL Karşılığı')
                    ->disabled()
                    ->numeric()
                    ->prefix('₺'),
            ])->visible(fn (callable $get) => $get('currency') !== 'TRY'),

            // Kredi kartı taksit bölümü
            Forms\Components\Grid::make(2)
                ->schema([
                    Forms\Components\TextInput::make('installments')
                        ->label('Taksit Sayısı')
                        ->numeric()
                        ->minValue(1)
                        ->maxValue(36)
                        ->live()
                        ->afterStateUpdated(function ($state, Forms\Set $set, callable $get) {
                            if ($state) {
                                $set('remaining_installments', $state);
                                
                                $amount = (float) $get('amount');
                                if ($amount > 0) {
                                    $monthlyAmount = round($amount / $state, 2);
                                    $set('monthly_amount', $monthlyAmount);
                                }
                            } else {
                                $set('remaining_installments', null);
                                $set('monthly_amount', null);
                            }
                        }),

                    Forms\Components\TextInput::make('monthly_amount')
                        ->label('Aylık Taksit Tutarı')
                        ->disabled()
                        ->prefix(fn (callable $get) => match($get('currency')) {
                            'TRY' => '₺',
                            'USD' => '$',
                            'EUR' => '€',
                            'GBP' => '£',
                            default => '₺',
                        }),
                ])
                ->visible(fn (callable $get) => $get('payment_method') === 'credit_card'),
        ];
    }

    /**
     * Abonelik bilgileri form şemasını oluşturur
     * 
     * @return Forms\Components\Section Abonelik bileşenleri
     */
    private function getSubscriptionSchema(): ?Forms\Components\Section // Return type nullable yapıldı
    {
        // Kopyalama modunda bu bölümü hiç oluşturma
        if ($this->isCopyMode) {
            return null;
        }

        return Forms\Components\Section::make('Abonelik Bilgileri')
            ->schema([
                Forms\Components\Toggle::make('is_subscription')
                    ->label('Abonelik mi?')
                    ->helperText('Bu işlem düzenli tekrarlanan bir abonelik ödemesi ise işaretleyin.')
                    ->default(false)
                    ->live(),
                
                Forms\Components\Grid::make(2)
                    ->schema([
                        Forms\Components\Select::make('subscription_period')
                            ->label('Abonelik Periyodu')
                            ->options([
                                'daily' => 'Günlük',
                                'weekly' => 'Haftalık',
                                'monthly' => 'Aylık',
                                'quarterly' => '3 Aylık',
                                'biannually' => '6 Aylık',
                                'annually' => 'Yıllık',
                            ])
                            ->required(fn (callable $get) => $get('is_subscription'))
                            ->native(false)
                            ->searchable()
                            ->preload(),
                            
                        Forms\Components\DatePicker::make('next_payment_date')
                            ->label('Sonraki Ödeme Tarihi')
                            ->displayFormat('d.m.Y')
                            ->default(fn () => now()->addMonth())
                            ->required(fn (callable $get) => $get('is_subscription'))
                            ->native(false),
                    ])
                    ->visible(fn (callable $get) => $get('is_subscription'))
            ])
            ->collapsible()
            ->collapsed(fn () => $this->isCopyMode);
    }

    /**
     * Vergilendirme form şemasını oluşturur
     * 
     * @return Forms\Components\Section Vergilendirme bileşenleri
     */
    private function getTaxationSchema(): Forms\Components\Section
    {
        return Forms\Components\Section::make('Vergilendirme')
            ->schema([
                Forms\Components\Grid::make(1)->schema([
                    Forms\Components\Select::make('is_taxable')
                        ->label('Vergilendirme')
                        ->options([
                            1 => 'Var',
                            0 => 'Yok',
                        ])
                        ->default(0)
                        ->live()
                        ->native(false)
                        ->afterStateUpdated(function ($state, callable $set) {
                            if (!$state) {
                                $set('tax_rate', null);
                                $set('tax_amount', null);
                                $set('has_withholding', 0);
                                $set('withholding_rate', null);
                                $set('withholding_amount', null);
                            } else {
                                // Vergilendirme var ise, stopaj varsayılan olarak 0 (yok)
                                $set('has_withholding', 0);
                            }
                        }),
                ]),

                Forms\Components\Grid::make(2)
                    ->schema([
                        Forms\Components\Select::make('tax_rate')
                            ->label('KDV Oranı')
                            ->options([
                                0 => '%0',
                                1 => '%1',
                                8 => '%8',
                                10 => '%10',
                                18 => '%18',
                                20 => '%20',
                            ])
                            ->required(fn (callable $get) => $get('is_taxable'))
                            ->native(false)
                            ->live()
                            ->afterStateUpdated(function ($state, callable $set, callable $get) {
                                if ($state === 0 || $state === null) {
                                    $set('tax_amount', null);
                                } else if ($get('amount')) {
                                    $amount = $get('amount');
                                    $taxRate = $state / 100;
                                    $netAmount = $amount / (1 + $taxRate);
                                    $set('tax_amount', round($amount - $netAmount, 2));
                                }
                            }),

                        Forms\Components\TextInput::make('tax_amount')
                            ->label('KDV Tutarı')
                            ->disabled()
                            ->numeric()
                            ->prefix(fn (callable $get) => match($get('currency')) {
                                'TRY' => '₺',
                                'USD' => '$',
                                'EUR' => '€',
                                'GBP' => '£',
                                default => '₺',
                            }),
                    ])->visible(fn (callable $get) => $get('is_taxable')),

                Forms\Components\Grid::make(2)
                    ->schema([
                        Forms\Components\Select::make('has_withholding')
                            ->label('Stopaj')
                            ->options([
                                1 => 'Var',
                                0 => 'Yok',
                            ])
                            ->default(0)
                            ->required(fn (callable $get) => $get('is_taxable'))
                            ->native(false)
                            ->live()
                            ->afterStateUpdated(function ($state, callable $set) {
                                if (!$state) {
                                    $set('withholding_rate', null);
                                    $set('withholding_amount', null);
                                }
                            }),

                        Forms\Components\TextInput::make('withholding_rate')
                            ->label('Stopaj Oranı (%)')
                            ->numeric()
                            ->required(fn (callable $get) => $get('has_withholding'))
                            ->live()
                            ->afterStateUpdated(function ($state, callable $set, callable $get) {
                                if ($state === 0 || $state === null || !$get('has_withholding')) {
                                    $set('withholding_amount', null);
                                } else if ($get('amount')) {
                                    $amount = $get('amount');
                                    $withholdingRate = floatval($state) / 100;
                                    $set('withholding_amount', round($amount * $withholdingRate, 2));
                                }
                            }),
                    ])->visible(fn (callable $get) => $get('is_taxable')),

                Forms\Components\Grid::make(1)
                    ->schema([
                        Forms\Components\TextInput::make('withholding_amount')
                            ->label('Stopaj Tutarı')
                            ->disabled()
                            ->numeric()
                            ->prefix(fn (callable $get) => match($get('currency')) {
                                'TRY' => '₺',
                                'USD' => '$',
                                'EUR' => '€',
                                'GBP' => '£',
                                default => '₺',
                            }),
                    ])->visible(fn (callable $get) => $get('is_taxable') && $get('has_withholding')),
            ])
            ->collapsible()
            ->collapsed(false);
    }

    /**
     * Form verilerini kaydeder
     * 
     * @throws \Exception Kayıt sırasında oluşabilecek hatalar
     * @return void
     */
    public function save(): void
    {
        $data = $this->form->getState();
        
        try {
            // Nakit işlemi kontrolü
            if ($data['payment_method'] === PaymentMethodEnum::CASH->value) {
                // Nakit hesabını bul veya oluştur
                $cashAccount = Account::firstOrCreate(
                    [
                        'user_id' => auth()->id(),
                        'type' => Account::TYPE_CASH,
                        'currency' => $data['currency'],
                        'status' => true,
                    ],
                    [
                        'name' => 'Nakit',
                        'balance' => 0,
                    ]
                );

                // İşlem tipine göre hesap ataması yap
                if ($data['type'] === 'income') {
                    $data['destination_account_id'] = $cashAccount->id;
                    $data['source_account_id'] = null;
                } elseif ($data['type'] === 'expense') {
                    $data['source_account_id'] = $cashAccount->id;
                    $data['destination_account_id'] = null;
                }
            } else {
                // Yeni hesap seçimini ayarla
                if (!empty($data['account_select'])) {
                    if ($data['type'] === 'income') {
                        $data['destination_account_id'] = $data['account_select'];
                        $data['source_account_id'] = null;
                    } elseif ($data['type'] === 'expense') {
                        $data['source_account_id'] = $data['account_select'];
                        $data['destination_account_id'] = null;
                    }
                }
            }

            // Düzenleme modunda
            if ($this->transaction) {
                $transactionData = TransactionData::fromArray($data);
                // boot() ile enjekte edilen servisi kullan
                $this->transactionService->update($this->transaction, $transactionData);
            } else {
                // Kredi kartı taksit kontrolü
                if ($data['payment_method'] === PaymentMethodEnum::CREDIT_CARD->value && !empty($data['installments']) && $data['installments'] > 1) {
                    if ($data['type'] !== 'expense') {
                        throw new \Exception('Taksitli işlem sadece gider olarak kaydedilebilir.');
                    }

                    $account = Account::query()
                        ->where('id', $data['account_select'])
                        ->whereNull('deleted_at')
                        ->first();
                    
                    if (!$account) {
                        throw new \Exception('Lütfen bir kredi kartı seçin.');
                    }
                    
                    if ($account->type !== Account::TYPE_CREDIT_CARD) {
                        throw new \Exception('Seçilen hesap bir kredi kartı değil.');
                    }

                    $data['source_account_id'] = $data['account_select'];
                    $data['destination_account_id'] = null;

                    $transactionData = TransactionData::fromArray($data);
                    // boot() ile enjekte edilen servisi kullan
                    $this->transactionService->create($transactionData);
                } else {
                    // Normal işlem oluşturma
                    // Normal işlem oluşturma - Abonelik bilgileri formdan alınacak
                    
                    $transactionData = TransactionData::fromArray($data);
                    // boot() ile enjekte edilen servisi kullan
                    $newTransaction = $this->transactionService->create($transactionData); // Oluşturulan işlemi al

                    // Eğer bu kopyalanmış bir işlemse (reference_id varsa), orijinal aboneliğin tarihini güncelle
                    if (!empty($data['reference_id'])) {
                        $originalSubscription = Transaction::find($data['reference_id']);
                        // Orijinal işlemin gerçekten bir abonelik olduğunu ve periyodu olduğunu kontrol et
                        if ($originalSubscription && $originalSubscription->is_subscription && $originalSubscription->subscription_period) {
                            try {
                                // Orijinal aboneliğin mevcut next_payment_date'ini kullanarak yeni tarihi hesapla
                                $newNextPaymentDate = $this->subscriptionService->calculateNextPaymentDate($originalSubscription);
                                $originalSubscription->update(['next_payment_date' => $newNextPaymentDate]);
                                Log::info('Orijinal abonelik tarihi güncellendi.', ['id' => $originalSubscription->id, 'new_date' => $newNextPaymentDate->toDateString()]);
                            } catch (\Exception $e) {
                                Log::error('Orijinal abonelik tarihi güncellenirken hata oluştu.', [
                                    'original_subscription_id' => $originalSubscription->id,
                                    'error' => $e->getMessage()
                                ]);
                                // Opsiyonel: Kullanıcıya uyarı gösterilebilir ama işlem devam etmeli.
                                // Notification::make()->warning()->title('Orijinal abonelik tarihi güncellenemedi.')->send();
                            }
                        }
                    }
                }
            }

            Notification::make()->success()->title('İşlem başarıyla kaydedildi.')->send(); // Mesaj güncellendi
            $this->redirectRoute('admin.transactions.index', navigate: true);
        } catch (\Exception $e) {
            Notification::make()
                ->danger()
                ->title('Hata!')
                ->body($e->getMessage())
                ->send();
        }
    }

    /**
     * Form işlemini iptal eder
     * 
     * @return void
     */
    public function cancel(): void
    {
        $this->redirectRoute('admin.transactions.index', navigate: true);
    }

    /**
     * Bileşenin görünümünü render eder
     * 
     * @return \Illuminate\Contracts\View\View
     */
    public function render(): View
    {
        return view('livewire.transaction.transaction-form');
    }
} 