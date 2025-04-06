<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Ayar modeli
 * 
 * Uygulama genelinde kullanılan ayarları temsil eder.
 * Ayarlar grup bazlı olarak saklanır ve farklı tiplerde değerler içerebilir.
 * Her ayar bir anahtar-değer çifti olarak saklanır.
 */
class Setting extends Model
{
    use HasFactory;

    /**
     * Doldurulabilir alanlar
     * 
     * @var array<string>
     */
    protected $fillable = [
        'group',
        'key',
        'value',
        'type',
    ];

    /**
     * Veri tipleri dönüşümleri
     * 
     * @var array<string, string>
     */
    protected $casts = [
        'value' => 'array',
    ];
}