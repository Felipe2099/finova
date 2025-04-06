<?php

declare(strict_types=1);

namespace App\Services\Debt\Contracts;

use App\Models\Debt;
use App\DTOs\Debt\DebtData;

/**
 * Borç/Alacak servisi arayüzü
 * 
 * Borç ve alacak işlemlerinin yönetimi için gerekli metodları tanımlar.
 * Borç/alacak kayıtlarının oluşturulması, güncellenmesi ve silinmesi işlemlerini yapar.
 */
interface DebtServiceInterface
{
    /**
     * Yeni bir borç/alacak kaydı oluşturur
     * 
     * @param DebtData $data Borç/Alacak verileri
     * @return Debt Oluşturulan borç/alacak kaydı
     */
    public function create(DebtData $data): Debt;

    /**
     * Mevcut bir borç/alacak kaydını günceller
     * 
     * @param Debt $debt Güncellenecek borç/alacak kaydı
     * @param DebtData $data Yeni borç/alacak verileri
     * @return Debt Güncellenmiş borç/alacak kaydı
     */
    public function update(Debt $debt, DebtData $data): Debt;

    /**
     * Borç/Alacak kaydını siler
     * 
     * @param Debt $debt Silinecek borç/alacak kaydı
     */
    public function delete(Debt $debt): void;

    /**
     * Borç/Alacak kaydının durumunu günceller
     * 
     * @param Debt $debt Güncellenecek borç/alacak kaydı
     */
    public function updateStatus(Debt $debt): void;

    /**
     * Borç/Alacak kayıtlarını sıralı şekilde getirir
     * 
     * @param string $sortBy Sıralama alanı
     * @param string $direction Sıralama yönü
     * @return \Illuminate\Database\Eloquent\Collection Sıralanmış borç/alacak kayıtları
     */
    public function getSortedDebts(string $sortBy = 'due_date', string $direction = 'asc'): \Illuminate\Database\Eloquent\Collection;

    /*
     * Borç/Alacak kaydına ödeme ekler
     * 
     * @param Debt $debt Ödeme eklenecek borç/alacak kaydı
     * @param array $data Ödeme verileri
     */
    // public function addPayment(Debt $debt, array $data): void;
}