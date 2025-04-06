<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;
use App\Models\Debt;
use App\Services\Debt\Contracts\DebtServiceInterface;

class Kernel extends ConsoleKernel
{
    protected function schedule(Schedule $schedule): void
    {
        $schedule->call(function () {
            Debt::where('due_date', '<', now()->startOfDay())
                ->where('status', 'pending')
                ->each(function (Debt $debt) {
                    $debt->update(['status' => 'overdue']);
                });
        })->daily();

        // Her gün sabah 6'da bloke süresi dolan işlemleri işle
        $schedule->command('app:process-blocked-transfers')->dailyAt('06:00');
    }

    protected function commands(): void
    {
        $this->load(__DIR__.'/Commands');
        require base_path('routes/console.php');
    }
} 