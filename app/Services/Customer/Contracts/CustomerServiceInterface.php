<?php

declare(strict_types=1);

namespace App\Services\Customer\Contracts;

use App\Models\Customer;
use App\DTOs\Customer\CustomerData;
use App\DTOs\Customer\NoteData;
use App\Models\CustomerNote;

/**
 * Müşteri servisi arayüzü
 * 
 * Müşteri işlemlerinin yönetimi için gerekli metodları tanımlar.
 * Müşterilerin oluşturulması, güncellenmesi, silinmesi ve not eklenmesi işlemlerini yapar.
 */
interface CustomerServiceInterface
{
    /**
     * Yeni bir müşteri oluşturur
     * 
     * @param CustomerData $data Müşteri verileri
     * @return Customer Oluşturulan müşteri
     */
    public function create(CustomerData $data): Customer;

    /**
     * Mevcut bir müşteriyi günceller
     * 
     * @param Customer $customer Güncellenecek müşteri
     * @param CustomerData $data Yeni müşteri verileri
     * @return Customer Güncellenmiş müşteri
     */
    public function update(Customer $customer, CustomerData $data): Customer;

    /**
     * Müşteriyi siler
     * 
     * @param Customer $customer Silinecek müşteri
     * @param bool $shouldNotify Bildirim gösterilip gösterilmeyeceği
     */
    public function delete(Customer $customer, bool $shouldNotify = true): void;

    /**
     * Müşteriye not ekler
     * 
     * @param Customer $customer Not eklenecek müşteri
     * @param NoteData $data Not verileri
     * @return CustomerNote Oluşturulan not
     */
    public function addNote(Customer $customer, NoteData $data): CustomerNote;
} 