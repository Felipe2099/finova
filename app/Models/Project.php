<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * Proje modeli
 * 
 * Proje yönetimi için kullanılan projeleri temsil eder.
 * Her proje bir kullanıcı tarafından oluşturulur ve bir veya daha fazla board içerebilir.
 */
class Project extends Model
{
    use HasFactory;

    /**
     * Doldurulabilir alanlar
     * 
     * @var array<string>
     */
    protected $fillable = ['name', 'description', 'status', 'created_by'];

    /**
     * Model başlatıldığında çalışacak metodlar
     * 
     * @return void
     */
    protected static function booted()
    {
        static::created(function ($project) {
            $project->board()->create(['name' => 'Ana Board']);
        });
    }

    /**
     * Projeyi oluşturan kullanıcı
     * 
     * @return BelongsTo
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Projeye ait boardlar
     * 
     * @return HasMany
     */
    public function boards(): HasMany
    {
        return $this->hasMany(Board::class);
    }

    /**
     * Projenin ana boardu
     * 
     * @return HasOne
     */
    public function board(): HasOne
    {
        return $this->hasOne(Board::class);
    }
}