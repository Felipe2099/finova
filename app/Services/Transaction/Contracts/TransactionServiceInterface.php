<?php

declare(strict_types=1);

namespace App\Services\Transaction\Contracts;

use App\Models\Transaction;
use App\DTOs\Transaction\TransactionData;

/**
 * İşlem servisi arayüzü
 * 
 * Temel işlem operasyonlarını tanımlar.
 * Her işlem tipi için ilgili alt servislere yönlendirme yapar.
 */
interface TransactionServiceInterface
{
    /**
     * Yeni bir işlem oluşturur
     * 
     * İşlem tipine göre ilgili servisi kullanır.
     * 
     * @param TransactionData $data İşlem verileri
     * @return Transaction Oluşturulan işlem
     * @throws \InvalidArgumentException Geçersiz işlem tipi durumunda
     */
    public function create(TransactionData $data): Transaction;

    /**
     * İşlemi günceller
     * 
     * İşlem verilerini günceller ve ilgili hesap bakiyelerini düzenler.
     * 
     * @param Transaction $transaction Güncellenecek işlem
     * @param TransactionData $data Yeni işlem verileri
     * @return Transaction Güncellenmiş işlem
     */
    public function update(Transaction $transaction, TransactionData $data): Transaction;

    /**
     * İşlemi siler
     * 
     * İşlemi silmeden önce ilgili hesap bakiyelerini geri alır.
     * 
     * @param Transaction $transaction Silinecek işlem
     * @return bool İşlem başarılı ise true
     */
    public function delete(Transaction $transaction): bool;
}