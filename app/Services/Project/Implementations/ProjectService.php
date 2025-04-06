<?php

declare(strict_types=1);

namespace App\Services\Project\Implementations;

use App\Models\Project;
use App\Models\Board;
use App\Models\Task;
use App\Models\TaskList;
use App\Services\Project\Contracts\ProjectServiceInterface;
use App\DTOs\Project\ProjectData;
use App\DTOs\Project\BoardData;
use Illuminate\Support\Facades\DB;
use Filament\Notifications\Notification;

/**
 * Proje servisi implementasyonu
 * 
 * Proje yönetimi için gerekli metodları içerir.
 * Projelerin, boardların, görev listelerinin ve görevlerin yönetimini gerçekleştirir.
 */
final class ProjectService implements ProjectServiceInterface
{
    /**
     * Yeni bir proje oluşturur
     * 
     * @param ProjectData $data Proje verileri
     * @return Project Oluşturulan proje
     */
    public function create(ProjectData $data): Project
    {
        return DB::transaction(function () use ($data) {
            return Project::create($data->toArray());
        });
    }

    /**
     * Mevcut bir projeyi günceller
     * 
     * @param Project $project Güncellenecek proje
     * @param ProjectData $data Güncellenecek veriler
     * @return Project Güncellenmiş proje
     */
    public function update(Project $project, ProjectData $data): Project
    {
        return DB::transaction(function () use ($project, $data) {
            $project->update([
                'name' => $data->name,
                'description' => $data->description,
                'status' => $data->status,
            ]);
            return $project->fresh();
        });
    }

    /**
     * Bir projeyi siler
     * 
     * @param Project $project Silinecek proje
     */
    public function delete(Project $project): void
    {
        DB::transaction(function () use ($project) {
            $project->delete();
        });
    }

    /**
     * Yeni bir board oluşturur
     * 
     * @param BoardData $data Board verileri
     * @return Board Oluşturulan board
     */
    public function createBoard(BoardData $data): Board
    {
        return DB::transaction(function () use ($data) {
            return Board::create($data->toArray());
        });
    }

    /**
     * Projenin varsayılan boardunu getirir veya oluşturur
     * 
     * @param Project $project Proje
     * @return Board Varsayılan board
     */
    public function getOrCreateDefaultBoard(Project $project): Board
    {
        return $project->boards()->firstOrCreate(
            [],
            ['name' => 'Ana Board']
        );
    }

    /**
     * Yeni bir görev listesi oluşturur
     * 
     * @param Board $board Board
     * @param array $data Görev listesi verileri
     * @return TaskList Oluşturulan görev listesi
     */
    public function createTaskList(Board $board, array $data): TaskList
    {
        return DB::transaction(function () use ($board, $data) {
            $maxOrder = $board->taskLists()->max('order') ?? 0;
            
            return $board->taskLists()->create([
                'name' => $data['name'],
                'order' => $maxOrder + 1,
            ]);
        });
    }

    /**
     * Mevcut bir görev listesini günceller
     * 
     * @param TaskList $list Güncellenecek görev listesi
     * @param array $data Güncellenecek veriler
     * @return TaskList Güncellenmiş görev listesi
     */
    public function updateTaskList(TaskList $list, array $data): TaskList
    {
        return DB::transaction(function () use ($list, $data) {
            $list->update([
                'name' => $data['name'],
            ]);
            return $list->fresh();
        });
    }

    /**
     * Görev listelerinin sırasını günceller
     * 
     * @param array $lists Sıralanacak görev listeleri
     */
    public function reorderTaskLists(array $lists): void
    {
        if (empty($lists)) return;

        DB::transaction(function () use ($lists) {
            foreach ($lists as $index => $listData) {
                $list = TaskList::find($listData['id']);
                if (!$list) continue;

                $list->update(['order' => $index]);
            }
        });
    }

    /**
     * Yeni bir görev oluşturur
     * 
     * @param TaskList $list Görev listesi
     * @param array $data Görev verileri
     * @return Task Oluşturulan görev
     */
    public function createTask(TaskList $list, array $data): Task
    {
        return DB::transaction(function () use ($list, $data) {
            $maxOrder = $list->tasks()->max('order') ?? 0;
            
            return $list->tasks()->create([
                'title' => $data['title'],
                'content' => $data['content'] ?? null,
                'priority' => $data['priority'],
                'due_date' => $data['due_date'],
                'checklist' => $data['checklist'] ?? [],
                'assigned_to' => !empty($data['assigned_to']) ? $data['assigned_to'] : null,
                'order' => $maxOrder + 1,
                'task_list_id' => $list->id
            ]);
        });
    }

    /**
     * Mevcut bir görevi günceller
     * 
     * @param Task $task Güncellenecek görev
     * @param array $data Güncellenecek veriler
     * @return Task Güncellenmiş görev
     */
    public function updateTask(Task $task, array $data): Task
    {
        $task->update([
            'title' => $data['title'],
            'content' => $data['content'] ?? null,
            'priority' => $data['priority'],
            'due_date' => $data['due_date'],
            'checklist' => $data['checklist'] ?? [],
            'assigned_to' => !empty($data['assigned_to']) ? $data['assigned_to'] : null
        ]);

        return $task;
    }

    /**
     * Bir görevi siler
     * 
     * @param Task $task Silinecek görev
     */
    public function deleteTask(Task $task): void
    {
        DB::transaction(function () use ($task) {
            $task->delete();
        });
    }

    /**
     * Görevlerin sırasını günceller
     * 
     * @param array $tasks Sıralanacak görevler
     * @param string|null $targetListId Hedef liste ID'si
     */
    public function reorderTasks(array $tasks, ?string $targetListId = null): void
    {
        if (empty($tasks)) return;

        DB::transaction(function () use ($tasks, $targetListId) {
            foreach ($tasks as $index => $taskData) {
                $task = Task::find($taskData['id']);
                if (!$task) continue;

                $task->update([
                    'order' => $index,
                    'task_list_id' => $targetListId ?? $task->task_list_id
                ]);
            }
        });
    }
} 