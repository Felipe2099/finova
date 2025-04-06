<?php

declare(strict_types=1);

namespace App\Services\Payment\Contracts;

/**
 * Ödeme servisi arayüzü
 * 
 * Finansal işlemlerin yönetimi için gerekli metodları tanımlar.
 * Ödemelerin işlenmesi, doğrulanması, durumlarının kontrolü ve hesaplar arası transfer işlemlerini yapar.
 */
interface PaymentServiceInterface
{
    /**
     * Ödeme işlemini gerçekleştirir
     * 
     * @param mixed $entity Ödeme yapılacak varlık (Debt, Loan, Account, Transaction)
     * @param array $data Ödeme verileri
     * @param string $paymentMethod Ödeme yöntemi
     */
    public function processPayment($entity, array $data, string $paymentMethod): void;

    /**
     * Ödeme verilerinin geçerliliğini kontrol eder
     * 
     * @param array $data Kontrol edilecek ödeme verileri
     * @return bool Verilerin geçerliliği
     */
    public function validatePayment(array $data): bool;

    /**
     * Ödeme durumunu getirir
     * 
     * @param string $paymentId Ödeme ID'si
     * @return string Ödeme durumu
     */
    public function getPaymentStatus(string $paymentId): string;

    /**
     * Hesaplar arası transfer işlemi yapar
     * 
     * @param int $sourceAccountId Kaynak hesap ID'si
     * @param int $targetAccountId Hedef hesap ID'si
     * @param float $amount Transfer tutarı
     * @param string|null $description Transfer açıklaması
     */
    public function transferBetweenAccounts(int $sourceAccountId, int $targetAccountId, float $amount, ?string $description = null): void;
} 