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
     * Aktif devamlı işlemleri getirir
     *
     * 'is_subscription' true olan tüm işlemleri getirir,
     * en yakın ödeme tarihine göre sıralar.
     *
     * @return \Illuminate\Database\Eloquent\Collection Aktif devamlı işlemler
     */
    public function getActiveSubscriptions(): \Illuminate\Database\Eloquent\Collection;

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
     * Aboneliği sonlandırır
     *
     * İşlemin 'is_subscription' flag'ini false yapar.
     *
     * @param Transaction $subscription Sonlandırılacak abonelik
     */
    public function endSubscription(Transaction $subscription): void;

    /**
     * Bir sonraki ödeme tarihini hesaplar
     * 
     * Abonelik periyoduna göre bir sonraki ödeme tarihini belirler.
     * 
     * @param Transaction|TransactionData $transaction Abonelik işlemi veya verisi
     * @return Carbon Bir sonraki ödeme tarihi
     */
    public function calculateNextPaymentDate(Transaction|TransactionData $transaction): Carbon;
} 