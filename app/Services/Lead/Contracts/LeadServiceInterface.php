<?php

declare(strict_types=1);

namespace App\Services\Lead\Contracts;

use App\Models\Lead;
use App\DTOs\Lead\LeadData;

/**
 * Lead servisi arayüzü
 * 
 * Potansiyel müşteri (lead) işlemlerinin yönetimi için gerekli metodları tanımlar.
 * Lead kayıtlarının oluşturulması, güncellenmesi, silinmesi ve müşteriye dönüştürülmesi işlemlerini yapar.
 */
interface LeadServiceInterface
{
    /**
     * Yeni bir lead kaydı oluşturur
     * 
     * @param LeadData $data Lead verileri
     * @return Lead Oluşturulan lead kaydı
     */
    public function create(LeadData $data): Lead;

    /**
     * Mevcut bir lead kaydını günceller
     * 
     * @param Lead $lead Güncellenecek lead kaydı
     * @param LeadData $data Yeni lead verileri
     * @return Lead Güncellenmiş lead kaydı
     */
    public function update(Lead $lead, LeadData $data): Lead;

    /**
     * Lead kaydını siler
     * 
     * @param Lead $lead Silinecek lead kaydı
     */
    public function delete(Lead $lead): void;

    /**
     * Lead'i müşteriye dönüştürür
     * 
     * Lead kaydını müşteri kaydına dönüştürür ve lead'in durumunu günceller.
     * Dönüşüm sonrası bildirim gösterir.
     * 
     * @param Lead $lead Dönüştürülecek lead kaydı
     * @param array $data Müşteri verileri
     */
    public function convertToCustomer(Lead $lead, array $data): void;
} 