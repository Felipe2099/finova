<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;

/**
 * Kullanıcı modeli
 * 
 * Sistemdeki kullanıcıları temsil eder.
 * Her kullanıcı için kimlik doğrulama ve yetkilendirme özellikleri sağlanır.
 * Spatie/Permission paketi ile rol tabanlı yetkilendirme desteklenir.
 * Yumuşak silme (soft delete) özelliği ve komisyon yönetimi desteği bulunur.
 */
class User extends Authenticatable
{
    use HasFactory, Notifiable, HasRoles, SoftDeletes;

    /**
     * Doldurulabilir alanlar
     * 
     * @var array<string>
     */
    protected $fillable = [
        'name',
        'email',
        'phone',
        'password',
        'status',
        'has_commission',
        'commission_rate',
    ];

    /**
     * Gizlenecek alanlar
     * 
     * @var array<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Veri tipleri dönüşümleri
     * 
     * @var array<string, string>
     */
    protected $casts = [
        'status' => 'boolean',
        'has_commission' => 'boolean',
        'commission_rate' => 'decimal:2',
        'password' => 'hashed',
    ];

    /**
     * Kullanıcının komisyona uygun olup olmadığını kontrol eder
     *
     * @return bool
     */
    public function isEligibleForCommission(): bool
    {
        return $this->has_commission && $this->commission_rate > 0;
    }

    /**
     * Get the AI conversations for the user.
     */
    public function aiConversations()
    {
        return $this->hasMany(AIConversation::class);
    }

    /**
     * Get the transactions for the user.
     */
    public function transactions()
    {
        return $this->hasMany(Transaction::class);
    }

}