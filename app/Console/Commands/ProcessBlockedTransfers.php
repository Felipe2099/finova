<?php

namespace App\Console\Commands;

use App\Services\Transfer\TransferService;
use Illuminate\Console\Command;

class ProcessBlockedTransfers extends Command
{
    protected $signature = 'app:process-blocked-transfers';
    protected $description = 'Bloke süresi dolan işlemleri tamamlar';

    public function handle(TransferService $transferService)
    {
        $this->info('Bloke süresi dolan işlemler işleniyor...');
        
        $completedCount = $transferService->processBlockedTransfers();
        
        $this->info("{$completedCount} işlem tamamlandı.");
        
        return Command::SUCCESS;
    }
} 