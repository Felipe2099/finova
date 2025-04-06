<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Potansiyel Müşteri (Lead) modeli
 * 
 * İşletmenin potansiyel müşterilerini temsil eder.
 * Her potansiyel müşteri bir kullanıcıya atanabilir ve müşteriye dönüştürülebilir.
 * Potansiyel müşterilerin durumu, iletişim bilgileri ve dönüşüm süreci takip edilebilir.
 */
class Lead extends Model
{
    use HasFactory;

    /**
     * Doldurulabilir alanlar
     * 
     * @var array<string>
     */
    protected $fillable = [
        'name',
        'type',
        'email',
        'phone',
        'address',
        'city',
        'district',
        'source',
        'status',
        'last_contact_date',
        'next_contact_date',
        'notes',
        'assigned_to',
        'converted_at',
        'converted_to_customer_id',
        'conversion_reason',
        'user_id',
    ];

    /**
     * Veri tipleri dönüşümleri
     * 
     * @var array<string, string>
     */
    protected $casts = [
        'last_contact_date' => 'datetime',
        'next_contact_date' => 'datetime',
        'converted_at' => 'datetime',
        'status' => 'string', // Enum için
    ];

    /**
     * Model boot metodu - user_id değişimini engeller
     */
    protected static function boot()
    {
        parent::boot();

        static::updating(function ($lead) {
            // Eğer user_id değiştirilmeye çalışılıyorsa, eski değeri koru
            if ($lead->isDirty('user_id')) {
                $lead->user_id = $lead->getOriginal('user_id');
            }
        });
    }

    /**
     * Potansiyel müşterinin sahibi olan kullanıcı
     * 
     * @return BelongsTo
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Potansiyel müşteriye atanan kullanıcı
     * 
     * @return BelongsTo
     */
    public function assignedTo(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    /**
     * Potansiyel müşterinin dönüştürüldüğü müşteri
     * 
     * @return BelongsTo
     */
    public function convertedToCustomer(): BelongsTo
    {
        return $this->belongsTo(Customer::class, 'converted_to_customer_id');
    }
}