<?php

declare(strict_types=1);

namespace App\Services\Transaction\Contracts;

use App\DTOs\Transaction\TransactionData;
use App\Models\Transaction;

/**
 * Gider işlemleri servisi arayüzü
 * 
 * Gider işlemlerinin yönetimi için gerekli metodları tanımlar.
 * Gider işlemlerinin oluşturulması ve yönetilmesi işlemlerini yapar.
 */
interface ExpenseTransactionServiceInterface
{
    /**
     * Yeni bir gider işlemi oluşturur
     * 
     * Gider işlemini kaydeder ve ilgili hesap bakiyesini günceller.
     * 
     * @param TransactionData $data Gider işlemi verileri
     * @return Transaction Oluşturulan gider işlemi
     */
    public function create(TransactionData $data): Transaction;
} 