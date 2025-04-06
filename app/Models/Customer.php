<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Müşteri modeli
 * 
 * İşletmenin müşterilerini temsil eder.
 * Her müşteri bir kullanıcıya ve müşteri grubuna ait olabilir.
 * Müşterilerin gelir işlemleri ve notları takip edilebilir.
 */
class Customer extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * Doldurulabilir alanlar
     * 
     * @var array<string>
     */
    protected $fillable = [
        'name',
        'type',
        'tax_number',
        'tax_office',
        'email',
        'phone',
        'address',
        'city',
        'district',
        'description',
        'status',
        'customer_group_id',
        'user_id',
    ];

    /**
     * Veri tipleri dönüşümleri
     * 
     * @var array<string, string>
     */
    protected $casts = [
        'status' => 'boolean'
    ];

    /**
     * Model boot metodu - user_id değişimini engeller
     */
    protected static function boot()
    {
        parent::boot();

        static::updating(function ($customer) {
            // Eğer user_id değiştirilmeye çalışılıyorsa, eski değeri koru
            if ($customer->isDirty('user_id')) {
                $customer->user_id = $customer->getOriginal('user_id');
            }
        });
    }

    /**
     * Müşterinin sahibi olan kullanıcı
     * 
     * @return BelongsTo
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Müşterinin ait olduğu grup
     * 
     * @return BelongsTo
     */
    public function customerGroup(): BelongsTo
    {
        return $this->belongsTo(CustomerGroup::class);
    }

    /**
     * Müşteriye ait gelir işlemleri
     * 
     * @return HasMany
     */
    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class, 'customer_id')
            ->where('type', 'income')
            ->latest('date');
    }

    /**
     * Müşteriye ait notlar
     * 
     * @return HasMany
     */
    public function notes(): HasMany
    {
        return $this->hasMany(CustomerNote::class);
    }

    public function credentials(): HasMany
    {
        return $this->hasMany(CustomerCredential::class);
    }

    public function agreements(): HasMany
    {
        return $this->hasMany(CustomerAgreement::class);
    }
}