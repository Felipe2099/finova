<?php

declare(strict_types=1);

namespace App\Services\Transaction\Implementations;

use App\DTOs\Transaction\TransactionData;
use App\Models\Transaction;
use App\Services\Transaction\Contracts\AccountBalanceServiceInterface;
use App\Services\Transaction\Contracts\IncomeTransactionServiceInterface;
use Illuminate\Support\Facades\DB;

/**
 * Gelir işlemleri servisi
 * 
 * Gelir işlemlerini yönetir.
 * Gelir işlemlerinin kaydedilmesi ve hesap bakiyelerinin güncellenmesi işlemlerini yapar.
 */
final class IncomeTransactionService implements IncomeTransactionServiceInterface
{
    public function __construct(
        private readonly AccountBalanceServiceInterface $balanceService
    ) {
    }

    /**
     * Yeni bir gelir işlemi oluşturur
     * 
     * Gelir işlemini kaydeder ve ilgili hesap bakiyesini günceller.
     * 
     * @param TransactionData $data Gelir işlemi verileri
     * @return Transaction Oluşturulan gelir işlemi
     */
    public function create(TransactionData $data): Transaction
    {
        return DB::transaction(function () use ($data) {
            $transaction = Transaction::create([
                ...$data->toArray(),
                'type' => 'income',
            ]);
            
            $this->balanceService->updateForIncome($transaction);
            return $transaction;
        });
    }
} 