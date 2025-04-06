<?php

declare(strict_types=1);

namespace App\DTOs\Lead;

/**
 * Potansiyel Müşteri Veri Transfer Nesnesi
 * 
 * Potansiyel müşteri (lead) verilerinin taşınması ve dönüştürülmesi için kullanılan DTO sınıfı.
 * Potansiyel müşteri oluşturma, güncelleme ve görüntüleme işlemlerinde kullanılır.
 */
class LeadData
{
    /**
     * @param string $name Potansiyel müşteri adı
     * @param string $type Müşteri türü
     * @param string|null $email E-posta adresi
     * @param string|null $phone Telefon numarası
     * @param string|null $city Şehir
     * @param string|null $district İlçe
     * @param string|null $address Adres
     * @param string|null $notes Notlar
     * @param string $source Kaynak
     * @param string $status Durum
     * @param int|null $assigned_to Atanan kullanıcı ID
     * @param string|null $next_contact_date Bir sonraki iletişim tarihi
     * @param string|null $last_contact_date Son iletişim tarihi
     * @param string|null $converted_at Müşteriye dönüştürülme tarihi
     * @param int|null $converted_to_customer_id Dönüştürülen müşteri ID
     * @param string|null $conversion_reason Dönüşüm nedeni
     * @param int|null $user_id Kullanıcı ID
     */
    public function __construct(
        public readonly string $name,
        public readonly string $type,
        public readonly ?string $email,
        public readonly ?string $phone,
        public readonly ?string $city,
        public readonly ?string $district,
        public readonly ?string $address,
        public readonly ?string $notes,
        public readonly string $source,
        public readonly string $status,
        public readonly ?int $assigned_to,
        public readonly ?string $next_contact_date,
        public readonly ?string $last_contact_date = null,
        public readonly ?string $converted_at = null,
        public readonly ?int $converted_to_customer_id = null,
        public readonly ?string $conversion_reason = null,
        public readonly ?int $user_id = null,
    ) {}

    /**
     * Dizi verisinden potansiyel müşteri verisi oluşturur
     * 
     * @param array $data Potansiyel müşteri verileri dizisi
     * @return self
     */
    public static function fromArray(array $data): self
    {
        return new self(
            name: $data['name'],
            type: $data['type'],
            email: $data['email'] ?? null,
            phone: $data['phone'] ?? null,
            city: $data['city'] ?? null,
            district: $data['district'] ?? null,
            address: $data['address'] ?? null,
            notes: $data['notes'] ?? null,
            source: $data['source'] ?? 'other',
            status: $data['status'] ?? 'new',
            assigned_to: $data['assigned_to'] ?? null,
            next_contact_date: $data['next_contact_date'] ?? null,
            last_contact_date: $data['last_contact_date'] ?? null,
            converted_at: $data['converted_at'] ?? null,
            converted_to_customer_id: $data['converted_to_customer_id'] ?? null,
            conversion_reason: $data['conversion_reason'] ?? null,
            user_id: $data['user_id'] ?? null,
        );
    }

    /**
     * Potansiyel müşteri verisini dizi formatına dönüştürür
     * 
     * @return array
     */
    public function toArray(): array
    {
        return [
            'name' => $this->name,
            'type' => $this->type,
            'email' => $this->email,
            'phone' => $this->phone,
            'city' => $this->city,
            'district' => $this->district,
            'address' => $this->address,
            'notes' => $this->notes,
            'source' => $this->source,
            'status' => $this->status,
            'assigned_to' => $this->assigned_to,
            'next_contact_date' => $this->next_contact_date,
            'last_contact_date' => $this->last_contact_date,
            'converted_at' => $this->converted_at,
            'converted_to_customer_id' => $this->converted_to_customer_id,
            'conversion_reason' => $this->conversion_reason,
            'user_id' => $this->user_id,
        ];
    }
} 