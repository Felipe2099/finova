<?php

declare(strict_types=1);

namespace App\Services\Transaction\Contracts;

use App\Models\Transaction;

/**
 * Hesap bakiyesi servisi arayüzü
 * 
 * Hesap bakiyelerinin güncellenmesi ve yönetilmesi için gerekli metodları tanımlar.
 * Farklı işlem tipleri için bakiye güncellemelerini yönetir.
 */
interface AccountBalanceServiceInterface
{
    /**
     * Gelir işlemi için hesap bakiyesini günceller
     * 
     * @param Transaction $transaction İşlenecek gelir işlemi
     */
    public function updateForIncome(Transaction $transaction): void;

    /**
     * Gider işlemi için hesap bakiyesini günceller
     * 
     * @param Transaction $transaction İşlenecek gider işlemi
     */
    public function updateForExpense(Transaction $transaction): void;

    /**
     * Transfer işlemi için hesap bakiyelerini günceller
     * 
     * @param Transaction $transaction İşlenecek transfer işlemi
     */
    public function updateForTransfer(Transaction $transaction): void;

    /**
     * Taksitli ödeme işlemi için hesap bakiyesini günceller
     * 
     * @param Transaction $transaction İşlenecek taksitli ödeme işlemi
     */
    public function updateForInstallment(Transaction $transaction): void;

    /**
     * Kredi ödemesi işlemi için hesap bakiyesini günceller
     * 
     * @param Transaction $transaction İşlenecek kredi ödemesi işlemi
     */
    public function updateForLoanPayment(Transaction $transaction): void;

    /**
     * İşlemi geri alır
     * 
     * İşlem tipine göre hesap bakiyelerini eski haline getirir.
     * 
     * @param Transaction $transaction Geri alınacak işlem
     */
    public function revertTransaction(Transaction $transaction): void;
} 