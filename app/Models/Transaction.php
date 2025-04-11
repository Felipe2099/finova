<?php

namespace App\Models;

use App\Enums\PaymentMethodEnum;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Services\Commission\CommissionService;
use App\Enums\TransactionTypeEnum;
/**
 * İşlem modeli
 * 
 * Finansal işlemleri temsil eder (gelir, gider, transfer, ödeme vb.).
 * Her işlem bir kullanıcıya ait olup, çeşitli hesaplar, müşteriler veya tedarikçilerle ilişkilendirilebilir.
 * İşlemler için vergi, stopaj, taksit ve abonelik özellikleri desteklenir.
 */
class Transaction extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * İşlem tipleri
     */
    const TYPE_INCOME = 'income';
    const TYPE_EXPENSE = 'expense';
    const TYPE_TRANSFER = 'transfer';
    const TYPE_LOAN_PAYMENT = 'loan_payment';
    const TYPE_DEBT_PAYMENT = 'debt_payment';
    const TYPE_CREDIT_PAYMENT = 'payment';
    const TYPE_ATM_DEPOSIT = 'atm_deposit';
    const TYPE_ATM_WITHDRAW = 'atm_withdraw';
    

    /**
     * Ödeme tipleri
     */
    const PAYMENT_CASH = 'cash';
    const PAYMENT_BANK_TRANSFER = 'bank_transfer';
    const PAYMENT_CREDIT_CARD = 'credit_card';

    /**
     * Veri tipleri dönüşümleri
     *
     * @var array<string, string>
     */
    protected $casts = [
        'date' => 'date',
        'amount' => 'decimal:2',
        'exchange_rate' => 'decimal:6',
        'try_equivalent' => 'decimal:2',
        'fee_amount' => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'withholding_amount' => 'decimal:2',
        'monthly_amount' => 'decimal:2',
        'next_payment_date' => 'date',
        'is_subscription' => 'boolean',
        'auto_renew' => 'boolean',
        'is_taxable' => 'boolean',
        'has_withholding' => 'boolean',
        'payment_method' => PaymentMethodEnum::class,
        'reference_id' => 'string',
    ];

    /**
     * Ödeme yöntemi enum değerleri
     */
    public const PAYMENT_METHOD_CASH = PaymentMethodEnum::CASH->value;
    public const PAYMENT_METHOD_BANK = PaymentMethodEnum::BANK->value;
    public const PAYMENT_METHOD_CREDIT_CARD = PaymentMethodEnum::CREDIT_CARD->value;
    public const PAYMENT_METHOD_CRYPTO = PaymentMethodEnum::CRYPTO->value;
    public const PAYMENT_METHOD_VIRTUAL_POS = PaymentMethodEnum::VIRTUAL_POS->value;

    /**
     * Ödeme yöntemi seçeneklerini döndürür
     * 
     * @return array<string, string>
     */
    public static function paymentMethods(): array
    {
        return PaymentMethodEnum::toArray();
    }

    /**
     * Doldurulabilir alanlar
     * 
     * @var array<string>
     */
    protected $fillable = [
        'user_id',
        'category_id',
        'source_account_id',
        'destination_account_id',
        'customer_id',
        'supplier_id',
        'type',
        'amount',
        'currency',
        'exchange_rate',
        'try_equivalent',
        'fee_amount',
        'date',
        'payment_method',
        'description',
        'installments',
        'remaining_installments',
        'monthly_amount',
        'next_payment_date',
        'is_subscription',
        'subscription_period',
        'auto_renew',
        'is_taxable',
        'tax_rate',
        'tax_amount',
        'has_withholding',
        'withholding_rate',
        'withholding_amount',
        'reference_id',
        'status',
    ];

    /**
     * İşlemin sahibi olan kullanıcı
     * 
     * @return BelongsTo
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * İşlemin kategorisi
     * 
     * @return BelongsTo
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    /**
     * İşlemin kaynak hesabı
     * 
     * @return BelongsTo
     */
    public function sourceAccount(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'source_account_id');
    }

    /**
     * İşlemin hedef hesabı
     * 
     * @return BelongsTo
     */
    public function destinationAccount(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'destination_account_id');
    }

    /**
     * İşlemin müşterisi
     * 
     * @return BelongsTo
     */
    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    /**
     * İşlemin tedarikçisi
     * 
     * @return BelongsTo
     */
    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    /**
     * Referans işlem
     * 
     * @return BelongsTo
     */
    public function referenceTransaction(): BelongsTo
    {
        return $this->belongsTo(Transaction::class, 'reference_id');
    }

    /**
     * İlişkili işlemler
     * 
     * @return HasMany
     */
    public function relatedTransactions(): HasMany
    {
        return $this->hasMany(Transaction::class, 'reference_id');
    }

    /**
     * Transfer hesabı
     * 
     * @return BelongsTo
     */
    public function transferAccount()
    {
        return $this->belongsTo(Account::class, 'transfer_account_id');
    }

    /**
     * İlişkili transfer işlemi
     * 
     * @return HasOne
     */
    public function relatedTransfer()
    {
        return $this->hasOne(Transaction::class, 'reference_id', 'reference_id')
            ->where('id', '!=', $this->id);
    }

    /**
     * Model olayları için observer metodları
     * 
     * @return void
     */
    protected static function booted()
    {
        static::creating(function ($transaction) {
            if (!isset($transaction->status)) {
                $transaction->status = 'completed';
            }
            
            // TRY eşdeğeri hesaplama
            if (!isset($transaction->try_equivalent)) {
                if ($transaction->currency !== 'TRY') {
                    $exchangeRate = $transaction->exchange_rate ?? 1;
                    $transaction->try_equivalent = $transaction->amount * $exchangeRate;
                } else {
                    $transaction->try_equivalent = $transaction->amount;
                }
            }
            
            // Diğer hesaplamalar...
            if ($transaction->is_taxable && $transaction->tax_rate) {
                $taxRate = $transaction->tax_rate / 100;
                $netAmount = $transaction->amount / (1 + $taxRate);
                $transaction->tax_amount = round($transaction->amount - $netAmount, 2);
            }
            
            if ($transaction->is_taxable && $transaction->has_withholding && $transaction->withholding_rate) {
                $withholdingRate = $transaction->withholding_rate / 100;
                $transaction->withholding_amount = round($transaction->amount * $withholdingRate, 2);
            }
            
            if (!isset($transaction->date)) {
                $transaction->date = $transaction->transaction_date ?? now();
            }
        });

        static::updating(function ($transaction) {
            if ($transaction->currency !== 'TRY') {
                $transaction->try_equivalent = $transaction->amount * ($transaction->exchange_rate ?? 1);
            } else {
                $transaction->try_equivalent = $transaction->amount;
            }
            if ($transaction->is_taxable && $transaction->tax_rate) {
                $taxRate = $transaction->tax_rate / 100;
                $netAmount = $transaction->amount / (1 + $taxRate);
                $transaction->tax_amount = round($transaction->amount - $netAmount, 2);
            } else {
                $transaction->tax_amount = null;
            }
            if ($transaction->is_taxable && $transaction->has_withholding && $transaction->withholding_rate) {
                $withholdingRate = $transaction->withholding_rate / 100;
                $transaction->withholding_amount = round($transaction->amount * $withholdingRate, 2);
            } else {
                $transaction->withholding_amount = null;
            }

            // Eğer gelir işlemi ise ve miktar değiştiyse komisyonu güncelle
            if ($transaction->type === TransactionTypeEnum::INCOME->value && $transaction->isDirty('amount')) {
                if ($transaction->commission) {
                    $commission = $transaction->commission;
                    $commission->commission_amount = $transaction->amount * ($commission->commission_rate / 100);
                    $commission->save();
                }
            }
        });

        static::created(function ($transaction) {
            // Sadece gelir işlemlerinde komisyon hesapla
            if ($transaction->type === TransactionTypeEnum::INCOME->value) {
                app(CommissionService::class)->createCommissionForTransaction($transaction);
            }
        });

        static::deleted(function ($transaction) {
            // İşlem silindiğinde ilişkili komisyonu da sil
            if ($transaction->commission) {
                $transaction->commission->delete();
            }
        });
    }

    /**
     * İşlemin komisyonu
     */
    public function commission(): HasOne
    {
        return $this->hasOne(Commission::class);
    }
}