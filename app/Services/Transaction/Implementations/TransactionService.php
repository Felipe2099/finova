<?php

declare(strict_types=1);

namespace App\Services\Transaction\Implementations;

use App\Models\Transaction;
use App\Services\Transaction\Contracts\TransactionServiceInterface;
use App\DTOs\Transaction\TransactionData;
use Illuminate\Support\Facades\DB;
use App\Services\Transaction\Contracts\ExpenseTransactionServiceInterface;
use App\Services\Transaction\Contracts\IncomeTransactionServiceInterface;
use App\Services\Transaction\Contracts\InstallmentTransactionServiceInterface;
use App\Services\Transaction\Contracts\SubscriptionTransactionServiceInterface;
use App\Services\Transaction\Contracts\TransferTransactionServiceInterface;
use App\Services\Transaction\Contracts\AccountBalanceServiceInterface;
use App\Enums\TransactionTypeEnum;

/**
 * İşlem (Transaction) servisi
 * 
 * Bu servis, gelir-gider takibi için yapılan tüm işlemleri yönetir.
 * Her işlem tipi için ilgili alt servislere yönlendirme yapar.
 */
final class TransactionService implements TransactionServiceInterface
{
    public function __construct(
        private readonly IncomeTransactionServiceInterface $incomeService,
        private readonly ExpenseTransactionServiceInterface $expenseService,
        private readonly TransferTransactionServiceInterface $transferService,
        private readonly InstallmentTransactionServiceInterface $installmentService,
        private readonly SubscriptionTransactionServiceInterface $subscriptionService,
        private readonly AccountBalanceServiceInterface $accountBalanceService
    ) {
    }

    /**
     * Yeni bir işlem oluşturur
     * 
     * İşlem tipine göre ilgili servisi kullanır.
     * 
     * @param TransactionData $data İşlem verileri
     * @return Transaction Oluşturulan işlem
     * @throws \InvalidArgumentException Geçersiz işlem tipi durumunda
     */
    public function create(TransactionData $data): Transaction
    {
        return match ($data->type) {
            TransactionTypeEnum::INCOME->value => $this->incomeService->create($data),
            TransactionTypeEnum::EXPENSE->value => $this->expenseService->create($data),
            TransactionTypeEnum::TRANSFER->value => $this->transferService->create($data),
            TransactionTypeEnum::INSTALLMENT->value => $this->installmentService->create($data),
            TransactionTypeEnum::SUBSCRIPTION->value => $this->subscriptionService->create($data),
            default => throw new \InvalidArgumentException('Geçersiz işlem tipi: ' . $data->type),
        };
    }

    /**
     * İşlemi günceller
     * 
     * Önce eski işlemin bakiye etkilerini geri alır, sonra işlemi günceller,
     * ardından yeni işlemin bakiye etkilerini uygular.
     * 
     * @param Transaction $transaction Güncellenecek işlem
     * @param TransactionData $data Yeni işlem verileri
     * @return Transaction Güncellenmiş işlem
     */
    public function update(Transaction $transaction, TransactionData $data): Transaction
    {
        return DB::transaction(function () use ($transaction, $data) {
            // 1. Eski işlemin bakiye etkilerini geri al
            // revertTransaction artık orijinal verileri içeriden alıyor.
            $this->accountBalanceService->revertTransaction($transaction);

            // 2. İşlemi güncelle
            // Not: İşlem tipi değişikliği desteklenmiyorsa burada kontrol eklenmeli.
            // Şu anki koda göre işlem tipi de güncellenebilir.
            $transaction->update($data->toArray());

            // 3. Yeni (güncellenmiş) işlemin bakiye etkilerini uygula
            // Güncellenmiş transaction nesnesini kullan
            match ($transaction->type) { 
                TransactionTypeEnum::INCOME->value => $this->accountBalanceService->updateForIncome($transaction),
                TransactionTypeEnum::EXPENSE->value => $this->accountBalanceService->updateForExpense($transaction),
                TransactionTypeEnum::TRANSFER->value => $this->accountBalanceService->updateForTransfer($transaction),
                TransactionTypeEnum::INSTALLMENT->value => $this->accountBalanceService->updateForInstallment($transaction),
                TransactionTypeEnum::SUBSCRIPTION->value => $this->accountBalanceService->updateForExpense($transaction), // Subscriptions behave like expenses for balance
                TransactionTypeEnum::LOAN_PAYMENT->value => $this->accountBalanceService->updateForLoanPayment($transaction),
                default => null,
            };

            return $transaction->fresh();
        });
    }

    /**
     * İşlemi siler
     * 
     * İşlemi silmeden önce ilgili hesap bakiyelerini geri alır.
     * 
     * @param Transaction $transaction Silinecek işlem
     * @return bool İşlem başarılı ise true
     */
    public function delete(Transaction $transaction): bool
    {
        return DB::transaction(function () use ($transaction) {
            // revertTransaction artık orijinal verileri içeriden aldığı için doğru çalışacaktır.
            $this->accountBalanceService->revertTransaction($transaction);
            return $transaction->delete();
        });
    }
}