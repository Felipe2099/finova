<?php

declare(strict_types=1);

namespace App\Services\Loan\Contracts;

use App\Models\Loan;
use App\Models\Transaction;
use App\DTOs\Loan\LoanData;

/**
 * Kredi servisi arayüzü
 * 
 * Kredi işlemlerinin yönetimi için gerekli metodları tanımlar.
 * Kredi kayıtlarının oluşturulması, güncellenmesi, silinmesi ve ödeme işlemlerini yapar.
 */
interface LoanServiceInterface
{
    /**
     * Yeni bir kredi kaydı oluşturur
     * 
     * @param array $data Kredi verileri
     * @return Loan Oluşturulan kredi kaydı
     */
    public function createLoan(array $data): Loan;

    /**
     * Mevcut bir kredi kaydını günceller
     * 
     * @param Loan $loan Güncellenecek kredi kaydı
     * @param LoanData $data Yeni kredi verileri
     * @return Loan Güncellenmiş kredi kaydı
     */
    public function update(Loan $loan, LoanData $data): Loan;

    /**
     * Kredi kaydına ödeme ekler
     * 
     * @param Loan $loan Ödeme eklenecek kredi kaydı
     * @param array $data Ödeme verileri
     */
    public function addPayment(Loan $loan, array $data): void;
    
    /**
     * Kredi kaydını siler
     * 
     * @param Loan $loan Silinecek kredi kaydı
     * @return array İşlem sonucu ve mesaj
     */
    public function delete(Loan $loan): array;

    /*
     * Ödeme kaydını günceller
     * 
     * @param Transaction $payment Güncellenecek ödeme kaydı
     * @param array $data Yeni ödeme verileri
     */
    //public function updatePayment(Transaction $payment, array $data): void;
    
    /*
     * Ödeme kaydını siler
     * 
     * @param Transaction $payment Silinecek ödeme kaydı
     */
    //public function deletePayment(Transaction $payment): void;

    /*
     * Taksit planını oluşturur
     * 
     * @param Loan $loan Taksit planı oluşturulacak kredi kaydı
     * @return array Taksit planı detayları
     */
    //public function generateInstallmentPlan(Loan $loan): array;

    /*
     * Toplam tutarı faiz ile birlikte hesaplar
     * 
     * @param Loan $loan Hesaplanacak kredi kaydı
     * @return float Toplam tutar
     */
    //public function calculateTotalWithInterest(Loan $loan): float;

    /*
     * Kredi detaylarını getirir
     * 
     * @param Loan $loan Detayları getirilecek kredi kaydı
     * @return array Kredi detayları
     */
    //public function getLoanDetails(Loan $loan): array;

    /*
     * Sonraki ödeme bilgilerini getirir
     * 
     * @param Loan $loan Bilgileri getirilecek kredi kaydı
     * @return array Sonraki ödeme bilgileri
     */
    //public function getNextPaymentInfo(Loan $loan): array;

    /*
     * Kredi durumunu günceller
     * 
     * @param Loan $loan Güncellenecek kredi kaydı
     */
    //public function updateStatus(Loan $loan): void;
}