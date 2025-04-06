<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Hesap modeli
 * 
 * Kullanıcıların finansal hesaplarını temsil eder.
 * Banka hesapları, kredi kartları, kripto cüzdanları, sanal POS ve nakit hesaplarını yönetir.
 */
class Account extends Model
{
    use HasFactory, SoftDeletes;

    /** @var string Banka hesabı tipi */
    const TYPE_BANK_ACCOUNT = 'bank_account';

    /** @var string Kredi kartı tipi */
    const TYPE_CREDIT_CARD = 'credit_card';

    /** @var string Kripto cüzdan tipi */
    const TYPE_CRYPTO_WALLET = 'crypto_wallet';

    /** @var string Sanal POS tipi */
    const TYPE_VIRTUAL_POS = 'virtual_pos';

    /** @var string Nakit tipi */
    const TYPE_CASH = 'cash';

    /**
     * Doldurulabilir alanlar
     * 
     * @var array<string>
     */
    protected $fillable = [
        'user_id',
        'name',
        'type',
        'currency',
        'balance',
        'details',
        'status',
    ];

    /**
     * Veri tipleri dönüşümleri
     * 
     * @var array<string, string>
     */
    protected $casts = [
        'balance' => 'float',
        'details' => 'array',
        'status' => 'boolean',
    ];

    /**
     * Hesabın sahibi olan kullanıcı
     * 
     * @return BelongsTo
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Hesaptan yapılan işlemler
     * 
     * @return HasMany
     */
    public function sourceTransactions(): HasMany
    {
        return $this->hasMany(Transaction::class, 'source_account_id');
    }

    /**
     * Hesaba yapılan işlemler
     * 
     * @return HasMany
     */
    public function destinationTransactions(): HasMany
    {
        return $this->hasMany(Transaction::class, 'destination_account_id');
    }

    /**
     * Hesabın tüm işlemleri
     * 
     * @return HasMany
     */
    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class, 'source_account_id')
            ->orWhere('destination_account_id', $this->id)
            ->orderBy('date', 'desc');
    }

    /**
     * Hesabın TRY cinsinden bakiyesini hesaplar
     * 
     * @return float TRY cinsinden bakiye
     */
    public function calculateTryBalance(): float
    {
        return $this->transactions()
            ->where(function ($query) {
                $query->where('source_account_id', $this->id)
                    ->orWhere('destination_account_id', $this->id);
            })
            ->get()
            ->reduce(function ($balance, $transaction) {
                if ($transaction->source_account_id === $this->id) {
                    return $balance - $transaction->try_equivalent;
                }
                return $balance + $transaction->try_equivalent;
            }, 0);
    }

    /**
     * Bakiyeyi formatlanmış şekilde döndürür
     * 
     * @return string Formatlanmış bakiye
     */
    public function getFormattedBalanceAttribute(): string
    {
        $balance = $this->balance;
        return number_format($balance, 2, ',', '.') . ' ' . $this->currency;
    }
}