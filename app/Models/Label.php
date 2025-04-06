<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Etiket modeli
 * 
 * Görevleri kategorize etmek ve gruplamak için kullanılan etiketleri temsil eder.
 * Her etiket bir renk ile ilişkilendirilir ve birden fazla göreve atanabilir.
 */
class Label extends Model
{
    use HasFactory;

    /**
     * Doldurulabilir alanlar
     * 
     * @var array<string>
     */
    protected $fillable = ['name', 'color'];

    /**
     * Etiketin atandığı görevler
     * 
     * @return BelongsToMany
     */
    public function tasks()
    {
        return $this->belongsToMany(Task::class, 'task_labels');
    }
}