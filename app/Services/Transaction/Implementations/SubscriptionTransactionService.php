<?php

declare(strict_types=1);

namespace App\Services\Transaction\Implementations;

use App\DTOs\Transaction\TransactionData;
use App\Models\Transaction;
use App\Services\Transaction\Contracts\AccountBalanceServiceInterface;
use App\Services\Transaction\Contracts\SubscriptionTransactionServiceInterface;
use Carbon\Carbon;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\DB;

/**
 * Abonelik işlemleri servisi
 * 
 * Abonelik işlemlerini yönetir.
 * Abonelik ödemelerini ve yenilemelerini takip eder.
 */
final class SubscriptionTransactionService implements SubscriptionTransactionServiceInterface
{
    public function __construct(
        private readonly AccountBalanceServiceInterface $balanceService
    ) {
    }

    /**
     * Yeni bir abonelik işlemi oluşturur
     * 
     * Abonelik işlemini kaydeder ve ilgili hesap bakiyesini günceller.
     * 
     * @param TransactionData $data Abonelik işlemi verileri
     * @return Transaction Oluşturulan abonelik işlemi
     */
    public function create(TransactionData $data): Transaction
    {
        return DB::transaction(function () use ($data) {
            $transaction = Transaction::create([
                ...$data->toArray(),
                'is_subscription' => true,
                'next_payment_date' => $this->calculateNextPaymentDate($data),
            ]);
            
            $this->balanceService->updateForExpense($transaction);
            return $transaction;
        });
    }

    /**
     * Abonelikten yeni bir işlem oluşturur
     * 
     * Abonelik ödemesini işler ve bir sonraki ödeme tarihini günceller.
     * 
     * @param Transaction $subscription Abonelik işlemi
     * @return Transaction Oluşturulan yeni işlem
     */
    public function createFromSubscription(Transaction $subscription): Transaction
    {
        return DB::transaction(function () use ($subscription) {
            $data = TransactionData::fromArray([
                'user_id' => $subscription->user_id,
                'category_id' => $subscription->category_id,
                'source_account_id' => $subscription->source_account_id,
                'destination_account_id' => $subscription->destination_account_id,
                'customer_id' => $subscription->customer_id,
                'supplier_id' => $subscription->supplier_id,
                'type' => $subscription->type,
                'amount' => $subscription->amount,
                'currency' => $subscription->currency,
                'exchange_rate' => $subscription->exchange_rate,
                'try_equivalent' => $subscription->try_equivalent,
                'date' => now()->toDateString(),
                'payment_method' => $subscription->payment_method,
                'description' => $subscription->description,
                'is_subscription' => false, 
                'status' => 'completed',
            ]);

            $newTransaction = Transaction::create($data->toArray());
            
            // Bir sonraki ödeme tarihini güncelle
            $subscription->next_payment_date = $this->calculateNextPaymentDate($subscription);
            $subscription->save();

            return $newTransaction;
        });
    }

    /**
     * Aboneliği deaktif eder
     * 
     * Aboneliğin otomatik yenilenmesini durdurur.
     * 
     * @param Transaction $subscription Deaktif edilecek abonelik
     */
    public function deactivateSubscription(Transaction $subscription): void
    {
        $subscription->auto_renew = false;
        $subscription->save();

        Notification::make()
            ->title('Abonelik sonlandırıldı')
            ->success()
            ->send();
    }

    /**
     * Yaklaşan abonelikleri getirir
     * 
     * @param int $days Kaç gün içindeki aboneliklerin getirileceği
     * @param int $limit Maksimum kaç abonelik getirileceği
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getUpcomingSubscriptions(int $days = 30, int $limit = 10): \Illuminate\Database\Eloquent\Collection
    {
        return Transaction::where('is_subscription', true)
            ->where('auto_renew', true)
            ->where('next_payment_date', '<=', now()->addDays($days))
            ->orderBy('next_payment_date')
            ->take($limit)
            ->get();
    }

    /**
     * Bir sonraki ödeme tarihini hesaplar
     * 
     * @param Transaction|TransactionData $transaction Abonelik işlemi
     * @return Carbon Bir sonraki ödeme tarihi
     */
    public function calculateNextPaymentDate(Transaction|TransactionData $transaction): Carbon
    {
        $currentDate = $transaction instanceof Transaction 
            ? Carbon::parse($transaction->next_payment_date ?? $transaction->date)
            : Carbon::parse($transaction->date);

        return match ($transaction->subscription_period) {
            'weekly' => $currentDate->addWeek(),
            'monthly' => $currentDate->addMonth(),
            'quarterly' => $currentDate->addMonths(3),
            'semiannual' => $currentDate->addMonths(6),
            'yearly' => $currentDate->addYear(),
            default => $currentDate->addMonth(),
        };
    }
} 