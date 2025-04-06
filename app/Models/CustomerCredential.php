<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Database\Eloquent\Factories\HasFactory;

/**
 * Müşteri Hassas Bilgi Modeli
 * 
 * Bu model, müşterilere ait hassas bilgileri (domain, hosting, sunucu vb.) yönetir.
 * Özellikler:
 * - Hassas bilgileri şifreli olarak saklama
 * - Bilgi ekleme/düzenleme/silme
 * - Bilgi geçmişi takibi
 * - Kullanıcı bazlı yetkilendirme
 * 
 * @package App\Models
 */
class CustomerCredential extends Model
{
    use SoftDeletes, HasFactory;

    /** @var array Doldurulabilir alanlar */
    protected $fillable = [
        'user_id',
        'customer_id',
        'name',
        'value',
        'status',
    ];

    /** @var array JSON olarak saklanacak alanlar */
    protected $casts = [
        'status' => 'boolean',
    ];

    /** @var array Şifrelenecek alanlar */
    protected $encryptable = [
        'value',
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

    /**
     * Şifreleme işlemi öncesi
     * 
     * @return void
     */
    protected static function boot()
    {
        parent::boot();

        static::saving(function ($model) {
            foreach ($model->encryptable as $field) {
                if (isset($model->attributes[$field])) {
                    // Array'i önce JSON'a, sonra şifrele
                    $model->attributes[$field] = encrypt(json_encode($model->attributes[$field]));
                }
            }
        });

        static::retrieved(function ($model) {
            foreach ($model->encryptable as $field) {
                if (isset($model->attributes[$field])) {
                    try {
                        // Şifreyi çöz ve JSON'dan array'e çevir
                        $decrypted = decrypt($model->attributes[$field]);
                        $model->attributes[$field] = json_decode($decrypted, true) ?: [];
                    } catch (\Exception $e) {
                        $model->attributes[$field] = [];
                    }
                }
            }
        });
    }
} 