<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Kredi modeli
 * 
 * Kullanıcıların kredi işlemlerini temsil eder.
 * Her kredi bir kullanıcıya ait olup, taksitli ödemeler ve ödeme planı içerebilir.
 * Kredi durumu, kalan tutar ve ödeme tarihleri takip edilebilir.
 */
class Loan extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * Doldurulabilir alanlar
     * 
     * @var array<string>
     */
    protected $fillable = [
        'user_id',
        'bank_name',
        'loan_type',
        'amount',
        'monthly_payment',
        'installments',
        'remaining_installments',
        'start_date',
        'next_payment_date',
        'due_date',
        'remaining_amount',
        'status',
        'notes',
    ];

    /**
     * Veri tipleri dönüşümleri
     * 
     * @var array<string, string>
     */
    protected $casts = [
        'amount' => 'float',
        'monthly_payment' => 'float',
        'remaining_amount' => 'float',
        'start_date' => 'date',
        'next_payment_date' => 'date',
        'due_date' => 'date',
    ];

    /**
     * Kredinin sahibi olan kullanıcı
     * 
     * @return BelongsTo
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
    
    /**
     * Krediye ait işlemler
     * 
     * @return HasMany
     */
    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class, 'related_id')
            ->where('related_type', self::class);
    }
    
    /**
     * Krediye ait ödemeler
     * 
     * @return HasMany
     */
    public function payments(): HasMany
    {
        return $this->hasMany(Transaction::class, 'related_id')
            ->where('related_type', self::class)
            ->where('category', 'loan_payment')
            ->orderBy('transaction_date', 'asc');
    }
}