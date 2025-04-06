<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Teklif modeli
 * 
 * Müşterilere sunulan teklifleri temsil eder.
 * Her teklif bir müşteriye ait olup, birden fazla teklif kalemi içerebilir.
 * Teklifin geçerlilik süresi, ödeme koşulları ve toplam tutarları takip edilebilir.
 */
class Proposal extends Model
{
    use HasFactory;

    /**
     * Doldurulabilir alanlar
     * 
     * @var array<string>
     */
    protected $fillable = [
        'customer_id',
        'number',
        'title',
        'content',
        'valid_until',
        'status',
        'payment_terms',
        'notes',
        'subtotal',
        'tax_total',
        'discount_total',
        'total',
        'currency',
        'created_by',
    ];

    /**
     * Veri tipleri dönüşümleri
     * 
     * @var array<string, string>
     */
    protected $casts = [
        'valid_until' => 'date',
        'subtotal' => 'float',
        'tax_total' => 'float',
        'discount_total' => 'float',
        'total' => 'float',
    ];

    /**
     * Teklifin ait olduğu müşteri
     * 
     * @return BelongsTo
     */
    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    /**
     * Teklifi oluşturan kullanıcı
     * 
     * @return BelongsTo
     */
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Teklife ait kalemler
     * 
     * @return HasMany
     */
    public function items()
    {
        return $this->hasMany(ProposalItem::class);
    }
}