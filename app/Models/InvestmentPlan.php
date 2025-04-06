<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Yatırım Planı modeli
 * 
 * Kullanıcıların yatırım planlarını ve portföylerini temsil eder.
 * Her yatırım planı bir kullanıcıya ait olup, farklı yatırım tiplerinde olabilir.
 * Yatırım tutarı, mevcut değer ve yatırım tarihi takip edilebilir.
 */
final class InvestmentPlan extends Model
{
    use HasFactory;

    /**
     * Doldurulabilir alanlar
     * 
     * @var array<string>
     */
    protected $fillable = [
        'user_id',
        'investment_name',
        'invested_amount',
        'current_value',
        'investment_type',
        'investment_date',
    ];

    /**
     * Veri tipleri dönüşümleri
     * 
     * @var array<string, string>
     */
    protected $casts = [
        'invested_amount' => 'decimal:2',
        'current_value' => 'decimal:2',
        'investment_date' => 'date',
    ];

    /**
     * Yatırım planının sahibi olan kullanıcı
     * 
     * @return BelongsTo
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}