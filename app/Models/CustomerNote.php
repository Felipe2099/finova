<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Müşteri Notu modeli
 * 
 * Müşterilerle ilgili notları ve aktiviteleri temsil eder.
 * Her not bir müşteriye ve kullanıcıya ait olup, belirli bir tipte olabilir.
 */
class CustomerNote extends Model implements Note
{
    use HasFactory;

    /**
     * Doldurulabilir alanlar
     * 
     * @var array<string>
     */
    protected $fillable = [
        'customer_id',
        'user_id',
        'assigned_user_id',
        'content',
        'type',
        'activity_date',
    ];

    /**
     * Veri tipleri dönüşümleri
     * 
     * @var array<string, string>
     */
    protected $casts = [
        'activity_date' => 'datetime',
    ];

    /**
     * Notun ait olduğu müşteri
     * 
     * @return BelongsTo
     */
    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    /**
     * Notu oluşturan kullanıcı
     * 
     * @return BelongsTo
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Nota atanan kullanıcı
     * 
     * @return BelongsTo
     */
    public function assignedUser()
    {
        return $this->belongsTo(User::class, 'assigned_user_id');
    }
}