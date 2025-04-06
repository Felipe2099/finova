<?php

namespace App\Livewire\Project\Board;

use App\Models\Board;
use App\Models\Project;
use App\Models\Task;
use App\Models\TaskList;
use App\Models\User;
use App\Services\Project\Contracts\ProjectServiceInterface;
use Livewire\Component;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Support\Exceptions\Halt;
use Filament\Actions\Action;
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Actions\Contracts\HasActions;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Actions\ActionGroup;
use Filament\Actions\StaticAction;
use Illuminate\Support\Facades\DB;

/**
 * Proje Panosu Yönetimi Bileşeni
 * 
 * Bu bileşen, proje panolarının yönetimini sağlar.
 * Özellikler:
 * - Görev listesi oluşturma ve düzenleme
 * - Görev ekleme, düzenleme ve silme
 * - Görev sıralama ve taşıma
 * - Görev atama ve etiketleme
 * - Görev kontrol listesi yönetimi
 * - Kanban görünümü
 * 
 * @package App\Livewire\Project\Board
 */
class BoardManager extends Component implements HasForms
{
    use InteractsWithForms;

    /** @var Project Proje */
    public Project $project;

    /** @var Board|null Aktif pano */
    public ?Board $board = null;

    /** @var int|null Düzenlenen görev listesi ID'si */
    public ?int $editingTaskListId = null;

    /** @var Task|null Düzenlenen görev */
    public ?Task $editingTask = null;

    /** @var array Görevler */
    public array $tasks = [];

    /** @var array Kullanıcılar */
    public $users = [];

    /** @var bool Liste modalı görünürlüğü */
    public bool $showListModal = false;

    /** @var bool Görev modalı görünürlüğü */
    public bool $showTaskModal = false;

    /** @var bool Silme modalı görünürlüğü */
    public bool $showDeleteModal = false;

    /** @var bool Liste düzenleme modalı görünürlüğü */
    public bool $showEditListModal = false;

    /** @var array Liste form verileri */
    public array $listData = [];

    /** @var array Görev form verileri */
    public array $taskData = [];

    /** @var int|null Silinecek görev ID'si */
    public ?int $deletingTaskId = null;

    /** @var TaskList|null Düzenlenen liste */
    public ?TaskList $editingList = null;

    /** @var ProjectServiceInterface Proje servisi */
    private ProjectServiceInterface $projectService;

    /** @var bool Kanban görünümü yenileme durumu */
    public bool $shouldRenderKanban = true;

    /**
     * Bileşen başlatılırken proje servisini enjekte eder
     * 
     * @param ProjectServiceInterface $projectService Proje servisi
     * @return void
     */
    public function boot(ProjectServiceInterface $projectService): void 
    {
        $this->projectService = $projectService;
    }

    /**
     * Bileşen başlatılırken proje ve pano verilerini yükler
     * 
     * @param Project $project Proje
     * @return void
     */
    public function mount(Project $project)
    {
        $this->project = $project;
        $this->board = $this->projectService->getOrCreateDefaultBoard($project);
        
        // Tasks array'ini hazırla
        foreach ($this->board->taskLists as $list) {
            $this->tasks[$list->id] = $list->tasks->sortBy('order')->values();
        }

        // Aktif kullanıcıları getir
        $this->users = User::orderBy('name')->get();
    }

    /**
     * Yeni liste ekleme modalını açar
     * 
     * @return void
     */
    public function addList(): void
    {
        // Liste eklerken state'i temizle
        $this->reset('listData');
        $this->showListModal = true;
    }

    /**
     * Yeni liste oluşturur
     * 
     * @return void
     */
    public function createList(): void
    {
        $this->validate([
            'listData.name' => 'required|string|max:255',
        ]);

        $this->projectService->createTaskList($this->board, $this->listData);

        $this->listData = [];
        $this->showListModal = false;

        Notification::make()
            ->success()
            ->title('Liste eklendi')
            ->send();

        // Sayfayı yenile
        $this->redirect(request()->header('Referer'));
    }

    /**
     * Yeni görev ekleme modalını açar
     * 
     * @param int $listId Liste ID'si
     * @return void
     */
    public function addTask(int $listId): void
    {
        // Task eklerken state'i temizle
        $this->reset(['editingTask', 'taskData']);
        
        $this->editingTaskListId = $listId;
        $this->taskData = [
            'title' => '',
            'content' => '',
            'priority' => 'medium',
            'due_date' => null,
            'checklist' => [],
            'assigned_to' => null
        ];
        $this->showTaskModal = true;
    }

    /**
     * Görev düzenleme modalını açar
     * 
     * @param int $taskId Görev ID'si
     * @return void
     */
    public function editTask(int $taskId): void
    {
        $this->editingTask = Task::find($taskId);
        $this->editingTaskListId = $this->editingTask->task_list_id;
        
        $this->taskData = [
            'title' => $this->editingTask->title,
            'content' => $this->editingTask->content ?? '',
            'priority' => $this->editingTask->priority,
            'due_date' => $this->editingTask->due_date?->format('Y-m-d'),
            'checklist' => $this->editingTask->checklist ?? [],
            'assigned_to' => $this->editingTask->assigned_to ?: null
        ];
        
        $this->showTaskModal = true;
    }

    /**
     * Görev oluşturur veya günceller
     * 
     * @return void
     */
    public function createTask(): void
    {
        $this->validate([
            'taskData.title' => 'required|string|max:255',
            'taskData.content' => 'nullable|string|max:2000',
            'taskData.priority' => 'required|in:low,medium,high',
            'taskData.due_date' => 'nullable|date',
            'taskData.checklist' => 'nullable|array',
            'taskData.assigned_to' => 'nullable|exists:users,id'
        ]);

        $list = TaskList::findOrFail($this->editingTaskListId);

        if ($this->editingTask) {
            $this->projectService->updateTask($this->editingTask, $this->taskData);
            $message = 'Görev güncellendi';
        } else {
            $this->projectService->createTask($list, $this->taskData);
            $message = 'Görev oluşturuldu';
        }

        $this->reset(['taskData', 'showTaskModal', 'editingTask', 'editingTaskListId']);

        Notification::make()
            ->success()
            ->title($message)
            ->send();
    }

    /**
     * Görev silme modalını açar
     * 
     * @param int $taskId Görev ID'si
     * @return void
     */
    public function confirmTaskDeletion(int $taskId): void
    {
        $this->deletingTaskId = $taskId;
        $this->showDeleteModal = true;
    }

    /**
     * Görevi siler
     * 
     * @return void
     */
    public function deleteTask(): void
    {
        $task = Task::findOrFail($this->deletingTaskId);
        $this->projectService->deleteTask($task);

        $this->showDeleteModal = false;
        $this->deletingTaskId = null;

        Notification::make()
            ->success()
            ->title('Görev silindi')
            ->send();
    }

    /**
     * Liste düzenleme modalını açar
     * 
     * @param TaskList $list Liste
     * @return void
     */
    public function editList(TaskList $list): void
    {
        // Liste düzenlerken state'i temizle
        $this->reset('listData');
        
        $this->editingList = $list;
        $this->listData = [
            'name' => $list->name,
        ];
        $this->showEditListModal = true;
    }

    /**
     * Listeyi günceller
     * 
     * @return void
     */
    public function updateList(): void
    {
        $this->validate([
            'listData.name' => 'required|string|max:255',
        ]);

        $this->projectService->updateTaskList($this->editingList, $this->listData);

        $this->reset(['listData', 'showEditListModal', 'editingList']);

        Notification::make()
            ->success()
            ->title('Liste güncellendi')
            ->send();
    }

    /**
     * Görev sıralamasını günceller
     * 
     * @param array $items Sıralama verileri
     * @param int|null $sourceListId Kaynak liste ID'si
     * @param int|null $targetListId Hedef liste ID'si
     * @return void
     */
    public function updateTaskOrder($items, $sourceListId = null, $targetListId = null): void
    {
        if (!$items) return;

        $this->projectService->reorderTasks($items, $targetListId ?? $sourceListId);
    }

    /**
     * Liste sıralamasını günceller
     * 
     * @param array $items Sıralama verileri
     * @return void
     */
    public function updateListOrder($items): void
    {
        if (!$items) return;
        $this->projectService->reorderTaskLists($items);
    }

    /**
     * Görev modalını kapatır
     * 
     * @return void
     */
    public function closeTaskModal(): void
    {
        $this->reset(['taskData', 'showTaskModal', 'editingTask', 'editingTaskListId']);
    }

    /**
     * Liste modalını kapatır
     * 
     * @return void
     */
    public function closeListModal(): void
    {
        $this->reset(['listData', 'showListModal']);
    }

    /**
     * Liste düzenleme modalını kapatır
     * 
     * @return void
     */
    public function closeEditListModal(): void
    {
        $this->reset(['listData', 'showEditListModal', 'editingList']);
    }

    /**
     * Bileşenin görünümünü render eder
     * 
     * @return \Illuminate\Contracts\View\View
     */
    public function render()
    {
        $lists = $this->board->taskLists()
            ->with(['tasks' => function($query) {
                $query->orderBy('order')->with(['labels', 'assignee']);
            }])
            ->orderBy('order')
            ->get();

        // Sayfayı yenileme
        $this->dispatch('kanban-updated');

        return view('livewire.project.board.board-manager', [
            'lists' => $lists,
        ]);
    }

    /**
     * Dinleyici olaylarını döndürür
     * 
     * @return array Dinleyici olayları
     */
    protected function getListeners()
    {
        return [
            'lists-reordered' => 'handleListReorder',
            'tasks-reordered' => 'handleTaskReorder'
        ];
    }

    /**
     * Liste sıralama olayını işler
     * 
     * @param array $args Olay argümanları
     * @return void
     */
    public function handleListReorder(...$args)
    {
        $lists = $args[0] ?? [];
        if (empty($lists)) return;
        
        $this->projectService->reorderTaskLists($lists);
        $this->shouldRenderKanban = true;
    }

    /**
     * Görev sıralama olayını işler
     * 
     * @param array $args Olay argümanları
     * @return void
     */
    public function handleTaskReorder(...$args)
    {
        $data = $args[0] ?? [];
        if (empty($data['tasks']) || !isset($data['targetListId'])) return;
        
        $this->projectService->reorderTasks($data['tasks'], $data['targetListId']);
        $this->shouldRenderKanban = true;
    }

    /**
     * Kontrol listesine yeni öğe ekler
     * 
     * @return void
     */
    public function addChecklistItem()
    {
        if (!isset($this->taskData['checklist'])) {
            $this->taskData['checklist'] = [];
        }
        
        $this->taskData['checklist'][] = [
            'text' => '',
            'completed' => false
        ];
    }

    /**
     * Kontrol listesinden öğe siler
     * 
     * @param int $index Silinecek öğe indeksi
     * @return void
     */
    public function removeChecklistItem($index)
    {
        unset($this->taskData['checklist'][$index]);
        $this->taskData['checklist'] = array_values($this->taskData['checklist']);
    }
} 