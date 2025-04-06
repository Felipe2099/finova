<?php

declare(strict_types=1);

namespace App\Services\Project\Contracts;

use App\Models\Project;
use App\Models\Board;
use App\Models\Task;
use App\Models\TaskList;
use App\DTOs\Project\ProjectData;
use App\DTOs\Project\BoardData;

/**
 * Proje servisi arayüzü
 * 
 * Proje yönetimi için gerekli metodları tanımlar.
 * Projelerin, boardların, görev listelerinin ve görevlerin yönetimini içerir.
 */
interface ProjectServiceInterface
{
    /**
     * Yeni bir proje oluşturur
     * 
     * @param ProjectData $data Proje verileri
     * @return Project Oluşturulan proje
     */
    public function create(ProjectData $data): Project;

    /**
     * Mevcut bir projeyi günceller
     * 
     * @param Project $project Güncellenecek proje
     * @param ProjectData $data Güncellenecek veriler
     * @return Project Güncellenmiş proje
     */
    public function update(Project $project, ProjectData $data): Project;

    /**
     * Bir projeyi siler
     * 
     * @param Project $project Silinecek proje
     */
    public function delete(Project $project): void;

    /**
     * Yeni bir board oluşturur
     * 
     * @param BoardData $data Board verileri
     * @return Board Oluşturulan board
     */
    public function createBoard(BoardData $data): Board;

    /**
     * Projenin varsayılan boardunu getirir veya oluşturur
     * 
     * @param Project $project Proje
     * @return Board Varsayılan board
     */
    public function getOrCreateDefaultBoard(Project $project): Board;

    /**
     * Yeni bir görev listesi oluşturur
     * 
     * @param Board $board Board
     * @param array $data Görev listesi verileri
     * @return TaskList Oluşturulan görev listesi
     */
    public function createTaskList(Board $board, array $data): TaskList;

    /**
     * Mevcut bir görev listesini günceller
     * 
     * @param TaskList $list Güncellenecek görev listesi
     * @param array $data Güncellenecek veriler
     * @return TaskList Güncellenmiş görev listesi
     */
    public function updateTaskList(TaskList $list, array $data): TaskList;

    /**
     * Görev listelerinin sırasını günceller
     * 
     * @param array $lists Sıralanacak görev listeleri
     */
    public function reorderTaskLists(array $lists): void;

    /**
     * Yeni bir görev oluşturur
     * 
     * @param TaskList $list Görev listesi
     * @param array $data Görev verileri
     * @return Task Oluşturulan görev
     */
    public function createTask(TaskList $list, array $data): Task;

    /**
     * Mevcut bir görevi günceller
     * 
     * @param Task $task Güncellenecek görev
     * @param array $data Güncellenecek veriler
     * @return Task Güncellenmiş görev
     */
    public function updateTask(Task $task, array $data): Task;

    /**
     * Bir görevi siler
     * 
     * @param Task $task Silinecek görev
     */
    public function deleteTask(Task $task): void;

    /**
     * Görevlerin sırasını günceller
     * 
     * @param array $tasks Sıralanacak görevler
     * @param string|null $targetListId Hedef liste ID'si
     */
    public function reorderTasks(array $tasks, ?string $targetListId = null): void;
} 