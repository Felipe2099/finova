<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Kategori modeli
 * 
 * İşlemlerin kategorilerini temsil eder.
 * Her kategori bir kullanıcıya ait olup, belirli bir tipte olabilir.
 */
class Category extends Model
{
    use HasFactory;

    /**
     * Doldurulabilir alanlar
     * 
     * @var array<string>
     */
    protected $fillable = [
        'user_id',
        'type',
        'name',
        'color',
        'status',
    ];

    /**
     * Veri tipleri dönüşümleri
     * 
     * @var array<string, string>
     */
    protected $casts = [
        'status' => 'boolean',
    ];

    /**
     * Kategorinin sahibi olan kullanıcı
     * 
     * @return BelongsTo
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}