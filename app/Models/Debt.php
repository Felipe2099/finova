<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Borç/Alacak modeli
 * 
 * İşletmenin müşterilerden alacaklarını ve tedarikçilere borçlarını temsil eder.
 * Her borç/alacak bir kullanıcıya ait olup, bir müşteri veya tedarikçi ile ilişkilidir.
 * Borç/alacak işlemleri ve ödemeleri takip edilebilir.
 */
final class Debt extends Model
{
    use SoftDeletes;

    /**
     * Doldurulabilir alanlar
     * 
     * @var array<string>
     */
    protected $fillable = [
        'user_id',
        'customer_id',
        'supplier_id',
        'type',
        'amount',
        'currency',
        'buy_price',
        'sell_price',
        'profit_loss',
        'description',
        'date',
        'due_date',
        'status',
    ];

    /**
     * Veri tipleri dönüşümleri
     * 
     * @var array<string, string>
     */
    protected $casts = [
        'amount' => 'decimal:2',
        'buy_price' => 'decimal:2',
        'sell_price' => 'decimal:2',
        'profit_loss' => 'decimal:2',
        'date' => 'date',
        'due_date' => 'date',
    ];

    /**
     * Borç/Alacağın sahibi olan kullanıcı
     * 
     * @return BelongsTo
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Borç/Alacağın ilişkili olduğu müşteri
     * 
     * @return BelongsTo
     */
    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    /**
     * Borç/Alacağın ilişkili olduğu tedarikçi
     * 
     * @return BelongsTo
     */
    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    /**
     * Borç/Alacağa ait işlemler
     * 
     * @return HasMany
     */
    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class, 'reference_id');
    }

    /**
     * Kalan borç/alacak tutarını hesaplar
     * 
     * @return float Kalan tutar
     */
    public function getRemainingAmountAttribute(): float
    {
        $paidAmount = $this->transactions()
            ->where('status', 'completed')
            ->sum('amount');

        return $this->amount - $paidAmount;
    }

    /**
     * Kar/zarar tutarını hesaplar
     * 
     * @return float Kar/zarar tutarı
     */
    public function getProfitLossAttribute(): float
    {
        if (!$this->buy_price || !$this->sell_price) {
            return 0;
        }

        return ($this->sell_price - $this->buy_price) * $this->amount;
    }
} 