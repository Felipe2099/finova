<?php

declare(strict_types=1);

namespace App\Services\Supplier\Implementations;

use App\Models\Supplier;
use App\Services\Supplier\Contracts\SupplierServiceInterface;
use App\DTOs\Supplier\SupplierData;
use Illuminate\Support\Facades\DB;
use Filament\Notifications\Notification;

/**
 * Tedarikçi servisi implementasyonu
 * 
 * Tedarikçi yönetimi için gerekli metodları içerir.
 * Tedarikçilerin oluşturulması, güncellenmesi ve silinmesi işlemlerini gerçekleştirir.
 */
final class SupplierService implements SupplierServiceInterface
{
    /**
     * Yeni bir tedarikçi oluşturur
     * 
     * @param SupplierData $data Tedarikçi verileri
     * @return Supplier Oluşturulan tedarikçi
     */
    public function create(SupplierData $data): Supplier
    {
        return DB::transaction(function () use ($data) {
            return Supplier::create($data->toArray());
        });
    }

    /**
     * Mevcut bir tedarikçiyi günceller
     * 
     * @param Supplier $supplier Güncellenecek tedarikçi
     * @param SupplierData $data Güncellenecek veriler
     * @return Supplier Güncellenmiş tedarikçi
     */
    public function update(Supplier $supplier, SupplierData $data): Supplier
    {
        return DB::transaction(function () use ($supplier, $data) {
            $supplier->update($data->toArray());
            return $supplier->fresh();
        });
    }

    /**
     * Bir tedarikçiyi siler
     * 
     * @param Supplier $supplier Silinecek tedarikçi
     */
    public function delete(Supplier $supplier): void
    {
        DB::transaction(function () use ($supplier) {
            $supplier->delete();
        });
        Notification::make()
            ->title('Tedarikçi silindi')
            ->success()
            ->send();
    }
}