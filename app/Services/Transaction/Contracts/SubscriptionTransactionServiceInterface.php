<?php

declare(strict_types=1);

namespace App\Services\Transaction\Contracts;

use App\DTOs\Transaction\TransactionData;
use App\Models\Transaction;
use Carbon\Carbon;

/**
 * Abonelik işlemleri servisi arayüzü
 * 
 * Abonelik işlemlerinin yönetimi için gerekli metodları tanımlar.
 * Aboneliklerin oluşturulması, yenilenmesi ve yönetilmesi işlemlerini yapar.
 */
interface SubscriptionTransactionServiceInterface
{
    /**
     * Yeni bir abonelik işlemi oluşturur
     * 
     * Abonelik işlemini kaydeder ve ilgili hesap bakiyesini günceller.
     * 
     * @param TransactionData $data Abonelik işlemi verileri
     * @return Transaction Oluşturulan abonelik işlemi
     */
    public function create(TransactionData $data): Transaction;

    /**
     * Yaklaşan abonelikleri getirir
     * 
     * Belirtilen gün sayısı içinde ödenmesi gereken abonelikleri listeler.
     * 
     * @param int $days Kaç gün içindeki aboneliklerin getirileceği
     * @param int $limit Maksimum kaç abonelik getirileceği
     * @return \Illuminate\Database\Eloquent\Collection Yaklaşan abonelikler
     */
    public function getUpcomingSubscriptions(int $days = 30, int $limit = 10): \Illuminate\Database\Eloquent\Collection;

    /**
     * Abonelikten yeni bir işlem oluşturur
     * 
     * Abonelik ödemesini işler ve bir sonraki ödeme tarihini günceller.
     * 
     * @param Transaction $subscription Abonelik işlemi
     * @return Transaction Oluşturulan yeni işlem
     */
    public function createFromSubscription(Transaction $subscription): Transaction;

    /**
     * Aboneliği deaktif eder
     * 
     * Aboneliğin otomatik yenilenmesini durdurur.
     * 
     * @param Transaction $subscription Deaktif edilecek abonelik
     */
    public function deactivateSubscription(Transaction $subscription): void;

    /**
     * Bir sonraki ödeme tarihini hesaplar
     * 
     * Abonelik periyoduna göre bir sonraki ödeme tarihini belirler.
     * 
     * @param Transaction $transaction Abonelik işlemi
     * @return Carbon Bir sonraki ödeme tarihi
     */
    public function calculateNextPaymentDate(Transaction $transaction): Carbon;
} 