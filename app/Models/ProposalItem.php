<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Teklif Kalemi modeli
 * 
 * Tekliflerde yer alan ürün veya hizmet kalemlerini temsil eder.
 * Her kalem bir teklife ait olup, fiyat, miktar, vergi ve indirim bilgilerini içerir.
 */
class ProposalItem extends Model
{
    use HasFactory;

    /**
     * Doldurulabilir alanlar
     * 
     * @var array<string>
     */
    protected $fillable = [
        'proposal_id',
        'name',
        'description',
        'price',
        'quantity',
        'unit',
        'discount',
        'tax_rate',
        'tax_included',
        'total',
    ];

    /**
     * Veri tipleri dönüşümleri
     * 
     * @var array<string, string>
     */
    protected $casts = [
        'price' => 'float',
        'quantity' => 'integer',
        'discount' => 'float',
        'tax_rate' => 'float',
        'tax_included' => 'boolean',
        'total' => 'float',
    ];

    /**
     * Kalemin ait olduğu teklif
     * 
     * @return BelongsTo
     */
    public function proposal()
    {
        return $this->belongsTo(Proposal::class);
    }
}