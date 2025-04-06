<?php

declare(strict_types=1);

namespace App\Services\Transaction\Contracts;

use App\DTOs\Transaction\TransactionData;
use App\Models\Transaction;

/**
 * Gelir işlemleri servisi arayüzü
 * 
 * Gelir işlemlerinin yönetimi için gerekli metodları tanımlar.
 * Gelir işlemlerinin oluşturulması ve yönetilmesi işlemlerini yapar.
 */
interface IncomeTransactionServiceInterface
{
    /**
     * Yeni bir gelir işlemi oluşturur
     * 
     * Gelir işlemini kaydeder ve ilgili hesap bakiyesini günceller.
     * 
     * @param TransactionData $data Gelir işlemi verileri
     * @return Transaction Oluşturulan gelir işlemi
     */
    public function create(TransactionData $data): Transaction;
} 