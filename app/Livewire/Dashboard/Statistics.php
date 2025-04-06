<?php

declare(strict_types=1);

namespace App\Livewire\Dashboard;

use Livewire\Component;
use App\Services\Statistics\Contracts\StatisticsServiceInterface;
use Illuminate\Contracts\View\View;

final class Statistics extends Component
{
    protected $listeners = ['transaction-updated' => '$refresh'];
    
    private StatisticsServiceInterface $statisticsService;
    
    public function boot(StatisticsServiceInterface $statisticsService): void
    {
        $this->statisticsService = $statisticsService;
    }
    
    public function render(): View
    {
        $statistics = $this->statisticsService->getDashboardStatistics();
        return view('livewire.dashboard.statistics', [
            'statistics' => $statistics
        ]);
    }
} 