<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Factories\HasFactory;

/**
 * Board modeli
 * 
 * Proje yönetiminde kullanılan boardları temsil eder.
 * Her board bir projeye ait olup, birden fazla görev listesi içerebilir.
 */
class Board extends Model
{
    use HasFactory;

    /**
     * Doldurulabilir alanlar
     * 
     * @var array<string>
     */
    protected $fillable = ['project_id', 'name'];

    /**
     * Boardun ait olduğu proje
     * 
     * @return BelongsTo
     */
    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    /**
     * Boarda ait görev listeleri
     * 
     * @return HasMany
     */
    public function taskLists(): HasMany
    {
        return $this->hasMany(TaskList::class)->orderBy('order');
    }
}