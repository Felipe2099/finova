<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\EloquentSortable\Sortable;
use Spatie\EloquentSortable\SortableTrait;

/**
 * Görev Listesi modeli
 * 
 * Proje boardlarında bulunan görev listelerini temsil eder.
 * Her liste bir boarda ait olup, sıralanabilir görevler içerir.
 */
class TaskList extends Model implements Sortable
{
    use HasFactory, SortableTrait;

    /**
     * Doldurulabilir alanlar
     * 
     * @var array<string>
     */
    protected $fillable = ['board_id', 'name', 'order'];

    /**
     * Sıralama ayarları
     * 
     * @var array<string, mixed>
     */
    public $sortable = [
        'order_column_name' => 'order',
        'sort_when_creating' => true,
    ];

    /**
     * Listenin ait olduğu board
     * 
     * @return BelongsTo
     */
    public function board(): BelongsTo
    {
        return $this->belongsTo(Board::class);
    }

    /**
     * Listeye ait görevler
     * 
     * @return HasMany
     */
    public function tasks(): HasMany
    {
        return $this->hasMany(Task::class)->orderBy('order');
    }
}