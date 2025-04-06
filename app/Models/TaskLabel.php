<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Görev-Etiket İlişki modeli
 * 
 * Görevler ve etiketler arasındaki çoka-çok ilişkiyi temsil eder.
 * Pivot tablo olarak kullanılır.
 */
class TaskLabel extends Model
{
    use HasFactory;

    /**
     * Doldurulabilir alanlar
     * 
     * @var array<string>
     */
    protected $fillable = [
        'task_id',
        'label_id',
    ];

    /**
     * İlişkili görev
     * 
     * @return BelongsTo
     */
    public function task()
    {
        return $this->belongsTo(Task::class);
    }

    /**
     * İlişkili etiket
     * 
     * @return BelongsTo
     */
    public function label()
    {
        return $this->belongsTo(Label::class);
    }
}