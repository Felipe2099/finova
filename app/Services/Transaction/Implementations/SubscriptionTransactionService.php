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
     * Aboneliği sonlandırır
     *
     * İşlemin 'is_subscription' flag'ini false yapar.
     *
     * @param Transaction $subscription Sonlandırılacak abonelik
     */
    public function endSubscription(Transaction $subscription): void
    {
        $subscription->is_subscription = false;
        $subscription->save();
    }

    /**
     * Aktif devamlı işlemleri getirir
     *
     * 'is_subscription' true olan tüm işlemleri getirir,
     * en yakın ödeme tarihine göre sıralar.
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getActiveSubscriptions(): \Illuminate\Database\Eloquent\Collection
    {
        return Transaction::where('is_subscription', true)
            ->orderBy('next_payment_date')
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
        // Başlangıç tarihini belirle: Eğer Transaction modeli ise mevcut next_payment_date'i,
        // yoksa işlem tarihini kullan. TransactionData ise onun tarihini kullan.
        $currentDate = $transaction instanceof Transaction
            ? Carbon::parse($transaction->next_payment_date ?? $transaction->date)
            : Carbon::parse($transaction->date);

        // Periyoda göre tarihi ileri al
        return match ($transaction->subscription_period) {
            'daily' => $currentDate->addDay(), // Günlük eklendi
            'weekly' => $currentDate->addWeek(),
            'monthly' => $currentDate->addMonth(), // addMonth() kullanıldı
            'quarterly' => $currentDate->addMonths(3),
            'biannually' => $currentDate->addMonths(6),
            'annually' => $currentDate->addYear(), // addYear() kullanıldı
            default => $currentDate->addMonth(), // Varsayılan olarak 1 ay ekle (daha güvenli)
        };
    }
} 