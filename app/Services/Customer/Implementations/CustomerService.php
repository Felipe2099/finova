<?php

declare(strict_types=1);

namespace App\Services\Customer\Implementations;

use App\Models\Customer;
use App\Models\CustomerNote;
use App\Services\Customer\Contracts\CustomerServiceInterface;
use App\DTOs\Customer\CustomerData;
use App\DTOs\Customer\NoteData;
use Illuminate\Support\Facades\DB;
use Filament\Notifications\Notification;

/**
 * Müşteri servisi implementasyonu
 * 
 * Müşteri işlemlerinin yönetimi için gerekli metodları içerir.
 * Müşterilerin oluşturulması, güncellenmesi, silinmesi ve not eklenmesi işlemlerini gerçekleştirir.
 */
class CustomerService implements CustomerServiceInterface
{
    /**
     * Yeni bir müşteri oluşturur
     * 
     * @param CustomerData $data Müşteri verileri
     * @return Customer Oluşturulan müşteri
     */
    public function create(CustomerData $data): Customer
    {
        return DB::transaction(function () use ($data) {
            return Customer::create($data->toArray());
        });
    }

    /**
     * Mevcut bir müşteriyi günceller
     * 
     * @param Customer $customer Güncellenecek müşteri
     * @param CustomerData $data Yeni müşteri verileri
     * @return Customer Güncellenmiş müşteri
     */
    public function update(Customer $customer, CustomerData $data): Customer
    {
        return DB::transaction(function () use ($customer, $data) {
            $customer->update([
                'name' => $data->name,
                'type' => $data->type,
                'email' => $data->email,
                'phone' => $data->phone,
                'tax_number' => $data->tax_number,
                'tax_office' => $data->tax_office,
                'city' => $data->city,
                'district' => $data->district,
                'address' => $data->address,
                'description' => $data->description,
                'status' => $data->status,
                'customer_group_id' => $data->customer_group_id,
            ]);
            return $customer->fresh();
        });
    }

    /**
     * Müşteriyi siler
     * 
     * Müşteriye ait tüm notları da siler ve istenirse bildirim gösterir.
     * 
     * @param Customer $customer Silinecek müşteri
     * @param bool $shouldNotify Bildirim gösterilip gösterilmeyeceği
     */
    public function delete(Customer $customer, bool $shouldNotify = true): void
    {
        DB::transaction(function () use ($customer, $shouldNotify) {
            // Müşteriye ait notları sil
            $customer->notes()->delete();
            
            // Müşteriyi sil
            $customer->delete();

            // Eğer bildirim isteniyorsa
            if ($shouldNotify) {
                Notification::make()
                    ->title('Müşteri silindi')
                    ->success()
                    ->send();
            }
        });
    }

    /**
     * Müşteriye not ekler
     * 
     * @param Customer $customer Not eklenecek müşteri
     * @param NoteData $data Not verileri
     * @return CustomerNote Oluşturulan not
     */
    public function addNote(Customer $customer, NoteData $data): CustomerNote
    {
        return DB::transaction(function () use ($customer, $data) {
            return $customer->notes()->create($data->toArray());
        });
    }
} 