<?php

declare(strict_types=1);

namespace App\Services\Transaction\Contracts;

use App\DTOs\Transaction\TransactionData;
use App\Models\Transaction;

/**
 * Transfer işlemleri servisi arayüzü
 * 
 * Transfer işlemlerinin yönetimi için gerekli metodları tanımlar.
 * Hesaplar arası para transferlerinin oluşturulması ve yönetilmesi işlemlerini yapar.
 */
interface TransferTransactionServiceInterface
{
    /**
     * Yeni bir transfer işlemi oluşturur
     * 
     * Transfer işlemini kaydeder ve ilgili hesap bakiyelerini günceller.
     * 
     * @param TransactionData $data Transfer işlemi verileri
     * @return Transaction Oluşturulan transfer işlemi
     */
    public function create(TransactionData $data): Transaction;
} 