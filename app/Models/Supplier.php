<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Tedarikçi modeli
 * 
 * İşletmenin tedarikçilerini temsil eder.
 * Her tedarikçi için iletişim bilgileri ve borç kayıtları tutulabilir.
 */
class Supplier extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * Doldurulabilir alanlar
     * 
     * @var array<string>
     */
    protected $fillable = [
        'name',
        'contact_name',
        'phone',
        'email',
        'address',
        'notes',
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
     * Tedarikçiye ait borçlar
     * 
     * @return HasMany
     */
    public function debts(): HasMany
    {
        return $this->hasMany(Debt::class, 'supplier_id');
    }
}