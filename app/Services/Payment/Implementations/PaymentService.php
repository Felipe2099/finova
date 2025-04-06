<?php

declare(strict_types=1);

namespace App\Services\Payment\Implementations;

use App\Models\Account;
use App\Models\Debt;
use App\Models\Loan;
use App\Models\Transaction;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use App\Services\Payment\Contracts\PaymentServiceInterface;

/**
 * Ödeme servisi implementasyonu
 * 
 * Finansal işlemlerin yönetimi için gerekli metodları içerir.
 * Ödemelerin işlenmesi, doğrulanması, durumlarının kontrolü ve hesaplar arası transfer işlemlerini gerçekleştirir.
 */
final class PaymentService implements PaymentServiceInterface
{
    /**
     * Ödeme işlemini gerçekleştirir
     * 
     * @param mixed $entity Ödeme yapılacak varlık (Debt, Loan, Account, Transaction)
     * @param array $data Ödeme verileri
     * @param string $paymentMethod Ödeme yöntemi
     */
    public function processPayment($entity, array $data, string $paymentMethod): void
    {
        DB::transaction(function () use ($entity, $data, $paymentMethod) {
            $amount = (float) $data['amount'];
            $sourceAccountId = $data['source_account_id'] ?? null;

            if ($sourceAccountId) {
                $sourceAccount = Account::findOrFail($sourceAccountId);
                if ($this->isDebit($entity)) {
                    $sourceAccount->balance -= $amount; 
                } else {
                    $sourceAccount->balance += $amount; 
                }
                $sourceAccount->save();
            }

            if ($entity instanceof Debt) {
                $entity->remaining_amount -= $amount;
                $entity->status = $entity->remaining_amount <= 0 ? 'paid' : $entity->status;
                $entity->save();
                $this->updateDebtStatus($entity);
            } elseif ($entity instanceof Loan) {
                $entity->remaining_amount -= $amount;
                $entity->status = $entity->remaining_amount <= 0 ? 'paid' : $entity->status;
                $entity->save();
                $this->updateLoanStatus($entity);
            } elseif ($entity instanceof Account && $entity->type === 'credit_card') {
                $this->updateCreditCardBalance($entity, $amount, $paymentMethod);
            } elseif ($entity instanceof Transaction) {
                $this->updateTransactionBalance($entity, $amount);
            }

            // Record the transaction
            $this->recordTransaction($entity, $amount, $paymentMethod, $sourceAccountId);
        });
    }

    /**
     * Borç durumunu günceller
     * 
     * @param Debt $debt Güncellenecek borç kaydı
     */
    private function updateDebtStatus(Debt $debt): void
    {
        if ($debt->due_date && $debt->due_date < Carbon::now()->startOfDay() && $debt->status === 'pending') {
            $debt->update(['status' => 'overdue']);
        }
    }

    /**
     * Kredi durumunu günceller
     * 
     * @param Loan $loan Güncellenecek kredi kaydı
     */
    private function updateLoanStatus(Loan $loan): void
    {
        if ($loan->due_date && $loan->due_date < Carbon::now()->startOfDay() && $loan->status === 'pending') {
            $loan->update(['status' => 'overdue']);
        }
    }

    /**
     * Kredi kartı bakiyesini günceller
     * 
     * @param Account $card Güncellenecek kredi kartı
     * @param float $amount İşlem tutarı
     * @param string $paymentMethod Ödeme yöntemi
     */
    private function updateCreditCardBalance(Account $card, float $amount, string $paymentMethod): void
    {
        $details = $card->details;
        if ($paymentMethod === 'payment') {
            $card->balance -= $amount; // Ödeme yapıldığında borç azalır
        } else {
            $card->balance += $amount; // Harcama yapıldığında borç artar
        }
        $card->save();
    }

    /**
     * İşlem bakiyesini günceller
     * 
     * @param Transaction $transaction Güncellenecek işlem kaydı
     * @param float $amount İşlem tutarı
     */
    private function updateTransactionBalance(Transaction $transaction, float $amount): void
    {
        if ($transaction->source_account_id) {
            $sourceAccount = Account::findOrFail($transaction->source_account_id);
            $sourceAccount->balance -= $amount;
            $sourceAccount->save();
        }
        if ($transaction->destination_account_id) {
            $destinationAccount = Account::findOrFail($transaction->destination_account_id);
            $destinationAccount->balance += $amount;
            $destinationAccount->save();
        }
    }

    /**
     * İşlemin borç mu alacak mı olduğunu kontrol eder
     * 
     * @param mixed $entity Kontrol edilecek varlık
     * @return bool İşlemin borç olup olmadığı
     */
    private function isDebit($entity): bool
    {
        return ($entity instanceof Debt && $entity->type === 'payable') ||
            ($entity instanceof Loan) ||
            ($entity instanceof Transaction && $entity->type === 'expense') ||
            ($entity instanceof Account && $entity->type === 'credit_card' && $entity->balance >= 0);
    }

    /**
     * İşlem türünü belirler
     * 
     * @param mixed $entity İşlem türü belirlenecek varlık
     * @return string İşlem türü
     */
    private function getTransactionType($entity): string
    {
        if ($entity instanceof Transaction) {
            return match($entity->type) {
                'loan_payment' => 'expense',
                'debt_payment' => 'income',
                'income' => 'income',
                'expense' => 'expense',
                'transfer' => 'transfer',
                'payment' => 'expense',
                default => 'expense'
            };
        }
        return 'expense';
    }

    /**
     * İşlem kaydı oluşturur
     * 
     * @param mixed $entity İşlem yapılan varlık
     * @param float $amount İşlem tutarı
     * @param string $paymentMethod Ödeme yöntemi
     * @param int|null $sourceAccountId Kaynak hesap ID'si
     */
    private function recordTransaction($entity, float $amount, string $paymentMethod, ?int $sourceAccountId): void
    {
        $type = $this->getTransactionType($entity);
        $description = $entity->description ?? 'Finansal İşlem';
        $categoryId = $entity->category_id ?? null;
        $destinationAccountId = $entity instanceof Account ? $entity->id : null;
        
        // Kredi taksit numarasını belirle
        $installmentNumber = null;
        if ($entity instanceof Loan && str_contains($description, 'Taksit')) {
            preg_match('/Taksit (\d+)/', $description, $matches);
            $installmentNumber = $matches[1] ?? null;
        }

        Transaction::create([
            'user_id' => auth()->id(),
            'category_id' => $categoryId,
            'source_account_id' => $sourceAccountId,
            'destination_account_id' => $destinationAccountId,
            'type' => $type,
            'amount' => $amount,
            'currency' => $entity->currency ?? 'TRY',
            'exchange_rate' => $entity->exchange_rate ?? null,
            'try_equivalent' => $entity->currency !== 'TRY' ? ($amount * ($entity->exchange_rate ?? 1)) : $amount,
            'date' => now(),
            'payment_method' => $paymentMethod,
            'description' => $description,
            'reference_id' => $entity instanceof Loan ? $entity->id : ($entity instanceof Transaction ? $entity->id : null),
            'installment_number' => $installmentNumber,
            'status' => 'completed'
        ]);
    }

    /**
     * Hesaplar arası transfer işlemi yapar
     * 
     * @param int $sourceAccountId Kaynak hesap ID'si
     * @param int $targetAccountId Hedef hesap ID'si
     * @param float $amount Transfer tutarı
     * @param string|null $description Transfer açıklaması
     */
    public function transferBetweenAccounts(int $sourceAccountId, int $targetAccountId, float $amount, ?string $description = null): void
    {
        DB::transaction(function () use ($sourceAccountId, $targetAccountId, $amount, $description) {
            $sourceAccount = Account::findOrFail($sourceAccountId);
            $targetAccount = Account::findOrFail($targetAccountId);

            $exchangeRate = $sourceAccount->currency !== $targetAccount->currency ? ($data['exchange_rate'] ?? 1) : 1;
            $targetAmount = $amount * $exchangeRate;

            $sourceAccount->balance -= $amount;
            $targetAccount->balance += $targetAmount;

            $sourceAccount->save();
            $targetAccount->save();

            Transaction::create([
                'user_id' => auth()->id(),
                'source_account_id' => $sourceAccountId,
                'destination_account_id' => $targetAccountId,
                'type' => 'transfer',
                'amount' => $amount,
                'currency' => $sourceAccount->currency,
                'exchange_rate' => $exchangeRate,
                'try_equivalent' => $amount * $exchangeRate,
                'date' => now(),
                'description' => $description ?? "{$sourceAccount->name}'dan {$targetAccount->name}'a transfer",
            ]);
        });
    }

    /**
     * Ödeme verilerinin geçerliliğini kontrol eder
     * 
     * @param array $data Kontrol edilecek ödeme verileri
     * @return bool Verilerin geçerliliği
     */
    public function validatePayment(array $data): bool
    {
        // Ödeme verilerinin geçerliliğini kontrol et
        if (empty($data['amount']) || !is_numeric($data['amount']) || $data['amount'] <= 0) {
            return false;
        }

        // Ödeme yöntemi kontrolü
        if (empty($data['payment_method'])) {
            return false;
        }

        // Hesap kontrolü
        if (isset($data['source_account_id']) && !Account::find($data['source_account_id'])) {
            return false;
        }

        if (isset($data['destination_account_id']) && !Account::find($data['destination_account_id'])) {
            return false;
        }

        return true;
    }

    /**
     * Ödeme durumunu getirir
     * 
     * @param string $paymentId Ödeme ID'si
     * @return string Ödeme durumu
     */
    public function getPaymentStatus(string $paymentId): string
    {
        $transaction = Transaction::find($paymentId);
        
        if (!$transaction) {
            return 'not_found';
        }

        return $transaction->status;
    }
}