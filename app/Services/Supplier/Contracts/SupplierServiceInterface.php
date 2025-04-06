<?php

declare(strict_types=1);

namespace App\Services\Supplier\Contracts;

use App\Models\Supplier;
use App\DTOs\Supplier\SupplierData;

/**
 * Tedarikçi servisi arayüzü
 * 
 * Tedarikçi yönetimi için gerekli metodları tanımlar.
 * Tedarikçilerin oluşturulması, güncellenmesi ve silinmesi işlemlerini içerir.
 */
interface SupplierServiceInterface
{
    /**
     * Yeni bir tedarikçi oluşturur
     * 
     * @param SupplierData $data Tedarikçi verileri
     * @return Supplier Oluşturulan tedarikçi
     */
    public function create(SupplierData $data): Supplier;

    /**
     * Mevcut bir tedarikçiyi günceller
     * 
     * @param Supplier $supplier Güncellenecek tedarikçi
     * @param SupplierData $data Güncellenecek veriler
     * @return Supplier Güncellenmiş tedarikçi
     */
    public function update(Supplier $supplier, SupplierData $data): Supplier;

    /**
     * Bir tedarikçiyi siler
     * 
     * @param Supplier $supplier Silinecek tedarikçi
     */
    public function delete(Supplier $supplier): void;
}