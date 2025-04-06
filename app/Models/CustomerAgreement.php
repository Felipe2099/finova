<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

/**
 * Müşteri Anlaşma Modeli
 * 
 * Bu model, müşterilerle yapılan anlaşmaları ve ödemeleri yönetir.
 * Özellikler:
 * - Anlaşma detayları (ad, açıklama, tutar)
 * - Ödeme takibi (başlangıç tarihi, sonraki ödeme tarihi)
 * - Anlaşma durumu (aktif, tamamlandı, iptal)
 * - Notlar ve geçmiş
 * 
 * @package App\Models
 */
class CustomerAgreement extends Model
{
    use SoftDeletes, HasFactory;

    /** @var array Doldurulabilir alanlar */
    protected $fillable = [
        'user_id',
        'customer_id',
        'name',
        'description',
        'amount',
        'start_date',
        'next_payment_date',
        'status',
        'notes',
    ];

    /** @var array JSON olarak saklanacak alanlar */
    protected $casts = [
        'amount' => 'decimal:2',
        'start_date' => 'date',
        'next_payment_date' => 'date',
        'status' => 'string',
    ];

    public const STATUS_ACTIVE = 'aktif';
    public const STATUS_COMPLETED = 'tamamlandi';
    public const STATUS_CANCELLED = 'iptal';

    public const STATUSES = [
        self::STATUS_ACTIVE => 'Aktif',
        self::STATUS_COMPLETED => 'Tamamlandı',
        self::STATUS_CANCELLED => 'İptal',
    ];

    /**
     * Müşteri ilişkisi
     * 
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    /**
     * Kullanıcı ilişkisi
     * 
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
} 