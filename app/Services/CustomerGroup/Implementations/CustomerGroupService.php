<?php

declare(strict_types=1);

namespace App\Services\CustomerGroup\Implementations;

use App\Models\CustomerGroup;
use App\Services\CustomerGroup\Contracts\CustomerGroupServiceInterface;
use App\DTOs\CustomerGroup\CustomerGroupData;
use Illuminate\Support\Facades\DB;
use Filament\Notifications\Notification;

/**
 * Müşteri grubu servisi implementasyonu
 * 
 * Müşteri gruplarının yönetimi için gerekli metodları içerir.
 * Müşteri gruplarının oluşturulması, güncellenmesi ve silinmesi işlemlerini gerçekleştirir.
 */
class CustomerGroupService implements CustomerGroupServiceInterface
{
    /**
     * Yeni bir müşteri grubu oluşturur
     * 
     * @param CustomerGroupData $data Müşteri grubu verileri
     * @return CustomerGroup Oluşturulan müşteri grubu
     */
    public function create(CustomerGroupData $data): CustomerGroup
    {
        return DB::transaction(function () use ($data) {
            return CustomerGroup::create($data->toArray());
        });
    }

    /**
     * Mevcut bir müşteri grubunu günceller
     * 
     * @param CustomerGroup $customerGroup Güncellenecek müşteri grubu
     * @param CustomerGroupData $data Yeni müşteri grubu verileri
     * @return CustomerGroup Güncellenmiş müşteri grubu
     */
    public function update(CustomerGroup $customerGroup, CustomerGroupData $data): CustomerGroup
    {
        return DB::transaction(function () use ($customerGroup, $data) {
            $customerGroup->update([
                'name' => $data->name,
                'description' => $data->description,
                'status' => $data->status,
            ]);
            return $customerGroup->fresh();
        });
    }

    /**
     * Müşteri grubunu siler
     * 
     * Silme işlemi sonrası başarı bildirimi gösterir.
     * 
     * @param CustomerGroup $customerGroup Silinecek müşteri grubu
     */
    public function delete(CustomerGroup $customerGroup): void
    {
        DB::transaction(function () use ($customerGroup) {
            $customerGroup->delete();
        });

        Notification::make()
        ->title('Müşteri grubu silindi')
        ->success()
        ->send();
    }
} 