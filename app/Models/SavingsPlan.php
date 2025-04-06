<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Tasarruf Planı modeli
 * 
 * Kullanıcıların tasarruf hedeflerini ve ilerleme durumlarını temsil eder.
 * Her tasarruf planı bir kullanıcıya ait olup, hedef miktar ve birikmiş miktar takip edilebilir.
 */
class SavingsPlan extends Model
{
    use HasFactory;

    /**
     * Doldurulabilir alanlar
     * 
     * @var array<string>
     */
    protected $fillable = [
        'user_id',
        'goal_name',
        'target_amount',
        'saved_amount',
        'target_date',
        'status',
    ];

    /**
     * Veri tipleri dönüşümleri
     * 
     * @var array<string, string>
     */
    protected $casts = [
        'target_date' => 'date',
        'target_amount' => 'decimal:2',
        'saved_amount' => 'decimal:2',
    ];

    /**
     * Tasarruf planının sahibi olan kullanıcı
     * 
     * @return BelongsTo
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}