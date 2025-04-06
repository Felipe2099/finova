<?php

declare(strict_types=1);

namespace App\Services\CustomerGroup\Contracts;

use App\Models\CustomerGroup;
use App\DTOs\CustomerGroup\CustomerGroupData;

/**
 * Müşteri grubu servisi arayüzü
 * 
 * Müşteri gruplarının yönetimi için gerekli metodları tanımlar.
 * Müşteri gruplarının oluşturulması, güncellenmesi ve silinmesi işlemlerini yapar.
 */
interface CustomerGroupServiceInterface
{
    /**
     * Yeni bir müşteri grubu oluşturur
     * 
     * @param CustomerGroupData $data Müşteri grubu verileri
     * @return CustomerGroup Oluşturulan müşteri grubu
     */
    public function create(CustomerGroupData $data): CustomerGroup;

    /**
     * Mevcut bir müşteri grubunu günceller
     * 
     * @param CustomerGroup $customerGroup Güncellenecek müşteri grubu
     * @param CustomerGroupData $data Yeni müşteri grubu verileri
     * @return CustomerGroup Güncellenmiş müşteri grubu
     */
    public function update(CustomerGroup $customerGroup, CustomerGroupData $data): CustomerGroup;

    /**
     * Müşteri grubunu siler
     * 
     * @param CustomerGroup $customerGroup Silinecek müşteri grubu
     */
    public function delete(CustomerGroup $customerGroup): void;
} 