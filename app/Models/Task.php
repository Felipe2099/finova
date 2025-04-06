<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Spatie\EloquentSortable\Sortable;
use Spatie\EloquentSortable\SortableTrait;

/**
 * Görev modeli
 * 
 * Proje yönetiminde kullanılan görevleri temsil eder.
 * Her görev bir görev listesine ait olup, öncelik ve son tarih bilgisi içerebilir.
 * Görevler sıralanabilir ve etiketlenebilir özelliktedir.
 */
class Task extends Model implements Sortable
{
    use HasFactory, SortableTrait;

    /**
     * Doldurulabilir alanlar
     * 
     * @var array<string>
     */
    protected $fillable = [
        'title',
        'content',
        'checklist',
        'priority',
        'due_date',
        'order',
        'assigned_to',
        'task_list_id'
    ];

    /**
     * Veri tipleri dönüşümleri
     * 
     * @var array<string, string>
     */
    protected $casts = [
        'content' => 'array',
        'checklist' => 'array',
        'due_date' => 'datetime',
        'completed' => 'boolean',
    ];

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
     * Görevin ait olduğu görev listesi
     * 
     * @return BelongsTo
     */
    public function taskList(): BelongsTo
    {
        return $this->belongsTo(TaskList::class);
    }

    /**
     * Göreve atanan kullanıcı
     * 
     * @return BelongsTo
     */
    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    /**
     * Göreve atanan etiketler
     * 
     * @return BelongsToMany
     */
    public function labels(): BelongsToMany
    {
        return $this->belongsToMany(Label::class, 'task_labels');
    }

    /**
     * Görev doğrulama kuralları
     * 
     * @return array<string, array<string>>
     */
    public static function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:2000'],
            'priority' => ['required', 'in:low,medium,high'],
            'due_date' => ['nullable', 'date'],
        ];
    }
}