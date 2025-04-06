<?php

declare(strict_types=1);

namespace App\Services\Transaction\Contracts;

use App\DTOs\Transaction\TransactionData;
use App\Models\Transaction;

/**
 * Taksitli ödeme işlemleri servisi arayüzü
 * 
 * Taksitli ödeme işlemlerinin yönetimi için gerekli metodları tanımlar.
 * Taksitli ödemelerin oluşturulması ve yönetilmesi işlemlerini yapar.
 */
interface InstallmentTransactionServiceInterface
{
    /**
     * Yeni bir taksitli ödeme işlemi oluşturur
     * 
     * Taksitli ödeme işlemini kaydeder ve ilgili hesap bakiyesini günceller.
     * 
     * @param TransactionData $data Taksitli ödeme verileri
     * @return Transaction Oluşturulan taksitli ödeme işlemi
     */
    public function create(TransactionData $data): Transaction;
} 