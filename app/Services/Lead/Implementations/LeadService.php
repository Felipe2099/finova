<?php

declare(strict_types=1);

namespace App\Services\Lead\Implementations;

use App\Models\Lead;
use App\Models\Customer;
use App\Services\Lead\Contracts\LeadServiceInterface;
use App\DTOs\Lead\LeadData;
use Illuminate\Support\Facades\DB;
use Filament\Notifications\Notification;

/**
 * Lead servisi implementasyonu
 * 
 * Potansiyel müşteri (lead) işlemlerinin yönetimi için gerekli metodları içerir.
 * Lead kayıtlarının oluşturulması, güncellenmesi, silinmesi ve müşteriye dönüştürülmesi işlemlerini gerçekleştirir.
 */
class LeadService implements LeadServiceInterface
{
    /**
     * Yeni bir lead kaydı oluşturur
     * 
     * @param LeadData $data Lead verileri
     * @return Lead Oluşturulan lead kaydı
     */
    public function create(LeadData $data): Lead
    {
        return DB::transaction(function () use ($data) {
            return Lead::create($data->toArray());
        });
    }

    /**
     * Mevcut bir lead kaydını günceller
     * 
     * @param Lead $lead Güncellenecek lead kaydı
     * @param LeadData $data Yeni lead verileri
     * @return Lead Güncellenmiş lead kaydı
     */
    public function update(Lead $lead, LeadData $data): Lead
    {
        return DB::transaction(function () use ($lead, $data) {
            $lead->update([
                'name' => $data->name,
                'type' => $data->type,
                'email' => $data->email,
                'phone' => $data->phone,
                'city' => $data->city,
                'district' => $data->district,
                'address' => $data->address,
                'notes' => $data->notes,
                'source' => $data->source,
                'status' => $data->status,
                'assigned_to' => $data->assigned_to,
                'next_contact_date' => $data->next_contact_date,
            ]);
            return $lead->fresh();
        });
    }

    /**
     * Lead kaydını siler
     * 
     * @param Lead $lead Silinecek lead kaydı
     */
    public function delete(Lead $lead): void
    {
        DB::transaction(function () use ($lead) {
            $lead->delete();
        });
    }

    /**
     * Lead'i müşteriye dönüştürür
     * 
     * Lead kaydını müşteri kaydına dönüştürür ve lead'in durumunu günceller.
     * Dönüşüm sonrası bildirim gösterir.
     * 
     * @param Lead $lead Dönüştürülecek lead kaydı
     * @param array $data Müşteri verileri
     */
    public function convertToCustomer(Lead $lead, array $data): void
    {
        DB::transaction(function () use ($lead, $data) {
            // Lead'i müşteriye çevir
            $customer = Customer::create([
                'name' => $lead->name,
                'email' => $lead->email,
                'phone' => $lead->phone,
                'description' => $lead->notes,
                'type' => $data['type'],
                'tax_number' => $data['tax_number'] ?? null,
                'tax_office' => $data['tax_office'] ?? null,
                'customer_group_id' => $data['customer_group_id'],
                'status' => true,
                'user_id' => $lead->user_id, 
            ]);

            // Lead'i güncelle
            $lead->update([
                'status' => 'converted',
                'converted_at' => now(),
                'converted_to_customer_id' => $customer->id,
                'conversion_reason' => $data['conversion_reason'] ?? null,
            ]);

            Notification::make()
                ->title('Müşteriye dönüştürüldü')
                ->success()
                ->send();
        });
    }
} 