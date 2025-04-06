<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

/**
 * Bütçe modeli
 * 
 * Kullanıcıların bütçe planlarını temsil eder.
 * Her bütçe bir kategoriye ait olup, belirli bir dönem için planlanır.
 */
class Budget extends Model
{
    use HasFactory, SoftDeletes;
    
    /**
     * Doldurulabilir alanlar
     * 
     * @var array<string>
     */
    protected $fillable = [
        'user_id',
        'category_id',
        'name',
        'description',
        'amount',
        'start_date',
        'end_date',
        'period',
        'status'
    ];
    
    /**
     * Veri tipleri dönüşümleri
     * 
     * @var array<string, string>
     */
    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'amount' => 'decimal:2',
        'status' => 'boolean'
    ];
    
    /**
     * Bütçenin sahibi olan kullanıcı
     * 
     * @return BelongsTo
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
    
    /**
     * Bütçenin ait olduğu kategori
     * 
     * @return BelongsTo
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }
}
