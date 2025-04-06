<?php

declare(strict_types=1);

namespace App\Services\Transaction\Implementations;

use App\DTOs\Transaction\TransactionData;
use App\Models\Transaction;
use App\Services\Transaction\Contracts\AccountBalanceServiceInterface;
use App\Services\Transaction\Contracts\ExpenseTransactionServiceInterface;
use Illuminate\Support\Facades\DB;

/**
 * Gider işlemleri servisi
 * 
 * Gider işlemlerini yönetir.
 * Gider işlemlerinin kaydedilmesi ve hesap bakiyelerinin güncellenmesi işlemlerini yapar.
 */
final class ExpenseTransactionService implements ExpenseTransactionServiceInterface
{
    public function __construct(
        private readonly AccountBalanceServiceInterface $balanceService
    ) {
    }

    /**
     * Yeni bir gider işlemi oluşturur
     * 
     * Gider işlemini kaydeder ve ilgili hesap bakiyesini günceller.
     * Kredi kartı işlemleri için özel mantık uygular.
     * 
     * @param TransactionData $data Gider işlemi verileri
     * @return Transaction Oluşturulan gider işlemi
     */
    public function create(TransactionData $data): Transaction
    {
        return DB::transaction(function () use ($data) {
            $transaction = Transaction::create([
                ...$data->toArray(),
                'type' => 'expense',
            ]);
            
            $this->balanceService->updateForExpense($transaction);
            return $transaction;
        });
    }
} 